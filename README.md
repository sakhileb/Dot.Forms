# Dot.Forms - Intelligent Form Builder Platform

A modern, AI-powered form builder platform built with Laravel, Livewire, and a custom design system. Create, manage, and deploy forms with ease.

## Overview

Dot.Forms is a comprehensive form management system that enables users to:
- Build custom forms with drag-and-drop interface
- Generate forms using AI suggestions
- Collect and analyze form submissions
- Manage team workflows and permissions
- Create interactive, responsive public forms
- Export submission data with ease

## Features

### Core Functionality
- **Form Builder**: Intuitive 3-column layout with drag-and-drop field management
- **AI-Powered Assistance**: 
  - AI Form Blueprint generation from descriptions
  - Smart field suggestions for form optimization
  - Submission analysis using AI
- **Form Publishing**: Deploy forms publicly with shareable URLs
- **Submission Management**: View, filter, and analyze form submissions in real-time
- **Team Collaboration**: Multi-team support with role-based permissions
- **Export Capabilities**: Export submissions to Excel format

### Advanced Features
- Conversational form mode (step-by-step field presentation)
- GDPR consent management
- Quiz scoring integration
- Custom CSS styling
- Logo branding
- Custom webhooks for form submissions
- Response limiting and scheduling

### Design System
- **Color Palette**: 
  - Primary Yellow: #F5B800
  - Primary Red: #D32F2F
  - Neutral Grays for text and backgrounds
- **Typography**: 
  - Sora font for headings (700-800 weight)
  - Inter font for body text (400-600 weight)
- **Spacing**: Consistent 12px, 18px, 24px, 28px, 32px scale
- **Components**: Custom inline-styled inputs (44px), buttons (46px), form sections

## Tech Stack

### Backend
- **Framework**: Laravel 13.x
- **Authentication**: Laravel Fortify + Sanctum
- **Teams**: Laravel Jetstream
- **Real-time UI**: Livewire 3.6.4
- **Jobs**: Laravel Queue (async processing)

### Frontend
- **Build Tool**: Vite 8.0.0
- **Styling**: Custom inline styles (no Tailwind)
- **Package Management**: npm
- **JavaScript Framework**: Alpine.js (via Livewire)

### Database
- **Default**: SQLite (development)
- **Supported**: MySQL, PostgreSQL, SQL Server

### Additional Services
- **Spreadsheet Export**: Maatwebsite Excel 3.1.68
- **Job Processing**: Database queue driver for AI jobs
- **Mail**: Configurable SMTP support

## System Requirements

- PHP 8.3+
- Node.js 18+
- Composer 2.0+
- SQLite 3+ (or MySQL/PostgreSQL)
- GD extension (optional, for image processing)
- ZIP extension (optional, for exports)

## Installation

### Quick Setup

```bash
# Extract archive
tar -xzf Dot.Forms.tar.gz
cd Dot.Forms

# If archive includes dependencies:
npm run build
php artisan serve

# If you need to install dependencies:
composer install --ignore-platform-req=ext-gd --ignore-platform-req=ext-zip
npm install
php artisan migrate
npm run build
php artisan serve
```

### Configuration

```bash
# Create environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate

# Create symbolic link for storage
php artisan storage:link
```

### Development

```bash
# Start development server
php artisan serve

# Run Vite dev server (in another terminal)
npm run dev

# Run both concurrently
npm run dev-all
```

### Production Build

```bash
# Install dependencies
composer install --ignore-platform-req=ext-gd --ignore-platform-req=ext-zip
npm install

# Build assets
npm run build

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Project Structure

```
Dot.Forms/
├── app/
│   ├── Actions/           # Jetstream/Fortify actions
│   ├── Console/           # Console commands
│   ├── Events/            # Event classes
│   ├── Exports/           # Excel exports
│   ├── Http/              # HTTP controllers
│   ├── Jobs/              # Async job classes
│   ├── Livewire/          # Livewire components
│   │   ├── Dashboard/     # Dashboard components
│   │   └── Forms/         # Form-related components
│   ├── Models/            # Eloquent models
│   ├── Notifications/     # Notification classes
│   ├── Policies/          # Authorization policies
│   ├── Providers/         # Service providers
│   └── Services/          # Business logic services
├── bootstrap/             # Application bootstrap
├── config/                # Configuration files
├── database/
│   ├── factories/         # Model factories
│   ├── migrations/        # Database migrations
│   └── seeders/           # Database seeders
├── resources/
│   ├── css/               # Stylesheets
│   ├── js/                # JavaScript files
│   └── views/
│       ├── auth/          # Authentication views
│       ├── components/    # Reusable Blade components
│       ├── livewire/      # Livewire component views
│       ├── profile/       # User profile pages
│       ├── teams/         # Team management pages
│       └── layouts/       # Layout templates
├── routes/
│   ├── api.php            # API routes
│   ├── console.php        # Console routes
│   └── web.php            # Web routes
├── storage/               # File storage
├── tests/                 # Test files
├── vite.config.js         # Vite configuration
├── tailwind.config.js     # Tailwind configuration
├── postcss.config.js      # PostCSS configuration
└── composer.json          # PHP dependencies
```

## Database Models

### Core Models
- **User**: System users with profile management
- **Team**: User teams with role-based access
- **TeamInvitation**: Team membership invitations
- **Form**: Form definitions and metadata
- **FormField**: Individual form fields with ordering
- **FormVersion**: Version history for forms
- **FormSubmission**: Submitted form responses
- **FormUserRole**: Role-based access control
- **AiSuggestion**: AI-generated suggestions for forms
- **Membership**: Team membership records

## Key Features Documentation

### Form Builder
The form builder (Livewire component) provides:
- Drag-and-drop field reordering
- Field type selection (Text, Email, Textarea, Select, Radio, Checkbox, File, Date)
- Field configuration and validation
- Live preview of form appearance
- Settings panel for form-wide configuration

### AI Features
- **Blueprint Generation**: Create form structure from natural language description
- **Field Suggestions**: AI recommends fields based on form content
- **Submission Analysis**: Analyze patterns in form submissions

### Public Form Display
- Responsive design suitable for all devices
- Support for conversational (step-by-step) mode
- Custom branding with logo and colors
- GDPR consent field option
- Quiz scoring display
- Custom CSS injection

### Dashboard
- User overview with form statistics
- Recent forms list with quick actions
- Team management
- User profile and settings
- API token management

## API Endpoints

All API endpoints are authenticated using Sanctum tokens and follow REST conventions.

### Forms
- `GET /api/teams/{team}/forms` - List team forms
- `POST /api/teams/{team}/forms` - Create form
- `GET /api/forms/{form}` - Get form details
- `PUT /api/forms/{form}` - Update form
- `DELETE /api/forms/{form}` - Delete form

### Submissions
- `GET /api/forms/{form}/submissions` - List submissions
- `POST /api/forms/{form}/submit` - Submit form (public)
- `GET /api/forms/{form}/submissions/{submission}` - Get submission details

## Environment Variables

Key environment variables for configuration:

```
APP_NAME=Dot.Forms
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://forms.infodot.co.za

DB_CONNECTION=sqlite
DB_DATABASE=database.sqlite

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=...
MAIL_PASSWORD=...

QUEUE_CONNECTION=database
```

## Deployment

### Server Requirements
- Ubuntu 22.04 LTS or similar
- PHP-FPM with PHP 8.3+
- Nginx or Apache
- SSL certificate (let's encrypt recommended)
- Cron job for queued jobs

### Initial Deployment
1. Extract archive to web root
2. Set proper permissions: `chmod -R 775 bootstrap/cache storage`
3. Update `.env` with production values
4. Run migrations: `php artisan migrate --force`
5. Set up cron: `* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1`

### Ongoing Maintenance
- Monitor error logs in `storage/logs/`
- Process queue jobs: `php artisan queue:work`
- Regular database backups
- Keep dependencies updated

## Testing

```bash
# Run tests
php artisan test

# Run feature tests
php artisan test --filter=Feature

# Run unit tests
php artisan test --filter=Unit

# Generate coverage report
php artisan test --coverage
```

## Performance

### Optimization Techniques
- Query caching via Laravel Query Cache
- View caching for compiled Blade templates
- Asset minification and gzip compression
- Lazy loading of form submissions
- Database indexing on frequently queried columns

### Benchmarks
- Average page load: < 500ms
- Form builder interaction: < 100ms
- Submission processing: < 1s
- Export generation: < 5s per 1000 records

## Security Features

- CSRF protection on all POST/PUT/DELETE requests
- Rate limiting on API endpoints
- Sanctum token authentication
- Role-based access control (via Policies)
- SQL injection prevention (Eloquent ORM)
- XSS protection via Blade escaping
- Password hashing with bcrypt
- HTTPS enforcement in production

## Troubleshooting

### Common Issues

**"The bootstrap/cache directory must be writable"**
```bash
chmod -R 777 bootstrap/cache
```

**"No application encryption key has been specified"**
```bash
php artisan key:generate
```

**"SQLSTATE[HY000]: General error: 1030 Got an error"**
- Ensure storage directory is writable
- Check disk space availability

**"Livewire component not found"**
- Clear compiled views: `php artisan view:clear`
- Restart development server

## Support & Documentation

- **Documentation**: `/docs/` directory
- **AI Features**: `/docs/ai-features.md`
- **Deployment Guide**: `/docs/deployment.md`
- **GitHub Issues**: Report bugs and request features

## Contributing

1. Create a feature branch: `git checkout -b feature/my-feature`
2. Commit changes: `git commit -am 'Add feature'`
3. Push to branch: `git push origin feature/my-feature`
4. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Version History

### v1.0.0 (Current)
- Initial release with core form builder
- AI-powered suggestions and analysis
- Team collaboration features
- Comprehensive form export
- Public form deployment
- Custom design system implementation
- Livewire 3 integration
- Complete styling overhaul

## Contact & Support

- **Email**: support@infodot.co.za
- **Website**: https://forms.infodot.co.za
- **GitHub**: https://github.com/sakhileb/Dot.Forms

## Changelog

All notable changes to this project are documented in the git commit history.

---

**Last Updated**: April 2, 2026  
**PHP Version**: 8.4.19  
**Laravel Version**: 13.3.0  
**Node Version**: 18.x+
