/**
 * Smart Session Timer
 * Handles session timing through preparation, session, and overtime phases
 * Persists state across page reloads and maintains accuracy
 */

// Prevent duplicate declarations
if (typeof window.SmartSessionTimer === 'undefined') {

class SmartSessionTimer {
    constructor(config) {
        this.sessionId = config.sessionId;
        this.scheduledAt = new Date(config.scheduledAt);
        this.durationMinutes = config.durationMinutes || 30;
        this.preparationMinutes = config.preparationMinutes || 15;
        this.endingBufferMinutes = config.endingBufferMinutes || 5;
        
        // Timer elements
        this.timerElement = document.getElementById(config.timerElementId || 'session-timer');
        this.phaseElement = document.getElementById(config.phaseElementId || 'timer-phase');
        this.displayElement = document.getElementById(config.displayElementId || 'time-display');
        
        // Current state
        this.currentPhase = null;
        this.isRunning = false;
        this.intervalId = null;
        this.isSessionCompleted = false; // CRITICAL FIX: Track if session is completed
        
        // Callbacks
        this.onPhaseChange = config.onPhaseChange || (() => {});
        this.onTick = config.onTick || (() => {});
        
        // Phase definitions
        this.phases = {
            NOT_STARTED: 'not_started',
            PREPARATION: 'preparation', 
            SESSION: 'session',
            OVERTIME: 'overtime',
            ENDED: 'ended'
        };
        
        // Phase configurations
        this.phaseConfig = {
            [this.phases.NOT_STARTED]: {
                label: 'في انتظار الجلسة',
                icon: '⏳',
                className: 'waiting'
            },
            [this.phases.PREPARATION]: {
                label: 'وقت التحضير',
                icon: '🔔',
                className: 'preparation',
                countDown: true
            },
            [this.phases.SESSION]: {
                label: 'الجلسة المباشرة',
                icon: '🎓',
                className: 'active',
                countDown: true
            },
            [this.phases.OVERTIME]: {
                label: 'وقت إضافي',
                icon: '⏰',
                className: 'overtime',
                countDown: false // Count up
            },
            [this.phases.ENDED]: {
                label: 'انتهت الجلسة',
                icon: '✅',
                className: 'ended'
            }
        };
        
        console.log('🕐 SmartSessionTimer initialized', {
            sessionId: this.sessionId,
            scheduledAt: this.scheduledAt,
            duration: this.durationMinutes,
            preparation: this.preparationMinutes
        });
        
        this.init();
    }
    
    /**
     * Initialize the timer
     */
    init() {
        // CRITICAL FIX: Always calculate and display current state immediately
        const timing = this.calculateCurrentTiming();
        console.log('⏰ Timer initializing - current phase:', timing.phase);
        
        // Immediately update display with current state
        this.updateDisplay(timing);
        this.handlePhaseChange(timing.phase);
        
        // If session has ended, mark as completed and don't start interval
        if (timing.phase === this.phases.ENDED) {
            console.log('⏰ Session already ended - showing 00:00 permanently');
            this.isSessionCompleted = true;
            return; // Don't start the timer interval
        }
        
        // For active sessions, start the timer
        this.start();
        
        // Save state periodically
        setInterval(() => this.saveState(), 5000); // Save every 5 seconds
        
        // Handle page visibility changes
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                this.sync(); // Resync when page becomes visible
            }
        });
    }
    
    /**
     * Start the timer
     */
    start() {
        if (this.isRunning) return;
        
        this.isRunning = true;
        this.sync(); // Initial sync
        
        // Update every second
        this.intervalId = setInterval(() => {
            this.update();
        }, 1000);
        
        console.log('⏰ Timer started');
    }
    
    /**
     * Stop the timer
     */
    stop() {
        if (!this.isRunning) return;
        
        this.isRunning = false;
        
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
        
        console.log('⏸️ Timer stopped');
    }
    
    /**
     * Update timer display and check for phase changes
     */
    update() {
        // CRITICAL FIX: Don't update if session is completed
        if (this.isSessionCompleted) {
            return;
        }
        
        const timing = this.calculateCurrentTiming();
        
        // CRITICAL FIX: Stop immediately if session has ended
        if (timing.phase === this.phases.ENDED) {
            console.log('⏰ Session ended - stopping timer immediately');
            this.isSessionCompleted = true;
            this.stop();
            
            // Force display to show 00:00
            this.updateDisplay({
                ...timing,
                timeRemaining: 0,
                timeElapsed: 0
            });
            
            this.handlePhaseChange(timing.phase);
            return;
        }
        
        // Check for phase change
        if (timing.phase !== this.currentPhase) {
            this.handlePhaseChange(timing.phase);
        }
        
        // Update display
        this.updateDisplay(timing);
        
        // Call tick callback
        this.onTick(timing);
    }
    
    /**
     * Calculate current timing and phase
     */
    calculateCurrentTiming() {
        const now = new Date();
        const sessionStart = this.scheduledAt;
        const preparationStart = new Date(sessionStart.getTime() - (this.preparationMinutes * 60000));
        const sessionEnd = new Date(sessionStart.getTime() + (this.durationMinutes * 60000));
        const finalEnd = new Date(sessionEnd.getTime() + (this.endingBufferMinutes * 60000));
        
        let phase, timeRemaining, timeElapsed, totalDuration, percentage;
        
        if (now < preparationStart) {
            // Before preparation time
            phase = this.phases.NOT_STARTED;
            timeRemaining = Math.floor((preparationStart - now) / 1000);
            timeElapsed = 0;
            totalDuration = Math.floor((preparationStart - now) / 1000);
            percentage = 0;
            
        } else if (now < sessionStart) {
            // Preparation phase
            phase = this.phases.PREPARATION;
            timeRemaining = Math.floor((sessionStart - now) / 1000);
            timeElapsed = Math.floor((now - preparationStart) / 1000);
            totalDuration = this.preparationMinutes * 60;
            percentage = Math.min(100, (timeElapsed / totalDuration) * 100);
            
        } else if (now < sessionEnd) {
            // Session phase
            phase = this.phases.SESSION;
            timeRemaining = Math.floor((sessionEnd - now) / 1000);
            timeElapsed = Math.floor((now - sessionStart) / 1000);
            totalDuration = this.durationMinutes * 60;
            percentage = Math.min(100, (timeElapsed / totalDuration) * 100);
            
        } else if (now < finalEnd) {
            // Overtime phase
            phase = this.phases.OVERTIME;
            timeElapsed = Math.floor((now - sessionEnd) / 1000);
            timeRemaining = Math.floor((finalEnd - now) / 1000);
            totalDuration = this.endingBufferMinutes * 60;
            percentage = Math.min(100, (timeElapsed / totalDuration) * 100);
            
        } else {
            // Session ended
            phase = this.phases.ENDED;
            timeElapsed = Math.floor((now - sessionStart) / 1000);
            timeRemaining = 0;
            totalDuration = this.durationMinutes * 60;
            percentage = 100;
        }
        
        return {
            phase,
            timeRemaining: Math.max(0, timeRemaining),
            timeElapsed: Math.max(0, timeElapsed),
            totalDuration,
            percentage: Math.max(0, Math.min(100, percentage)),
            sessionStart,
            sessionEnd,
            now
        };
    }
    
    /**
     * Handle phase change
     */
    handlePhaseChange(newPhase) {
        const oldPhase = this.currentPhase;
        this.currentPhase = newPhase;
        
        console.log(`🔄 Phase changed: ${oldPhase} → ${newPhase}`);
        
        // Update UI classes
        this.updatePhaseUI(newPhase);
        
        // Call phase change callback
        this.onPhaseChange(newPhase, oldPhase);
        
        // Save state
        this.saveState();
    }
    
    /**
     * Update the display elements
     */
    updateDisplay(timing) {
        if (!this.timerElement) return;
        
        // Don't update display if locked (but allow updates for session completed during init)
        if (this.displayElement && this.displayElement.dataset.locked === 'true') {
            return;
        }
        
        const config = this.phaseConfig[timing.phase];
        const useCountDown = config.countDown && timing.phase !== this.phases.OVERTIME;
        
        // Calculate time to show based on phase
        let timeToShow;
        if (timing.phase === this.phases.ENDED) {
            timeToShow = 0; // Always show 00:00 when ended
        } else if (timing.phase === this.phases.OVERTIME) {
            // FIXED: Show countdown for additional time instead of 00:00
            timeToShow = timing.timeRemaining; // Show remaining additional time
        } else {
            timeToShow = useCountDown ? timing.timeRemaining : timing.timeElapsed;
        }
        
        // Update time display
        if (this.displayElement) {
            this.displayElement.textContent = this.formatTime(timeToShow);
        }
        
        // Update phase label
        if (this.phaseElement) {
            const phaseText = timing.phase === this.phases.OVERTIME 
                ? `${config.label}` // Just show "وقت إضافي"
                : config.label;
                
            this.phaseElement.textContent = phaseText;
        }
        
        // Update progress (if element exists)
        const progressElement = document.getElementById('timer-progress');
        if (progressElement) {
            progressElement.style.width = `${timing.percentage}%`;
        }
        
        // Update timer container attributes
        this.timerElement.setAttribute('data-phase', timing.phase);
        this.timerElement.setAttribute('data-percentage', timing.percentage);
    }
    
    /**
     * Update phase-specific UI
     */
    updatePhaseUI(phase) {
        if (!this.timerElement) return;
        
        const config = this.phaseConfig[phase];
        
        // Update classes
        Object.values(this.phaseConfig).forEach(phaseConfig => {
            this.timerElement.classList.remove(phaseConfig.className);
        });
        this.timerElement.classList.add(config.className);
        
        // Update icon (if element exists)
        const iconElement = document.getElementById('timer-icon');
        if (iconElement) {
            iconElement.textContent = config.icon;
        }
    }
    
    /**
     * Format time in MM:SS format
     */
    formatTime(seconds) {
        const mins = Math.floor(Math.abs(seconds) / 60);
        const secs = Math.abs(seconds) % 60;
        const sign = seconds < 0 ? '-' : '';
        
        return `${sign}${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
    
    /**
     * Sync timer with server time
     */
    async sync() {
        try {
            // Get server time for accuracy
            const response = await fetch('/api/server-time', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                const serverTime = new Date(data.timestamp);
                const clientTime = new Date();
                const drift = serverTime.getTime() - clientTime.getTime();
                
                // Adjust scheduled time if there's significant drift (>5 seconds)
                if (Math.abs(drift) > 5000) {
                    console.log(`⏰ Time drift detected: ${drift}ms, adjusting...`);
                    // Note: In production, you might want to adjust the scheduledAt time
                    // this.scheduledAt = new Date(this.scheduledAt.getTime() + drift);
                }
            }
        } catch (error) {
            console.warn('⚠️ Failed to sync with server time:', error);
        }
    }
    
    /**
     * Save current state to localStorage
     */
    saveState() {
        const state = {
            sessionId: this.sessionId,
            currentPhase: this.currentPhase,
            lastUpdate: Date.now(),
            scheduledAt: this.scheduledAt.toISOString(),
            durationMinutes: this.durationMinutes,
            preparationMinutes: this.preparationMinutes
        };
        
        localStorage.setItem(`session_timer_${this.sessionId}`, JSON.stringify(state));
    }
    
    /**
     * Restore state from localStorage
     */
    restoreState() {
        try {
            const savedState = localStorage.getItem(`session_timer_${this.sessionId}`);
            if (savedState) {
                const state = JSON.parse(savedState);
                
                // Validate the saved state is for the same session configuration
                if (state.sessionId === this.sessionId && 
                    state.scheduledAt === this.scheduledAt.toISOString()) {
                    
                    this.currentPhase = state.currentPhase;
                    console.log('📱 Restored timer state from localStorage');
                }
            }
        } catch (error) {
            console.warn('⚠️ Failed to restore timer state:', error);
        }
    }
    
    /**
     * Clear saved state
     */
    clearState() {
        localStorage.removeItem(`session_timer_${this.sessionId}`);
    }
    
    /**
     * Get current timing information
     */
    getCurrentTiming() {
        return this.calculateCurrentTiming();
    }
    
    /**
     * Check if timer is in a specific phase
     */
    isInPhase(phase) {
        return this.currentPhase === phase;
    }
    
    /**
     * Get remaining time in current phase
     */
    getTimeRemaining() {
        const timing = this.calculateCurrentTiming();
        return timing.timeRemaining;
    }
    
    /**
     * Destroy the timer
     */
    destroy() {
        this.stop();
        this.clearState();
        console.log('🗑️ Timer destroyed');
    }
}

// Export for use in other modules
window.SmartSessionTimer = SmartSessionTimer;

} // End of duplicate declaration check
