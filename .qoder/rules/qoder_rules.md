---
trigger: always_on
alwaysApply: true
---
# Qoder Rules Guidelines for Itqan Platform

## Required Rule Structure

- **File Format**: Use Markdown (.md) format for better readability
- **Clear Documentation**: Each rule should have clear descriptions and examples
- **Code Examples**: Use language-specific code blocks with ✅ DO and ❌ DON'T patterns
- **Cross-References**: Link related rules and files when relevant

## Rule Content Guidelines

- Start with high-level overview and context
- Include specific, actionable requirements
- Show examples of correct implementation patterns
- Reference existing codebase when possible
- Keep rules DRY by referencing other rule files

## Code Examples Format

```php
// ✅ DO: Show good examples with clear context
class GoodExample extends Model
{
    use ScopedToAcademy;
    
    protected $fillable = ['name', 'description'];
}

// ❌ DON'T: Show anti-patterns to avoid
class BadExample extends Model
{
    // Missing tenant scoping - violates multi-tenancy rules
    protected $fillable = ['*']; // Dangerous mass assignment
}
```

## Rule Categories

1. **Core Framework Rules** - Laravel, Filament, Livewire patterns
2. **Architecture Rules** - Multi-tenancy, service layer, design patterns
3. **UI/UX Rules** - Arabic support, accessibility, responsive design
4. **Integration Rules** - LiveKit, Google Calendar, payment gateways
5. **Quality Rules** - Testing, security, performance standards

## Rule Maintenance

- Update rules when new patterns emerge in the codebase
- Add examples from actual implementation
- Remove outdated patterns and deprecated approaches
- Cross-reference related rules for consistency
- Document breaking changes and migration paths

## Best Practices

- Use bullet points for clarity and scanning
- Keep descriptions concise but comprehensive
- Include both positive and negative examples
- Reference actual code files over theoretical examples
- Use consistent formatting across all rule files
- Organize rules by complexity (simple → advanced)