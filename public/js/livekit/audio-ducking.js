/**
 * Receive-side soft ducker.
 *
 * When the local participant is actively speaking, attenuate every remote
 * participant's playback by ~30 %. This reduces the acoustic signal the local
 * microphone re-captures from device speakers, which WebRTC's AEC cannot fully
 * cancel on its own (telemetry shows many sessions with ERL < 0 dB and
 * ERLE ≈ 0 — classic speaker-to-mic coupling).
 *
 * No UI, no numbers, no user-visible behaviour. Just a volume change that the
 * user perceives as "the room quieted down while I spoke" — the natural turn-
 * taking cue real conversations already have.
 *
 * Contract: listens for `livekit-local-speaking` (emitted by connection.js
 * from the LiveKit ActiveSpeakersChanged event). The detail carries
 * `{ speaking: boolean }`.
 *
 * === Debugging audio quality (team-only, never user-facing) ===
 *
 *   Per-session audio metrics land in storage/logs/meeting-telemetry-*.log.
 *   Interpretation keys:
 *     echo_return_loss_db              > 20 healthy, < 5 / negative = physical
 *                                      speaker-to-mic coupling (no headphones)
 *     echo_return_loss_enhancement_db  > 10 healthy AEC, < 1 = AEC has given up
 *     fraction_lost                    < 0.01 healthy, > 0.05 = burst
 *     rtt                              < 0.15 healthy (seconds)
 *
 *   When ERL stays negative with ERLE ≈ 0, no codec tweak will help — the
 *   speaker is physically louder than what AEC can subtract. Reach out to the
 *   teacher off-platform and ask them to use a headset.
 */
(function () {
    const DUCKED_VOLUME = 0.7;
    const FULL_VOLUME = 1.0;

    // Half-life of the ease in/out in ms. Short enough to react before echo
    // returns; long enough to avoid audible clicks at the boundary.
    const RAMP_MS = 120;
    const FRAME_MS = 16;

    let currentVolume = FULL_VOLUME;
    let targetVolume = FULL_VOLUME;
    let rampTimer = null;

    function isRemoteAudio(el) {
        // Every remote track LiveKit plays is an <audio> element whose
        // srcObject is a MediaStream. The local participant's own mic is never
        // played back (LiveKit mutes it at the sink), so any playing audio
        // with a srcObject is remote. Elements without srcObject may exist as
        // keepalive / ringtone / UI sounds — leave those alone.
        return el.tagName === 'AUDIO' && el.srcObject instanceof MediaStream;
    }

    function applyCurrentVolume() {
        document.querySelectorAll('audio').forEach((el) => {
            if (isRemoteAudio(el)) el.volume = currentVolume;
        });
    }

    function stepRamp() {
        const delta = targetVolume - currentVolume;
        if (Math.abs(delta) < 0.01) {
            currentVolume = targetVolume;
            applyCurrentVolume();
            clearInterval(rampTimer);
            rampTimer = null;
            return;
        }
        // Simple exponential approach toward target; feels smooth to the ear.
        const stepFraction = FRAME_MS / RAMP_MS;
        currentVolume += delta * stepFraction;
        applyCurrentVolume();
    }

    function setTarget(target) {
        if (target === targetVolume) return;
        targetVolume = target;
        if (rampTimer) return;
        rampTimer = setInterval(stepRamp, FRAME_MS);
    }

    window.addEventListener('livekit-local-speaking', (e) => {
        const speaking = !!(e.detail && e.detail.speaking);
        setTarget(speaking ? DUCKED_VOLUME : FULL_VOLUME);
    });

    // When new remote audio elements are added (participant joined, track
    // subscribed) apply the current duck level so they don't pop in at full
    // volume mid-duck.
    const mo = new MutationObserver((records) => {
        for (const r of records) {
            for (const node of r.addedNodes) {
                if (node instanceof HTMLElement) {
                    if (isRemoteAudio(node)) node.volume = currentVolume;
                    node.querySelectorAll && node.querySelectorAll('audio').forEach((el) => {
                        if (isRemoteAudio(el)) el.volume = currentVolume;
                    });
                }
            }
        }
    });
    mo.observe(document.body, { childList: true, subtree: true });
})();
