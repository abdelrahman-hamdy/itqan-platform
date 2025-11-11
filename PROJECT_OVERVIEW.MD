### **High-Level Overview – “Quran & Academic Learning Platform”**

The product is a **multi-tenant SaaS** where every tenant represents an **independent academy**.  Each academy can run *Quran memorisation* programmes, *live academic tutoring* and *pre-recorded courses* under its own branding while the **global Super-Admin** supervises the entire installation.

---

## 1. Users & Roles

| Role                   | Scope             | Key Powers                                                                                                                                                                              |
| ---------------------- | ----------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Super-Admin**        | Global            | Create academies, seed default data, see & message anyone, full financial dashboard.                                                                                                    |
| **Academy Admin**      | One academy       | Configure branding, prices, currencies, teachers, supervisors, packages, subjects.                                                                                                      |
| **Supervisor**         | Assigned entities | Acts as support/TA. Reads every chat in assigned circles/private lessons/courses, may chat (read-only in teacher↔student threads) with assigned users. Generates daily quality reports. |
| **Teacher – Quran**    | One academy       | Certified status & Quran-specific profile. Creates individual schedules, gives homework, reports, grades quizzes.                                                                       |
| **Teacher – Academic** | One academy       | Chooses subjects/grades; otherwise same capabilities as Quran teacher.                                                                                                                  |
| **Student**            | One academy       | Purchases subscriptions/courses, attends Meet sessions, submits homework, takes quizzes, receives reports & certificates.                                                               |
| **Parent**             | One academy       | Linked to exactly one student; can view progress, pay invoices, and chat with the student’s teacher/supervisor.                                                                         |

---

## 2. Three Product Pillars

### 2.1 Quran Memorisation

| Mode                    | Flow                                                                                                                                                                                                                                                                                                                  |
| ----------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Individual (1-to-1)** | Student browses teacher cards → books a free trial → Admin/Supervisor approves → Teacher schedules Meet link → after trial student buys a **monthly package** (X sessions). <br>• *Extra sessions*: student may buy add-on bundles (flat price) before package end; unused add-ons expire with the main subscription. |
| **Group Circles**       | Admin creates circle (topic, capacity, fixed weekly schedule, monthly price). Students join → automatic Meet link 15 min before each session. Attendance, homework & collective chat are tied to the circle.                                                                                                          |

### 2.2 Live Academic Tutoring

| Type                    | Flow                                                                                                                                                  |
| ----------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Private lessons**     | Mirrors 1-to-1 Quran but based on academic subjects & hourly pricing.                                                                                 |
| **Interactive Courses** | Admin/Teacher defines course (N lessons over arbitrary date range, one-time payment). <br>Students who join late gain access to past Meet recordings. |

### 2.3 Recorded Courses

* Hierarchical catalog (Admin-defined categories).
* One-off purchase → perpetual access to lesson videos, PDFs, quizzes.
* Secure URLs via signed tokens + tenant-scoped S3 prefix.

---

## 3. Cross-Cutting Functional Blocks

### 3.1 Messaging (Chatify Fork)

* **1-to-1 conversations** created on-demand, respecting role matrix.
* **Group chats** auto-generated per circle & interactive course.
* **Supervisor mirror channels** broadcast real-time copies of teacher-student traffic (read-only).
* Attachments stored in `MediaLibrary` under `tenants/{tenantId}/chat/…`.

### 3.2 Quizzes

* **Multiple-choice only**, unlimited questions.
* Teacher sets points per question, pass threshold %, max attempts.
* Each attempt stores chosen option, correctness, total score, pass/fail, timestamp.
* Randomised question & choice order per attempt.
* Grading runs in queue; results push to student + parent.

### 3.3 Homework

* Created per session; either text instructions or file attachment.
* Student uploads answer (text/file) → Teacher marks “submitted / graded” and can assign score.

### 3.4 Payments & Billing

* Gateways: **Paymob** (Egypt/MENA) & **Tap** (GCC). Currency per academy.
* Invoice => many items (subscription, add-on, recorded-course).
* No auto-renew yet; students renew manually when lessons remaining == 0 or subscription end approaches.

### 3.5 Meet Integration

* Google Calendar API generates a fresh Meet link 15 min before each scheduled lesson.
* Cron job inserts `meet_link` into session record and triggers reminder notification.
* Recordings kept only for **Interactive Courses** & **Private Academic Lessons** (if teacher toggles “record”).
* Stored to tenant S3 path; URL surfaced in session history.

### 3.6 Notifications

* **FCM push** (mobile + web) for: session reminder, homework assigned, quiz graded, payment receipt.
* **E-mail** for account confirmation, invoices/receipts, teacher activation, password reset.

### 3.7 Audit & Activity Log

* Spatie Activity-log for every create/update/delete on sensitive models.
* Viewable by Admin & Super-Admin through Filament “Audit” panel.

---

## 4. Tenant Life-Cycle

1. **Super-Admin** clicks “New Academy” → wizard collects name, branding, currency.
2. System seeds: default roles, settings, empty packages table, timezone = “Asia/Riyadh”.
3. Admin invites teachers & supervisors.
4. Public site instantly available via `<subdomain>.your-saas.com`—root domain serves the default **Itqan Academy**.

---

## 5. Main User Journeys (Simplified)

| # | Actor           | Journey                                                                                                                                                |
| - | --------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 1 | **Student**     | Discover teacher → Book trial → Approve email → Attend → Buy package → Join recurring sessions → Submit homework/quizzes → Receive certificate.        |
| 2 | **Teacher**     | Sign-up form → Profile pending ⚠️ → Admin sets prices → Status “Active” → Define availability → Receive bookings/notifications → Fill session reports. |
| 3 | **Parent**      | Accept invite link → View child dashboard → Chat with teacher → Pay next invoice.                                                                      |
| 4 | **Supervisor**  | Assigned to Circle X → Joins observer channel → Flags behaviour issues → Sends support messages.                                                       |
| 5 | **Admin**       | Add subject “Physics Grade 8” → Create Interactive Course → Set price & schedule → Promote on landing page.                                            |
| 6 | **Super-Admin** | Reviews tenant KPIs → Creates Black-Friday coupon across all academies → Bulk e-mail campaign.                                                         |

---

## 6. Tech Stack & Ops Summary

* **Backend**: Laravel 11, PHP 8.3, MySQL 8, Redis 7.
* **Frontend**: Blade + Livewire, TailwindCSS 3, Filament 4 (RTL & dark-mode ready).
* **WebSockets**: Soketi + Pusher protocol for Chatify & Livewire events.
* **CI/CD**: GitHub Actions → SSH/Envoy deploy.
* **Storage**: DigitalOcean Spaces (per-tenant prefix).
* **Monitoring**: Laravel Horizon, Spatie Health, Sentry SDK.

---

## 7. Future-Proofing (Roadmap Highlights)

| Milestone                | Add-on                                                                               |
| ------------------------ | ------------------------------------------------------------------------------------ |
| **MVP-Complete**         | all features above + Itqan Academy live.                                             |
| **Auto-Renew Billing**   | subscription cron + saved cards.                                                     |
| **Embedded LiveKit**     | replace Meet for lower latency + self-recording.                                     |
| **Analytics Dashboards** | Filament widgets + Laravel Pulse for BI (attendance, retention).                     |
| **Mobile Apps**          | Flutter wrapper using same FCM project & REST endpoints.                             |
| **Marketplace**          | allow teachers to publish paid recorded courses globally across tenants (rev-share). |

---

### **Key Take-away**

*The platform unifies Quran memorisation, academic tutoring, messaging, quizzes and billing **inside one Laravel SaaS**, yet keeps each academy fully branded and isolated.  Livewire + Filament accelerates admin screens while Chatify (forked) delivers real-time communication under strict role-based rules.  Add-on sessions, pass-threshold quizzes and supervisor oversight address the nuanced workflows you outlined, providing a solid, extensible base for future growth.*
