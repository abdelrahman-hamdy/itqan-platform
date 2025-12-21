# Critical User Journeys - Itqan Platform

## Overview
This document maps complete end-to-end user flows for the critical business processes.

---

## 1. Authentication Flows

### 1.1 Student Registration (Web)
```
1. Visit academy homepage (with subdomain)
2. Click "Register" button
3. Fill registration form (name, email, phone, password)
4. Submit form
5. Receive email verification (optional based on academy settings)
6. Complete profile with additional information
7. Redirect to student dashboard
```

### 1.2 Parent Registration with Child
```
1. Visit academy homepage
2. Select "Register as Parent" option
3. Fill parent information
4. Add child information (can add multiple children)
5. Link children to parent account
6. Submit registration
7. Children can now login with separate accounts linked to parent
```

### 1.3 Teacher Registration (Quran/Academic)
```
1. Admin creates teacher invitation OR teacher applies
2. Teacher fills profile form with qualifications
3. Teacher profile enters "pending" approval status
4. Admin reviews and approves teacher
5. Teacher can now access teacher dashboard
```

### 1.4 Login Flow
```
1. Visit login page
2. Enter email/phone and password
3. System validates credentials
4. System checks user_type and active_status
5. Redirect to appropriate dashboard based on role
```

### 1.5 Password Reset
```
1. Click "Forgot Password"
2. Enter email
3. Receive reset link email
4. Click link and enter new password
5. Redirect to login
```

---

## 2. Quran Session Workflows

### 2.1 Individual Circle Enrollment
```
1. Student/Parent browses available teachers
2. Views teacher profile and packages
3. Selects a package (sessions per month)
4. Proceeds to payment
5. Payment processed successfully
6. Subscription created with session quota
7. Individual circle created linking student to teacher
8. Sessions can now be scheduled
```

### 2.2 Session Scheduling (Teacher)
```
1. Teacher views their individual circles
2. Selects a circle to schedule session
3. Chooses date and time slot
4. System checks for conflicts
5. Session created with status "scheduled"
6. Student/Parent notified of session
```

### 2.3 Session Lifecycle
```
1. Session starts with status "scheduled"
2. 10 minutes before: Status changes to "ready", meeting link generated
3. Teacher/Student join meeting via LiveKit
4. Session transitions to "ongoing"
5. Session ends (manually or automatically)
6. Status changes to "completed"
7. Subscription session count decremented
8. Attendance records finalized
```

### 2.4 Group Circle Enrollment
```
1. Admin creates group circle with schedule
2. Students enroll in circle
3. Sessions auto-generated based on schedule
4. Students attend scheduled sessions
5. Attendance tracked per student
```

---

## 3. Academic Session Workflows

### 3.1 Academic Individual Lesson Subscription
```
1. Student/Parent views academic subjects
2. Selects a subject and teacher
3. Chooses package (hours/sessions)
4. Processes payment
5. Subscription activated
6. Individual lesson relationship created
```

### 3.2 Interactive Course Enrollment
```
1. Browse available interactive courses
2. View course details (syllabus, schedule, price)
3. Enroll in course
4. Pay course fee
5. Enrollment confirmed
6. Access to scheduled sessions granted
```

### 3.3 Academic Session Report
```
1. Teacher completes session
2. Teacher fills session report
3. Report includes: topics covered, homework assigned, student performance
4. Parent receives notification about session completion
5. Parent can view report in their dashboard
```

---

## 4. Payment Workflows

### 4.1 Subscription Payment (Paymob)
```
1. User selects package/subscription
2. System creates payment intent
3. User redirected to Paymob payment page
4. User completes payment
5. Paymob webhook received
6. Payment verified and logged
7. Subscription activated
8. User notified of successful payment
```

### 4.2 Payment Webhook Processing
```
1. Paymob sends webhook to callback URL
2. System validates webhook signature
3. System updates payment status
4. If successful: activate subscription
5. If failed: notify user, keep subscription pending
6. Log all webhook events for audit
```

### 4.3 Subscription Renewal
```
1. System checks for expiring subscriptions
2. Sends renewal reminder notifications
3. User initiates renewal payment
4. Payment processed
5. Subscription extended
```

---

## 5. Parent Monitoring Workflows

### 5.1 Parent Dashboard Access
```
1. Parent logs in
2. Views list of linked children
3. Selects a child to monitor
4. Views child's:
   - Upcoming sessions
   - Attendance records
   - Session reports
   - Quiz results
   - Homework status
```

### 5.2 Parent Viewing Session Report
```
1. Parent receives notification about completed session
2. Navigates to reports section
3. Views detailed session report
4. Can see teacher feedback
5. Can view homework assigned
```

### 5.3 Parent Managing Subscriptions
```
1. Parent views active subscriptions per child
2. Can see remaining sessions
3. Can initiate subscription renewal
4. Can view payment history
```

---

## 6. Teacher Workflows

### 6.1 Teacher Dashboard
```
1. Teacher logs in
2. Views today's scheduled sessions
3. Views calendar for upcoming sessions
4. Can access:
   - Student list
   - Session management
   - Attendance records
   - Earnings reports
```

### 6.2 Conducting a Session
```
1. Session reaches "ready" status
2. Teacher clicks "Start Session"
3. LiveKit meeting room created
4. Teacher joins meeting
5. Students join meeting
6. Attendance auto-tracked
7. Teacher can control participants
8. Session ends
9. Teacher completes session report
```

### 6.3 Grading Homework
```
1. Teacher views pending homework submissions
2. Opens submission details
3. Reviews attached files
4. Provides grade and feedback
5. Submits grade
6. Student/Parent notified
```

---

## 7. Admin Workflows

### 7.1 Academy Management
```
1. Admin logs into Academy panel
2. Can manage:
   - Teachers (approve/suspend)
   - Students (view/edit)
   - Subscriptions (view/edit)
   - Sessions (overview)
   - Reports (analytics)
```

### 7.2 Teacher Approval
```
1. New teacher registration received
2. Admin views pending teachers
3. Reviews qualifications and documents
4. Approves or rejects teacher
5. Teacher notified of decision
```

### 7.3 Session Monitoring
```
1. Admin views all academy sessions
2. Can filter by status, date, teacher
3. Can view ongoing sessions
4. Can intervene if needed
```

---

## 8. Meeting System Workflows

### 8.1 Meeting Room Creation
```
1. Session reaches preparation window (10 min before)
2. AutoMeetingCreationService triggered
3. LiveKit room created
4. Meeting link stored in session
5. Session status updated to "ready"
```

### 8.2 Joining a Meeting
```
1. User clicks "Join Meeting" button
2. System verifies user permission (SessionPolicy)
3. System generates LiveKit token for user
4. User redirected to meeting page
5. LiveKit SDK connects user to room
6. Attendance event recorded (join time)
```

### 8.3 Meeting End
```
1. Teacher clicks "End Session" OR
   Session auto-ends after duration + buffer
2. All participants disconnected
3. Room closed
4. Attendance calculated (duration per participant)
5. Session status updated to "completed"
```

---

## 9. Notification Workflows

### 9.1 Session Reminder
```
1. Scheduled job runs
2. Checks for sessions starting within reminder window
3. Sends push/email/SMS notifications
4. To: Teacher, Student, Parent
```

### 9.2 Session Completion Notification
```
1. Session marked as completed
2. Notification sent to parent
3. Includes: session summary, next session info
```

---

## 10. Error & Edge Case Scenarios

### 10.1 Payment Failed
- User notified of failure
- Subscription remains inactive
- Retry option provided

### 10.2 Student No-Show
- Session can be marked as "absent"
- Session still counts towards subscription
- Parent notified

### 10.3 Teacher Cancellation
- Session status set to "cancelled"
- Session does NOT count towards subscription
- Student/Parent notified
- Reschedule option provided

### 10.4 Technical Issues During Meeting
- Reconnection attempts
- Fallback meeting link
- Session can be rescheduled

---

## Permission Boundaries

### Super Admin
- Access to ALL panels
- Can manage all academies
- Full system control

### Academy Admin
- Access to Academy panel only
- Can manage their academy only
- Cannot access other academies

### Quran Teacher
- Access to Teacher panel
- Can only see their own circles/sessions
- Can manage their students' progress

### Academic Teacher
- Access to Academic Teacher panel
- Can only see their own lessons/courses
- Can manage homework and grades

### Student
- Cannot access admin panels
- Can only view their own sessions
- Can submit homework and take quizzes

### Parent
- Cannot access admin panels
- Can view linked children's data
- Can make payments for children
- Cannot modify session data

---

*Last Updated: 2025-12-21*
