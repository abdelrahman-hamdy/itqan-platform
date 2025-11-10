# Interactive Course Access Flow Diagram

## Visual Flow Chart

```
┌─────────────────────────────────────────────────────────────────┐
│                    User Accesses Interactive Course               │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
                ┌─────────────────┐
                │ Is Authenticated?│
                └────────┬─────────┘
                         │
         ┌───────────────┴────────────────┐
         │                                 │
      NO │                              YES│
         ▼                                 ▼
┌─────────────────┐            ┌──────────────────┐
│  PUBLIC VIEW    │            │ Check User Type  │
│                 │            └────────┬──────────┘
│ - Course Info   │                     │
│ - Pricing       │     ┌───────────────┼───────────────┐
│ - Enroll CTA    │     │               │               │
│                 │   STUDENT          TEACHER        OTHER
└─────────────────┘     │               │               │
                        ▼               ▼               ▼
              ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
              │Check Enrolled│  │Check Assigned│  │ Redirect to  │
              └──────┬───────┘  └──────┬───────┘  │   Dashboard  │
                     │                  │          └──────────────┘
        ┌────────────┼──────────┐       │
        │            │          │       │
     ENROLLED     PENDING   NOT ENR  ASSIGNED  NOT ASSIGNED
        │            │          │       │          │
        ▼            ▼          ▼       ▼          ▼
┌──────────────┐ ┌────────┐ ┌──────┐ ┌──────┐ ┌──────────┐
│STUDENT VIEW  │ │PAYMENT │ │PUBLIC│ │TEACHER│ │ PUBLIC   │
│              │ │PAGE    │ │VIEW  │ │VIEW   │ │ VIEW     │
│- Full Access │ │        │ │      │ │       │ │          │
│- Sessions    │ │- Pay   │ │-Enroll│- Manage│ │- Can     │
│- Homework    │ │- Confirm│      │ │- Grade│ │  Enroll  │
│- Progress    │ │        │       │ │- Students│         │
└──────────────┘ └────────┘ └──────┘ └──────┘ └──────────┘
```

## Route to View Mapping

### Public Routes (Unauthenticated or Non-Enrolled)
```
/interactive-courses
  ↓ [Middleware: redirect.authenticated.public]
  → IF authenticated & enrolled → redirect to my-interactive-courses
  → IF authenticated & pending → redirect to enroll page
  → ELSE → public.interactive-courses.index

/interactive-courses/{course}
  ↓ [Middleware: redirect.authenticated.public]
  → IF authenticated & enrolled → redirect to my-interactive-courses/{course}
  → IF authenticated & pending → redirect to enroll page
  → ELSE → public.interactive-courses.show
```

### Student Routes (Enrolled Students Only)
```
/my-interactive-courses
  ↓ [Middleware: auth, role:student]
  → student.interactive-courses (list view)

/my-interactive-courses/{course}
  ↓ [Middleware: auth, interactive.course]
  ↓ [Controller: validates enrollment status]
  → IF enrolled/completed → student.interactive-course-detail
  → IF not enrolled → redirect to public view with error
```

### Teacher Routes (Assigned Teachers Only)
```
/my-interactive-courses/{course}
  ↓ [Middleware: auth, interactive.course]
  ↓ [Controller: validates teacher assignment]
  → IF assigned/creator → teacher.interactive-course-detail
  → IF not assigned → redirect to public view with info
```

## Enrollment Status Decision Tree

```
┌─────────────────────────────────────────────┐
│         Student Enrollment Status            │
└──────────────────┬──────────────────────────┘
                   │
    ┌──────────────┼──────────────┬──────────────┐
    │              │              │              │
    ▼              ▼              ▼              ▼
┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐
│enrolled │  │completed│  │ pending │  │dropped/ │
│         │  │         │  │         │  │expelled │
└────┬────┘  └────┬────┘  └────┬────┘  └────┬────┘
     │            │            │            │
     ▼            ▼            ▼            ▼
┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐
│ STUDENT │  │ STUDENT │  │ PAYMENT │  │ PUBLIC  │
│  VIEW   │  │  VIEW   │  │  PAGE   │  │  VIEW   │
│         │  │         │  │         │  │         │
│Full     │  │Read-    │  │Complete │  │Re-enroll│
│Access   │  │Only     │  │Payment  │  │Option   │
└─────────┘  └─────────┘  └─────────┘  └─────────┘
```

## Middleware Logic Summary

### RedirectAuthenticatedPublicViews Middleware

```php
// For interactive-course type
if (user is student) {
    if (has enrollment) {
        if (status = enrolled OR completed)
            → redirect to my.interactive-course.show
        elseif (status = pending)
            → redirect to interactive-courses.enroll
        else (dropped/expelled)
            → allow public view access
    } else {
        → allow public view access (to enroll)
    }
}

if (user is teacher) {
    if (assigned to course OR created course)
        → redirect to my.interactive-course.show
    else
        → allow public view access
}
```

## Controller Logic Summary

### StudentProfileController::showInteractiveCourse()

```php
// For students
if (user is student) {
    $enrollment = check_enrollment(course_id, student_id);

    if (!$enrollment OR status NOT IN [enrolled, completed]) {
        → redirect to public view with error
        → "يجب التسجيل في الكورس أولاً للوصول إلى محتواه"
    }

    → return student.interactive-course-detail view
}

// For teachers
if (user is teacher) {
    if (NOT assigned AND NOT creator) {
        → abort 403 (Access denied)
    }

    → return teacher.interactive-course-detail view
}
```

## Security Layers

```
┌─────────────────────────────────────────────┐
│              Security Layers                 │
├─────────────────────────────────────────────┤
│ 1. Route Middleware                         │
│    - Auth check                             │
│    - Role check                             │
│    - redirect.authenticated.public          │
├─────────────────────────────────────────────┤
│ 2. Controller Validation                    │
│    - Enrollment status check                │
│    - Teacher assignment check               │
│    - Academy membership check               │
├─────────────────────────────────────────────┤
│ 3. View-Level Access Control                │
│    - @guest directives                      │
│    - @auth directives                       │
│    - Conditional content rendering          │
└─────────────────────────────────────────────┘
```

## Example User Journeys

### Journey 1: New Student Discovering a Course
```
1. User is NOT logged in
2. Visits: /interactive-courses
   → Sees: Public course listing
3. Clicks on course
   → URL: /interactive-courses/5
   → Sees: Public course detail with pricing
4. Clicks "Enroll"
   → Redirected to: /login
5. After login → Redirected back to: /interactive-courses/5
   → Middleware detects: authenticated but not enrolled
   → Allowed to see: Public view with enroll button
6. Clicks "Enroll Now"
   → URL: /interactive-courses/5/enroll
   → Fills form, submits
   → Enrollment created with status: 'pending'
7. Redirected to payment or dashboard
8. After payment → enrollment status: 'enrolled'
9. Next visit to /interactive-courses/5
   → Middleware detects: enrolled
   → Redirected to: /my-interactive-courses/5
   → Sees: STUDENT VIEW ✅
```

### Journey 2: Enrolled Student Accessing Course
```
1. User is logged in as student
2. User has enrollment with status: 'enrolled'
3. Visits: /interactive-courses/5
   → Middleware checks enrollment
   → Found: enrolled status
   → Redirected to: /my-interactive-courses/5
4. Controller validates enrollment
   → Confirmed: enrolled status
   → Renders: student.interactive-course-detail
5. Student sees: Full course content ✅
```

### Journey 3: Teacher Accessing Their Course
```
1. User is logged in as academic_teacher
2. User is assigned to course ID 5
3. Visits: /interactive-courses/5
   → Middleware checks: isAcademicTeacher()
   → Checks: assigned_teacher_id matches user
   → Redirected to: /my-interactive-courses/5
4. Controller validates teacher access
   → Confirmed: assigned teacher
   → Renders: teacher.interactive-course-detail
5. Teacher sees: Course management interface ✅
```

### Journey 4: Student Trying to Access Non-Enrolled Course
```
1. User is logged in as student
2. User has NO enrollment for course 5
3. Visits: /my-interactive-courses/5 (direct URL)
   → Controller checks enrollment
   → Not found OR wrong status
   → Redirected to: /interactive-courses/5
   → With error: "يجب التسجيل في الكورس أولاً"
4. Student sees: Public view with enroll option ✅
```

## Testing Checklist

- [ ] Unauthenticated user sees public view
- [ ] Authenticated student (not enrolled) sees public view
- [ ] Authenticated student (enrolled) sees student view
- [ ] Authenticated student (pending) redirected to enrollment/payment
- [ ] Authenticated student (dropped) sees public view
- [ ] Assigned teacher sees teacher view
- [ ] Non-assigned teacher sees public view
- [ ] Direct URL access blocked for non-enrolled students
- [ ] Middleware redirects work correctly
- [ ] Error messages display appropriately
- [ ] Navigation links are correct for each user type

---

**Key Principle**: Defense in Depth
- Middleware: First line of defense (redirects)
- Controller: Second line of defense (validation)
- View: Third line of defense (conditional rendering)
