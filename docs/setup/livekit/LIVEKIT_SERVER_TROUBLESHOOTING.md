# LiveKit Server Startup Troubleshooting

## Issue: LiveKit Server Stuck at "Starting"

The container keeps showing "Starting" and never becomes healthy.

---

## Immediate Fix Steps

### Step 1: Check LiveKit Server Logs

```bash
# View live logs (this is the MOST important step)
docker logs livekit-server -f

# Or check last 50 lines
docker logs livekit-server --tail 50
```

**Look for error messages**. Common errors:
- YAML syntax error
- Port binding failed
- Redis connection failed
- Invalid configuration

### Step 2: Stop All Containers and Clean Up

```bash
cd /opt/livekit

# Stop all containers
docker compose down

# Remove containers completely
docker compose rm -f

# Optional: Remove volumes if you want fresh start
docker volume prune -f
```

### Step 3: Fix Docker Compose Version Warning

Edit `/opt/livekit/docker-compose.yml`:

```bash
nano /opt/livekit/docker-compose.yml
```

**Remove the first line** `version: '3.8'` (it's obsolete in Docker Compose V2):

```yaml
# DELETE THIS LINE:
# version: '3.8'

# Start directly with:
services:
  redis:
    ...
```

Save and exit.

### Step 4: Verify LiveKit Configuration

Check your LiveKit config file:

```bash
# View the config
cat /opt/livekit/livekit.yaml

# Check for syntax errors (no command, but look for obvious issues)
# Common issues:
# - Extra spaces before colons
# - Missing colons
# - Incorrect indentation
```

**Here's the CORRECT configuration** (copy-paste this if unsure):

```bash
cat > /opt/livekit/livekit.yaml <<'EOF'
port: 7880

rtc:
  port_range_start: 50000
  port_range_end: 60000
  use_external_ip: true

keys:
  APIxdLnkvjeS3PV: coCkSrJcJmAKQcmODKd3qgCaa80YJSnrvGEDebrPAIJC

redis:
  address: redis:6379

log_level: info

room:
  auto_create: true
  empty_timeout: 300
  max_participants: 100
EOF
```

### Step 5: Test Redis Separately

```bash
cd /opt/livekit

# Start only Redis
docker compose up -d redis

# Wait 5 seconds
sleep 5

# Test Redis
docker exec livekit-redis redis-cli ping
# Should return: PONG

# If Redis works, continue to next step
```

### Step 6: Start LiveKit Server Manually (Debug Mode)

```bash
cd /opt/livekit

# Start LiveKit in foreground to see errors
docker compose up livekit-server

# Watch the output for errors
# Press Ctrl+C to stop when done
```

**Common Errors and Fixes**:

#### Error: "address already in use"
```bash
# Check what's using port 7880
sudo lsof -i :7880

# Or
sudo netstat -tlnp | grep 7880

# Kill the process or change LiveKit port
```

#### Error: "cannot connect to redis"
```bash
# Verify Redis container name
docker ps | grep redis

# Should be: livekit-redis

# If different, update livekit.yaml redis address
```

#### Error: "failed to bind to port"
```bash
# Check if UFW is blocking
sudo ufw status

# Ensure 7880 is allowed
sudo ufw allow 7880/tcp
```

### Step 7: Start with Minimal Configuration

If still failing, try minimal config:

```bash
cat > /opt/livekit/livekit.yaml <<'EOF'
port: 7880

keys:
  APIxdLnkvjeS3PV: coCkSrJcJmAKQcmODKd3qgCaa80YJSnrvGEDebrPAIJC

log_level: debug
EOF
```

Then start:

```bash
docker compose up livekit-server
```

### Step 8: Check System Resources

```bash
# Check available memory
free -h

# Check disk space
df -h

# Check CPU usage
top
# Press 'q' to exit
```

LiveKit needs:
- At least 1GB RAM available
- At least 2GB disk space
- CPU not at 100%

### Step 9: Fresh Start with Correct Config

After identifying the issue, do a complete restart:

```bash
cd /opt/livekit

# Stop everything
docker compose down

# Remove old containers and networks
docker compose rm -f
docker network prune -f

# Start services in order
docker compose up -d redis
sleep 10

docker compose up -d livekit-server
sleep 10

docker compose up -d nginx

# Check status
docker compose ps
```

---

## Expected Healthy Output

After successful start, you should see:

```bash
docker compose ps

# Expected output:
NAME                IMAGE                          STATUS
livekit-redis       redis:7-alpine                 Up X seconds (healthy)
livekit-server      livekit/livekit-server:latest  Up X seconds (healthy)
livekit-nginx       nginx:alpine                   Up X seconds (healthy)
```

Test LiveKit:

```bash
# Should return 404 (but connection works)
curl http://localhost:7880/

# Check health (if endpoint exists)
docker logs livekit-server --tail 20
# Should see: "starting livekit-server" and no errors
```

---

## Most Likely Causes

Based on your symptoms, the most likely issues are:

1. **Port 7880 already in use** - Check with `lsof -i :7880`
2. **Redis connection timeout** - Verify Redis started first and is healthy
3. **Invalid YAML syntax** - Extra spaces, wrong indentation
4. **Firewall blocking internal ports** - Check UFW rules

---

## Quick Diagnostic Commands

Run these to gather information:

```bash
# 1. Check what's running
docker ps -a

# 2. Check LiveKit logs (MOST IMPORTANT)
docker logs livekit-server --tail 100

# 3. Check Redis logs
docker logs livekit-redis --tail 50

# 4. Check network
docker network inspect livekit_livekit-network

# 5. Verify config file
cat /opt/livekit/livekit.yaml

# 6. Check port usage
sudo netstat -tlnp | grep -E '7880|6379'
```

---

## Nuclear Option: Complete Reset

If nothing works, completely reset:

```bash
cd /opt/livekit

# Stop and remove everything
docker compose down -v

# Remove all containers
docker rm -f $(docker ps -aq) 2>/dev/null || true

# Remove networks
docker network prune -f

# Remove volumes
docker volume prune -f

# Rebuild from Step 7 in main guide
```
