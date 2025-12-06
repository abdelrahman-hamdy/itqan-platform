# TURN Server - Complete Guide

## What is TURN?

**TURN** (Traversal Using Relays around NAT) is a protocol that **relays media traffic** when direct peer-to-peer connections fail due to firewalls or restrictive NATs.

---

## How WebRTC Connection Works (ICE Framework)

WebRTC uses **ICE (Interactive Connectivity Establishment)** to find the best connection path. It tries methods in this order:

### 1. Direct Connection (Host Candidate)
```
[Client A] ←────────→ [Client B]
  Direct peer-to-peer (BEST - lowest latency, no server load)
```

**When it works:**
- Both clients on same local network
- Both have public IPs
- No firewalls blocking

**Success rate:** ~20-30% on internet

---

### 2. STUN-Assisted Connection (Server Reflexive)
```
[Client A] ──→ [STUN Server] ←── [Client B]
              ↓
         (discovers public IP)
              ↓
[Client A] ←────────────────→ [Client B]
      Direct connection using discovered public IPs
```

**How STUN works:**
1. Client asks STUN server: "What's my public IP?"
2. STUN responds: "You are 203.0.113.5:54321"
3. Client shares this with peer
4. Peers connect directly using public IPs

**When it works:**
- Moderate NAT (not symmetric NAT)
- No strict firewall rules
- UDP traffic allowed

**Success rate:** ~60-70% additional (total ~80-90%)

**STUN is lightweight:** Just helps with IP discovery, doesn't relay traffic

---

### 3. TURN-Relayed Connection (Relay Candidate)
```
[Client A] ──→ [TURN Server] ←── [Client B]
              ↑           ↓
              All media traffic flows through here
```

**How TURN works:**
1. WebRTC tries direct and STUN first
2. Both fail due to restrictive NAT/firewall
3. Client connects to TURN server
4. TURN relays ALL media packets between peers

**When it's needed:**
- Symmetric NAT (randomizes ports, blocks direct connections)
- Corporate firewalls blocking peer-to-peer
- Strict mobile carrier NATs
- VPN users
- Countries with restrictive internet policies

**Success rate:** ~10-20% additional (brings total to ~99%)

**TURN is heavy:** Relays all video/audio traffic, uses bandwidth

---

## Real-World Example

### Scenario: Student in Egypt, Teacher in Saudi Arabia

**Without TURN:**
```
Student (Egypt)          Teacher (Saudi)
├─ Behind corporate firewall
├─ Symmetric NAT          ├─ Home router (moderate NAT)
├─ Blocks UDP             ├─ ISP allows UDP
└─ Blocks peer-to-peer    └─ STUN works

Result: ❌ Connection FAILS
Student's firewall blocks both direct and STUN-assisted connections
```

**With TURN:**
```
Student (Egypt)     →  TURN Server (VPS)  ←  Teacher (Saudi)
├─ TCP to TURN:443     (conference.itqanway.com)
├─ Works through FW    ↓
└─ ✅ Success          Relays all traffic    ✅ Success
```

**Result:** ✅ Connection WORKS
TURN relays traffic through your server, bypassing firewall restrictions

---

## Benefits of TURN

### 1. **Reliability** (99% vs 80-90% connection success)
Without TURN:
- ~80-90% of connections work (direct + STUN)
- ~10-20% fail completely

With TURN:
- ~99% of connections work
- Only fails if TURN server itself is unreachable

### 2. **Corporate Networks**
Many companies block:
- Direct peer-to-peer connections (security policy)
- UDP traffic (only allow TCP)
- Non-standard ports

TURN can use **TCP on port 443** (same as HTTPS), which almost never gets blocked.

### 3. **Mobile Networks**
Some mobile carriers use **Carrier-Grade NAT (CGNAT)**:
- Multiple users share one public IP
- Symmetric NAT that randomizes ports
- Breaks direct WebRTC connections

TURN bypasses this completely.

### 4. **Geographic Issues**
- Cross-border connections may be blocked by ISPs
- TURN in a neutral location can relay traffic

### 5. **Consistent Quality**
- Predictable connection path
- Easier to troubleshoot (all traffic through your server)
- Can monitor/log connection quality

---

## Costs/Downsides of TURN

### 1. **Server Resources**
TURN relays ALL media:
- Video: 2-5 Mbps per participant
- Audio: 50-100 Kbps per participant
- For 10 participants in one room: **20-50 Mbps bandwidth**

Example monthly bandwidth (10 sessions/day, 1 hour each, 10 participants):
```
5 Mbps × 10 participants × 10 sessions × 60 minutes × 30 days
= ~90 TB/month
```

Cloud bandwidth costs:
- AWS: ~$90/TB = **$8,100/month**
- DigitalOcean: ~$10/TB = **$900/month**
- Dedicated server with unlimited bandwidth: **$50-200/month**

**This is why we only use TURN as fallback!**

### 2. **Latency**
Direct: 20-50ms
STUN: 30-80ms
TURN: 50-150ms (extra hop through server)

### 3. **Server Load**
- CPU for encryption/decryption
- Memory for connection state
- Disk I/O for logging

---

## Why We Disabled It

When you ran LiveKit setup, the TURN server tried to bind to **port 3478 UDP**, but got this error:

```
could not listen on TURN UDP port: listen udp4 0.0.0.0:3478: bind: address already in use
```

**Possible causes:**
1. Another TURN server already running
2. Previous LiveKit instance didn't clean up
3. System service using that port

**We disabled it because:**
- Not critical for testing (80-90% connections still work)
- Can be added later separately
- Avoiding port conflicts during setup

---

## How to Properly Integrate TURN with LiveKit

You have **3 options:**

---

### Option 1: Use LiveKit's Built-in TURN (Different Ports)

**Pros:**
- Integrated with LiveKit
- Single configuration file
- Automatic credential management

**Cons:**
- Shares resources with LiveKit server
- Port 3478 conflict needs resolving

#### Step 1: Find What's Using Port 3478

On your server:
```bash
ssh root@31.97.126.52

# Check what's using port 3478
sudo lsof -i :3478
# OR
sudo netstat -tulnp | grep 3478

# If something is there, identify and stop it:
# Example output: turnserver 12345 root
sudo systemctl stop coturn
# OR
sudo kill <PID>
```

#### Step 2: Re-enable TURN in livekit.yaml

```bash
cd /opt/livekit/conference.itqanway.com
nano livekit.yaml
```

Uncomment and configure TURN section:
```yaml
turn:
  enabled: true
  domain: turn.itqanway.com

  # TLS certificate (same as LiveKit)
  tls_port: 5349
  udp_port: 3478

  # External IP (your server)
  external_tls: true

  # TURN will use LiveKit's API key for auth
```

#### Step 3: Update DNS

Add A record for TURN subdomain:
```
turn.itqanway.com → 31.97.126.52
```

#### Step 4: Open Firewall Ports

```bash
# TURN standard ports
sudo ufw allow 3478/udp  # TURN UDP
sudo ufw allow 3478/tcp  # TURN TCP
sudo ufw allow 5349/tcp  # TURN TLS
sudo ufw status
```

#### Step 5: Restart LiveKit

```bash
cd /opt/livekit/conference.itqanway.com
docker compose restart livekit

# Check logs for TURN startup
docker logs livekit-server | grep -i turn
# Should show: "TURN server started on port 3478"
```

#### Step 6: Test TURN

```bash
# Install test tool
npm install -g turnutils-uclient

# Test TURN server
turnutils-uclient -v \
  -u "test" \
  -w "test" \
  turn.itqanway.com:3478

# Should show: "0 failures, 100% success"
```

---

### Option 2: Separate Coturn Server (RECOMMENDED)

**Pros:**
- Dedicated resources for TURN
- Can scale independently
- Industry-standard (coturn)
- No port conflicts with LiveKit

**Cons:**
- Additional setup
- Separate service to maintain

#### Step 1: Install Coturn

```bash
ssh root@31.97.126.52

# Install coturn
apt update
apt install -y coturn

# Enable coturn
sudo sed -i 's/#TURNSERVER_ENABLED=1/TURNSERVER_ENABLED=1/' /etc/default/coturn
```

#### Step 2: Configure Coturn

```bash
sudo nano /etc/turnserver.conf
```

Add this configuration:
```conf
# Listening ports
listening-port=3478
tls-listening-port=5349

# External IP (your server)
external-ip=31.97.126.52

# Relay IP (same server)
relay-ip=31.97.126.52

# Realm (your domain)
realm=turn.itqanway.com
server-name=turn.itqanway.com

# SSL certificates (use Let's Encrypt from LiveKit)
cert=/opt/livekit/conference.itqanway.com/certbot/conf/live/conference.itqanway.com/fullchain.pem
pkey=/opt/livekit/conference.itqanway.com/certbot/conf/live/conference.itqanway.com/privkey.pem

# Authentication
use-auth-secret
static-auth-secret=YOUR_RANDOM_SECRET_HERE_CHANGE_THIS

# Security
no-multicast-peers
no-cli
fingerprint

# Performance
min-port=49152
max-port=65535

# Logging
verbose
log-file=/var/log/coturn/turnserver.log
```

Generate a strong secret:
```bash
openssl rand -hex 32
# Use output as static-auth-secret
```

#### Step 3: Start Coturn

```bash
sudo systemctl enable coturn
sudo systemctl start coturn
sudo systemctl status coturn

# Check logs
sudo tail -f /var/log/coturn/turnserver.log
```

#### Step 4: Configure LiveKit to Use External TURN

```bash
cd /opt/livekit/conference.itqanway.com
nano livekit.yaml
```

Add external TURN config:
```yaml
turn:
  enabled: true
  domain: turn.itqanway.com
  external_tls: true

  # Point to coturn server
  udp_port: 3478
  tls_port: 5349
```

Restart LiveKit:
```bash
docker compose restart livekit
```

---

### Option 3: Cloud TURN Service (EASIEST)

**Pros:**
- No server setup
- Managed service
- Global edge locations (low latency)
- Pay per usage

**Cons:**
- Monthly cost
- Dependency on third party

#### Popular Providers:

**1. Twilio TURN (metered.live)**
```yaml
# In livekit.yaml
turn:
  enabled: false  # Disable built-in

# Configure in LiveKit cloud settings or client SDK
```

Use Twilio's TURN servers:
```javascript
// In your frontend
const iceServers = [
  {
    urls: 'stun:stun.l.google.com:19302'
  },
  {
    urls: 'turn:global.turn.twilio.com:3478?transport=udp',
    username: 'YOUR_TWILIO_USERNAME',
    credential: 'YOUR_TWILIO_CREDENTIAL'
  }
];
```

**Cost:** ~$0.40 per GB transferred

**2. Xirsys**
Dedicated WebRTC TURN service
**Cost:** ~$50/month for 100GB

**3. Amazon Kinesis Video Streams with WebRTC**
Managed TURN/STUN service
**Cost:** ~$0.015 per GB

---

## Testing TURN Server

### Test 1: Check Ports Are Open

```bash
# From your local machine
nc -vz turn.itqanway.com 3478
nc -vz turn.itqanway.com 5349
```

### Test 2: Use Trickle ICE Tool

1. Go to: https://webrtc.github.io/samples/src/content/peerconnection/trickle-ice/
2. Remove default servers
3. Add your TURN server:
   ```
   turn:turn.itqanway.com:3478
   ```
4. Add credentials (if using static auth)
5. Click "Gather candidates"

**Expected output:**
```
relay candidate (TURN relayed)
srflx candidate (STUN discovered)
host candidate (local)
```

If you see "relay" candidate, TURN is working! ✅

### Test 3: Monitor TURN Usage

```bash
# If using coturn
sudo tail -f /var/log/coturn/turnserver.log

# If using LiveKit built-in
docker logs -f livekit-server | grep TURN
```

During a meeting, you should see:
```
TURN allocation created
TURN permission added
TURN channel bound
```

---

## Recommended Setup for Your Use Case

**For Interactive Course Platform (Itqan):**

### Phase 1: Current (No TURN)
✅ You're here now
- Works for ~80-90% of users
- Lower server costs
- Good for testing/early users

### Phase 2: Add TURN When...
You should add TURN when you notice:
1. **User complaints** about connection failures
2. **Corporate/school users** can't connect (common in education!)
3. **Mobile users** having issues
4. **Scale beyond 100 active users** (reliability becomes critical)

### Phase 3: Implementation
**Recommended approach for you:**

**Use Separate Coturn Server** (Option 2)
- Dedicated resources
- Easy to monitor
- Free (just server resources)
- Scales independently

**Why not cloud TURN?**
- Your server has good bandwidth
- One-time setup vs monthly costs
- More control over data
- Better for MENA region (local server = lower latency)

---

## Quick Decision Matrix

| Scenario | Need TURN? | Priority |
|----------|-----------|----------|
| Students on home WiFi | ❌ No | - |
| Students on mobile 4G/5G | ⚠️ Maybe | Medium |
| Students at university | ✅ Yes | High |
| Corporate training clients | ✅ Yes | Critical |
| Government/school networks | ✅ Yes | Critical |
| International students | ⚠️ Maybe | Medium |
| < 50 concurrent users | ❌ No | - |
| 50-200 users | ⚠️ Yes | Medium |
| > 200 users | ✅ Yes | High |

---

## Implementation Checklist

When you're ready to add TURN, follow this order:

- [ ] 1. Monitor current connection failures (add logging)
- [ ] 2. Identify what's using port 3478 (if anything)
- [ ] 3. Decide: Built-in vs Coturn vs Cloud
- [ ] 4. Add DNS record for turn.itqanway.com
- [ ] 5. Open firewall ports (3478, 5349)
- [ ] 6. Configure TURN server
- [ ] 7. Update livekit.yaml
- [ ] 8. Test with Trickle ICE
- [ ] 9. Test with real users from restrictive networks
- [ ] 10. Monitor bandwidth usage
- [ ] 11. Set up usage alerts (if bandwidth is limited)

---

## Cost Estimation (For Your Server)

**Assumptions:**
- 100 concurrent sessions/day
- 10 participants per session
- 1 hour average duration
- 10% of connections need TURN (after STUN)
- 3 Mbps per TURN connection

**Daily bandwidth:**
```
100 sessions × 10% TURN usage = 10 sessions needing TURN
10 sessions × 10 participants × 3 Mbps × 60 minutes
= ~1.8 TB/day
```

**Monthly bandwidth:**
```
1.8 TB/day × 30 days = ~54 TB/month
```

**Your Hostinger VPS:**
- If unlimited bandwidth: ✅ No extra cost
- If metered: Check your plan's bandwidth limit

**Recommendation:** Add bandwidth monitoring BEFORE enabling TURN:
```bash
# Install vnstat
apt install vnstat
vnstat -l  # Monitor live traffic
```

---

## Summary

### What is TURN?
A relay server that ensures WebRTC connections work even through restrictive firewalls/NATs.

### Benefits?
- 99% connection success rate (vs 80-90%)
- Critical for corporate/school networks
- Better user experience
- Professional-grade reliability

### Why disabled?
- Port conflict during setup
- Not critical for early testing
- Can add later when needed

### How to integrate?
1. **Best for you:** Separate Coturn server (free, scalable)
2. **Alternative:** LiveKit built-in (simpler, but shared resources)
3. **If budget allows:** Cloud TURN service (managed, global)

### When to add it?
- After you have regular users
- When you get connection failure reports
- Before targeting corporate/school clients

---

**Next Step:** Monitor your current connection success rate, then implement TURN when needed!
