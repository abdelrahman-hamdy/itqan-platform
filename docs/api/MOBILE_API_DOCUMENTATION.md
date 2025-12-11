# Itqan Platform Mobile API Documentation

**Version:** 1.0
**Base URL:** `https://{subdomain}.itqan-platform.com/api/v1`
**Last Updated:** December 2024

---

## Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Multi-Tenancy (Academy Resolution)](#multi-tenancy)
4. [Response Format](#response-format)
5. [Error Handling](#error-handling)
6. [Rate Limiting](#rate-limiting)
7. [API Endpoints](#api-endpoints)
   - [Public Endpoints](#public-endpoints)
   - [Authentication Endpoints](#authentication-endpoints)
   - [Student Endpoints](#student-endpoints)
   - [Parent Endpoints](#parent-endpoints)
   - [Teacher Endpoints](#teacher-endpoints)
   - [Common Endpoints](#common-endpoints)

---

## Overview

The Itqan Platform Mobile API provides RESTful endpoints for mobile applications supporting:
- **Students**: Access sessions, subscriptions, homework, quizzes, certificates
- **Parents**: Monitor children's progress, view reports, manage payments
- **Teachers**: Manage sessions, students, homework, and earnings

### Key Features
- Multi-tenant architecture (academy-scoped authentication)
- Real-time video meetings via LiveKit integration
- Chat system via WireChat integration
- Support for Quran memorization and Academic tutoring

---

## Authentication

### Overview
The API uses **Laravel Sanctum** for token-based authentication. Tokens are academy-scoped, meaning the same email can exist in different academies with separate accounts.

### Token Lifecycle
- **Expiration:** 30 days from creation
- **Format:** Bearer token
- **Storage:** Store securely in device keychain/secure storage

### Required Headers (All Authenticated Requests)
```
Authorization: Bearer {token}
X-Academy-Subdomain: {academy_subdomain}
Accept: application/json
Content-Type: application/json
```

### Token Abilities
Each token is issued with abilities based on user type:
| User Type | Abilities |
|-----------|-----------|
| Student | `read`, `write`, `student:*` |
| Parent | `read`, `write`, `parent:*` |
| Quran Teacher | `read`, `write`, `teacher:*`, `quran:*` |
| Academic Teacher | `read`, `write`, `teacher:*`, `academic:*` |

---

## Multi-Tenancy

### Academy Resolution
Every request must identify the target academy using ONE of these methods:

1. **HTTP Header (Recommended)**
   ```
   X-Academy-Subdomain: itqan-academy
   ```

2. **Query Parameter**
   ```
   GET /api/v1/academy/branding?academy=itqan-academy
   ```

### Important Notes
- Users can have accounts in multiple academies with the same email
- Each academy has separate branding, settings, and data
- Switching academies requires re-authentication

---

## Response Format

### Success Response
```json
{
  "success": true,
  "message": "Success message",
  "data": {
    // Response payload
  },
  "meta": {
    "timestamp": "2024-12-08T10:30:00.000000Z",
    "request_id": "550e8400-e29b-41d4-a716-446655440000",
    "api_version": "v1"
  }
}
```

### Paginated Response
```json
{
  "success": true,
  "message": "Success",
  "data": [...],
  "meta": {
    "timestamp": "2024-12-08T10:30:00.000000Z",
    "request_id": "...",
    "api_version": "v1"
  },
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 150,
    "total_pages": 10,
    "has_more": true,
    "from": 1,
    "to": 15
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error description",
  "error_code": "ERROR_CODE",
  "errors": {
    "field_name": ["Validation error message"]
  },
  "meta": {
    "timestamp": "2024-12-08T10:30:00.000000Z",
    "request_id": "...",
    "api_version": "v1"
  }
}
```

---

## Error Handling

### Standard Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `VALIDATION_ERROR` | 422 | Request validation failed |
| `INVALID_CREDENTIALS` | 401 | Wrong email or password |
| `UNAUTHENTICATED` | 401 | Token missing or invalid |
| `TOKEN_EXPIRED` | 401 | Token has expired |
| `FORBIDDEN` | 403 | No permission for this action |
| `ACCOUNT_INACTIVE` | 403 | User account is inactive |
| `UNSUPPORTED_USER_TYPE` | 403 | User type not supported on mobile |
| `ACADEMY_NOT_FOUND` | 404 | Academy subdomain invalid |
| `ACADEMY_INACTIVE` | 403 | Academy is disabled |
| `ACADEMY_MAINTENANCE` | 503 | Academy in maintenance mode |
| `ACADEMY_MISMATCH` | 403 | User doesn't belong to academy |
| `REGISTRATION_DISABLED` | 403 | Academy doesn't allow registration |
| `RESOURCE_NOT_FOUND` | 404 | Resource not found |
| `EMAIL_EXISTS` | 409 | Email already registered |
| `STUDENT_CODE_NOT_FOUND` | 404 | Student code invalid |
| `RATE_LIMIT_EXCEEDED` | 429 | Too many requests |
| `INTERNAL_ERROR` | 500 | Server error |

---

## Rate Limiting

| Endpoint Type | Limit |
|--------------|-------|
| Default | 60 requests/minute |
| Login | 10 requests/minute |
| Registration | 10 requests/minute |
| Password Reset | 5 requests/minute |

When rate limited, response includes:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 0
Retry-After: 45
```

---

## API Endpoints

---

## Public Endpoints

### Get Academy Branding
Retrieve academy branding for pre-auth screens (login, register).

```
GET /api/v1/academy/branding
```

**Headers:**
```
X-Academy-Subdomain: {subdomain}
```

**Response:**
```json
{
  "success": true,
  "message": "Academy branding retrieved successfully",
  "data": {
    "id": 1,
    "subdomain": "itqan-academy",
    "name": "أكاديمية إتقان",
    "name_en": "Itqan Academy",
    "description": "منصة تعليمية متكاملة",
    "logo_url": "https://example.com/storage/logos/logo.png",
    "favicon_url": "https://example.com/storage/favicons/favicon.png",
    "brand_color": {
      "name": "blue",
      "label": "أزرق",
      "primary": "#3b82f6",
      "shades": {
        "50": "#eff6ff",
        "100": "#dbeafe",
        "200": "#bfdbfe",
        "300": "#93c5fd",
        "400": "#60a5fa",
        "500": "#3b82f6",
        "600": "#2563eb",
        "700": "#1d4ed8",
        "800": "#1e40af",
        "900": "#1e3a8a",
        "950": "#172554"
      }
    },
    "gradient_palette": {
      "name": "ocean",
      "label": "محيطي",
      "from_color": "#3b82f6",
      "to_color": "#06b6d4",
      "gradient_class": "from-blue-500 to-cyan-500",
      "preview_hex": "#3b82f6"
    },
    "is_active": true,
    "allow_registration": true,
    "maintenance_mode": false,
    "country": {
      "code": "SA",
      "name": "Saudi Arabia"
    },
    "timezone": {
      "code": "Asia/Riyadh",
      "name": "Riyadh (GMT+3)"
    },
    "currency": {
      "code": "SAR",
      "symbol": "ر.س",
      "name": "Saudi Riyal"
    },
    "contact": {
      "email": "info@itqan-academy.com",
      "phone": "+966501234567",
      "website": "https://itqan-academy.com"
    },
    "urls": {
      "full_domain": "itqan-academy.itqan-platform.com",
      "full_url": "https://itqan-academy.itqan-platform.com"
    }
  },
  "meta": {...}
}
```

### Get Server Time
Sync client time with server.

```
GET /api/v1/server-time
```

**Response:**
```json
{
  "success": true,
  "data": {
    "timestamp": "2024-12-08T10:30:00.000000Z",
    "unix_timestamp": 1733653800,
    "timezone": "UTC"
  },
  "meta": {...}
}
```

---

## Authentication Endpoints

### Login
Authenticate user and receive access token.

```
POST /api/v1/login
```

**Request Body:**
```json
{
  "email": "student@example.com",
  "password": "password123",
  "device_name": "iPhone 15 Pro"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "email": "student@example.com",
      "first_name": "أحمد",
      "last_name": "محمد",
      "full_name": "أحمد محمد",
      "phone": "+966501234567",
      "avatar_url": "https://example.com/storage/avatars/user.jpg",
      "user_type": "student",
      "user_type_label": "طالب",
      "is_active": true,
      "email_verified": true,
      "phone_verified": false,
      "profile_completed": true,
      "last_login_at": "2024-12-08T10:30:00.000000Z",
      "created_at": "2024-01-15T08:00:00.000000Z",
      "academy": {
        "id": 1,
        "name": "أكاديمية إتقان",
        "subdomain": "itqan-academy"
      },
      "profile": {
        "id": 1,
        "student_code": "STU-2024-001",
        "grade_level": {
          "id": 5,
          "name": "الصف الخامس"
        },
        "birth_date": "2012-05-15",
        "age": 12,
        "gender": "male",
        "nationality": "Saudi",
        "enrollment_date": "2024-01-15"
      }
    },
    "academy": {...},
    "token": "1|abc123xyz...",
    "token_type": "Bearer",
    "expires_at": "2025-01-07T10:30:00.000000Z",
    "abilities": ["read", "write", "student:*"]
  },
  "meta": {...}
}
```

### Logout
Revoke current access token.

```
POST /api/v1/logout
```

**Headers:** Requires authentication

**Response:**
```json
{
  "success": true,
  "message": "Logged out successfully",
  "data": null,
  "meta": {...}
}
```

### Get Current User
Get authenticated user info.

```
GET /api/v1/me
```

**Response:** Same structure as login response user object.

---

### Student Registration

```
POST /api/v1/register/student
```

**Request Body:**
```json
{
  "first_name": "أحمد",
  "last_name": "محمد",
  "email": "student@example.com",
  "phone": "+966501234567",
  "password": "password123",
  "password_confirmation": "password123",
  "birth_date": "2012-05-15",
  "gender": "male",
  "nationality": "Saudi",
  "grade_level_id": 5,
  "parent_phone": "+966507654321"
}
```

**Response:** Same structure as login response.

---

### Parent Registration

#### Step 1: Verify Student Code

```
POST /api/v1/register/parent/verify-student
```

**Request Body:**
```json
{
  "student_code": "STU-2024-001"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Student found",
  "data": {
    "student": {
      "id": 1,
      "name": "أحمد محمد",
      "student_code": "STU-2024-001",
      "grade_level": "الصف الخامس",
      "has_parent": false
    }
  },
  "meta": {...}
}
```

#### Step 2: Complete Registration

```
POST /api/v1/register/parent
```

**Request Body:**
```json
{
  "first_name": "محمد",
  "last_name": "أحمد",
  "email": "parent@example.com",
  "phone": "+966507654321",
  "password": "password123",
  "password_confirmation": "password123",
  "student_code": "STU-2024-001",
  "relationship_type": "father",
  "preferred_contact_method": "whatsapp",
  "occupation": "Engineer"
}
```

**Relationship Types:** `father`, `mother`, `guardian`, `other`

**Response:** Same structure as login, plus:
```json
{
  "linked_student": {
    "id": 1,
    "name": "أحمد محمد",
    "student_code": "STU-2024-001"
  }
}
```

---

### Teacher Registration (Two-Step)

#### Step 1: Select Teacher Type

```
POST /api/v1/register/teacher/step1
```

**Request Body:**
```json
{
  "teacher_type": "quran_teacher"
}
```

**Teacher Types:** `quran_teacher`, `academic_teacher`

**Response:**
```json
{
  "success": true,
  "message": "Step 1 completed. Please proceed to step 2.",
  "data": {
    "teacher_type": "quran_teacher",
    "registration_token": "encrypted_token_string",
    "next_step": "step2"
  },
  "meta": {...}
}
```

#### Step 2: Complete Registration

```
POST /api/v1/register/teacher/step2
```

**Request Body (Quran Teacher):**
```json
{
  "registration_token": "encrypted_token_string",
  "first_name": "عبدالله",
  "last_name": "الشيخ",
  "email": "teacher@example.com",
  "phone": "+966509876543",
  "password": "password123",
  "password_confirmation": "password123",
  "qualification_degree": "master",
  "university": "جامعة الإمام",
  "years_experience": 10,
  "bio": "حافظ للقرآن الكريم مع إجازة في القراءات العشر"
}
```

**Request Body (Academic Teacher):** Same as above plus:
```json
{
  "subject_ids": [1, 3, 5],
  "grade_level_ids": [4, 5, 6]
}
```

**Qualification Degrees:** `bachelor`, `master`, `phd`, `other`

**Response:**
```json
{
  "success": true,
  "message": "Registration submitted successfully",
  "data": {
    "user": {...},
    "academy": {...},
    "requires_approval": true,
    "message": "Your registration is pending approval. You will be notified once approved."
  },
  "meta": {...}
}
```

> **Note:** Teacher accounts require admin approval before activation. No token is returned until approved.

---

### Password Reset

#### Request Reset Link

```
POST /api/v1/forgot-password
```

**Request Body:**
```json
{
  "email": "user@example.com"
}
```

#### Verify Reset Token

```
POST /api/v1/verify-reset-token
```

**Request Body:**
```json
{
  "email": "user@example.com",
  "token": "123456"
}
```

#### Reset Password

```
POST /api/v1/reset-password
```

**Request Body:**
```json
{
  "email": "user@example.com",
  "token": "123456",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

---

### Token Management

#### Refresh Token

```
POST /api/v1/token/refresh
```

**Response:** New token with extended expiration.

#### Validate Token

```
GET /api/v1/token/validate
```

**Response:**
```json
{
  "success": true,
  "data": {
    "valid": true,
    "expires_at": "2025-01-07T10:30:00.000000Z",
    "abilities": ["read", "write", "student:*"]
  }
}
```

#### Revoke Current Token

```
DELETE /api/v1/token/revoke
```

#### Revoke All Tokens

```
DELETE /api/v1/token/revoke-all
```

---

## Student Endpoints

All endpoints prefixed with `/api/v1/student`

### Dashboard

```
GET /api/v1/student/dashboard
```

**Response:**
```json
{
  "success": true,
  "data": {
    "student": {
      "id": 1,
      "name": "أحمد محمد",
      "student_code": "STU-2024-001",
      "grade_level": "الصف الخامس",
      "avatar_url": "..."
    },
    "stats": {
      "active_subscriptions": 3,
      "today_sessions": 2,
      "upcoming_sessions": 5,
      "pending_homework": 3,
      "certificates_earned": 2
    },
    "today_sessions": [...],
    "upcoming_sessions": [...],
    "pending_homework": [...],
    "recent_notifications": [...]
  }
}
```

---

### Sessions

#### List All Sessions

```
GET /api/v1/student/sessions
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `type` | string | Filter by type: `quran`, `academic`, `interactive` |
| `status` | string | Filter by status: `scheduled`, `live`, `completed`, `cancelled` |
| `date_from` | date | Filter from date (Y-m-d) |
| `date_to` | date | Filter to date (Y-m-d) |
| `page` | int | Page number |
| `per_page` | int | Items per page (default: 15) |

#### Get Today's Sessions

```
GET /api/v1/student/sessions/today
```

#### Get Upcoming Sessions

```
GET /api/v1/student/sessions/upcoming
```

#### Get Session Details

```
GET /api/v1/student/sessions/{type}/{id}
```

**Path Parameters:**
- `type`: `quran`, `academic`, or `interactive`
- `id`: Session ID

**Response:**
```json
{
  "success": true,
  "data": {
    "session": {
      "id": 1,
      "type": "quran",
      "title": "جلسة حفظ - سورة البقرة",
      "status": "scheduled",
      "status_label": "مجدولة",
      "scheduled_at": "2024-12-08T14:00:00.000000Z",
      "duration_minutes": 45,
      "can_join": false,
      "join_available_at": "2024-12-08T13:50:00.000000Z",
      "teacher": {
        "id": 5,
        "name": "الشيخ عبدالله",
        "avatar_url": "...",
        "rating": 4.9
      },
      "meeting": {
        "has_meeting": true,
        "room_name": "session-quran-1",
        "can_join": false
      },
      "subscription": {
        "id": 10,
        "type": "quran",
        "name": "حلقة فردية"
      }
    }
  }
}
```

#### Submit Session Feedback

```
POST /api/v1/student/sessions/{type}/{id}/feedback
```

**Request Body:**
```json
{
  "rating": 5,
  "comment": "جلسة ممتازة ومفيدة جداً"
}
```

---

### Subscriptions

#### List All Subscriptions

```
GET /api/v1/student/subscriptions
```

**Query Parameters:**
- `type`: `quran`, `academic`, `course`
- `status`: `active`, `expired`, `cancelled`, `all`

#### Get Subscription Details

```
GET /api/v1/student/subscriptions/{type}/{id}
```

#### Get Subscription Sessions

```
GET /api/v1/student/subscriptions/{type}/{id}/sessions
```

#### Toggle Auto-Renewal

```
PATCH /api/v1/student/subscriptions/{type}/{id}/toggle-auto-renew
```

**Note:** Only available for `quran` and `academic` types.

#### Cancel Subscription

```
PATCH /api/v1/student/subscriptions/{type}/{id}/cancel
```

**Request Body:**
```json
{
  "reason": "Optional cancellation reason"
}
```

---

### Homework

#### List Homework

```
GET /api/v1/student/homework
```

**Query Parameters:**
- `type`: `academic`, `interactive`
- `status`: `pending`, `submitted`, `graded`

#### Get Homework Details

```
GET /api/v1/student/homework/{type}/{id}
```

#### Submit Homework

```
POST /api/v1/student/homework/{type}/{id}/submit
```

**Request Body (multipart/form-data):**
```
answer: "Text answer content"
files[]: (file upload)
```

#### Save Draft

```
POST /api/v1/student/homework/{type}/{id}/draft
```

---

### Quizzes

#### List Quizzes

```
GET /api/v1/student/quizzes
```

**Query Parameters:**
- `status`: `available`, `in_progress`, `completed`

#### Get Quiz Details

```
GET /api/v1/student/quizzes/{id}
```

#### Start Quiz

```
POST /api/v1/student/quizzes/{id}/start
```

**Response:** Returns quiz questions and attempt ID.

#### Submit Quiz

```
POST /api/v1/student/quizzes/{id}/submit
```

**Request Body:**
```json
{
  "attempt_id": 123,
  "answers": {
    "1": "a",
    "2": "c",
    "3": ["a", "c"]
  }
}
```

#### Get Quiz Result

```
GET /api/v1/student/quizzes/{id}/result
```

---

### Certificates

#### List Certificates

```
GET /api/v1/student/certificates
```

#### Get Certificate Details

```
GET /api/v1/student/certificates/{id}
```

#### Download Certificate

```
GET /api/v1/student/certificates/{id}/download
```

**Response:** Returns PDF file or download URL.

---

### Payments

#### List Payments

```
GET /api/v1/student/payments
```

**Query Parameters:**
- `status`: `pending`, `paid`, `failed`, `refunded`
- `date_from`: Start date
- `date_to`: End date

#### Get Payment Details

```
GET /api/v1/student/payments/{id}
```

#### Get Payment Receipt

```
GET /api/v1/student/payments/{id}/receipt
```

---

### Calendar

#### Get Calendar Events

```
GET /api/v1/student/calendar
```

**Query Parameters:**
- `start_date`: Start of range (Y-m-d)
- `end_date`: End of range (Y-m-d)

#### Get Monthly Calendar

```
GET /api/v1/student/calendar/month/{year}/{month}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "year": 2024,
    "month": 12,
    "events": [
      {
        "id": 1,
        "type": "quran",
        "title": "جلسة حفظ",
        "start": "2024-12-08T14:00:00.000000Z",
        "end": "2024-12-08T14:45:00.000000Z",
        "date": "2024-12-08",
        "color": "#22c55e",
        "status": "scheduled",
        "can_join": false
      }
    ]
  }
}
```

---

### Profile

#### Get Profile

```
GET /api/v1/student/profile
```

#### Update Profile

```
PUT /api/v1/student/profile
```

**Request Body:**
```json
{
  "first_name": "أحمد",
  "last_name": "محمد",
  "phone": "+966501234567",
  "birth_date": "2012-05-15",
  "nationality": "Saudi"
}
```

#### Update Avatar

```
POST /api/v1/student/profile/avatar
```

**Request Body (multipart/form-data):**
```
avatar: (image file, max 2MB, jpg/png)
```

---

### Browse Teachers

#### List Quran Teachers

```
GET /api/v1/student/teachers/quran
```

**Query Parameters:**
- `search`: Search by name
- `gender`: `male`, `female`
- `min_rating`: Minimum rating (1-5)
- `price_min`: Minimum session price
- `price_max`: Maximum session price

#### Get Quran Teacher Details

```
GET /api/v1/student/teachers/quran/{id}
```

#### List Academic Teachers

```
GET /api/v1/student/teachers/academic
```

**Additional Query Parameters:**
- `subject_id`: Filter by subject
- `grade_level_id`: Filter by grade level

#### Get Academic Teacher Details

```
GET /api/v1/student/teachers/academic/{id}
```

---

### Interactive Courses

#### List Courses

```
GET /api/v1/student/courses/interactive
```

**Query Parameters:**
- `status`: `upcoming`, `ongoing`, `completed`
- `enrolled`: `true` to show only enrolled courses

#### Get Course Details

```
GET /api/v1/student/courses/interactive/{id}
```

---

## Parent Endpoints

All endpoints prefixed with `/api/v1/parent`

### Dashboard

```
GET /api/v1/parent/dashboard
```

**Response:**
```json
{
  "success": true,
  "data": {
    "parent": {
      "id": 1,
      "name": "محمد أحمد",
      "avatar": "..."
    },
    "children": [
      {
        "id": 1,
        "user_id": 10,
        "name": "أحمد محمد",
        "student_code": "STU-2024-001",
        "avatar": "...",
        "grade_level": "الصف الخامس",
        "relationship": "father",
        "today_sessions_count": 2,
        "active_subscriptions_count": 3
      }
    ],
    "stats": {
      "total_children": 2,
      "total_today_sessions": 3,
      "total_active_subscriptions": 5,
      "upcoming_sessions": 8
    },
    "upcoming_sessions": [...]
  }
}
```

---

### Children Management

#### List Children

```
GET /api/v1/parent/children
```

#### Link New Child

```
POST /api/v1/parent/children/link
```

**Request Body:**
```json
{
  "student_code": "STU-2024-002",
  "relationship_type": "father"
}
```

#### Get Child Details

```
GET /api/v1/parent/children/{id}
```

#### Set Active Child

```
PUT /api/v1/parent/children/{id}/active
```

#### Unlink Child

```
DELETE /api/v1/parent/children/{id}/unlink
```

#### Get Child's Quizzes

```
GET /api/v1/parent/children/{childId}/quizzes
```

#### Get Child's Certificates

```
GET /api/v1/parent/children/{childId}/certificates
```

---

### Reports

#### Progress Report (All Children)

```
GET /api/v1/parent/reports/progress
```

#### Progress Report (Specific Child)

```
GET /api/v1/parent/reports/progress/{childId}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "reports": {
      "child": {
        "id": 1,
        "name": "أحمد محمد",
        "avatar": "...",
        "grade_level": "الصف الخامس"
      },
      "quran": {
        "active_subscriptions": 1,
        "total_subscriptions": 2,
        "completed_sessions": 25,
        "total_sessions": 30,
        "current_surah": "البقرة",
        "current_page": 45,
        "memorized_pages": 120
      },
      "academic": {
        "active_subscriptions": 2,
        "completed_sessions": 18,
        "total_sessions": 24,
        "subjects": [
          {"name": "الرياضيات", "status": "active"},
          {"name": "العلوم", "status": "active"}
        ]
      },
      "courses": {
        "active_enrollments": 1,
        "completed_enrollments": 0,
        "completed_sessions": 5,
        "total_sessions": 12,
        "average_progress": 41.6
      },
      "overall_stats": {
        "total_sessions_completed": 48,
        "attendance_rate": 95.5,
        "active_subscriptions": 4
      }
    }
  }
}
```

#### Attendance Report

```
GET /api/v1/parent/reports/attendance
GET /api/v1/parent/reports/attendance/{childId}
```

**Query Parameters:**
- `start_date`: Start of period (Y-m-d)
- `end_date`: End of period (Y-m-d)

#### Subscription Report

```
GET /api/v1/parent/reports/subscription/{type}/{id}
```

---

### Sessions (for Children)

#### List All Sessions

```
GET /api/v1/parent/sessions
```

**Query Parameters:**
- `child_id`: Filter by specific child
- `type`: `quran`, `academic`, `interactive`
- `status`: Session status

#### Today's Sessions

```
GET /api/v1/parent/sessions/today
```

#### Upcoming Sessions

```
GET /api/v1/parent/sessions/upcoming
```

#### Session Details

```
GET /api/v1/parent/sessions/{type}/{id}
```

---

### Payments

#### List Payments

```
GET /api/v1/parent/payments
```

**Query Parameters:**
- `child_id`: Filter by child
- `status`: Payment status

#### Get Payment Details

```
GET /api/v1/parent/payments/{id}
```

#### Initiate Payment

```
POST /api/v1/parent/payments/initiate
```

**Request Body:**
```json
{
  "subscription_type": "quran",
  "subscription_id": 10,
  "payment_method": "card",
  "return_url": "itqan://payment-callback"
}
```

---

### Subscriptions

#### List Subscriptions

```
GET /api/v1/parent/subscriptions
```

**Query Parameters:**
- `child_id`: Filter by child
- `type`: `quran`, `academic`, `course`
- `status`: Subscription status

#### Get Subscription Details

```
GET /api/v1/parent/subscriptions/{type}/{id}
```

---

### Profile

#### Get Profile

```
GET /api/v1/parent/profile
```

#### Update Profile

```
PUT /api/v1/parent/profile
```

#### Update Avatar

```
POST /api/v1/parent/profile/avatar
```

#### Change Password

```
POST /api/v1/parent/profile/change-password
```

**Request Body:**
```json
{
  "current_password": "oldpassword",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

#### Delete Account

```
DELETE /api/v1/parent/profile
```

**Request Body:**
```json
{
  "password": "currentpassword",
  "reason": "Optional deletion reason"
}
```

---

## Teacher Endpoints

All endpoints prefixed with `/api/v1/teacher`

### Dashboard

```
GET /api/v1/teacher/dashboard
```

**Response:**
```json
{
  "success": true,
  "data": {
    "teacher": {
      "id": 1,
      "name": "الشيخ عبدالله",
      "teacher_type": "quran_teacher",
      "rating": 4.9,
      "total_students": 45
    },
    "stats": {
      "today_sessions": 5,
      "this_week_sessions": 23,
      "this_month_earnings": 5000,
      "pending_evaluations": 3
    },
    "today_sessions": [...],
    "upcoming_sessions": [...]
  }
}
```

---

### Schedule

#### Get Weekly Schedule

```
GET /api/v1/teacher/schedule
```

**Query Parameters:**
- `start_date`: Week start (defaults to current week)

#### Get Daily Schedule

```
GET /api/v1/teacher/schedule/{date}
```

**Path Parameters:**
- `date`: Date in Y-m-d format

---

### Quran Teacher Routes

Requires `quran_teacher` user type.

#### Circles - Individual

```
GET /api/v1/teacher/quran/circles/individual
GET /api/v1/teacher/quran/circles/individual/{id}
```

#### Circles - Group

```
GET /api/v1/teacher/quran/circles/group
GET /api/v1/teacher/quran/circles/group/{id}
GET /api/v1/teacher/quran/circles/group/{id}/students
```

#### Quran Sessions

```
GET /api/v1/teacher/quran/sessions
GET /api/v1/teacher/quran/sessions/{id}
```

**Query Parameters:**
- `status`: Session status
- `date`: Specific date
- `circle_id`: Filter by circle

#### Complete Session

```
POST /api/v1/teacher/quran/sessions/{id}/complete
```

**Request Body:**
```json
{
  "attendance": [
    {"student_id": 1, "attended": true},
    {"student_id": 2, "attended": false, "absence_reason": "sick"}
  ],
  "notes": "Session notes"
}
```

#### Cancel Session

```
POST /api/v1/teacher/quran/sessions/{id}/cancel
```

**Request Body:**
```json
{
  "reason": "Cancellation reason"
}
```

#### Evaluate Student

```
POST /api/v1/teacher/quran/sessions/{id}/evaluate
```

**Request Body:**
```json
{
  "student_id": 1,
  "memorization_rating": 5,
  "tajweed_rating": 4,
  "revision_rating": 5,
  "from_page": 40,
  "to_page": 42,
  "notes": "Excellent progress"
}
```

#### Update Session Notes

```
PUT /api/v1/teacher/quran/sessions/{id}/notes
```

---

### Academic Teacher Routes

Requires `academic_teacher` user type.

#### Lessons

```
GET /api/v1/teacher/academic/lessons
GET /api/v1/teacher/academic/lessons/{id}
```

#### Interactive Courses

```
GET /api/v1/teacher/academic/courses
GET /api/v1/teacher/academic/courses/{id}
GET /api/v1/teacher/academic/courses/{id}/students
```

#### Academic Sessions

```
GET /api/v1/teacher/academic/sessions
GET /api/v1/teacher/academic/sessions/{id}
```

#### Complete Academic Session

```
POST /api/v1/teacher/academic/sessions/{id}/complete
```

#### Cancel Academic Session

```
POST /api/v1/teacher/academic/sessions/{id}/cancel
```

#### Update Evaluation

```
PUT /api/v1/teacher/academic/sessions/{id}/evaluation
```

---

### Students

#### List Students

```
GET /api/v1/teacher/students
```

**Query Parameters:**
- `search`: Search by name or code
- `subscription_type`: Filter by subscription type

#### Get Student Details

```
GET /api/v1/teacher/students/{id}
```

#### Create Student Report

```
POST /api/v1/teacher/students/{id}/report
```

**Request Body:**
```json
{
  "report_type": "progress",
  "content": "Report content",
  "recommendations": "Recommendations for improvement"
}
```

---

### Homework Management

#### List Homework

```
GET /api/v1/teacher/homework
```

#### Get Homework Details

```
GET /api/v1/teacher/homework/{type}/{id}
```

#### Assign Homework

```
POST /api/v1/teacher/homework/assign
```

**Request Body:**
```json
{
  "session_id": 10,
  "session_type": "academic",
  "title": "حل التمارين صفحة 45",
  "description": "حل جميع التمارين من 1 إلى 10",
  "due_date": "2024-12-15",
  "attachments": []
}
```

#### Update Homework

```
PUT /api/v1/teacher/homework/{type}/{id}
```

#### Get Submissions

```
GET /api/v1/teacher/homework/{type}/{id}/submissions
```

#### Grade Submission

```
POST /api/v1/teacher/homework/submissions/{submissionId}/grade
```

**Request Body:**
```json
{
  "grade": 95,
  "feedback": "عمل ممتاز! استمر على هذا المستوى"
}
```

---

### Meetings

#### Create Meeting

```
POST /api/v1/teacher/meetings/create
```

**Request Body:**
```json
{
  "session_type": "quran",
  "session_id": 10
}
```

#### Get Meeting Token

```
GET /api/v1/teacher/meetings/{sessionType}/{sessionId}/token
```

---

### Earnings

#### Earnings Summary

```
GET /api/v1/teacher/earnings
```

**Response:**
```json
{
  "success": true,
  "data": {
    "current_month": {
      "total": 5000,
      "sessions_count": 25,
      "currency": "SAR"
    },
    "last_month": {
      "total": 4500,
      "sessions_count": 23
    },
    "pending_payout": 3500,
    "total_earned": 45000
  }
}
```

#### Earnings History

```
GET /api/v1/teacher/earnings/history
```

**Query Parameters:**
- `month`: Filter by month (Y-m)
- `year`: Filter by year

#### Payouts

```
GET /api/v1/teacher/payouts
```

---

### Profile

#### Get Profile

```
GET /api/v1/teacher/profile
```

#### Update Profile

```
PUT /api/v1/teacher/profile
```

#### Update Avatar

```
POST /api/v1/teacher/profile/avatar
```

#### Change Password

```
POST /api/v1/teacher/profile/change-password
```

---

## Common Endpoints

Available to all authenticated users.

### Notifications

#### List Notifications

```
GET /api/v1/notifications
```

**Query Parameters:**
- `unread_only`: `true` to show only unread
- `type`: Filter by notification type
- `page`: Page number

**Response:**
```json
{
  "success": true,
  "data": {
    "notifications": [
      {
        "id": "uuid",
        "type": "session_reminder",
        "title": "تذكير بالجلسة",
        "body": "جلستك مع الشيخ عبدالله بعد 10 دقائق",
        "data": {
          "session_type": "quran",
          "session_id": 10
        },
        "read_at": null,
        "created_at": "2024-12-08T13:50:00.000000Z"
      }
    ]
  },
  "pagination": {...}
}
```

#### Get Unread Count

```
GET /api/v1/notifications/unread-count
```

**Response:**
```json
{
  "success": true,
  "data": {
    "count": 5
  }
}
```

#### Mark as Read

```
PUT /api/v1/notifications/{id}/read
```

#### Mark All as Read

```
PUT /api/v1/notifications/read-all
```

#### Delete Notification

```
DELETE /api/v1/notifications/{id}
```

#### Clear All Notifications

```
DELETE /api/v1/notifications/clear-all
```

---

### Meetings (LiveKit)

#### Get Meeting Token

```
GET /api/v1/meetings/{sessionType}/{sessionId}/token
```

**Path Parameters:**
- `sessionType`: `quran`, `academic`, `interactive`
- `sessionId`: Session ID

**Response:**
```json
{
  "success": true,
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "room_name": "session-quran-10",
    "server_url": "wss://livekit.itqan-platform.com",
    "participant_name": "أحمد محمد",
    "participant_identity": "user_10",
    "role": "student",
    "permissions": {
      "can_publish": true,
      "can_subscribe": true,
      "can_publish_data": true
    }
  }
}
```

#### Get Meeting Info

```
GET /api/v1/meetings/{sessionType}/{sessionId}/info
```

**Response:**
```json
{
  "success": true,
  "data": {
    "session": {
      "id": 10,
      "type": "quran",
      "status": "live",
      "started_at": "2024-12-08T14:00:00.000000Z"
    },
    "meeting": {
      "room_name": "session-quran-10",
      "is_active": true,
      "participants_count": 2,
      "recording": false
    },
    "can_join": true,
    "is_teacher": false
  }
}
```

---

### Chat (WireChat)

#### List Conversations

```
GET /api/v1/chat/conversations
```

**Response:**
```json
{
  "success": true,
  "data": {
    "conversations": [
      {
        "id": 1,
        "type": "direct",
        "participant": {
          "id": 5,
          "name": "الشيخ عبدالله",
          "avatar_url": "...",
          "is_online": true
        },
        "last_message": {
          "id": 100,
          "body": "السلام عليكم",
          "sent_at": "2024-12-08T10:00:00.000000Z",
          "is_read": true
        },
        "unread_count": 0
      }
    ]
  }
}
```

#### Create Conversation

```
POST /api/v1/chat/conversations
```

**Request Body:**
```json
{
  "participant_id": 5,
  "initial_message": "السلام عليكم"
}
```

#### Get Conversation

```
GET /api/v1/chat/conversations/{id}
```

#### Get Messages

```
GET /api/v1/chat/conversations/{id}/messages
```

**Query Parameters:**
- `before`: Message ID to load messages before (pagination)
- `limit`: Number of messages (default: 50)

#### Send Message

```
POST /api/v1/chat/conversations/{id}/messages
```

**Request Body:**
```json
{
  "body": "Message content",
  "attachments": []
}
```

**Request Body (with file):** Use `multipart/form-data`
```
body: "Message content"
attachments[]: (file)
```

#### Mark Conversation as Read

```
PUT /api/v1/chat/conversations/{id}/read
```

#### Get Unread Count

```
GET /api/v1/chat/unread-count
```

---

## WebSocket Events (Real-time)

The API uses Laravel Reverb for real-time events. Connect to:
```
wss://{subdomain}.itqan-platform.com/app/{app_key}
```

### Channels

| Channel | Description |
|---------|-------------|
| `private-user.{userId}` | User-specific notifications |
| `private-session.{type}.{id}` | Session updates |
| `private-chat.{conversationId}` | Chat messages |

### Events

| Event | Description |
|-------|-------------|
| `SessionStatusChanged` | Session status update |
| `NewNotification` | New notification received |
| `NewChatMessage` | New chat message |
| `AttendanceUpdated` | Attendance record updated |

---

## Deep Linking

### URL Schemes

**Universal Links (Recommended):**
```
https://app.itqan-platform.com/open/{subdomain}
https://app.itqan-platform.com/session/{type}/{id}
https://app.itqan-platform.com/certificate/{id}
https://app.itqan-platform.com/payment-callback
```

**Custom Scheme:**
```
itqan://open/{subdomain}
itqan://session/{type}/{id}
itqan://certificate/{id}
```

---

## Best Practices

### 1. Token Storage
- Store tokens in secure keychain (iOS) or encrypted SharedPreferences (Android)
- Never store tokens in plain text or local storage

### 2. Error Handling
- Always check `success` field in responses
- Handle specific error codes appropriately
- Show localized error messages to users

### 3. Offline Support
- Cache critical data locally
- Queue actions for later when offline
- Sync when connection is restored

### 4. Performance
- Use pagination for list endpoints
- Implement pull-to-refresh
- Cache images and static content

### 5. Security
- Use HTTPS only
- Validate SSL certificates
- Don't log sensitive data
- Implement certificate pinning (optional)

---

## Changelog

### Version 1.0 (December 2024)
- Initial API release
- Student, Parent, and Teacher endpoints
- Authentication with multi-tenancy support
- LiveKit meeting integration
- WireChat integration
- Real-time notifications

---

## Support

For API support, contact:
- **Email:** api-support@itqan-platform.com
- **Documentation:** https://docs.itqan-platform.com/api
