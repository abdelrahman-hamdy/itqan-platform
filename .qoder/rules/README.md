---
trigger: always_on
alwaysApply: true
---
# Qoder Rules for Itqan Platform

This directory contains comprehensive development guidelines and coding standards for the Itqan Platform project. These rules ensure consistency, quality, and maintainability across the entire codebase.

## Rules Overview

### 📋 [Base Rules](./qoder_rules.md)
Fundamental guidelines for creating and maintaining Qoder rules, including structure, formatting, and documentation standards.

### 🚀 [Laravel Best Practices](./laravel_best_practices.md)
Comprehensive Laravel development standards covering:
- Model conventions and multi-tenancy requirements
- Controller patterns and API design
- Service layer implementation
- Performance optimization
- Testing standards
- Arabic content handling

### 🎥 [LiveKit Meeting Integration](./livekit_meeting_integration.md)
**CRITICAL REQUIREMENTS** for video conferencing implementation:
- ❌ NO separate meeting routes (integrate into session pages only)
- ✅ Single unified UI for all participant roles
- ✅ Direct LiveKit JavaScript SDK usage
- ✅ Group video call functionality focus

### 🎨 [UI Design System](./ui_design_system.md)
Complete design system for Arabic-first interfaces:
- Color palettes and typography systems
- Component patterns and layouts
- RTL/Arabic support guidelines
- Responsive design standards
- Accessibility requirements

### 📅 [Google Calendar Integration](./google_calendar_integration.md)
Guidelines for Calendar API and Google Meet integration:
- OAuth 2.0 authentication flow
- Meet link generation requirements
- Error handling and token management
- Multi-tenancy considerations
- Security best practices

### ⚡ [Development Workflow](./development_workflow.md)
Comprehensive development process covering:
- Task-driven development approach
- Git workflow and branching strategy
- Code quality standards
- Testing requirements
- CI/CD pipeline configuration

## Quick Reference

### Essential Requirements for Every Feature

#### Multi-Tenancy (CRITICAL)
```php
// ✅ ALWAYS use ScopedToAcademy trait
class YourModel extends Model
{
    use ScopedToAcademy;
    
    protected $fillable = ['name', 'academy_id'];
}
```

#### Arabic/RTL Support
```blade
{{-- ✅ Proper Arabic text handling --}}
<div class="text-right" dir="rtl">
    <h1 class="font-arabic">منصة إتقان</h1>
</div>
```

#### LiveKit Integration
```javascript
// ✅ Direct SDK usage in session pages
import { Room } from 'livekit-client';
const room = new Room();
await room.connect(serverUrl, token);
```

#### Form Validation
```php
// ✅ Custom Form Request classes
class StoreSessionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'start_time' => 'required|date|after:now',
        ];
    }
    
    public function messages(): array
    {
        return [
            'title.required' => 'العنوان مطلوب',
        ];
    }
}
```

## Rule Categories

### 🔥 Critical Rules (Never Violate)
- Multi-tenancy scoping for all models
- LiveKit integration in session pages only
- Arabic/RTL support implementation
- Security and data isolation

### ⚡ Important Rules (Follow Consistently)
- Service layer pattern for business logic
- Proper error handling and logging
- Performance optimization techniques
- Testing coverage requirements

### 📋 Style Rules (Maintain Consistency)
- Naming conventions and code formatting
- UI component patterns
- Documentation standards
- Git commit message formats

## Anti-Patterns to Avoid

### ❌ Never Do These
```php
// Multi-tenancy violations
Student::withoutGlobalScopes()->get(); // Bypasses tenant scoping

// Separate meeting routes
Route::get('/meetings/{session}/join', ...); // FORBIDDEN

// Poor Arabic support
<div class="text-left">النص العربي</div> // Wrong alignment

// Fat controllers
public function store(Request $request) {
    // 50+ lines of business logic - WRONG
}
```

## Getting Started

1. **Read Base Rules**: Start with [qoder_rules.md](./qoder_rules.md) for structure understanding
2. **Laravel Standards**: Review [laravel_best_practices.md](./laravel_best_practices.md) for core patterns
3. **Feature-Specific**: Check relevant rule files based on what you're implementing
4. **Testing**: Ensure all changes follow the testing guidelines
5. **Review**: Use these rules as a checklist during code review

## Rule Updates

These rules are living documents that evolve with the project. When you encounter new patterns or best practices:

1. Document the pattern in the appropriate rule file
2. Include both positive and negative examples
3. Update related rules for consistency
4. Test the pattern with actual implementation

## Support

For questions about these rules or suggestions for improvements, refer to the specific rule files which contain detailed examples and explanations for each guideline.

---

**Remember**: These rules exist to ensure the Itqan platform maintains high quality, security, and user experience standards while supporting Arabic users and multi-tenant architecture effectively.