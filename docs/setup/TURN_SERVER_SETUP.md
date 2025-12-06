# Phase 3: TURN Server Setup (Coturn)

## What is TURN?
TURN helps users behind strict firewalls/NAT connect to video meetings. Essential for corporate networks in Middle East.

## Step-by-Step Installation

### Step 1: Install Coturn
Connect to your server via Terminal and run:

```bash
sudo apt update
sudo apt install -y coturn
```

Wait for installation to complete (about 30 seconds).

### Step 2: Enable Coturn Service

```bash
sudo sed -i 's/#TURNSERVER_ENABLED=1/TURNSERVER_ENABLED=1/' /etc/default/coturn
```

This enables Coturn to start on boot.

### Step 3: Generate TURN Credentials

Run this to generate a secure shared secret:

```bash
TURN_SECRET=$(openssl rand -hex 32)
echo "Your TURN Secret: $TURN_SECRET"
```

**IMPORTANT**: Copy the output - you'll need it in Step 5!

### Step 4: Get Your Server IP

```bash
SERVER_IP=$(curl -4 -s ifconfig.me)
echo "Your Server IP: $SERVER_IP"
```

**IMPORTANT**: Copy this IP - you'll need it in Step 5!

### Step 5: Create Coturn Configuration

#### 5a. Open nano editor:
```bash
sudo nano /etc/turnserver.conf
```

#### 5b. Delete everything in the file:
- Press `Ctrl+K` repeatedly until file is empty

#### 5c. Paste this configuration:
Press `Command+V` (Mac) to paste:

```
# Listener configuration
listening-port=3478
tls-listening-port=5349

# Server IP (replace with your IP from Step 4)
listening-ip=0.0.0.0
relay-ip=YOUR_SERVER_IP_HERE

# External IP
external-ip=YOUR_SERVER_IP_HERE

# Realm
realm=livekit.itqan-platform.com

# Authentication
use-auth-secret
static-auth-secret=YOUR_TURN_SECRET_HERE

# Security
no-multicast-peers
no-cli
no-tlsv1
no-tlsv1_1

# Logging
verbose
log-file=/var/log/turnserver/turnserver.log
```

#### 5d. Replace placeholders:
- Use arrow keys to navigate
- Find `YOUR_SERVER_IP_HERE` (appears 2 times)
- Delete it and type your actual IP from Step 4
- Find `YOUR_TURN_SECRET_HERE`
- Delete it and type your actual secret from Step 3

#### 5e. Save and exit:
- Press `Ctrl+O` (save)
- Press `Enter` (confirm filename)
- Press `Ctrl+X` (exit)

### Step 6: Create Log Directory

```bash
sudo mkdir -p /var/log/turnserver
sudo chown turnserver:turnserver /var/log/turnserver
```

### Step 7: Configure Firewall

```bash
sudo ufw allow 3478/tcp
sudo ufw allow 3478/udp
sudo ufw allow 5349/tcp
sudo ufw allow 5349/udp
sudo ufw allow 49152:65535/udp
```

### Step 8: Start Coturn

```bash
sudo systemctl restart coturn
sudo systemctl enable coturn
```

### Step 9: Verify Coturn is Running

```bash
sudo systemctl status coturn
```

You should see:
- **Active: active (running)** in green

If you see errors, run:
```bash
sudo journalctl -u coturn -n 50
```

And share the output.

### Step 10: Update LiveKit Config

#### 10a. Open LiveKit config:
```bash
nano /opt/livekit/config/livekit.yaml
```

#### 10b. Add TURN configuration:
- Use arrow keys to navigate to the `rtc:` section
- After the `node_ip: "31.97.126.52"` line, add these lines:

```yaml
  stun_servers:
    - stun.l.google.com:19302
  turn_servers:
    - host: YOUR_SERVER_IP_HERE
      port: 3478
      protocol: udp
      username: livekit
      credential: YOUR_TURN_SECRET_HERE
```

**Replace**:
- `YOUR_SERVER_IP_HERE` with your IP from Step 4
- `YOUR_TURN_SECRET_HERE` with your secret from Step 3

#### 10c. Save and exit:
- Press `Ctrl+O` (save)
- Press `Enter` (confirm)
- Press `Ctrl+X` (exit)

### Step 11: Restart LiveKit

```bash
cd /opt/livekit
docker-compose restart
```

Wait 10 seconds, then verify:

```bash
docker logs livekit-server --tail 30
```

You should see TURN servers listed in the startup logs.

### Step 12: Test TURN Server

```bash
sudo apt install -y stun-client
stun YOUR_SERVER_IP_HERE -p 3478
```

**Replace** `YOUR_SERVER_IP_HERE` with your actual IP.

You should see output containing your external IP address.

## Verification Checklist

Run these commands and share results:

```bash
# Test 1: Coturn status
sudo systemctl status coturn | grep Active

# Test 2: Coturn listening ports
sudo netstat -tulpn | grep turnserver

# Test 3: LiveKit TURN config
docker logs livekit-server 2>&1 | grep -i turn
```

## Next Steps

Once TURN is verified:
- **Phase 4**: Update Laravel .env to point to self-hosted LiveKit
- **Phase 5**: Test from your application
- **Phase 6**: Production cutover

## Troubleshooting

### Coturn won't start
```bash
# Check for port conflicts
sudo netstat -tulpn | grep 3478

# Check config syntax
sudo turnserver -c /etc/turnserver.conf --log-file=stdout
```

### TURN test fails
```bash
# Check firewall
sudo ufw status | grep 3478

# Check Coturn logs
sudo tail -f /var/log/turnserver/turnserver.log
```
