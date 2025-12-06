# Scripts Directory

Organized utility scripts for development, deployment, and maintenance.

---

## 📁 Folder Structure

### `/dev` (Development Tools)

Daily development scripts:

- **`dev-full.sh`** ⭐ Main development server
  - Runs Laravel server, queue worker, Vite, and Pail logs concurrently
  - Usage: `./scripts/dev/dev-full.sh`

- **`run-scheduler.sh`** - Laravel scheduler for development
  - Runs `php artisan schedule:work`

- **`scheduler-worker.sh`** - Background scheduler worker

**Quick Start**: `./scripts/dev/dev-full.sh` starts everything you need.

---

### `/deployment` (Production Deployment)

Production deployment scripts:

- **`deploy-hostinger.sh`** - Deploy to Hostinger VPS
  - Handles git pull, composer install, migrations, cache clearing

- **`finalize-recording-setup.sh`** ⭐ LiveKit recording server setup
  - Configures egress, webhooks, and restarts services
  - Run on server: `bash finalize-recording-setup.sh`

---

### `/setup` (Database Setup & Migrations)

One-time setup and migration scripts:

- `create-academy-settings-table.php` - Create academy settings table
- `migrate_teacher_subjects_grade_levels.php` - Migrate teacher data
- `setup-academy-country-field.php` - Add country field
- `setup-test-interactive-enrollment.php` - Test enrollment data
- `hostinger-setup.php` - Initial server setup
- `close-stale-cycles.php` - Clean up old billing cycles

**Usage**: `php scripts/setup/{script-name}.php`

---

### `/livekit` (LiveKit Video Server)

LiveKit server management and testing:

**Configuration:**
- `configure-livekit-webhooks.sh` - Configure webhooks
- `monitor-webhooks.sh` - Monitor webhook events
- `watch-webhooks.sh` - Watch webhook logs in real-time

**Testing:**
- `test-livekit-server.php` - Test server connectivity
- `test-livekit-simple.php` - Simple connection test

**Usage**:
```bash
# Configure webhooks
./scripts/livekit/configure-livekit-webhooks.sh

# Monitor webhooks
./scripts/livekit/monitor-webhooks.sh
```

---

### `/chat` (Chat System Management)

WireChat service management:

**Service Control:**
- `chat-status.sh` - Check chat service status
- `restart-chat.sh` - Restart chat services
- `restart-chat-services.sh` - Restart all components
- `stop-chat.sh` - Stop chat services

**Monitoring:**
- `monitor-chat.sh` - Real-time chat monitoring

**Cleanup:**
- `remove-chatify.sh` - Remove old Chatify
- `verify-chatify-removed.sh` - Verify removal

**Usage**:
```bash
# Check status
./scripts/chat/chat-status.sh

# Restart if needed
./scripts/chat/restart-chat.sh
```

---

### `/maintenance` (System Maintenance)

System maintenance and cleanup:

- `clear-session-attendance.sh` - Clear session attendance data
- `update-gradient-views.sh` - Update view files
- `show-final-status.sh` - Display system status

---

### `/ngrok` (Ngrok Tunneling)

Ngrok tunnel management for local development:

- `setup-ngrok.sh` - Setup ngrok tunnel for webhooks

**Usage**: `./scripts/ngrok/setup-ngrok.sh`

---

## 🎯 Common Tasks

### Start Development
```bash
./scripts/dev/dev-full.sh
```

### Deploy to Production
```bash
./scripts/deployment/deploy-hostinger.sh
```

### Setup LiveKit Recording
```bash
# On server
bash scripts/deployment/finalize-recording-setup.sh
```

### Check Chat Status
```bash
./scripts/chat/chat-status.sh
```

### Monitor Webhooks
```bash
./scripts/livekit/monitor-webhooks.sh
```

---

## 📝 Notes

- All scripts are executable (`chmod +x` applied)
- Scripts using Laravel require Composer dependencies
- Some scripts need to run on specific environments (dev/prod)
- Check script comments for specific requirements

---

## 🔗 Related

- **Tests**: `/tests/` directory
- **Documentation**: `/docs/` directory
- **LiveKit Setup**: `/docs/setup/livekit/`
