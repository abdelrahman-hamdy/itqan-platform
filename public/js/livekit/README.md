# LiveKit Meeting Modules

A modular, event-driven implementation of LiveKit meeting functionality that replaces the monolithic `ProfessionalLiveKitMeeting` class with clean, maintainable modules.

## Architecture Overview

The refactored system consists of 6 specialized modules that work together to provide a robust video meeting experience:

```
┌─────────────────┐    ┌─────────────────┐
│   index.js      │    │  connection.js  │
│   (Orchestrator)│◄──►│  (Room & Events)│
└─────────────────┘    └─────────────────┘
         │                       │
         ▼                       ▼
┌─────────────────┐    ┌─────────────────┐
│ participants.js │    │   tracks.js     │
│ (People & DOM)  │    │ (Media Streams) │
└─────────────────┘    └─────────────────┘
         │                       │
         ▼                       ▼
┌─────────────────┐    ┌─────────────────┐
│   layout.js     │    │  controls.js    │
│ (UI Positioning)│    │ (User Actions)  │
└─────────────────┘    └─────────────────┘
```

## Module Breakdown

### 1. `connection.js` - Room Management
**Responsibility**: Create, connect, and manage the LiveKit Room instance.

**Key Features**:
- Room creation with optimized settings
- Connection state management
- Automatic reconnection with exponential backoff
- Event listener setup for all room events
- Token management for authentication

**Events Handled**:
- `ConnectionStateChanged`
- `ParticipantConnected/Disconnected`
- `TrackSubscribed/Unsubscribed`
- `TrackMuted/Unmuted`
- `ActiveSpeakersChanged`
- `DataReceived`

### 2. `tracks.js` - Media Stream Management
**Responsibility**: Handle all track-related operations using proper SDK events.

**Key Features**:
- **Event-driven camera detection** (replaces manual `hasActiveVideoTrack` logic)
- Automatic track attachment/detachment
- Camera overlay management based on track state
- Audio/video element creation and management
- Participant track state tracking

**Replaces Manual Logic**:
- ❌ `hasActiveVideoTrack()` with multiple fallback strategies
- ❌ DOM-based video state detection
- ❌ Periodic polling for track state
- ✅ Uses `TrackSubscribed/Unsubscribed` events
- ✅ Uses `TrackMuted/Unmuted` events
- ✅ Uses `publication.isMuted` and `publication.track` properties

### 3. `participants.js` - Participant Management
**Responsibility**: Manage participant lifecycle and DOM representation.

**Key Features**:
- Participant addition/removal
- DOM element creation with avatars and role badges
- Participant list management in sidebar
- Identity-to-DOM mapping
- Role detection (teacher/student) from metadata or configuration

**UI Elements**:
- Participant video tiles with placeholders
- Teacher badges and role indicators
- Participant list in sidebar with status indicators
- Avatar generation with consistent colors

### 4. `layout.js` - UI Layout Management
**Responsibility**: Handle all layout calculations and responsive design (DOM-only).

**Key Features**:
- Grid layout calculation based on participant count
- Focus mode for individual participants
- Responsive design and container resizing
- Sidebar-aware layout adjustments
- Aspect ratio optimization (16:9)

**Layout Modes**:
- **Grid Mode**: Optimal grid arrangement for all participants
- **Focus Mode**: Large view for one participant, small grid for others
- **Responsive**: Adapts to screen size and sidebar state

### 5. `controls.js` - User Interface Controls
**Responsibility**: Handle all user interactions and control states.

**Key Features**:
- Microphone/camera toggle using SDK methods
- Screen sharing controls
- Chat functionality via data channels
- Hand raise signaling
- Recording controls (teacher only)
- Keyboard shortcuts
- Meeting timer and participant count

**SDK Integration**:
- Uses `localParticipant.setMicrophoneEnabled()`
- Uses `localParticipant.setCameraEnabled()`
- Uses `localParticipant.setScreenShareEnabled()`
- Uses data channels for chat and signaling

### 6. `index.js` - Main Orchestrator
**Responsibility**: Coordinate all modules and provide the main API.

**Key Features**:
- Module initialization in correct order
- Event routing between modules
- Global state management
- Backward compatibility API
- Error handling and cleanup

## Event-Driven Flow

### Camera State Detection (Before vs After)

**❌ OLD (Manual Detection)**:
```javascript
// Multiple fallback strategies with polling
hasActiveVideoTrack(participant) {
    // Strategy 1: Check videoTracks Map
    // Strategy 2: Check trackPublications 
    // Strategy 3: Legacy object check
    // Strategy 4: DOM-based detection
    // Strategy 5: Fallback assumptions
}

// Periodic polling
setInterval(() => {
    this.synchronizeAllVideoStates();
}, 2000);
```

**✅ NEW (Event-Driven)**:
```javascript
// Single source of truth from SDK events
handleTrackMuted(publication, participant) {
    if (publication.kind === 'video') {
        this.updateVideoDisplay(participantId, false);
    }
}

handleTrackUnmuted(publication, participant) {
    if (publication.kind === 'video') {
        this.updateVideoDisplay(participantId, true);
    }
}
```

### Participant Management Flow

1. **Connection**: `ParticipantConnected` event → `participants.addParticipant()`
2. **Track Subscription**: `TrackSubscribed` event → `tracks.handleTrackSubscribed()`
3. **Track Attachment**: Automatic video/audio element creation and attachment
4. **UI Update**: Camera overlay, participant list, and layout updates
5. **State Sync**: All modules stay in sync through event callbacks

### Focus Mode Flow

1. **User Click**: Participant element clicked
2. **Layout Switch**: `layout.applyFocusMode()` called
3. **DOM Restructure**: Switch from grid to horizontal layout
4. **Element Cloning**: Create focused view of selected participant
5. **Exit Handling**: ESC key or button click to return to grid

## Usage

### Basic Integration
```javascript
// Replace the old monolithic class
import { initializeLiveKitMeeting } from './livekit/index.js';

// Initialize meeting
const meeting = await initializeLiveKitMeeting({
    serverUrl: 'wss://your-livekit-server',
    csrfToken: 'your-csrf-token',
    roomName: 'meeting-room',
    participantName: 'User Name',
    role: 'teacher' // or 'student'
});

// Meeting is now running with all modules active
```

### Adding New UI Features

To add new control buttons or features:

1. **Add UI Element**: Create button in the HTML template
2. **Update Controls**: Add event listener in `controls.js`
3. **SDK Integration**: Use appropriate LiveKit SDK method
4. **State Management**: Update control state and notify other modules
5. **Visual Feedback**: Update button appearance in `updateControlButtons()`

Example:
```javascript
// In controls.js
async toggleNewFeature() {
    await this.localParticipant.setNewFeatureEnabled(!this.newFeatureEnabled);
    this.updateControlButtons();
    this.notifyControlStateChange('newFeature', this.newFeatureEnabled);
}
```

## Key Improvements

### 1. **Eliminated Manual Polling**
- No more `setInterval` checks for camera state
- No more DOM-based video detection
- No more manual synchronization loops

### 2. **Proper SDK Event Usage**
- All camera detection via `TrackMuted/Unmuted` events
- All participant management via `ParticipantConnected/Disconnected`
- All media via `TrackSubscribed/Unsubscribed`

### 3. **Clean Module Separation**
- Each module has a single responsibility
- Clear interfaces between modules
- Easy to test and maintain
- Easy to extend with new features

### 4. **Better Error Handling**
- Graceful degradation on errors
- Proper cleanup on destruction
- Automatic reconnection on network issues

### 5. **Performance Improvements**
- No unnecessary DOM queries
- Event-driven updates only when needed
- Optimized layout calculations
- Reduced memory usage

## Migration Guide

### For Developers
1. Replace `new ProfessionalLiveKitMeeting()` with `initializeLiveKitMeeting()`
2. Update any direct method calls to use the new module structure
3. Remove any custom polling or manual state management
4. Test all meeting functionality to ensure compatibility

### For Blade Templates
The public API remains largely compatible, but you may need to update:
- Event listener setup (now handled automatically)
- Custom control implementations
- Any direct access to internal properties

## Testing

### Acceptance Tests
These tests must pass to verify the refactoring is successful:

1. **✅ Camera Toggle**: When participant toggles camera, overlay shows/hides instantly
2. **✅ Late Join**: Joining late shows existing participants with correct camera state
3. **✅ Screen Share**: Start/stop updates UI via events only
4. **✅ Active Speaker**: Highlighting updates via `ActiveSpeakersChanged`
5. **✅ No Polling**: No `setInterval` loops for camera state detection
6. **✅ No DOM Detection**: No `videoWidth/videoHeight` checks

### Manual Testing Checklist
- [ ] Join meeting with multiple participants
- [ ] Toggle camera on/off for each participant
- [ ] Verify camera overlays update immediately
- [ ] Test focus mode by clicking participants
- [ ] Test screen sharing
- [ ] Test chat functionality
- [ ] Test hand raise feature
- [ ] Verify no console errors
- [ ] Check network tab for efficient API usage

## Troubleshooting

### Common Issues

1. **Camera state not updating**: Check that `TrackMuted/Unmuted` events are properly handled
2. **Focus mode not working**: Verify layout module initialization and container elements
3. **Controls not responding**: Check that control module has proper room/participant references
4. **Layout issues**: Verify CSS classes and responsive design rules

### Debug Tools

Enable debug logging:
```javascript
// Add to browser console
localStorage.setItem('livekit-debug', 'true');
```

Check module states:
```javascript
// Get current meeting instance
const meeting = getCurrentMeeting();
console.log(meeting.getMeetingState());
```

## Future Enhancements

The modular structure makes it easy to add:
- Breakout rooms (new module)
- Recording management (extend controls)
- Whiteboard integration (new module)
- Custom video filters (extend tracks)
- Advanced layout modes (extend layout)
- Analytics and monitoring (new module)

Each enhancement can be added as a new module or extension to existing modules without affecting the core functionality.
