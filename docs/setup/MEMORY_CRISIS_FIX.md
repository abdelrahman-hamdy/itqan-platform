# CRITICAL: Server Out of Memory - Immediate Fix

## Current Situation

Your server has **critically low memory**:
```
Total:     31GB
Used:      30GB (97%)
Free:      258MB
Available: 149MB
Swap:      3.4GB/4GB used (heavily swapping)
```

This is why all commands are hanging. The server can't execute anything due to memory exhaustion.

---

## IMMEDIATE ACTION: Find Memory Hogs

Run these commands to identify what's consuming memory:

```bash
# Show top memory-consuming processes (sorted by memory usage)
ps aux --sort=-%mem | head -20

# Alternative: Use top (press Shift+M to sort by memory, then 'q' to quit)
top -o %MEM

# Docker-specific: Show memory usage by containers
docker stats --no-stream --format "table {{.Name}}\t{{.MemUsage}}\t{{.MemPerc}}"
```

**Send me the output of these commands** and we'll identify the culprit.

---

## Common Causes & Fixes

### Cause 1: Runaway Application Process

**Symptoms**: PHP, Node.js, Python, or other application consuming 10GB+

**Fix**:
```bash
# Find the process ID (PID) from ps aux output above
# Then kill it:
kill -9 <PID>

# Example if PHP is the culprit:
pkill -9 php

# Example if Node.js is the culprit:
pkill -9 node
```

### Cause 2: Docker Container Memory Leak

**Symptoms**: One or more Docker containers consuming excessive memory

**Fix**:
```bash
# Stop all Docker containers
docker stop $(docker ps -aq)

# Or stop specific container
docker stop <container_name>

# Check memory after stopping
free -h
```

### Cause 3: MySQL/Database Consuming Too Much

**Symptoms**: mysql or mysqld process using 10GB+

**Fix**:
```bash
# Restart MySQL (if it's a Docker container)
docker restart <mysql_container_name>

# Or if it's a systemd service
systemctl restart mysql
```

### Cause 4: File System Cache Buildup

**Symptoms**: buff/cache is very high, but no specific process shows high usage

**Fix**:
```bash
# Drop caches (safe operation, doesn't lose data)
sync
echo 3 > /proc/sys/vm/drop_caches

# Check memory again
free -h
```

---

## Emergency: Force Free Memory

If you can't identify the cause quickly, use these **nuclear options**:

### Option 1: Restart Docker (Frees all container memory)
```bash
systemctl restart docker
sleep 10
free -h
```

### Option 2: Reboot Server (Last resort)
```bash
# WARNING: This will disconnect all users and stop all services
reboot
```

After reboot, SSH back in and check memory:
```bash
ssh root@31.97.126.52
free -h
# Should now show much more free memory
```

---

## After Freeing Memory: Resume LiveKit Setup

Once you have **at least 5GB free memory**, proceed:

### Step 1: Clean Up Docker
```bash
cd /opt/livekit

# Remove all stopped containers
docker container prune -f

# Remove unused images
docker image prune -f

# Remove unused networks
docker network prune -f
```

### Step 2: Start Services One by One
```bash
cd /opt/livekit

# Start Redis first (uses ~50MB)
docker compose up -d redis
sleep 5
free -h  # Check memory usage

# Start LiveKit server (uses ~200-500MB)
docker compose up -d livekit-server
sleep 10
free -h  # Check memory usage

# Start Nginx (uses ~10MB)
docker compose up -d nginx
free -h  # Check memory usage
```

### Step 3: Monitor Memory Usage
```bash
# Watch memory in real-time
watch -n 2 free -h

# Or check Docker container memory
docker stats
```

---

## Prevention: Set Docker Memory Limits

After fixing, prevent this from happening again by setting memory limits:

Edit `/opt/livekit/docker-compose.yml`:

```yaml
services:
  redis:
    image: redis:7-alpine
    deploy:
      resources:
        limits:
          memory: 512M  # Max 512MB for Redis
        reservations:
          memory: 128M
    # ... rest of config

  livekit-server:
    image: livekit/livekit-server:latest
    deploy:
      resources:
        limits:
          memory: 2G  # Max 2GB for LiveKit
        reservations:
          memory: 512M
    # ... rest of config

  livekit-egress:
    image: livekit/egress:latest
    deploy:
      resources:
        limits:
          memory: 4G  # Max 4GB for Egress (needs more for video processing)
        reservations:
          memory: 1G
    # ... rest of config

  nginx:
    image: nginx:alpine
    deploy:
      resources:
        limits:
          memory: 256M  # Max 256MB for Nginx
        reservations:
          memory: 64M
    # ... rest of config
```

Then restart with limits:
```bash
docker compose down
docker compose up -d
```

---

## Quick Diagnostic Summary

Run these and send me the output:

```bash
echo "=== MEMORY USAGE ==="
free -h

echo -e "\n=== TOP 10 MEMORY CONSUMERS ==="
ps aux --sort=-%mem | head -11

echo -e "\n=== DOCKER CONTAINER MEMORY ==="
docker stats --no-stream --format "table {{.Name}}\t{{.MemUsage}}\t{{.MemPerc}}" 2>/dev/null || echo "Docker not responding"

echo -e "\n=== DISK USAGE ==="
df -h

echo -e "\n=== SWAP USAGE ==="
swapon --show
```

---

## Expected Healthy State

After fixing, your server should look like this:

```bash
$ free -h
               total        used        free      shared  buff/cache   available
Mem:            31Gi        5Gi        24Gi       100Mi       2Gi        25Gi
Swap:          4.0Gi       100Mi       3.9Gi
```

With LiveKit running:
- **Redis**: ~50-100MB
- **LiveKit Server**: ~200-500MB
- **Nginx**: ~10-20MB
- **Total Docker**: ~500MB-1GB max
- **Available Memory**: Should stay above 20GB

---

## What Likely Happened

Based on typical scenarios:
1. **Most likely**: Another application on this server is consuming memory (PHP app, database, etc.)
2. **Possible**: Docker container memory leak from previous failed starts
3. **Less likely**: System process gone rogue

**Next step**: Run the diagnostic commands above and identify the memory hog, then we'll kill it and proceed with LiveKit setup.
