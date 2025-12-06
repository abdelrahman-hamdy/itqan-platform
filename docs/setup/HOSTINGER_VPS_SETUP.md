# Hostinger VPS Setup Guide (From Scratch)

Complete setup guide for your Hostinger KVM 8 VPS - starting from the control panel.

**For Mac M3 Users**

---

## Part 1: Initial VPS Provisioning (5-10 minutes)

### Step 1: Access Hostinger Control Panel

1. **Login to hPanel**
   - Go to: https://hpanel.hostinger.com
   - Enter your Hostinger account credentials
   - Click "Login"

2. **Navigate to VPS Section**
   - On the top menu, click **"VPS"**
   - You should see your KVM 8 plan listed
   - Status will show as "Not Set Up" or "Pending Setup"

---

### Step 2: Start VPS Setup

1. **Click the "Setup" Button**
   - Find your KVM 8 VPS plan
   - Click the blue **"Setup"** button
   - You'll be taken to the OS selection screen

2. **Choose Operating System Template**

   **IMPORTANT:** Select the correct template for LiveKit deployment:

   - Click on **"Plain OS"** tab (NOT "OS with Panel" or "Application")
   - Select: **Ubuntu 22.04 LTS** (recommended) or **Ubuntu 24.04 LTS**
   - **DO NOT** select any control panel (cPanel/Plesk) - we don't need it

   **Why Plain OS?**
   - Faster setup (1-3 minutes vs 10-15 minutes with control panel)
   - Lower resource usage (no overhead from control panel)
   - Perfect for Docker-based deployments like LiveKit
   - $0 extra cost (control panels cost $10-20/month)

3. **Set Server Details**

   You'll be asked for:
   - **Hostname**: Enter `livekit-server` (or any name you prefer)
   - **Root Password**: Create a STRONG password
     - At least 12 characters
     - Mix of uppercase, lowercase, numbers, symbols
     - Example: `Ltk@2025!Secure#Pass`
     - **SAVE THIS PASSWORD!** You'll need it for SSH access

4. **Choose Data Center Location**

   - For Middle East users, select: **Singapore** (closest to Saudi Arabia)
   - Alternative: **Europe (Amsterdam or London)**
   - **Avoid**: US data centers (higher latency to Middle East)

5. **Confirm and Start Provisioning**

   - Review your selections
   - Click **"Continue"** or **"Set Up VPS"**
   - Wait for provisioning to complete

---

### Step 3: Wait for VPS to Be Ready

**Provisioning Time:**
- Plain Ubuntu OS: **1-5 minutes** ✅
- You'll see a progress bar or status indicator
- Status will change from "Setting Up" → "Active" when ready

**What's Happening Behind the Scenes:**
1. Server resources allocated (8 CPU cores, 32GB RAM, 400GB SSD)
2. Ubuntu OS installed
3. Network configured and IP address assigned
4. Firewall initialized
5. SSH server started

**You'll receive:**
- Email notification when VPS is ready
- Dashboard will show "Active" status

---

### Step 4: Get Your SSH Credentials

Once VPS shows "Active" status:

1. **In hPanel, click "Manage"** next to your VPS
2. **Go to "SSH Access"** section (left sidebar)
3. **Note down these details:**

   ```
   Server IP Address: ___.___.___.___  (e.g., 123.45.67.89)
   SSH Port: 22 (default)
   Username: root
   Password: [the one you set during setup]
   ```

4. **Copy the IP address** - you'll need it immediately!

---

## Part 2: Connect from Mac M3 (5 minutes)

### Step 1: Open Terminal on Your Mac

**Quick ways to open Terminal:**
- Press `Command + Space`, type "Terminal", press Enter
- Or: Applications → Utilities → Terminal
- Or: Use Spotlight search (Command + Space)

---

### Step 2: Connect via SSH

**Copy and paste this command** (replace `YOUR_SERVER_IP` with your actual IP):

```bash
ssh root@YOUR_SERVER_IP
```

**Example:**
```bash
ssh root@123.45.67.89
```

**First Time Connecting?**

You'll see a message like:
```
The authenticity of host '123.45.67.89' can't be established.
ED25519 key fingerprint is SHA256:xxxxxxxxxxx.
Are you sure you want to continue connecting (yes/no/[fingerprint])?
```

**Type:** `yes` and press Enter

**Then enter your root password** when prompted.

**✅ Success!** You should see:
```
Welcome to Ubuntu 22.04.x LTS
root@livekit-server:~#
```

---

### Step 3: Verify You're Connected

Run these commands to verify everything:

```bash
# Check server hostname
hostname
# Should show: livekit-server

# Check Ubuntu version
lsb_release -a
# Should show: Ubuntu 22.04 or 24.04

# Check server resources
free -h
# Should show ~32GB RAM

nproc
# Should show: 8 (CPU cores)

df -h
# Should show ~400GB disk
```

**All good?** Continue to the next section! ✅

---

## Part 3: Initial Server Configuration (15 minutes)

### Step 1: Update System Packages

**Critical first step - always update on a new server:**

```bash
# Update package lists
apt update

# Upgrade all packages (will take 3-5 minutes)
apt upgrade -y

# Clean up
apt autoremove -y
```

**You may see:**
- Download progress bars
- "Setting up..." messages
- "Processing triggers..." messages

**This is normal!** Wait for it to complete.

---

### Step 2: Set Timezone to Saudi Arabia

```bash
# Set timezone
timedatectl set-timezone Africa/Cairo

# Verify
timedatectl
# Look for: "Time zone: Asia/Riyadh (AST, +0300)"

# Also verify date/time
date
# Should show correct Saudi time
```

---

### Step 3: Create Swap File (Prevents Out-of-Memory Issues)

```bash
# Create 4GB swap file
fallocate -l 4G /swapfile

# Set correct permissions
chmod 600 /swapfile

# Format as swap
mkswap /swapfile

# Enable swap
swapon /swapfile

# Make permanent (survives reboots)
echo '/swapfile none swap sw 0 0' >> /etc/fstab

# Verify swap is active
free -h
# Under "Swap", you should see "4.0Gi" total
```

**Why do we need swap?**
- Prevents server from crashing if RAM fills up
- Provides emergency memory buffer
- Recommended for production servers

---

### Step 4: Configure Basic Firewall

**CRITICAL:** Always allow SSH BEFORE enabling firewall, or you'll lock yourself out!

```bash
# Install UFW (Uncomplicated Firewall)
apt install ufw -y

# FIRST: Allow SSH (port 22) - CRITICAL!
ufw allow 22/tcp
# Output: Rules updated

# Allow HTTP/HTTPS (for future use)
ufw allow 80/tcp
ufw allow 443/tcp

# Now it's safe to enable the firewall
ufw --force enable

# Check status
ufw status
# Should show: Status: active
# Rules for: 22/tcp, 80/tcp, 443/tcp
```

**⚠️ If you get disconnected:**
- Don't panic!
- Close Terminal
- Reconnect: `ssh root@YOUR_SERVER_IP`
- Enter password again

---

### Step 5: Install Essential Tools

```bash
# Install monitoring and diagnostic tools (one command, takes ~1 minute)
apt install -y \
    htop \
    iftop \
    ifstat \
    net-tools \
    curl \
    wget \
    vim \
    git \
    unzip \
    ca-certificates \
    gnupg \
    lsb-release

# During installation, you may see a dialog titled 'Daemons using outdated libraries'
# It will ask: "Which services should be restarted?" and show options like:
#   [ ] networkd-dispatcher.service
#   [ ] unattended-upgrades.service
#
# What to do? It is safe to keep both boxes checked (default) and press <OK>.
# This will restart those background services, which is generally recommended.
# You can use arrow keys to select/deselect, then press Enter to continue.

# Verify key tools installed
htop --version
curl --version
```

**What these tools do:**
- `htop`: Interactive CPU/RAM monitor
- `iftop`: Real-time bandwidth monitor
- `ifstat`: Network statistics
- `curl/wget`: Download files from internet
- `vim`: Text editor
- `git`: Version control (for updates)

---

### Step 6: Install Docker & Docker Compose

**This is essential for LiveKit deployment.**

```bash
# Add Docker's official GPG key
mkdir -p /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg

# Set up Docker repository
echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
  $(lsb_release -cs) stable" | tee /etc/apt/sources.list.d/docker.list > /dev/null

# Update apt package index
apt update

# Install Docker Engine
apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

# Start Docker service
systemctl start docker
systemctl enable docker

# Verify Docker is running
docker --version
# Should show: Docker version 27.x.x or newer

docker compose version
# Should show: Docker Compose version v2.x.x

# Test Docker with hello-world
docker run hello-world
# Should see: "Hello from Docker!" message
```

**If you see the "Hello from Docker!" message, Docker is working perfectly!** ✅

---

### Step 7: Create Scripts Directory

```bash
# Create directories for scripts and monitoring
mkdir -p /opt/scripts
mkdir -p /opt/monitoring

# Set permissions
chmod 755 /opt/scripts
chmod 755 /opt/monitoring
```

---

### Step 8: Get Your Server's Public IP

You need this IP for LiveKit configuration:

```bash
# Get public IP
curl -4 ifconfig.me

# Or alternative method
curl -4 icanhazip.com

# Copy this IP address and save it!
# Example: 123.45.67.89
```

**Save this IP!** You'll need it multiple times during LiveKit deployment.

---

### Step 9: Verify Server Resources

Run this comprehensive check:

```bash
echo "=== Server Information ==="
echo ""
echo "Hostname:"
hostname

echo ""
echo "Public IP:"
curl -4 -s ifconfig.me

echo ""
echo "Ubuntu Version:"
lsb_release -d | cut -f2

echo ""
echo "CPU Cores:"
nproc

echo ""
echo "Memory:"
free -h | grep Mem | awk '{print $2 " total, " $3 " used, " $4 " free"}'

echo ""
echo "Disk Space:"
df -h / | tail -1 | awk '{print $2 " total, " $3 " used, " $4 " available"}'

echo ""
echo "Swap:"
free -h | grep Swap | awk '{print $2 " total, " $3 " used, " $4 " free"}'

echo ""
echo "Timezone:"
timedatectl | grep "Time zone"

echo ""
echo "Docker Status:"
systemctl is-active docker

echo ""
echo "Firewall Status:"
ufw status | grep Status
```

**Expected Output:**
```
=== Server Information ===

Hostname:
livekit-server

Public IP:
123.45.67.89

Ubuntu Version:
Ubuntu 22.04.x LTS

CPU Cores:
8

Memory:
32Gi total, 500Mi used, 31Gi free

Disk Space:
400G total, 5G used, 375G available

Swap:
4.0Gi total, 0B used, 4.0Gi free

Timezone:
Time zone: Asia/Riyadh (AST, +0300)

Docker Status:
active

Firewall Status:
Status: active
```

**If everything matches above, you're ready for LiveKit deployment!** ✅

---

## Part 4: Optional Security Hardening (10 minutes)

### Optional: Change SSH Port (Advanced)

**Why?** Default port 22 is constantly scanned by bots. Changing it reduces automated attacks.

**Only do this if comfortable with SSH:**

```bash
# Backup SSH config
cp /etc/ssh/sshd_config /etc/ssh/sshd_config.backup

# Edit SSH config
nano /etc/ssh/sshd_config

# Find line: #Port 22
# Change to: Port 2222 (or any port 1024-65535)
# Remove the # to uncomment

# Save: Ctrl+O, Enter, Ctrl+X

# Allow new port in firewall
ufw allow 2222/tcp

# Restart SSH
systemctl restart sshd

# Test connection in NEW terminal window (don't close current one!)
# In new terminal:
ssh -p 2222 root@YOUR_SERVER_IP

# If successful, close old connection
# If failed, revert changes in current terminal
```

**⚠️ WARNING:** Only do this if you know what you're doing. If misconfigured, you'll lock yourself out!

---

### Optional: Create Non-Root User (Security Best Practice)

```bash
# Create new user
adduser livekit
# Set a strong password
# Press Enter for all other questions (accept defaults)

# Add to sudo group
usermod -aG sudo livekit

# Add to docker group (so they can use Docker without sudo)
usermod -aG docker livekit

# Test the new user
su - livekit

# Test sudo access
sudo whoami
# Should output: root

# Test docker access
docker ps
# Should list containers (empty list is fine)

# Exit back to root
exit
```

**In production, you should use this non-root user instead of root.**

For this guide, we'll continue as root for simplicity.

---

## Verification Checklist ✅

Before proceeding to LiveKit deployment, verify ALL items:

**Server Access:**
- [✅] Can SSH into server from Mac Terminal
- [✅] Server responds and shows Ubuntu welcome message
- [✅] Root password works

**System Configuration:**
- [✅] System updated (`apt update && apt upgrade` completed)
- [✅] Timezone set to Asia/Riyadh
- [✅] Hostname set to `livekit-server` (or your chosen name)
- [✅] Public IP address known and saved

**Resources:**
- [✅] 8 CPU cores confirmed (`nproc` shows 8)
- [✅] 32GB RAM confirmed (`free -h` shows ~32Gi)
- [✅] 400GB disk space confirmed (`df -h` shows ~400G)
- [✅] 4GB swap active (`free -h` shows 4.0Gi swap)

**Software Installed:**
- [✅] Docker installed and running (`docker --version` works)
- [✅] Docker Compose installed (`docker compose version` works)
- [✅] Docker tested successfully (`docker run hello-world` worked)
- [✅] Essential tools installed (htop, iftop, curl, vim, etc.)

**Security:**
- [✅] UFW firewall enabled and active
- [✅] SSH port (22) allowed in firewall
- [✅] HTTP/HTTPS ports (80, 443) allowed in firewall

**Network:**
- [✅] Internet connectivity working (`curl ifconfig.me` returns IP)
- [✅] DNS resolution working (`ping -c 2 google.com` succeeds)

---

## Quick Reference Commands (Cheat Sheet)

```bash
# Connect to server
ssh root@YOUR_SERVER_IP

# Check system resources
htop                 # Press F10 to exit
free -h              # Memory usage
df -h                # Disk usage
nproc                # CPU cores

# Monitor network
iftop -i eth0        # Real-time bandwidth (press Q to exit)
ifstat -i eth0 1 5   # Network stats for 5 seconds

# Docker commands
docker ps            # List running containers
docker ps -a         # List all containers
docker images        # List downloaded images
docker logs CONTAINER_NAME   # View container logs

# Firewall
ufw status           # Check firewall status
ufw allow PORT/tcp   # Allow a port

# Service management
systemctl status docker      # Check Docker status
systemctl restart docker     # Restart Docker
systemctl enable docker      # Enable Docker on boot

# Server management
reboot               # Reboot server (use with caution!)
shutdown -h now      # Shutdown server
top                  # System monitor (press Q to exit)
```

---

## Common Mac Terminal Tips

### Keyboard Shortcuts
- `Command + K`: Clear terminal screen
- `Command + T`: New terminal tab
- `Control + C`: Cancel current command
- `Control + D`: Logout/exit
- `Control + R`: Search command history
- `↑ / ↓ arrows`: Navigate command history

### Copy/Paste in Terminal
- **Copy**: Select text, then `Command + C`
- **Paste**: `Command + V`

### Multiple SSH Sessions
You can have multiple SSH connections open:
- Open new Terminal tab: `Command + T`
- Connect again: `ssh root@YOUR_SERVER_IP`
- Switch between tabs: `Command + Shift + [ or ]`

---

## Troubleshooting

### Can't Connect via SSH?

**Error: "Connection refused"**
```bash
# Check if VPS is active in hPanel
# Wait 5 minutes and try again
# Verify IP address is correct
```

**Error: "Permission denied (publickey,password)"**
```bash
# Double-check your root password
# Copy-paste password to avoid typos
# Reset password in hPanel if needed:
#   VPS → Manage → SSH Access → Reset Password
```

**Error: "Connection timeout"**
```bash
# Check your internet connection
# Try from different network (mobile hotspot)
# Check if firewall blocking outgoing SSH (unlikely on Mac)
```

### VPS Stuck in "Setting Up"?

- Wait 10-15 minutes (sometimes takes longer)
- Check email for error notifications
- Contact Hostinger support via live chat in hPanel
- They're available 24/7 and very responsive

### Docker Installation Failed?

```bash
# Remove partial installation
apt remove docker docker-engine docker.io containerd runc

# Start fresh with installation steps from Step 6
# Make sure you ran `apt update` first
```

### Forgot Root Password?

1. Go to hPanel → VPS → Manage
2. Click "SSH Access" on left sidebar
3. Click "Reset Password"
4. Set new password
5. Reconnect with new password

---

## Next Steps

**🎉 Congratulations!** Your Hostinger VPS is fully configured and ready for LiveKit deployment.

**You've completed:**
- ✅ VPS provisioning through hPanel
- ✅ SSH access from Mac M3
- ✅ System updates and timezone configuration
- ✅ Swap file creation
- ✅ Firewall setup
- ✅ Docker & Docker Compose installation
- ✅ Server verification

**What's Next:**

1. **Proceed to LiveKit Deployment**
   - Refer to the main deployment plan
   - Start with Phase 2: LiveKit Server Setup
   - All prerequisites are now met!

2. **Keep This Information Handy:**
   - Server IP: `_______________`
   - Root Password: `_______________`
   - SSH Command: `ssh root@YOUR_IP`

---

## Support & Resources

**Hostinger Support:**
- 24/7 Live Chat: Available in hPanel
- Response time: Usually < 5 minutes
- They're very helpful with VPS setup questions

**Documentation:**
- [How to Set up a VPS - Hostinger Tutorials](https://www.hostinger.com/tutorials/how-to-set-up-vps)
- [VPS Help Center](https://support.hostinger.com/en/collections/944797-vps)
- [hPanel Tutorial](https://www.hostinger.com/tutorials/hpanel-tutorial)

**Mac Terminal Help:**
- [Mac Terminal Guide](https://support.apple.com/guide/terminal/welcome/mac)
- Type `man ssh` in Terminal for SSH manual

---

**Server Setup Complete!** ✅

You're now ready to deploy LiveKit on your Hostinger KVM 8 VPS.

---

**Sources:**
- [How to Set up a VPS for Effective Management and Performance](https://www.hostinger.com/tutorials/how-to-set-up-vps)
- [Hostinger hPanel 2025: Login, VPS Tools & Features](https://hostings.info/hosting/schools/hostinger-hpanel)
- [What Are the Available Operating Systems for VPS at Hostinger?](https://support.hostinger.com/en/articles/1583571-what-are-the-available-operating-systems-for-vps)
- [I bought a VPS now what? 8 steps to follow after purchasing a VPS](https://www.hostinger.com/ph/tutorials/i-bought-a-vps-now-what)
