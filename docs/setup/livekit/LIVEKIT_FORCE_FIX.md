# LiveKit Server - Force Fix for Unresponsive Container

## Current Problem

The LiveKit server container is in an unresponsive state:
- `docker logs` hangs indefinitely
- `docker compose down` can't stop the container
- Container shows "Starting" forever

This indicates the container crashed or is in a corrupted state. We need to force kill it and diagnose the issue.

---

## STEP 1: Force Kill the Unresponsive Container

Run these commands on the server (31.97.126.52):

```bash
# Force kill the container (this will work even if it's unresponsive)
docker kill livekit-server

# If that doesn't work, force remove it
docker rm -f livekit-server

# Clean up any leftover containers
docker container prune -f

# Verify it's gone
docker ps -a | grep livekit
# Should show nothing
```

---

## STEP 2: Check What Went Wrong

### Check Docker Daemon Logs
```bash
# Check Docker daemon logs for errors
journalctl -u docker --since "15 minutes ago" | grep -i "livekit\|error\|fail"

# If journalctl is not available, check Docker logs directly
tail -100 /var/log/docker.log 2>/dev/null || echo "Docker log file not found"
```

### Check System Resources
```bash
# Check available memory
free -h

# Check disk space
df -h

# Check if OOM (Out of Memory) killer was triggered
dmesg | grep -i "killed process\|out of memory" | tail -20
```

### Check Kernel Messages
```bash
# Check recent kernel messages
dmesg | tail -50
```

**What to look for:**
- OOM messages (container killed due to low memory)
- Disk space issues (no space left on device)
- Docker daemon errors
- Segmentation faults or kernel panics

---

## STEP 3: Fix Docker Compose File

The version warning you saw indicates an issue. Let's fix it:

```bash
cd /opt/livekit

# Backup current docker-compose.yml
cp docker-compose.yml docker-compose.yml.backup

# Remove the obsolete 'version' line (first line of file)
sed -i '1d' docker-compose.yml

# Verify it was removed
head -5 docker-compose.yml
# Should start with 'services:' now, not 'version: 3.8'
```

---

## STEP 4: Start with Minimal LiveKit Configuration

Let's test with the absolute minimal configuration to see what errors appear:

```bash
cd /opt/livekit

# Create minimal livekit.yaml (backup original first)
cp livekit.yaml livekit.yaml.backup

# Create minimal config
cat > livekit.yaml <<'EOF'
port: 7880

keys:
  APIxdLnkvjeS3PV: coCkSrJcJmAKQcmODKd3qgCaa80YJSnrvGEDebrPAIJC

log_level: debug
EOF
```

---

## STEP 5: Start LiveKit in Foreground (to see errors)

```bash
cd /opt/livekit

# Make sure Redis is running
docker compose up -d redis
sleep 5

# Start LiveKit in FOREGROUND mode to see all output
docker compose up livekit-server
```

**This will show all errors in real-time.** Watch the output carefully.

**Common errors you might see:**

### Error: "address already in use"
**Cause:** Port 7880 is still bound from previous attempt

**Fix:**
```bash
# Press Ctrl+C to stop
# Find and kill process using port 7880
sudo lsof -ti:7880 | xargs kill -9

# Or restart Docker
sudo systemctl restart docker
sleep 10

# Try again
docker compose up livekit-server
```

### Error: "cannot connect to redis"
**Cause:** Redis container name mismatch

**Fix:**
```bash
# Check Redis container name
docker ps | grep redis

# If it's NOT named "livekit-redis", update livekit.yaml:
# Remove redis section entirely from livekit.yaml for now
sed -i '/redis:/,+1d' livekit.yaml

# Try again
docker compose up livekit-server
```

### Error: "permission denied" or "cannot bind"
**Cause:** Firewall or port permissions

**Fix:**
```bash
# Check firewall
sudo ufw status

# If active, allow 7880
sudo ufw allow 7880/tcp

# Try again
docker compose up livekit-server
```

### Error: Container crashes immediately with no output
**Cause:** Corrupted Docker image

**Fix:**
```bash
# Press Ctrl+C to stop
# Remove LiveKit image completely
docker rmi livekit/livekit-server:latest

# Pull fresh image
docker pull livekit/livekit-server:latest

# Try again
docker compose up livekit-server
```

---

## STEP 6: If It Starts Successfully

Once you see output like:
```
INFO    starting livekit-server     {"version": "...", "port": 7880}
```

**Press Ctrl+C** to stop it, then start in background:

```bash
docker compose up -d livekit-server

# Wait 10 seconds
sleep 10

# Check status
docker compose ps

# Should show:
# livekit-server    Up X seconds (healthy)
```

---

## STEP 7: If It Still Fails

If LiveKit still won't start even with minimal config, try:

### Option A: Check Docker Daemon Health
```bash
# Restart Docker daemon
sudo systemctl restart docker
sleep 10

# Verify Docker is healthy
docker info

# Try starting containers again
cd /opt/livekit
docker compose up -d redis
sleep 5
docker compose up livekit-server
```

### Option B: Use Different LiveKit Version
```bash
cd /opt/livekit

# Edit docker-compose.yml
nano docker-compose.yml

# Change line:
#   image: livekit/livekit-server:latest
# To:
#   image: livekit/livekit-server:v1.5.0

# Save and exit (Ctrl+X, Y, Enter)

# Pull new version
docker pull livekit/livekit-server:v1.5.0

# Try again
docker compose up livekit-server
```

### Option C: Check Server Resources
```bash
# Maybe the server doesn't have enough resources
# LiveKit needs at least:
# - 1GB RAM available
# - 2GB disk space
# - CPU not at 100%

# Check current usage
htop
# Press 'q' to exit

# Or
top
# Press 'q' to exit
```

---

## STEP 8: Once Working, Continue Server Setup

After LiveKit starts successfully:

1. **Restore full configuration:**
   ```bash
   cd /opt/livekit

   # Restore the full livekit.yaml (if you backed it up)
   cp livekit.yaml.backup livekit.yaml

   # Or recreate it with full config from LIVEKIT_COMPLETE_SERVER_SETUP.md Step 8
   ```

2. **Start all services:**
   ```bash
   docker compose down
   docker compose up -d redis
   sleep 10
   docker compose up -d livekit-server
   sleep 10
   docker compose up -d nginx

   # Check all are healthy
   docker compose ps
   ```

3. **Continue with SSL setup** (Step 12 in LIVEKIT_COMPLETE_SERVER_SETUP.md)

---

## Quick Diagnostic Command Summary

Run all these commands and send me the output if you're still stuck:

```bash
# System info
echo "=== SYSTEM RESOURCES ==="
free -h
df -h

echo -e "\n=== DOCKER VERSION ==="
docker --version
docker compose version

echo -e "\n=== RUNNING CONTAINERS ==="
docker ps -a

echo -e "\n=== DOCKER DAEMON STATUS ==="
sudo systemctl status docker | head -20

echo -e "\n=== PORT 7880 USAGE ==="
sudo netstat -tlnp | grep 7880

echo -e "\n=== RECENT DOCKER ERRORS ==="
journalctl -u docker --since "15 minutes ago" | grep -i error | tail -20

echo -e "\n=== KERNEL MESSAGES ==="
dmesg | tail -30

echo -e "\n=== LIVEKIT CONFIG ==="
cat /opt/livekit/livekit.yaml

echo -e "\n=== DOCKER COMPOSE CONFIG ==="
cat /opt/livekit/docker-compose.yml | head -40
```

---

## Expected Healthy Output

After successful fix, you should see:

```bash
$ docker compose ps

NAME                IMAGE                          STATUS
livekit-redis       redis:7-alpine                 Up X seconds (healthy)
livekit-server      livekit/livekit-server:latest  Up X seconds (healthy)
livekit-nginx       nginx:alpine                   Up X seconds (healthy)
```

And this should work without hanging:
```bash
$ docker logs livekit-server --tail 20
INFO    starting livekit-server     {"version": "...", "port": 7880}
INFO    rtc service started
...
```
