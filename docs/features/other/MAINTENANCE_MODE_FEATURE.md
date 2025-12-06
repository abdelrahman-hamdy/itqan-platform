# Maintenance Mode Feature

## Overview
A comprehensive maintenance mode feature has been implemented for the multi-tenant academy system. This allows each academy to independently enable maintenance mode with custom messages while respecting the multi-tenant architecture.

## Components Implemented

### 1. Middleware: `CheckMaintenanceMode`
- **Location**: `app/Http/Middleware/CheckMaintenanceMode.php`
- **Purpose**: Checks if the current academy is in maintenance mode and redirects to maintenance page
- **Features**:
  - Multi-tenant aware (safely checks current academy from request/app container)
  - Admin bypass (super_admin, admin, supervisor roles can access)
  - Academy admin bypass (academy owner can access their own academy)
  - Excluded paths (admin panel, API endpoints, assets, etc.)
  - JSON response for AJAX requests
  - **Fixed**: Safe handling of `current_academy` resolution to prevent "Target class does not exist" errors

### 2. Maintenance View
- **Location**: `resources/views/errors/maintenance.blade.php`
- **Features**:
  - Beautiful, modern design with RTL support
  - Uses academy brand colors dynamically
  - Shows custom maintenance message or default
  - Progress animation and status indicators
  - Contact information display
  - Auto-refresh every 60 seconds
  - Mobile responsive

### 3. Filament Integration
- **Updated**: `app/Filament/Resources/AcademyManagementResource.php`
- **Added Fields**:
  - Maintenance mode toggle (reactive)
  - Custom maintenance message textarea (appears when maintenance is enabled)
  - Message stored in `academic_settings` JSON field

### 4. Localization
- **Arabic**: `lang/ar/messages.php`
- **English**: `lang/en/messages.php`
- **Includes**: All maintenance-related messages and labels

## How to Use

### Enable Maintenance Mode

1. **Via Filament Admin Panel**:
   - Navigate to "إدارة الأكاديميات" (Academy Management)
   - Edit the target academy
   - In "الإعدادات" (Settings) section, toggle "وضع الصيانة" (Maintenance Mode)
   - Optionally add a custom message in "رسالة الصيانة" field
   - Save changes

2. **Via Code**:
   ```php
   $academy = Academy::find(1);
   $academy->maintenance_mode = true;
   $academy->academic_settings = [
       'maintenance_message' => 'Custom maintenance message here'
   ];
   $academy->save();
   ```

3. **Via Artisan Command** (for testing):
   ```bash
   php artisan test:maintenance
   ```

### Who Can Bypass Maintenance Mode?

- **Super Admins**: Full system access
- **Admins**: Can access admin panel
- **Supervisors**: Can access their assigned areas
- **Academy Owner**: The admin assigned to the specific academy
- **All Others**: See maintenance page

### Excluded Paths

The following paths are accessible during maintenance:
- `/admin/*` - Admin panel
- `/filament/*` - Filament resources
- `/livewire/*` - Livewire components
- `/login`, `/logout` - Authentication
- `/api/webhooks/*` - Webhook endpoints
- Static assets (CSS, JS, images)

## Testing

1. **Backend Test**:
   ```bash
   php artisan test:maintenance
   ```

2. **Frontend Test**:
   - Enable maintenance mode for an academy
   - Visit the academy's subdomain
   - Verify maintenance page appears
   - Login as admin and verify bypass works

## Database Fields

- **Table**: `academies`
- **Fields**:
  - `maintenance_mode` (boolean) - Maintenance status
  - `academic_settings` (JSON) - Contains `maintenance_message` key

## Security Considerations

- Maintenance mode is checked after tenant resolution
- Admin users can always bypass to manage the system
- API endpoints remain accessible for critical operations
- Webhooks continue to function during maintenance

## Multi-Tenant Considerations

- Each academy has independent maintenance mode
- Main domain (no subdomain) is not affected if no academy is resolved
- Academy-specific brand colors and information are used in maintenance page
- Maintenance status is checked per-request based on current tenant

## Troubleshooting

### Error: "Target class [current_academy] does not exist"
**Solution**: The middleware now safely checks if `current_academy` exists in the app container before trying to access it:
```php
// Safe resolution of current academy
$academy = $request->get('academy');
if (!$academy && app()->has('current_academy')) {
    $academy = app('current_academy');
}
```

## Future Enhancements

Consider adding:
- Scheduled maintenance (start/end times)
- IP whitelist for specific users
- Maintenance mode expiry
- Email notifications when entering/exiting maintenance
- Maintenance history logging