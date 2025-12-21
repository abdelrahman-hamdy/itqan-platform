# Laravel + Filament Comprehensive Refactoring Prompt System

## Overview

This document provides a structured, context-preserving prompt system for comprehensively analyzing, refactoring, and optimizing a Laravel + Filament application. It is based on trusted resources including:

- **Laravel Best Practices** (github.com/alexeymezenin/laravel-best-practices - 12.2K+ stars)
- **PSR Standards** (PSR-2, PSR-4, PSR-12)
- **Larastan/PHPStan** official documentation
- **Filament PHP** official documentation
- **Anthropic's Context Engineering** best practices

---

## PART 1: PROJECT CONTEXT FOUNDATION

### 1.1 Initial Project Context Document

Before starting any analysis, create and maintain this context document. Copy this template and fill it out completely:

```markdown
# PROJECT CONTEXT DOCUMENT
Last Updated: [DATE]

## Project Identity
- Project Name: [NAME]
- Laravel Version: [VERSION]
- PHP Version: [VERSION]
- Filament Version: [VERSION]
- Database: [MySQL/PostgreSQL/SQLite]

## Architecture Overview
- Primary Purpose: [DESCRIPTION]
- Main Modules/Features: [LIST]
- Authentication System: [Breeze/Jetstream/Fortify/Custom]
- API Approach: [Sanctum/Passport/None]

## Directory Structure Summary
```
app/
├── Console/
├── Exceptions/
├── Filament/
│   ├── Resources/
│   ├── Pages/
│   └── Widgets/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Requests/
├── Models/
├── Policies/
├── Providers/
├── Services/
└── [Other directories]
```

## Key Configuration Files
- config/filament.php: [Status: Present/Missing]
- config/filament-shield.php: [If using shield]
- AdminPanelProvider.php location: [PATH]

## Known Issues (High-Level)
1. [Issue 1]
2. [Issue 2]
3. [Issue 3]

## Previous AI Agent Contributions
- Agent 1 worked on: [AREA]
- Agent 2 worked on: [AREA]
(This helps identify potential integration issues)
```

---

## PART 2: SYSTEMATIC FILE ANALYSIS PROMPTS

### 2.1 Phase 1: Core Structure Analysis

#### Prompt 1A: Models Analysis

```
# CONTEXT PRESERVATION BLOCK
You are analyzing Laravel models for: [PROJECT_NAME]
Laravel Version: [VERSION] | Filament Version: [VERSION]
This is Phase 1A of the refactoring analysis.

# TASK
Analyze ALL model files in app/Models/ for the following issues:

## CHECKLIST - Laravel Model Best Practices
For each model, check:

### Naming Conventions (Laravel Standard)
- [ ] Model name is singular (User, not Users)
- [ ] Model name is PascalCase
- [ ] Table follows convention: plural snake_case (users, order_items)
- [ ] Primary key follows convention: id (not custom_id, user_id)
- [ ] Foreign keys follow convention: {model}_id (user_id, not userId)

### Relationships
- [ ] belongsTo/hasOne methods are singular (user(), not users())
- [ ] hasMany/belongsToMany methods are plural (comments(), not comment())
- [ ] Pivot tables named alphabetically: article_user (not user_article)
- [ ] No duplicate relationship definitions
- [ ] Inverse relationships properly defined

### Properties & Casts
- [ ] $fillable or $guarded defined (not both, not neither)
- [ ] Date fields in $casts with 'datetime' type
- [ ] Boolean fields properly cast
- [ ] JSON fields properly cast
- [ ] No unnecessary $table override if following conventions
- [ ] No unnecessary $primaryKey override if using 'id'

### Scopes
- [ ] Reusable query logic extracted to scopes
- [ ] Scope names are descriptive (scopeActive, scopeVerified)
- [ ] No duplicate scope logic across models

### Accessors/Mutators
- [ ] Complex attribute logic uses accessors
- [ ] Date formatting done via accessors, not in controllers/views
- [ ] No business logic in accessors (keep them simple)

### Issues Found
Report using this format:
| File | Line | Issue | Severity | Fix |
|------|------|-------|----------|-----|

### Duplicate Logic Detected
List any logic that appears in multiple models:
| Logic Description | Found In Models | Recommendation |
|-------------------|-----------------|----------------|
```

#### Prompt 1B: Controllers Analysis

```
# CONTEXT PRESERVATION BLOCK
You are analyzing Laravel controllers for: [PROJECT_NAME]
Previous analysis phase completed: Models (Phase 1A)
This is Phase 1B of the refactoring analysis.

# TASK
Analyze ALL controller files in app/Http/Controllers/ for the following issues:

## CHECKLIST - Laravel Controller Best Practices

### Naming Conventions
- [ ] Controller name is singular: ArticleController (not ArticlesController)
- [ ] Controller name ends with "Controller"
- [ ] Resource controller methods follow standard: index, create, store, show, edit, update, destroy

### Single Responsibility Principle
- [ ] No business logic in controllers (move to Services)
- [ ] No direct database queries (use Models/Repositories)
- [ ] No file handling logic (move to Services)
- [ ] No email sending logic (use Notifications/Mailables)
- [ ] Controllers only handle HTTP request/response

### Validation
- [ ] Validation NOT in controller methods
- [ ] Using Form Request classes for validation
- [ ] Form Requests properly named: StoreUserRequest, UpdateArticleRequest

### Fat Models, Skinny Controllers Pattern
Check for these anti-patterns:
- [ ] Complex Eloquent queries should be in Model methods
- [ ] Data transformation should use Resources/Transformers
- [ ] Authorization should use Policies

### Dependency Injection
- [ ] Using constructor injection, not 'new Class()'
- [ ] Services injected via constructor
- [ ] No direct facade usage where injection is better

### Return Types
- [ ] Methods have return type declarations
- [ ] Using Resources for API responses
- [ ] Using view() helper correctly

### Issues Found
Report using this format:
| File | Line | Issue | Severity | Fix |
|------|------|-------|----------|-----|

### Business Logic to Extract
| Controller | Method | Logic Description | Suggested Service |
|------------|--------|-------------------|-------------------|
```

#### Prompt 1C: Services Analysis

```
# CONTEXT PRESERVATION BLOCK
You are analyzing Laravel services for: [PROJECT_NAME]
Previous phases completed: Models (1A), Controllers (1B)
This is Phase 1C of the refactoring analysis.

# TASK
Analyze ALL service files in app/Services/ (and any service directories)

## CHECKLIST - Service Layer Best Practices

### Single Responsibility
- [ ] Each service handles one domain area
- [ ] No mixed responsibilities (e.g., UserService handling orders)
- [ ] Methods do one thing well

### Dependency Injection
- [ ] Services registered in ServiceProvider if needed
- [ ] Dependencies injected via constructor
- [ ] Using interfaces where appropriate

### Naming
- [ ] Clear, descriptive service names
- [ ] Method names describe action: calculateTotal(), sendNotification()

### Error Handling
- [ ] Proper exception handling
- [ ] Custom exceptions where appropriate
- [ ] Not swallowing errors silently

### DUPLICATE LOGIC DETECTION
Cross-reference with other services and look for:
- [ ] Same calculations in multiple services
- [ ] Same validation logic repeated
- [ ] Same data transformations repeated
- [ ] Same API calls repeated

### Issues Found
| File | Line | Issue | Severity | Fix |
|------|------|-------|----------|-----|

### Duplicate Logic Map
| Logic Type | Location 1 | Location 2 | Consolidation Strategy |
|------------|------------|------------|------------------------|
```

---

### 2.2 Phase 2: Filament-Specific Analysis

#### Prompt 2A: Filament Resources Analysis

```
# CONTEXT PRESERVATION BLOCK
You are analyzing Filament Resources for: [PROJECT_NAME]
Laravel Version: [VERSION] | Filament Version: [VERSION]
Previous phases completed: Models (1A), Controllers (1B), Services (1C)
This is Phase 2A - Filament Resources Analysis.

# TASK
Analyze ALL Filament Resource files in app/Filament/Resources/

## CHECKLIST - Filament Resource Best Practices

### Structure & Organization
- [ ] Resource name matches model: UserResource for User model
- [ ] Pages properly defined in getPages()
- [ ] Navigation properly configured

### Forms
- [ ] form() method properly structured
- [ ] Using appropriate field types
- [ ] Validation rules consistent with Form Requests
- [ ] No duplicate validation (should match model/request rules)
- [ ] Reactive fields properly configured
- [ ] Hidden fields not exposing sensitive data

### Tables
- [ ] table() method properly structured
- [ ] Columns optimized (only necessary columns)
- [ ] Proper use of searchable() and sortable()
- [ ] Filters logically organized
- [ ] Actions properly defined

### Performance
- [ ] getEloquentQuery() optimized with select() and with()
- [ ] N+1 queries prevented
- [ ] Large datasets using proper pagination

### UNUSED SETTINGS DETECTION
Check for:
- [ ] Unused navigation groups defined
- [ ] Unused form fields (fields not used anywhere)
- [ ] Unused table columns
- [ ] Unused filters
- [ ] Unused actions
- [ ] Dead code in mutators/afterSave hooks

### DUPLICATE SETTINGS DETECTION
Check for:
- [ ] Same form schema in multiple resources
- [ ] Same table columns repeated
- [ ] Same filters duplicated
- [ ] Same action definitions copied

### Issues Found
| Resource | Component | Issue | Severity | Fix |
|----------|-----------|-------|----------|-----|

### Unused Settings Found
| Resource | Setting Type | Setting Name | Evidence |
|----------|--------------|--------------|----------|

### Duplicate Settings Found
| Setting Type | Resources Involved | Consolidation Strategy |
|--------------|-------------------|------------------------|
```

#### Prompt 2B: Filament Pages & Widgets Analysis

```
# CONTEXT PRESERVATION BLOCK
You are analyzing Filament Pages & Widgets for: [PROJECT_NAME]
Previous phases completed: 1A-1C (Core), 2A (Resources)
This is Phase 2B - Filament Pages & Widgets Analysis.

# TASK
Analyze ALL Filament Pages (app/Filament/Pages/) and Widgets (app/Filament/Widgets/)

## CHECKLIST - Pages Best Practices

### Custom Pages
- [ ] Properly extending correct base class
- [ ] View file exists and properly referenced
- [ ] Navigation properly configured
- [ ] Authorization implemented if needed

### Dashboard Widgets
- [ ] Widget data queries optimized
- [ ] Using caching for expensive queries
- [ ] Poll interval appropriate (not too frequent)
- [ ] Proper column span configuration

### UNUSED COMPONENTS
- [ ] Widgets registered but not displayed
- [ ] Pages defined but not in navigation
- [ ] Unused widget properties
- [ ] Dead code in widget methods

### DUPLICATE LOGIC IN WIDGETS
- [ ] Same stats calculations in multiple widgets
- [ ] Same data queries repeated
- [ ] Same formatting logic duplicated

### Issues Found
| File | Component Type | Issue | Severity | Fix |
|------|----------------|-------|----------|-----|
```

#### Prompt 2C: Filament Configuration Analysis

```
# CONTEXT PRESERVATION BLOCK
You are analyzing Filament Configuration for: [PROJECT_NAME]
Previous phases completed: 1A-1C (Core), 2A-2B (Filament Components)
This is Phase 2C - Filament Configuration Analysis.

# TASK
Analyze Filament configuration files and Panel Providers

## FILES TO CHECK
- config/filament.php (if published)
- config/filament-*.php (all filament configs)
- app/Providers/Filament/*PanelProvider.php

## CHECKLIST - Configuration Best Practices

### Panel Provider
- [ ] Brand name properly set
- [ ] Colors properly configured
- [ ] Navigation organized logically
- [ ] Plugins properly registered
- [ ] Middleware correctly applied
- [ ] Auth guard correctly configured

### Config Files
- [ ] No conflicting settings between files
- [ ] Default values overridden only when necessary
- [ ] Environment variables used for sensitive/varying values

### UNUSED CONFIGURATION
- [ ] Published config with unchanged defaults
- [ ] Registered plugins not being used
- [ ] Defined navigation groups with no items
- [ ] Custom themes not applied
- [ ] Middleware registered but not functioning

### CONFLICTING CONFIGURATION
- [ ] Same setting defined differently in multiple places
- [ ] Panel provider settings conflicting with config files
- [ ] Plugin configurations conflicting

### Issues Found
| File | Setting | Issue | Recommendation |
|------|---------|-------|----------------|
```

---

### 2.3 Phase 3: Database & Migrations Analysis

#### Prompt 3A: Migrations & Schema Analysis

```
# CONTEXT PRESERVATION BLOCK
You are analyzing Database Migrations for: [PROJECT_NAME]
Previous phases completed: 1A-1C (Core), 2A-2C (Filament)
This is Phase 3A - Migrations Analysis.

# TASK
Analyze ALL migration files in database/migrations/

## CHECKLIST - Migration Best Practices

### Structure
- [ ] Every up() has corresponding down() method
- [ ] Migrations are reversible
- [ ] Table naming follows Laravel conventions (plural snake_case)
- [ ] Column naming follows Laravel conventions (snake_case)

### Columns
- [ ] Proper column types used
- [ ] Nullable columns marked correctly
- [ ] Default values appropriate
- [ ] Indexes defined for frequently queried columns
- [ ] Foreign keys properly defined with cascades

### Conventions
- [ ] Timestamps columns present (created_at, updated_at)
- [ ] Soft deletes properly implemented where needed
- [ ] No redundant indexes

### Issues Found
| Migration | Issue | Severity | Fix |
|-----------|-------|----------|-----|

### Schema Inconsistencies
| Table | Issue | Related Model | Fix |
|-------|-------|---------------|-----|
```

---

### 2.4 Phase 4: Cross-Cutting Concerns

#### Prompt 4A: Route Analysis

```
# CONTEXT PRESERVATION BLOCK
You are analyzing Routes for: [PROJECT_NAME]
Previous phases completed: 1A-1C, 2A-2C, 3A
This is Phase 4A - Routes Analysis.

# TASK
Analyze ALL route files in routes/

## CHECKLIST - Route Best Practices

### Naming Conventions
- [ ] Route URIs are plural: /articles, /users
- [ ] Route names use snake_case with dots: users.show_active
- [ ] Resource routes used where appropriate

### Organization
- [ ] Routes properly grouped
- [ ] Middleware applied at group level where possible
- [ ] No duplicate route definitions
- [ ] No conflicting route patterns

### Logic in Routes
- [ ] NO business logic in route files
- [ ] Closures only for simple returns (prefer controllers)
- [ ] Route model binding used appropriately

### Issues Found
| Route File | Line | Issue | Fix |
|------------|------|-------|-----|
```

#### Prompt 4B: Validation Consistency Analysis

```
# CONTEXT PRESERVATION BLOCK
You are analyzing Validation Rules for: [PROJECT_NAME]
Previous phases completed: 1A-4A
This is Phase 4B - Validation Consistency Analysis.

# TASK
Cross-reference validation rules across:
- Form Request classes (app/Http/Requests/)
- Filament Resource forms
- Model $rules (if any)
- Inline controller validation (anti-pattern)

## CHECKLIST

### Consistency
- [ ] Same field validated consistently across locations
- [ ] Filament form rules match Form Request rules
- [ ] No conflicting validation rules for same field

### Completeness
- [ ] All user inputs validated
- [ ] All API inputs validated
- [ ] File uploads properly validated

### DUPLICATE VALIDATION RULES
| Field | Location 1 | Location 2 | Consistent? | Action |
|-------|------------|------------|-------------|--------|

### CONFLICTING VALIDATION
| Field | Location 1 Rules | Location 2 Rules | Resolution |
|-------|------------------|------------------|------------|
```

#### Prompt 4C: Duplicate Logic Comprehensive Report

```
# CONTEXT PRESERVATION BLOCK
You are creating a Duplicate Logic Report for: [PROJECT_NAME]
Previous phases completed: 1A-4B
This is Phase 4C - Duplicate Logic Consolidation Analysis.

# TASK
Compile all duplicate logic found across phases and create consolidation plan.

## DUPLICATE LOGIC CATEGORIES

### 1. Calculation Logic
| Calculation Type | Locations Found | Lines of Code | Consolidation Target |
|------------------|-----------------|---------------|---------------------|

### 2. Query Logic
| Query Description | Locations Found | Should Be | Scope/Method |
|-------------------|-----------------|-----------|--------------|

### 3. Validation Logic
| Validation Rule Set | Locations Found | Single Source of Truth |
|---------------------|-----------------|------------------------|

### 4. Formatting Logic
| Format Type | Locations Found | Consolidation Method |
|-------------|-----------------|---------------------|

### 5. Business Rules
| Rule Description | Locations Found | Service/Trait |
|------------------|-----------------|---------------|

## CONSOLIDATION PRIORITIES
Rank by: Impact x Complexity (Lower complexity = Higher priority)

| Priority | Logic Type | Impact | Complexity | Recommendation |
|----------|------------|--------|------------|----------------|
| 1        |            |        |            |                |
| 2        |            |        |            |                |
```

---

## PART 3: STATIC ANALYSIS INTEGRATION

### 3.1 Larastan/PHPStan Setup Prompt

```
# CONTEXT PRESERVATION BLOCK
Setting up static analysis for: [PROJECT_NAME]
Laravel Version: [VERSION] | PHP Version: [VERSION]

# TASK
Set up and run Larastan for comprehensive static analysis.

## SETUP STEPS

### 1. Install Larastan
composer require --dev larastan/larastan

### 2. Create phpstan.neon
```neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    paths:
        - app/
        - config/
        - database/
        - routes/
    
    level: 6  # Start at 6, increase to 9 progressively
    
    ignoreErrors:
        # Add specific ignores as needed
    
    checkMissingIterableValueType: false
    checkGenericClassInNonGenericObjectType: false
```

### 3. Run Analysis
./vendor/bin/phpstan analyse

### 4. Generate Baseline (for legacy code)
./vendor/bin/phpstan analyse --generate-baseline

## ANALYSIS LEVELS EXPLANATION
- Level 0-3: Basic type checking, undefined variables
- Level 4-5: Unknown methods, return types
- Level 6-7: Missing typehints, strict checking
- Level 8-9: Maximum strictness

## REPORT FORMAT
After running, categorize findings:

### Type Errors
| File | Line | Error | Fix |
|------|------|-------|-----|

### Undefined Methods/Properties
| File | Line | Error | Likely Cause |
|------|------|-------|--------------|

### Return Type Issues
| File | Method | Expected | Actual | Fix |
|------|--------|----------|--------|-----|
```

### 3.2 Laravel Pint Setup Prompt

```
# TASK
Set up Laravel Pint for code style consistency.

## SETUP
composer require --dev laravel/pint

## RUN
./vendor/bin/pint --test  # Check only
./vendor/bin/pint         # Auto-fix

## CUSTOM CONFIG (pint.json)
{
    "preset": "laravel",
    "rules": {
        "array_syntax": {"syntax": "short"},
        "binary_operator_spaces": {"default": "single_space"},
        "blank_line_before_statement": {
            "statements": ["return", "throw", "try"]
        }
    }
}

## REPORT FINDINGS
| File | Issue Type | Line | Fixed? |
|------|------------|------|--------|
```

---

## PART 4: REFACTORING PLAN TEMPLATE

### 4.1 Comprehensive Refactoring Plan

```
# REFACTORING PLAN FOR [PROJECT_NAME]
Generated: [DATE]
Analyst: [AI Model Version]

## EXECUTIVE SUMMARY
- Total Issues Found: [NUMBER]
- Critical Issues: [NUMBER]
- High Priority: [NUMBER]
- Medium Priority: [NUMBER]
- Low Priority: [NUMBER]

## PHASE 1: CRITICAL FIXES (Week 1)
### Security Issues
| Issue | File(s) | Fix | Test Required |
|-------|---------|-----|---------------|

### Breaking Bugs
| Issue | File(s) | Fix | Test Required |
|-------|---------|-----|---------------|

## PHASE 2: HIGH PRIORITY REFACTORING (Week 2-3)
### Architecture Issues
| Issue | Current State | Target State | Files Affected |
|-------|---------------|--------------|----------------|

### Performance Issues
| Issue | Current | Optimization | Expected Improvement |
|-------|---------|--------------|---------------------|

## PHASE 3: CODE CONSOLIDATION (Week 4-5)
### Duplicate Logic Removal
| Logic Type | Consolidate From | Consolidate To | Files Affected |
|------------|------------------|----------------|----------------|

### Unused Code Removal
| Type | Files/Components | Safe to Remove? | Verification |
|------|------------------|-----------------|--------------|

## PHASE 4: BEST PRACTICES ALIGNMENT (Week 6+)
### Naming Convention Fixes
| Current | Should Be | Files | Impact |
|---------|-----------|-------|--------|

### Structure Improvements
| Current Pattern | Better Pattern | Files | Migration Path |
|-----------------|----------------|-------|----------------|

## TESTING REQUIREMENTS
### Unit Tests Needed
| Component | Test Type | Priority |
|-----------|-----------|----------|

### Integration Tests Needed
| Flow | Test Coverage | Priority |
|------|---------------|----------|

## ROLLBACK PLAN
For each phase, document:
- Git branch strategy
- Database backup requirements
- Rollback commands
- Verification steps

## SUCCESS METRICS
- PHPStan level achieved: [TARGET]
- Test coverage: [TARGET]%
- Duplicate code reduction: [TARGET]%
- Performance improvement: [BASELINE → TARGET]
```

---

## PART 5: CONTEXT PRESERVATION STRATEGIES

### 5.1 Session Continuity Template

When starting a new conversation session, begin with:

```
# SESSION CONTINUITY DOCUMENT

## Previous Session Summary
- Date: [DATE]
- Phases Completed: [LIST]
- Key Findings: [SUMMARY]
- Pending Items: [LIST]

## Files Already Analyzed
[LIST OF FILES WITH STATUS]

## Issues Already Documented
[REFERENCE TO ISSUES LIST]

## Current Focus
Starting from: [SPECIFIC FILE/COMPONENT]
Goal for this session: [SPECIFIC GOAL]

## Important Context
- [KEY DECISION 1]
- [KEY DECISION 2]
- [PATTERN ESTABLISHED]
```

### 5.2 Batch Processing Strategy

For large codebases, analyze in batches:

```
# BATCH PROCESSING STRATEGY

## Batch Definition
- Batch Size: 5-10 files per analysis
- Grouping Logic: By feature area OR by file type

## Current Batch: [N] of [TOTAL]
Files in this batch:
1. [FILE_PATH]
2. [FILE_PATH]
3. [FILE_PATH]
4. [FILE_PATH]
5. [FILE_PATH]

## Previous Batch Summary
- Issues found: [COUNT]
- Patterns identified: [LIST]
- Cross-references needed: [LIST]

## Running Totals
- Total files analyzed: [N]
- Total issues found: [N]
- Critical issues: [N]
```

---

## PART 6: QUICK REFERENCE CHECKLISTS

### 6.1 Laravel Naming Conventions Quick Reference

| What | Convention | Good Example | Bad Example |
|------|------------|--------------|-------------|
| Controller | Singular | ArticleController | ArticlesController |
| Model | Singular | User | Users |
| Table | Plural snake_case | article_comments | articleComments |
| Pivot Table | Alphabetical singular | article_user | user_article |
| Migration | Timestamped | 2024_01_01_create_users_table | create_users |
| Route URI | Plural | /articles | /article |
| Route Name | snake_case.dots | users.show_active | users-show-active |
| Method | camelCase | getAll | get_all |
| Variable | camelCase | $articlesWithAuthor | $articles_with_author |
| Config | snake_case | google_calendar.php | googleCalendar.php |
| View | kebab-case | show-filtered.blade.php | showFiltered.blade.php |

### 6.2 Common Anti-Patterns to Flag

| Anti-Pattern | What to Look For | Fix |
|--------------|------------------|-----|
| Fat Controller | Business logic in controllers | Move to Services |
| N+1 Query | Queries in loops, Blade @foreach with relations | Use eager loading |
| God Model | Model with 50+ methods | Split into Services/Traits |
| env() in code | Direct env() calls outside config | Use config() helper |
| Hard-coded strings | Text in controllers/models | Use lang files |
| Validation in controller | $request->validate() | Use Form Requests |
| Raw SQL | DB::select() with raw SQL | Use Eloquent |
| New Class | new ServiceClass() | Use dependency injection |

### 6.3 Filament-Specific Anti-Patterns

| Anti-Pattern | What to Look For | Fix |
|--------------|------------------|-----|
| Duplicate Form Fields | Same schema in multiple resources | Extract to Trait/Method |
| N+1 in Tables | Missing getEloquentQuery() optimization | Add with() and select() |
| Hardcoded Labels | 'label' => 'User Name' | Use localization |
| Missing Authorization | No Policy or canAccess() | Implement Policies |
| Widget Query in Render | Query in getViewData() without cache | Add caching |
| Unused Plugins | Registered but unused | Remove from Panel |

---

## USAGE INSTRUCTIONS

1. **Start with Part 1** - Fill out the Project Context Document completely
2. **Run Phase 1 prompts (1A-1C)** - Core Laravel analysis
3. **Run Phase 2 prompts (2A-2C)** - Filament-specific analysis
4. **Run Phase 3 prompt (3A)** - Database analysis
5. **Run Phase 4 prompts (4A-4C)** - Cross-cutting concerns
6. **Set up static analysis** - Part 3 prompts
7. **Generate Refactoring Plan** - Part 4 template
8. **Maintain context** - Use Part 5 strategies between sessions

**Important**: Always include the "CONTEXT PRESERVATION BLOCK" at the start of each prompt to maintain consistency and prevent context loss.

---

## APPENDIX: TRUSTED RESOURCES

- Laravel Best Practices: https://github.com/alexeymezenin/laravel-best-practices
- PSR Standards: https://www.php-fig.org/psr/
- Larastan Documentation: https://github.com/larastan/larastan
- Filament Documentation: https://filamentphp.com/docs
- Laravel Official Docs: https://laravel.com/docs
- Anthropic Context Engineering: https://www.anthropic.com/engineering/effective-context-engineering-for-ai-agents
