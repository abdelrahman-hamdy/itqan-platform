/**
 * LiveKit Meeting Telemetry
 *
 * Captures structured events from the LiveKit meeting lifecycle and ships them
 * to the Laravel server log via POST /api/sessions/meeting/telemetry.
 * All events are also written to the browser console for live debugging.
 *
 * Usage:
 *   const mt = new MeetingTelemetry({sessionId, userId, userRole, csrfToken});
 *   mt.event('connection', 'connect_started', {url});
 *   mt.error('connection', 'connect_failed', err);
 *   mt.startStatsPolling(room);
 *
 * Why: lets us answer "is the echo fix working?" and "is the reconnect fix
 * working?" with objective data instead of guessing from anecdotes. The key
 * audio metrics (echoReturnLoss, echoReturnLossEnhancement) are the canonical
 * WebRTC signals for AEC quality.
 */
class MeetingTelemetry {
    constructor(config = {}) {
        this.sessionId = config.sessionId ?? null;
        this.sessionType = config.sessionType ?? null;
        this.userId = config.userId ?? null;
        this.userRole = config.userRole ?? null;
        this.endpoint = config.endpoint || '/api/sessions/meeting/telemetry';
        this.csrfToken = config.csrfToken || null;

        this.buffer = [];
        this.maxBufferSize = 50;
        this.maxBufferRetained = 200; // Drop events if backlog exceeds this on flush failure
        this.flushIntervalMs = 5000;
        this.statsIntervalMs = 10000;

        this.flushTimerId = null;
        this.statsTimerId = null;
        this.statsRoom = null;
        this.statsPrev = {}; // For derived deltas

        this.deviceInfo = this.collectDeviceInfo();
        this.startFlushTimer();

        // Store bound handler refs so destroy() can remove them — otherwise
        // repeated instantiation (e.g. SPA navigation back into the meeting page)
        // would leak listeners on every cycle.
        this._unloadHandler = () => this.flushSync();
        window.addEventListener('beforeunload', this._unloadHandler);
        window.addEventListener('pagehide', this._unloadHandler);

        // Track audio device changes — critical for debugging Bluetooth earbuds
        // that plug in or disconnect mid-meeting. Debounce the handler because
        // Bluetooth profile switching can fire `devicechange` several times per
        // second while HFP/A2DP negotiate.
        this._deviceChangeDebounceMs = 1000;
        this._deviceChangeDebounceId = null;
        this._deviceChangeHandler = () => {
            if (this._deviceChangeDebounceId) clearTimeout(this._deviceChangeDebounceId);
            this._deviceChangeDebounceId = setTimeout(() => {
                this._deviceChangeDebounceId = null;
                this.event('devices', 'changed', {});
                this.enumerateAudioDevices();
            }, this._deviceChangeDebounceMs);
        };
        if (navigator.mediaDevices && typeof navigator.mediaDevices.addEventListener === 'function') {
            navigator.mediaDevices.addEventListener('devicechange', this._deviceChangeHandler);
        }

        this.event('telemetry', 'started', { device: this.deviceInfo });
        // Log the initial set of available audio devices so we can correlate
        // user reports like "my earbuds don't work" with what the browser
        // actually sees.
        this.enumerateAudioDevices();

        // Main-thread paralysis probe. A rolling summary of longtasks
        // (>50ms synchronous blocking) is emitted every 10 s so we can tell
        // "page went black at 02:15" from "page felt laggy because main
        // thread was blocked 4.2 s out of every 10 s". Attribution
        // (containerSrc) tells us which iframe/script was running.
        this._startLongTaskProbe();
    }

    /**
     * Aggregate longtask entries into 10-second buckets and ship to server.
     * Using a single PerformanceObserver has near-zero overhead; the cost
     * lives entirely in the tasks being observed.
     */
    _startLongTaskProbe() {
        if (typeof PerformanceObserver !== 'function') return;
        if (!PerformanceObserver.supportedEntryTypes?.includes('longtask')) return;

        this._longTaskBucket = { count: 0, total_ms: 0, max_ms: 0, entries: [] };
        try {
            this._longTaskObs = new PerformanceObserver((list) => {
                for (const e of list.getEntries()) {
                    const dur = Math.round(e.duration);
                    this._longTaskBucket.count++;
                    this._longTaskBucket.total_ms += dur;
                    if (dur > this._longTaskBucket.max_ms) this._longTaskBucket.max_ms = dur;
                    // Keep up to 5 of the biggest offenders with attribution
                    if (this._longTaskBucket.entries.length < 5 || dur > (this._longTaskBucket.entries[4]?.dur || 0)) {
                        const attr = (e.attribution || []).slice(0, 2).map(a => ({
                            type: a.containerType || null,
                            src: (a.containerSrc || '').slice(-60),
                            name: a.containerName || null,
                        }));
                        this._longTaskBucket.entries.push({ dur, attr });
                        this._longTaskBucket.entries.sort((a, b) => b.dur - a.dur);
                        if (this._longTaskBucket.entries.length > 5) this._longTaskBucket.entries.length = 5;
                    }
                }
            });
            this._longTaskObs.observe({ type: 'longtask', buffered: true });
        } catch (_) { return; }

        this._longTaskTimer = setInterval(() => {
            const b = this._longTaskBucket;
            if (b.count === 0) return;
            // Also capture JS heap if exposed
            const mem = performance.memory ? {
                used_mb: Math.round(performance.memory.usedJSHeapSize / 1048576),
                total_mb: Math.round(performance.memory.totalJSHeapSize / 1048576),
            } : null;
            this.event('perf', 'longtask_bucket', {
                count: b.count,
                total_ms: b.total_ms,
                max_ms: b.max_ms,
                top: b.entries,
                heap: mem,
            });
            this._longTaskBucket = { count: 0, total_ms: 0, max_ms: 0, entries: [] };
        }, 10_000);
    }

    _stopLongTaskProbe() {
        try { this._longTaskObs?.disconnect(); } catch (_) {}
        if (this._longTaskTimer) { clearInterval(this._longTaskTimer); this._longTaskTimer = null; }
    }

    /**
     * Enumerate audio input/output devices and log them to telemetry.
     * Labels are only populated after the user has granted mic permission, so
     * we log both the id (stable) and the label (if available).
     */
    async enumerateAudioDevices() {
        try {
            if (!navigator.mediaDevices || typeof navigator.mediaDevices.enumerateDevices !== 'function') {
                this.event('devices', 'enumerate_unsupported', {});
                return;
            }
            const devices = await navigator.mediaDevices.enumerateDevices();
            const inputs = [];
            const outputs = [];
            for (const d of devices) {
                const entry = {
                    id: (d.deviceId || '').substring(0, 12),
                    label: d.label || '',
                    group: (d.groupId || '').substring(0, 12),
                };
                if (d.kind === 'audioinput') inputs.push(entry);
                else if (d.kind === 'audiooutput') outputs.push(entry);
            }
            this.event('devices', 'enumerated', {
                audio_input_count: inputs.length,
                audio_output_count: outputs.length,
                inputs,
                outputs,
            });
        } catch (e) {
            this.error('devices', 'enumerate_failed', e);
        }
    }

    collectDeviceInfo() {
        const info = {
            ua: navigator.userAgent,
            platform: navigator.platform,
            language: navigator.language,
            online: navigator.onLine,
            cores: navigator.hardwareConcurrency || null,
            memory: navigator.deviceMemory || null,
        };
        try { info.screen = `${screen.width}x${screen.height}`; } catch (e) {}
        if (navigator.connection) {
            info.connection = {
                effectiveType: navigator.connection.effectiveType,
                downlink: navigator.connection.downlink,
                rtt: navigator.connection.rtt,
                saveData: navigator.connection.saveData,
            };
        }
        return info;
    }

    /**
     * Log a regular event. Buffered, flushed periodically.
     */
    event(category, name, data = {}) {
        this.buffer.push(this.buildEvent(category, name, 'info', data));
        try { console.log('[MT]', category, name, data); } catch (e) {}
        if (this.buffer.length >= this.maxBufferSize) this.flush();
    }

    /**
     * Log an error event. Captures stack/message and flushes immediately.
     */
    error(category, name, err, extra = {}) {
        const errInfo = {
            ...extra,
            err_message: err && err.message ? err.message : String(err),
            err_name: err && err.name ? err.name : 'Error',
            err_stack: err && err.stack ? String(err.stack).substring(0, 1500) : null,
        };
        this.buffer.push(this.buildEvent(category, name, 'error', errInfo));
        try { console.error('[MT]', category, name, errInfo); } catch (e) {}
        // Errors flush immediately so we never lose them on a crash
        this.flush();
    }

    /**
     * Log a warning event.
     */
    warn(category, name, data = {}) {
        this.buffer.push(this.buildEvent(category, name, 'warning', data));
        try { console.warn('[MT]', category, name, data); } catch (e) {}
        if (this.buffer.length >= this.maxBufferSize) this.flush();
    }

    buildEvent(category, name, level, data) {
        return {
            t: Date.now(),
            iso: new Date().toISOString(),
            session_id: this.sessionId,
            session_type: this.sessionType,
            user_id: this.userId,
            user_role: this.userRole,
            category,
            name,
            level,
            data,
        };
    }

    async flush() {
        if (this.buffer.length === 0) return;
        const events = this.buffer.splice(0, this.buffer.length);
        try {
            const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
            if (this.csrfToken) headers['X-CSRF-TOKEN'] = this.csrfToken;
            const response = await fetch(this.endpoint, {
                method: 'POST',
                headers,
                credentials: 'same-origin',
                body: JSON.stringify({ events }),
                keepalive: true,
            });
            if (!response.ok) this.requeueOrDrop(events);
        } catch (e) {
            this.requeueOrDrop(events);
        }
    }

    /**
     * Push failed events back to the head of the buffer so they retry on the
     * next flush. If the buffer is saturated (server unreachable for a long
     * time), drop the overflow and surface a console warning so an operator
     * watching the browser devtools knows telemetry is being dropped.
     */
    requeueOrDrop(events) {
        if (this.buffer.length + events.length < this.maxBufferRetained) {
            this.buffer.unshift(...events);
            return;
        }
        try {
            console.warn('[MT] telemetry buffer overflow — dropping', events.length, 'events (server unreachable?)');
        } catch (e) {}
    }

    /**
     * Synchronous flush via sendBeacon — for unload paths only.
     */
    flushSync() {
        if (this.buffer.length === 0) return;
        if (!navigator.sendBeacon) return;
        const events = this.buffer.splice(0, this.buffer.length);
        try {
            const blob = new Blob([JSON.stringify({ events })], { type: 'application/json' });
            navigator.sendBeacon(this.endpoint, blob);
        } catch (e) {}
    }

    startFlushTimer() {
        if (this.flushTimerId) clearInterval(this.flushTimerId);
        this.flushTimerId = setInterval(() => this.flush(), this.flushIntervalMs);
    }

    stopFlushTimer() {
        if (this.flushTimerId) {
            clearInterval(this.flushTimerId);
            this.flushTimerId = null;
        }
    }

    /**
     * Begin polling WebRTC stats from the local participant's audio track.
     * Captures echoReturnLoss / echoReturnLossEnhancement (AEC quality)
     * and audio level / packet loss / jitter / RTT.
     */
    startStatsPolling(room) {
        this.statsRoom = room;
        if (this.statsTimerId) clearInterval(this.statsTimerId);
        this.statsTimerId = setInterval(() => {
            this.sampleStats().catch((e) => this.error('stats', 'sample_failed', e));
        }, this.statsIntervalMs);
        this.event('stats', 'polling_started', { intervalMs: this.statsIntervalMs });
    }

    stopStatsPolling() {
        if (this.statsTimerId) {
            clearInterval(this.statsTimerId);
            this.statsTimerId = null;
            this.event('stats', 'polling_stopped', {});
        }
        this.statsRoom = null;
        this.statsPrev = {};
    }

    async sampleStats() {
        const room = this.statsRoom;
        if (!room || !room.localParticipant) return;
        if (room.state === 'disconnected') {
            this.stopStatsPolling();
            return;
        }
        const lp = room.localParticipant;

        // Local audio sender stats — the most important signal for echo detection
        try {
            const audioPub = this.firstAudioPublication(lp);
            if (audioPub && audioPub.track) {
                const trackStats = await this.extractAudioSenderStats(audioPub.track);
                if (trackStats) {
                    this.event('stats', 'audio_sender', trackStats);
                }
            }
        } catch (e) {
            // Don't escalate to error — stats are best-effort
        }

        // Connection-quality info from LiveKit's own observer
        try {
            this.event('stats', 'connection_quality', {
                local_quality: lp.connectionQuality || 'unknown',
                room_state: room.state || null,
                remote_count: room.remoteParticipants ? room.remoteParticipants.size : 0,
            });
        } catch (e) {}
    }

    firstAudioPublication(lp) {
        if (!lp) return null;
        if (lp.audioTrackPublications) {
            for (const pub of lp.audioTrackPublications.values()) return pub;
        }
        if (lp.getTrackPublications) {
            for (const pub of lp.getTrackPublications()) {
                if (pub.kind === 'audio') return pub;
            }
        }
        return null;
    }

    async extractAudioSenderStats(track) {
        if (!track) return null;
        let report;
        try {
            // LocalAudioTrack exposes getRTCStatsReport()
            if (typeof track.getRTCStatsReport === 'function') {
                report = await track.getRTCStatsReport();
            } else if (track.sender && typeof track.sender.getStats === 'function') {
                report = await track.sender.getStats();
            }
        } catch (e) {
            return null;
        }
        if (!report) return null;

        const out = {};
        report.forEach((s) => {
            if (s.type === 'media-source' && s.kind === 'audio') {
                if (typeof s.audioLevel === 'number') out.audio_level = s.audioLevel;
                if (typeof s.totalAudioEnergy === 'number') out.total_audio_energy = s.totalAudioEnergy;
                // The smoking-gun metrics for AEC quality:
                if (typeof s.echoReturnLoss === 'number') out.echo_return_loss_db = s.echoReturnLoss;
                if (typeof s.echoReturnLossEnhancement === 'number') out.echo_return_loss_enhancement_db = s.echoReturnLossEnhancement;
            }
            if (s.type === 'outbound-rtp' && s.kind === 'audio') {
                if (typeof s.packetsSent === 'number') out.packets_sent = s.packetsSent;
                if (typeof s.bytesSent === 'number') out.bytes_sent = s.bytesSent;
                if (typeof s.retransmittedPacketsSent === 'number') out.retransmitted = s.retransmittedPacketsSent;
                if (typeof s.targetBitrate === 'number') out.target_bitrate = s.targetBitrate;
            }
            if (s.type === 'remote-inbound-rtp' && s.kind === 'audio') {
                if (typeof s.packetsLost === 'number') out.packets_lost = s.packetsLost;
                if (typeof s.jitter === 'number') out.jitter = s.jitter;
                if (typeof s.roundTripTime === 'number') out.rtt = s.roundTripTime;
                if (typeof s.fractionLost === 'number') out.fraction_lost = s.fractionLost;
            }
        });

        // Compute deltas vs previous sample (per-second rates)
        const prev = this.statsPrev || {};
        const now = Date.now();
        if (prev.t && out.packets_sent != null && prev.packets_sent != null) {
            const dt = (now - prev.t) / 1000;
            if (dt > 0) {
                out.packets_per_sec = Math.round((out.packets_sent - prev.packets_sent) / dt);
                if (out.bytes_sent != null && prev.bytes_sent != null) {
                    out.kbps = Math.round(((out.bytes_sent - prev.bytes_sent) * 8 / 1000) / dt);
                }
            }
        }
        this.statsPrev = { ...out, t: now };

        return Object.keys(out).length > 0 ? out : null;
    }

    /**
     * Update identity context after page state changes (e.g., once the user is known).
     */
    updateContext(ctx) {
        if (ctx.sessionId !== undefined) this.sessionId = ctx.sessionId;
        if (ctx.sessionType !== undefined) this.sessionType = ctx.sessionType;
        if (ctx.userId !== undefined) this.userId = ctx.userId;
        if (ctx.userRole !== undefined) this.userRole = ctx.userRole;
    }

    destroy() {
        this.stopFlushTimer();
        this.stopStatsPolling();
        this._stopLongTaskProbe();
        this.flushSync();

        if (this._unloadHandler) {
            window.removeEventListener('beforeunload', this._unloadHandler);
            window.removeEventListener('pagehide', this._unloadHandler);
            this._unloadHandler = null;
        }
        if (this._deviceChangeHandler && navigator.mediaDevices && typeof navigator.mediaDevices.removeEventListener === 'function') {
            navigator.mediaDevices.removeEventListener('devicechange', this._deviceChangeHandler);
            this._deviceChangeHandler = null;
        }
        if (this._deviceChangeDebounceId) {
            clearTimeout(this._deviceChangeDebounceId);
            this._deviceChangeDebounceId = null;
        }
    }
}

window.MeetingTelemetryClass = MeetingTelemetry;
// Singleton instance — initialized in livekit-interface.blade.php
window.MT = null;
