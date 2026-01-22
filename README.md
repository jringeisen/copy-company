# Copy Company

A content platform for creators to manage blogs, newsletters, and social media posts from a single dashboard.

## Features

- **Blog Publishing** - Write posts with a rich TipTap editor, schedule or publish immediately
- **Newsletter Sending** - Send posts as newsletters to subscribers with batch processing for scale
- **Subscriber Management** - Import/export subscribers, track confirmations and unsubscribes
- **Content Sprints** - AI-powered content idea generation
- **Social Posts** - Create and schedule social media content derived from blog posts
- **Multi-brand Support** - Each user can create brands with custom domains and branding

## Tech Stack

- **Backend**: Laravel 12, PHP 8.4
- **Frontend**: Vue 3, Inertia.js v2, TipTap Editor
- **Styling**: Tailwind CSS v4
- **Auth**: Laravel Fortify
- **Queues**: Laravel Horizon
- **Testing**: Pest PHP

## Requirements

- PHP 8.4+
- Node.js 18+
- Composer
- SQLite/MySQL/PostgreSQL

## Installation

```bash
# Clone the repository
git clone <repository-url>
cd copy-company

# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate
php artisan db:seed

# Build assets
npm run build
```

## Development

```bash
# Start the development server (if not using Laravel Herd)
php artisan serve

# Start Vite dev server for hot reloading
npm run dev

# Or run both together
composer run dev

# Process queued jobs
php artisan queue:work

# Start Horizon dashboard
php artisan horizon
```

## Testing

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/Controllers/PostControllerTest.php

# Run tests matching a pattern
php artisan test --filter="bulk delete"

# Run tests in parallel
php artisan test --parallel
```

## Code Quality

```bash
# Format PHP code
vendor/bin/pint

# Format only changed files
vendor/bin/pint --dirty
```

## Architecture

### Domain Model

```
User
 └── Brand (central entity)
      ├── Post → NewsletterSend, SocialPost
      ├── Subscriber
      └── ContentSprint
```

### Key Concepts

- **Brand**: The central entity. All content belongs to a brand. Users access their current brand via `auth()->user()->currentBrand()`.
- **Post**: Blog posts with TipTap JSON content. Can be published to blog, sent as newsletter, or both.
- **ContentSprint**: AI-generated batches of content ideas that can be converted to draft posts.
- **NewsletterSend**: Tracks email campaign sending with batch job processing for scalability.

### Public URLs

- Blog index: `/blog/{brand-slug}`
- Blog post: `/blog/{brand-slug}/{post-slug}`

## License

Proprietary - All rights reserved.
