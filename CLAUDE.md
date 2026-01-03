# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Wordsmith is a content platform for creators to manage blogs, newsletters, and social media posts. Users create a **Brand** which serves as the central entity - all content (posts, subscribers, social posts) belongs to a brand.

## Common Commands

```bash
# Development
npm run dev              # Start Vite dev server
php artisan serve        # Start Laravel server (or use Herd)
php artisan queue:work   # Process queued jobs
php artisan horizon      # Start Horizon queue dashboard

# Testing
php artisan test                                    # Run all tests
php artisan test tests/Feature/ExampleTest.php     # Run single file
php artisan test --filter="test name"              # Filter by name
php artisan test --parallel                        # Run in parallel

# Code Quality
vendor/bin/pint --dirty  # Format changed PHP files

# Database
php artisan migrate      # Run migrations
php artisan db:seed      # Seed database
```

## Architecture

### Domain Model Hierarchy

```
User
 └── Brand (central entity, accessed via auth()->user()->currentBrand())
      ├── Post (blog posts with TipTap JSON content)
      │    ├── NewsletterSend (email campaign tracking)
      │    └── SocialPost (social media derivatives)
      ├── Subscriber (newsletter subscribers)
      └── ContentSprint (AI-generated content ideas)
```

### Key Patterns

- **Multi-tenant via Brand**: All content is scoped to a brand. Controllers call `auth()->user()->currentBrand()` to get the active brand.
- **API Resources with Inertia**: Use `->resolve()` when passing Resource collections to Inertia views to convert to plain arrays.
- **TipTap Editor**: Post content is stored as TipTap JSON in the `content` column (cast as array), with HTML in `content_html`.
- **Job Batching for Newsletters**: `ProcessNewsletterSend` creates batches of `SendNewsletterToSubscriber` jobs for scalable email delivery.

### Public Routes

Public blog is accessible at `/@{brand-slug}` and `/@{brand-slug}/{post-slug}`.

### Background Jobs

| Job | Purpose | Queue |
|-----|---------|-------|
| `ProcessNewsletterSend` | Orchestrates batch email sending | default |
| `SendNewsletterToSubscriber` | Sends single email | newsletters |
| `GenerateContentSprint` | AI content idea generation | default |
| `PublishScheduledPosts` | Publishes due scheduled posts | default |

### Scheduled Commands

Defined in `routes/console.php`:
- `posts:publish-scheduled` - Every minute
- `newsletters:process-scheduled` - Every minute

## Tech Stack

- **Backend**: Laravel 12, PHP 8.4
- **Frontend**: Vue 3, Inertia.js v2, Tailwind v4
- **Editor**: TipTap (ProseMirror-based)
- **Auth**: Laravel Fortify
- **Queues**: Laravel Horizon
- **Testing**: Pest PHP

---

<laravel-boost-guidelines>

## Laravel Boost MCP Tools

Laravel Boost provides powerful tools for this application:
- `search-docs` - Search version-specific Laravel ecosystem documentation
- `database-query` - Execute read-only SQL queries
- `database-schema` - View table structure
- `tinker` - Execute PHP in application context
- `get-absolute-url` - Generate correct URLs for the user
- `list-routes` - View registered routes
- `list-artisan-commands` - View available Artisan commands

## Conventions

- Follow existing code conventions in sibling files
- Use descriptive names: `isRegisteredForDiscounts`, not `discount()`
- Check for existing components before creating new ones
- Run `vendor/bin/pint --dirty` before finalizing changes

## PHP

- Use PHP 8 constructor property promotion
- Always use explicit return type declarations
- Always use curly braces for control structures
- Prefer PHPDoc blocks over inline comments
- Enum keys should be TitleCase

## Laravel

- Use `php artisan make:*` commands with `--no-interaction`
- Create Form Request classes for validation (not inline)
- Use Eloquent relationships over raw queries
- Avoid `DB::` - prefer `Model::query()`
- Use eager loading to prevent N+1 queries
- Use `config()` not `env()` outside config files
- Use named routes with `route()` function

## Testing (Pest)

- Use `test()` or `it()` functions with `expect()` assertions
- Use factories with custom states for model creation
- Run minimal tests with `--filter` while developing
- Tests use `RefreshDatabase` trait

## Inertia + Vue

- Pages in `resources/js/Pages/`
- Use `router.visit()` or `<Link>` for navigation
- Use `useForm` helper or `<Form>` component for forms
- Add skeleton loading states for deferred props

## Tailwind v4

- Configuration via CSS `@theme` directive
- Use `@import "tailwindcss"` not `@tailwind` directives
- Use gap utilities for spacing, not margins
- Use `shrink-*` not `flex-shrink-*`, `grow-*` not `flex-grow-*`

## Laravel Fortify

- Config in `config/fortify.php`
- Actions in `app/Actions/Fortify/`
- Use `search-docs` before implementing auth features

</laravel-boost-guidelines>
