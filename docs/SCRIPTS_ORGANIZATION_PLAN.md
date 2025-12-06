# Scripts & Files Organization Plan

**Current Status**: 77 files in root directory (PHP, Shell, Python, JS, JSON, TXT)

**Goal**: Organize into logical structure, keep only required files in root

---

## Files Analysis

### KEEP in Root (Required Config) - 8 files

**Build/Config Files** (Must stay in root):
1. ✅ `composer.json` - PHP dependencies
2. ✅ `package.json` - NPM dependencies
3. ✅ `package-lock.json` - NPM lock file
4. ✅ `postcss.config.js` - PostCSS configuration
5. ✅ `tailwind.config.js` - TailwindCSS configuration
6. ✅ `vite.config.js` - Vite build configuration

**MCP Configuration** (For Claude Code integration):
7. ✅ `mcp.json` - Main MCP config
8. ⚠️ `.mcp.json` - Hidden duplicate? (need to check)

**Other Config**:
9. ⚠️ `.qoder.json` - Qoder config (check if still used)
10. ⚠️ `soketi.json` - Soketi WebSocket config (check if still used)

---

## MOVE to scripts/ Folder

### scripts/dev/ (Development Tools) - 3 files

**Active development scripts**:
1. ✅ `dev-full.sh` - Main development server (runs server, queue, vite, pail)
2. ✅ `run-scheduler.sh` - Scheduler for development
3. ✅ `scheduler-worker.sh` - Scheduler worker

### scripts/deployment/ (Deployment) - 2 files

1. ✅ `deploy-hostinger.sh` - Hostinger deployment script
2. ✅ `finalize-recording-setup.sh` - LiveKit recording server setup

### scripts/setup/ (Setup & Migration) - 6 files

**Database setup/migration**:
1. ✅ `create-academy-settings-table.php` - Creates academy settings
2. ✅ `migrate_teacher_subjects_grade_levels.php` - Data migration
3. ✅ `setup-academy-country-field.php` - Academy field setup
4. ✅ `setup-test-interactive-enrollment.php` - Test data setup
5. ✅ `hostinger-setup.php` - Hostinger server setup
6. ✅ `close-stale-cycles.php` - Billing cycle cleanup

### scripts/livekit/ (LiveKit Tools) - 6 files

**Server configuration**:
1. ✅ `configure-livekit-webhooks.sh` - Configure LiveKit webhooks
2. ✅ `monitor-webhooks.sh` - Monitor webhook events
3. ✅ `watch-webhooks.sh` - Watch webhook logs

**Testing**:
4. ⚠️ `test-livekit-server.php` - Server connectivity test (keep or move to tests/)
5. ⚠️ `test-livekit-simple.php` - Simple connection test (keep or move to tests/)
6. ⚠️ `test-livekit-mute.php` - Mute functionality test (move to tests/)

### scripts/chat/ (Chat System) - 9 files

**Chat service management**:
1. ✅ `chat-status.sh` - Check chat service status
2. ✅ `restart-chat.sh` - Restart chat services
3. ✅ `restart-chat-services.sh` - Restart all chat components
4. ✅ `stop-chat.sh` - Stop chat services
5. ✅ `monitor-chat.sh` - Monitor chat in real-time

**Cleanup scripts**:
6. ✅ `remove-chatify.sh` - Remove old Chatify package
7. ✅ `verify-chatify-removed.sh` - Verify Chatify removal

**Testing**:
8. ❌ `test-chat-final.sh` - DELETE (old test)
9. ❌ `test-chat-fixes.sh` - DELETE (old test)

### scripts/maintenance/ (Maintenance) - 3 files

1. ✅ `clear-session-attendance.sh` - Clear session attendance
2. ✅ `update-gradient-views.sh` - Update view files
3. ✅ `show-final-status.sh` - Show system status

### scripts/ngrok/ (Ngrok Tools) - 2 files

1. ✅ `setup-ngrok.sh` - Setup ngrok tunnel
2. ❌ `test-ngrok-webhook.sh` - DELETE (testing only)

---

## MOVE to tests/ Folder

### tests/integration/ - 2 files (Keep)

**Active integration tests**:
1. ✅ `test-recording-integration.php` ⭐ Recent, keep
2. ✅ `test-cron-jobs.sh` - Cron job testing

### tests/debug/ - 3 files (Keep)

**Debugging tools** (useful for troubleshooting):
1. ✅ `diagnose-attendance.php` - Attendance diagnostics
2. ✅ `diagnose-chat.php` - Chat diagnostics
3. ✅ `debug-interactive-course-access.php` - Course access debugging

---

## DELETE (Obsolete Test Files) - 35 files

### Old LiveKit Tests (10 files)
1. ❌ `test-livekit-integration.sh` - Superseded
2. ❌ `test-livekit-leave-event.sh` - Old test
3. ❌ `test-livekit-webhook-direct.sh` - Old test
4. ❌ `test-livekit-mute.php` - Old test
5. ❌ `test-dynamic-webhook.sh` - Old test
6. ❌ `test-webhook-endpoint.php` - Old test
7. ❌ `test-webhook-local.php` - Old test
8. ❌ `test-complete-attendance-cycle.sh` - Old test
9. ❌ `test-short-session.sh` - Old test
10. ❌ `test-attendance-fix.php` - Old test

### Old Chat Tests (6 files)
1. ❌ `test-chat-final.sh` - Old test
2. ❌ `test-chat-fixes.sh` - Old test
3. ❌ `test-chat-layout.sh` - Old test
4. ❌ `test-chat-media-files.sh` - Old test
5. ❌ `test-wirechat-only.sh` - Old test
6. ❌ `test-message-flow.sh` - Old test

### Old WireChat Tests (3 files)
1. ❌ `test-wirechat-message.php` - Old test
2. ❌ `test-wirechat-realtime.php` - Old test
3. ❌ `test-wirechat-realtime.js` - Old test

### Old Route/Permission Tests (3 files)
1. ❌ `test-route-direct.php` - Old test
2. ❌ `test-permission-routes.php` - Old test
3. ❌ `test-realtime-messaging.php` - Old test

### Old Session Tests (4 files)
1. ❌ `test-next-session.php` - Old test
2. ❌ `test-preparation-time-fix.php` - Old test
3. ❌ `test-preparation-window.php` - Old test
4. ❌ `debug-ready-sessions.php` - Old debug script

### Old Separation Test (1 file)
1. ❌ `test-interactive-course-separation.sh` - Old test

### Old MCP Test (1 file)
1. ❌ `test-mcp-setup.sh` - Old test

### Temporary Servers (2 files)
1. ❌ `simple-server.py` - Temp test server
2. ❌ `simple-ws-server.js` - Temp WebSocket server

### Old Fix Verification (2 files)
1. ❌ `verify-fix.sh` - Old verification script
2. ❌ `fix-qoder-mcp.sh` - Old fix script

---

## MOVE to docs/architecture/ (Text Files) - 4 files

1. ✅ `ALL_78_MODELS_MAPPING.txt` - Database model mapping
2. ✅ `DATABASE_FINDINGS_AND_RECOMMENDATIONS.txt` - DB analysis
3. ✅ `SESSIONS_ARCHITECTURE_DIAGRAM.txt` - Architecture diagram
4. ❌ `CHAT_DEBUG_QUICK_REFERENCE.txt` - DELETE (debug notes)

---

## Summary

**Total Files Analyzed**: 77

**Keep in Root**: 8-10 (config files only)
**Move to scripts/**: 32 files
  - dev/ (3)
  - deployment/ (2)
  - setup/ (6)
  - livekit/ (6)
  - chat/ (9)
  - maintenance/ (3)
  - ngrok/ (2)
**Move to tests/**: 5 files
  - integration/ (2)
  - debug/ (3)
**Move to docs/**: 3 files (architecture/)
**DELETE**: 35 obsolete test files

---

## New Root Directory

After cleanup, root will only contain:
```
/
├── composer.json
├── package.json
├── package-lock.json
├── postcss.config.js
├── tailwind.config.js
├── vite.config.js
├── mcp.json
├── soketi.json (if still used)
├── CLAUDE.md
└── QUICK_START.md
```

All scripts organized in:
```
scripts/
├── dev/
├── deployment/
├── setup/
├── livekit/
├── chat/
├── maintenance/
└── ngrok/

tests/
├── integration/
└── debug/
```

---

## Benefits

✅ Clean root directory (77 → ~10 files)
✅ Scripts organized by purpose
✅ Easy to find development/deployment tools
✅ Removed 45% of files (35/77)
✅ Preserved all useful scripts and tools
