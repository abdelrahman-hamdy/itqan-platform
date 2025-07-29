# Itqan Educational Platform

A comprehensive multi-tenant SaaS platform for Quran memorization and academic learning, built with Laravel 11.

## 🌟 Project Overview

Itqan is a sophisticated educational platform that enables Islamic academies to deliver:
- **Quran Memorization Programs** (Individual & Group Circles)
- **Live Academic Tutoring** (Private Lessons & Interactive Courses)
- **Recorded Course Catalog** with secure content delivery
- **Real-time Communication** with role-based restrictions
- **Assessment System** with quizzes and homework management
- **Multi-Gateway Payments** supporting MENA and GCC regions

### Key Features
- 🏢 **Multi-Tenant Architecture** - Each academy operates independently with full data isolation
- 🎛️ **Multi-Panel UI System** - Specialized interfaces optimized for each user role
- 👥 **Advanced Registration System** - Role-specific forms with teacher approval workflow
- 📱 **Automatic Parent Accounts** - WhatsApp/SMS notifications for seamless onboarding
- 💬 **Enhanced Chat System** - Role-restricted communication with supervisor monitoring
- 📱 **Mobile-First Design** - Responsive interfaces with dedicated mobile layouts
- 🔒 **Enterprise Security** - Tenant isolation and comprehensive data protection
- 💳 **Flexible Billing** - Multiple payment gateways supporting regional currencies
- 📊 **Comprehensive Analytics** - Progress tracking and business intelligence dashboards

## 🎛️ Multi-Panel Interface Architecture

### Power User Panels (Filament-Based)
- **Super-Admin Panel** (`/admin`) - Global system management across all academies
- **Academy Admin Panel** (`/{academy}/panel`) - Tenant-scoped academy administration  
- **Teacher Panel** (`/{academy}/teacher-panel`) - Specialized teaching tools and scheduling
- **Supervisor Panel** (`/{academy}/supervisor-panel`) - Quality monitoring and oversight

### End User Areas (Blade + Livewire)
- **Student Area** (`/{academy}/student`) - Simple, mobile-optimized dashboard
- **Parent Area** (`/{academy}/parent`) - Family-oriented interface for multiple children

> **Design Philosophy**: Each role gets an interface specifically designed for their needs and complexity level.

## 🛠 Technology Stack

- **Backend**: Laravel 11, PHP 8.3, MySQL 8, Redis 7
- **Frontend**: Blade + Livewire 3, TailwindCSS 3, Filament 4
- **Real-time**: Soketi/Pusher WebSockets
- **Storage**: DigitalOcean Spaces (S3-compatible)
- **Payments**: Paymob (MENA) + Tap Payments (GCC)
- **Integrations**: Google Calendar API, FCM, Email Services

## 📋 Quick Start

### Prerequisites
- PHP 8.3+ with required extensions
- Composer 2.7+
- Node.js 20+
- MySQL 8.0+
- Redis 7.0+

### Installation

1. **Clone and Install Dependencies**
```bash
git clone <repository-url> itqan-platform
cd itqan-platform
composer install
npm install
```

2. **Environment Configuration**
```bash
cp .env.example .env
php artisan key:generate
```

3. **Database Setup**
```bash
# Configure database connection in .env
php artisan migrate
php artisan db:seed --class=TenantSeeder
```

4. **Build Assets**
```bash
npm run build
# or for development
npm run dev
```

5. **Start Development Server**
```bash
php artisan serve
```

## 📚 Documentation

### Core Documentation
- [📖 PROJECT_OVERVIEW.MD](PROJECT_OVERVIEW.MD) - Original comprehensive project specification with user roles and features
- [📋 TECHNICAL_PLAN.MD](TECHNICAL_PLAN.MD) - Original technical implementation plan with package requirements
- [🏗 SYSTEM_ARCHITECTURE.md](SYSTEM_ARCHITECTURE.md) - Updated technical architecture with multi-panel UI design
- [🗺 DEVELOPMENT_ROADMAP.md](DEVELOPMENT_ROADMAP.md) - 26-week development plan updated for multi-panel system
- [⚙️ TECHNICAL_SPECIFICATIONS.md](TECHNICAL_SPECIFICATIONS.md) - Comprehensive database schemas, APIs, and UI implementation

### Multi-Panel UI Architecture Documentation
- [🎛️ UI_ARCHITECTURE_PROPOSAL.md](UI_ARCHITECTURE_PROPOSAL.md) - Complete multi-panel interface specification with role-based design
- [📋 UI_PROPOSAL_IMPLEMENTATION_PLAN.md](UI_PROPOSAL_IMPLEMENTATION_PLAN.md) - Detailed 8-week implementation timeline
- [📊 ANALYSIS_RESPONSE_SUMMARY.md](ANALYSIS_RESPONSE_SUMMARY.md) - Design analysis and decision rationale
- [✅ PROJECT_COMPLETE_SUMMARY.md](PROJECT_COMPLETE_SUMMARY.md) - Final comprehensive implementation summary

### Setup and Configuration
- [⚙️ SETUP_COMPLETE_SUMMARY.md](SETUP_COMPLETE_SUMMARY.md) - Laravel 11 + Filament 4 setup completion summary
- [📋 .taskmaster/tasks/tasks.json](.taskmaster/tasks/tasks.json) - Complete TaskMaster development plan with 20 main tasks

### Documentation System
- [📚 DOCUMENTATION_INDEX.md](DOCUMENTATION_INDEX.md) - **Complete documentation cross-reference and consistency guide**

### Quick Reference
- **Default Admin**: Access `/admin` with seeded super-admin credentials
- **Default Tenant**: "itqan-academy" serves as the root domain academy
- **Multi-Tenancy**: Subdomains automatically resolve to respective academies
- **File Storage**: Tenant-isolated paths in DigitalOcean Spaces

## 👥 User Roles & Permissions

| Role | Scope | Key Responsibilities |
|------|--------|---------------------|
| **Super-Admin** | Global | Create academies, global oversight, financial dashboard |
| **Academy Admin** | Per Academy | Configure branding, manage teachers, set pricing |
| **Supervisor** | Assigned Entities | Quality monitoring, chat oversight, support |
| **Teacher** | Per Academy | Conduct sessions, assign homework, create quizzes |
| **Student** | Per Academy | Attend sessions, submit assignments, take quizzes |
| **Parent** | Per Academy | Monitor child progress, handle payments, communicate |

## 📝 Advanced Registration System

### Registration Flow Overview
- **Teacher Registration**: Multi-step form with Quran vs Academic teacher choice
- **Student Registration**: Includes parent phone for automatic parent account creation
- **Teacher Approval**: Admin reviews profiles, sets pricing, manually approves
- **Parent Notifications**: WhatsApp/SMS notifications with login credentials

### Registration Features
- **Role-Specific Forms**: Tailored fields for each user type
- **Academy-Specific Data**: Grade levels and subjects managed per academy
- **Multi-Channel Notifications**: WhatsApp (free/paid options) + SMS fallback
- **Approval Workflow**: Complete teacher review and pricing system

## 🔧 Development Setup

### Environment Variables
Key environment variables to configure:

```bash
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=itqan_platform
DB_USERNAME=root
DB_PASSWORD=

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# File Storage (DigitalOcean Spaces)
AWS_ACCESS_KEY_ID=your_spaces_key
AWS_SECRET_ACCESS_KEY=your_spaces_secret
AWS_DEFAULT_REGION=nyc3
AWS_BUCKET=itqan-platform
AWS_ENDPOINT=https://nyc3.digitaloceanspaces.com

# Payment Gateways
PAYMOB_API_KEY=your_paymob_key
TAP_SECRET_KEY=your_tap_key

# Google Services
GOOGLE_CALENDAR_CLIENT_ID=your_google_client_id
GOOGLE_CALENDAR_CLIENT_SECRET=your_google_client_secret

# Push Notifications
FCM_SERVER_KEY=your_fcm_server_key
FCM_SENDER_ID=your_fcm_sender_id
```

### Local Development

1. **Database Seeding**
```bash
php artisan db:seed --class=TenantSeeder
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=DemoDataSeeder
```

2. **Queue Worker**
```bash
php artisan queue:work
```

3. **WebSocket Server** (Production)
```bash
# Install Soketi
npm install -g @soketi/soketi
soketi start
```

## 🚀 Deployment

### Production Checklist
- [ ] Configure production database
- [ ] Set up Redis cluster
- [ ] Configure DigitalOcean Spaces
- [ ] Set up payment gateway credentials
- [ ] Configure Google Calendar API
- [ ] Set up FCM for push notifications
- [ ] Configure email service
- [ ] Set up SSL certificates
- [ ] Configure queue workers
- [ ] Set up monitoring and logging

### CI/CD Pipeline
The project includes GitHub Actions for automated deployment:
- Code quality checks (PHPStan, Pint)
- Automated testing (PHPUnit, Pest)
- Asset building
- Zero-downtime deployment

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Generate coverage report
php artisan test --coverage
```

## 📊 Performance

### Optimization Features
- **Database Indexing**: Strategic composite indexes for multi-tenant queries
- **Redis Caching**: Multi-level caching for tenant settings and user permissions
- **Queue Processing**: Background job processing for heavy operations
- **CDN Integration**: Global content delivery for static assets
- **Query Optimization**: Eager loading and query result caching

### Monitoring
- Laravel Horizon for queue monitoring
- Laravel Telescope for development debugging
- Spatie Health for system health checks
- Custom Filament widgets for business metrics

## 🔒 Security

### Security Features
- **Multi-Tenant Isolation**: Complete data separation between academies
- **Role-Based Access Control**: Granular permissions system
- **Signed URLs**: Secure file access with expiration
- **Input Validation**: Comprehensive request validation
- **CSRF Protection**: Built-in Laravel CSRF protection
- **SQL Injection Prevention**: Eloquent ORM with parameter binding

### Compliance
- Payment security through certified gateways
- Data encryption for sensitive information
- Audit logging for all critical operations
- Secure file storage with access controls

## 🌍 Internationalization

- **RTL Support**: Full right-to-left layout for Arabic
- **Multi-Language**: Arabic and English supported
- **Timezone Handling**: Per-academy timezone configuration
- **Currency Support**: Multiple currencies with per-academy settings

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Code Standards
- Follow PSR-12 coding standards
- Use Laravel best practices
- Write comprehensive tests
- Document all public methods
- Follow semantic versioning

## 📄 License

This project is proprietary software. All rights reserved.

## 🆘 Support

For technical support or questions:
- Create an issue in the repository
- Contact the development team
- Check the documentation files

---

**Built with ❤️ for the Islamic education community**
