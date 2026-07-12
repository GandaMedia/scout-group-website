# Scout Group Website

An open-source website and administration platform for Scout Groups. It combines a public, CMS-managed website with secure tools for volunteers, and is designed to be manageable by people without technical experience.

## What it includes

- Filament administration for pages, navigation, group details, sections, leaders, news and calendar events
- Public contact and joining forms protected by Cloudflare Turnstile
- Online Scout Manager (OSM) OAuth, waiting-list synchronisation, calendars and leader imports
- Secure volunteer registration, email verification, approval, separate administrator access and two-factor authentication
- Media management, audit history and role-based permissions
- Optional project and meal-planning tools with PDF and Excel exports
- Responsive public pages built with Laravel, Inertia, Vue and Tailwind CSS

Everything is configurable from the administration panel after installation. No source-code editing is required for ordinary group details or website content.

## Technology

- PHP 8.4 and Laravel 13
- Filament 5 and Livewire 4
- Inertia 3, Vue 3, TypeScript and Tailwind CSS 4
- Pest 4, Laravel Wayfinder and Laravel Boost
- Spatie Media Library, Settings and Permissions
- Saloon for OSM integrations

## Quick start

Requirements: PHP 8.4, Composer 2, Node.js 22 and npm.

```bash
git clone https://github.com/GandaMedia/scout-group-website.git
cd scout-group-website
composer run setup
```

The setup command installs dependencies, creates `.env`, generates an application key, recreates and seeds the local database, and builds the frontend.

> `composer run setup` runs `migrate:fresh --seed`. Never use it against a shared or production database.

Register your first account at `/register`, verify its email address, then promote it locally:

```bash
php artisan users:bootstrap-admin you@example.org --force
```

Open `/admin` to set the group name, logo text, contact details, headquarters, charity details, enabled sections, homepage cards and website content.

## Local development

The default configuration uses SQLite and the database queue. Laravel Herd, Sail or `composer run dev` can be used locally.

```bash
composer install
npm ci
php artisan migrate
npm run build
```

For active development:

```bash
composer run dev
```

## Queues and scheduled tasks

Email and OSM work is queued. Keep a worker running:

```bash
php artisan queue:work --tries=3 --timeout=120
```

The scheduler runs calendar synchronisation daily and waiting-list synchronisation every five minutes:

```bash
php artisan schedule:work
```

## Online Scout Manager

OSM is optional. Without OSM credentials, the CMS, news, calendar, forms and volunteer tools continue to work normally.

To enable OSM:

1. Create an OAuth application in OSM.
2. Set `OSM_CLIENT_ID`, `OSM_CLIENT_SECRET` and `OSM_REDIRECT_URI` in the environment.
3. Use the exact callback URL `https://your-domain.example/admin/osm/callback`.
4. Sign in as an administrator and open **OSM settings**.
5. Connect OSM, map the required sections and enable only the integrations the group uses.

Calendar feed URLs are added through the Filament administration panel and are never included in this repository.

## Deploying to Laravel Cloud

Laravel Cloud is the simplest production deployment route. Import this GitHub repository into [Laravel Cloud](https://cloud.laravel.com), create a staging environment, and select PHP 8.4 and Node.js 22.

### Resources

Attach PostgreSQL, Laravel Object Storage for uploaded media, and a cache store (or use the database for a small installation).

Build commands:

```bash
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run build
```

Deploy command:

```bash
php artisan migrate --force
```

Do not run `migrate:fresh`, `db:seed` or `storage:link` during production deployment.

### Environment

```dotenv
APP_NAME="Your Scout Group"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
LOG_LEVEL=warning
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=s3
LARAVEL_PDF_DRIVER=dompdf
```

Add real mail and Turnstile credentials. Add OSM credentials only when using the integration.

### Sleep to reduce charity costs

Laravel Cloud's Sleep option can reduce costs for low-traffic community and charity websites by sleeping the application when it is idle. Enable Sleep on the web compute cluster when its wake-up delay is acceptable for the group.

Queues and scheduled tasks need deliberate configuration when Sleep is enabled. Confirm that the selected Cloud resources and worker arrangement remain available for queued email and scheduled synchronisation. Always verify current behaviour and pricing in the [Laravel Cloud documentation](https://cloud.laravel.com/docs).

### First production administrator

Do not run `DatabaseSeeder` in production. Register and verify the account normally, then run this from the Cloud Commands screen:

```bash
php artisan users:bootstrap-admin you@example.org --force
```

## Production checklist

- Configure the group profile and replace all starter content.
- Configure SMTP and confirm queued verification, approval and password-reset emails.
- Configure Turnstile allowed hostnames and object-storage CORS.
- Enable and test the scheduler.
- Test OSM against the intended sections before enabling synchronisation.
- Confirm database and object-storage backups.
- Have the Group Trustee Board review the seeded legal-policy templates.

## Quality checks

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
vendor/bin/filacheck --fix
npm run format:check
npm run lint:check
npm run types
npm run build
```

## Safeguarding and privacy

This application may store information about young people, parents, carers and volunteers. Each deploying organisation is responsible for its lawful configuration, access control, retention, safeguarding processes, privacy notices and backups. Never commit production data or credentials.

## Licence

Scout Group Website is open source under the [MIT Licence](LICENSE). Scout names, logos and other trademarks remain the property of their respective owners and are not granted by the MIT Licence.
