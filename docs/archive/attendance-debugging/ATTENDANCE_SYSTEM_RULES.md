# 📋 ATTENDANCE SYSTEM RULES & REQUIREMENTS

**Date:** 2025-11-13
**Version:** 1.0

---

## 🎯 **CORE PRINCIPLES**

### **1. Server-Authoritative Model**
- **ONLY** LiveKit webhooks can record attendance
- **NEVER** trust frontend for attendance data
- **NO** API calls from frontend to record join/leave
- **ALL** attendance decisions made server-side

### **2. Meeting-Based Attendance Only**
- Attendance is **ONLY** counted when user is in LiveKit meeting room
- Page presence **DOES NOT** count as attendance
- Browser tab open **DOES NOT** count as attendance
- **ONLY** LiveKit participant events trigger attendance

### **3. Session Timing Boundaries**
- **NO** attendance before `session.scheduled_at`
- **NO** attendance after `session.scheduled_at + duration + 30 min grace`
- **ONLY** count time within session window
- **AUTO-CLOSE** all cycles when session ends

---

## ⏰ **TIMING WINDOWS**

```
Timeline:
├─────────────────┼───────────────────────┼─────────────────┼──────────►
PREPARATION       SESSION TIME            GRACE PERIOD     ENDED
(15 min before)   (scheduled duration)    (30 min after)   (closed)

Rules:
- PREPARATION: Can join meeting, but NO attendance counted
- SESSION TIME: Full attendance counting
- GRACE PERIOD: Attendance still counts (late joiners)
- ENDED: NO attendance, auto-close all cycles
```

### **Timing Rules:**

1. **Before Session Start (Preparation Time)**
   - Users CAN join LiveKit meeting
   - Attendance record created with open cycle
   - Duration calculation returns 0
   - Real-time calculation disabled
   - UI shows "Session not started yet"

2. **During Session (scheduled_at to scheduled_at + duration)**
   - Full attendance tracking active
   - Real-time calculation enabled
   - Duration increments every minute
   - All joins/leaves recorded via webhooks

3. **Grace Period (duration to duration + 30 min)**
   - Late joiners can still get attendance
   - Attendance continues counting
   - Teacher can still conduct session
   - Normal tracking continues

4. **After Session Ends (> duration + 30 min)**
   - All open cycles auto-closed
   - No new attendance recorded
   - Duration calculation stopped
   - Final status calculated and locked

---

## 🔄 **LIFECYCLE STATES**

### **1. Attendance Record States**

```
NOT_CREATED → CREATED → TRACKING → CALCULATED → FINALIZED
```

- **NOT_CREATED**: No record exists yet
- **CREATED**: Record created, no cycles yet
- **TRACKING**: Active cycles, real-time tracking
- **CALCULATED**: Session ended, status calculated
- **FINALIZED**: Synced to report, locked

### **2. Join-Leave Cycle States**

```
OPEN → CLOSED → VALIDATED
```

- **OPEN**: `joined_at` set, no `left_at`
- **CLOSED**: Both `joined_at` and `left_at` set
- **VALIDATED**: Duration calculated, capped at session boundaries

---

## 📊 **CALCULATION RULES**

### **1. Duration Calculation**

```php
RULE: Duration = SUM(all_cycles_within_session_window)

For each cycle:
  effective_join = MAX(actual_join, session_start)
  effective_leave = MIN(actual_leave, session_end)

  if (effective_join < effective_leave) {
    duration += effective_leave - effective_join
  }
```

### **2. Status Determination**

```
attendance_percentage = (total_duration / session_duration) * 100

Status Rules:
- present: >= 80% attendance
- late: >= 50% AND joined > 10 min late
- partial: >= 30% AND < 80%
- absent: < 30% OR no cycles
```

### **3. Real-Time Calculation**

```
if (NOW < session_start) return 0
if (NOW > session_end) return final_duration
if (has_open_cycle) {
  current = NOW - effective_join
  return completed_duration + current
}
return completed_duration
```

---

## 🚫 **WHAT NOT TO DO**

### **Frontend Restrictions:**
1. ❌ **NEVER** call `/api/meetings/attendance/join`
2. ❌ **NEVER** call `/api/meetings/attendance/leave`
3. ❌ **NEVER** send attendance data in requests
4. ❌ **NEVER** calculate duration in JavaScript
5. ❌ **NEVER** determine status in frontend

### **Backend Restrictions:**
1. ❌ **NEVER** accept attendance data from frontend
2. ❌ **NEVER** calculate outside session window
3. ❌ **NEVER** modify finalized attendance
4. ❌ **NEVER** trust participant count over webhooks
5. ❌ **NEVER** allow duplicate open cycles

---

## ✅ **WHAT TO DO**

### **Frontend Responsibilities:**
1. ✅ **DISPLAY** attendance data from API
2. ✅ **POLL** `/api/sessions/{id}/attendance-status` for updates
3. ✅ **LISTEN** to WebSocket for real-time updates
4. ✅ **SEND** heartbeat pings when in meeting
5. ✅ **SHOW** loading states during updates

### **Backend Responsibilities:**
1. ✅ **RECEIVE** LiveKit webhooks only
2. ✅ **VALIDATE** webhook signatures
3. ✅ **CREATE** attendance records on first join
4. ✅ **UPDATE** cycles on participant events
5. ✅ **CALCULATE** duration within boundaries
6. ✅ **BROADCAST** updates via WebSocket
7. ✅ **AUTO-CLOSE** stale cycles
8. ✅ **SYNC** to reports when finalized

---

## 🔐 **SECURITY RULES**

### **1. Webhook Security**
```
- Validate HMAC signature on every webhook
- Reject unsigned webhooks
- Log rejected attempts
- Rate limit webhook endpoints
```

### **2. Data Integrity**
```
- Use database transactions for cycle updates
- Lock records during calculation
- Prevent concurrent modifications
- Audit all changes
```

### **3. User Validation**
```
- Verify user belongs to session
- Check subscription status
- Validate participant identity matches user
- Prevent impersonation
```

---

## 🚀 **SCALABILITY RULES**

### **1. Database Optimization**
```
- Index: session_id, user_id, created_at
- Index: is_currently_in_meeting, last_heartbeat_at
- Partition by academy_id for multi-tenancy
- Archive old attendance records
```

### **2. Caching Strategy**
```
- Cache calculated attendance for 5 minutes
- Cache session details for duration
- Use Redis for real-time tracking
- Invalidate on status change
```

### **3. Queue Management**
```
- Process webhooks asynchronously
- Batch report syncs every 5 minutes
- Retry failed operations with backoff
- Dead letter queue for permanent failures
```

---

## 📈 **MONITORING RULES**

### **1. Health Checks**
```
Every minute:
- Check for stale heartbeats
- Close abandoned cycles
- Sync pending reports
- Clean orphaned records
```

### **2. Metrics to Track**
```
- Webhook success rate
- Average calculation time
- Concurrent participants
- Database query time
- WebSocket connection count
```

### **3. Alerts**
```
- Webhook failures > 5% → Alert
- Calculation time > 1s → Alert
- Stale cycles > 100 → Alert
- Database locks > 10s → Alert
```

---

## 🧪 **TESTING REQUIREMENTS**

### **1. Unit Tests**
- Duration calculation with all edge cases
- Status determination logic
- Boundary condition handling
- Cycle validation

### **2. Integration Tests**
- Webhook → Attendance flow
- Real-time calculation
- Report synchronization
- Multi-user scenarios

### **3. Load Tests**
- 1000 concurrent users
- 100 webhooks/second
- 10,000 attendance records
- Measure response times

---

## 📋 **IMPLEMENTATION CHECKLIST**

### **Phase 1: Core Fix**
- [ ] Ensure webhooks are ONLY source of attendance
- [ ] Disable ALL frontend attendance recording
- [ ] Implement session boundary checks
- [ ] Add proper cycle validation

### **Phase 2: Optimization**
- [ ] Add Redis caching layer
- [ ] Implement batch processing
- [ ] Optimize database queries
- [ ] Add connection pooling

### **Phase 3: Monitoring**
- [ ] Add comprehensive logging
- [ ] Implement metrics collection
- [ ] Create monitoring dashboard
- [ ] Set up alerting

### **Phase 4: Scaling**
- [ ] Database partitioning
- [ ] Read replicas
- [ ] Queue workers scaling
- [ ] WebSocket clustering

---

## 🎯 **SUCCESS CRITERIA**

1. **Accuracy**: 100% accurate attendance within 1 minute precision
2. **Reliability**: 99.9% uptime for attendance tracking
3. **Performance**: < 100ms response time for status queries
4. **Scalability**: Support 10,000+ concurrent users
5. **Security**: Zero unauthorized attendance modifications
6. **Auditability**: Complete audit trail for all changes

---

## 📝 **SUMMARY**

The attendance system must be:
- **Server-authoritative** (webhooks only)
- **Meeting-based** (not page-based)
- **Time-bounded** (session window only)
- **Real-time** (WebSocket updates)
- **Scalable** (10,000+ users)
- **Reliable** (99.9% uptime)
- **Secure** (validated webhooks)
- **Auditable** (full history)

**Golden Rule**: If it's not from a LiveKit webhook during session time, it doesn't count as attendance.