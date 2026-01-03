# Content Platform - Claude Code Development Prompt

## Project Overview

Build a creator-first content platform where users write once and publish everywhere (blog, newsletter, social media). The platform should feel like the user's own home on the internet, with AI assistance available as an optional collaborator—not a replacement for their voice.

**Core Philosophy:**
- Creator-first, AI-assisted (not AI-first)
- One source of truth (the Post) that flows to all channels
- Users own their content, data, and audience
- AI helps as much or as little as the user wants
- Substack-inspired: the blog post IS the newsletter

## Tech Stack

- **Backend:** Laravel 11+
- **Frontend:** Vue 3 with Composition API
- **Routing:** Inertia.js
- **Styling:** Tailwind CSS
- **Database:** MySQL
- **Queue:** Redis with Laravel Horizon
- **AI:** Anthropic Claude API (for content assistance)
- **File Storage:** S3-compatible (local disk for development)
- **Email:** Laravel Mail with support for built-in sending + ESP integrations

## Project Setup

Initialize a new Laravel project with the following:

```bash
laravel new content-platform
cd content-platform

# Install frontend dependencies
composer require inertiajs/inertia-laravel
composer require laravel/sanctum
composer require laravel/horizon
composer require laravel/cashier

npm install @inertiajs/vue3 vue @tailwindcss/typography @tiptap/vue-3 @tiptap/starter-kit @tiptap/extension-placeholder
```

## Database Schema

### Core Tables

```php
// database/migrations/create_brands_table.php
Schema::create('brands', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    
    // Brand identity
    $table->string('name');
    $table->string('slug')->unique();
    $table->string('tagline')->nullable();
    $table->text('description')->nullable();
    $table->string('logo_path')->nullable();
    $table->string('favicon_path')->nullable();
    
    // Custom domain
    $table->string('custom_domain')->nullable()->unique();
    $table->boolean('domain_verified')->default(false);
    
    // Branding colors
    $table->string('primary_color')->default('#6366f1');
    $table->string('secondary_color')->default('#1f2937');
    
    // Industry for AI context
    $table->string('industry')->nullable();
    
    // Voice/tone settings for AI
    $table->json('voice_settings')->nullable();
    // Structure: { tone: 'warm', style: 'conversational', sample_texts: [] }
    
    // Newsletter settings
    $table->enum('newsletter_provider', ['built_in', 'mailchimp', 'flodesk', 'convertkit', 'klaviyo'])->default('built_in');
    $table->json('newsletter_credentials')->nullable(); // Encrypted ESP API keys
    
    // Social connections
    $table->json('social_connections')->nullable();
    // Structure: { instagram: { access_token, user_id }, pinterest: {...} }
    
    $table->timestamps();
});

// database/migrations/create_posts_table.php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('brand_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    
    // Core content
    $table->string('title');
    $table->string('slug');
    $table->text('excerpt')->nullable();
    $table->longText('content'); // Store as JSON for TipTap/structured content
    $table->longText('content_html')->nullable(); // Rendered HTML version
    $table->string('featured_image')->nullable();
    
    // Publishing status
    $table->enum('status', ['draft', 'scheduled', 'published', 'archived'])->default('draft');
    $table->timestamp('published_at')->nullable();
    $table->timestamp('scheduled_at')->nullable();
    
    // Distribution controls (user chooses each)
    $table->boolean('publish_to_blog')->default(true);
    $table->boolean('send_as_newsletter')->default(true);
    $table->boolean('generate_social')->default(true);
    
    // SEO
    $table->string('seo_title')->nullable();
    $table->text('seo_description')->nullable();
    $table->json('tags')->nullable();
    
    // AI tracking (transparency for user)
    $table->integer('ai_assistance_percentage')->default(0);
    
    // Analytics cache
    $table->integer('view_count')->default(0);
    $table->integer('email_open_count')->default(0);
    $table->integer('email_click_count')->default(0);
    
    $table->unique(['brand_id', 'slug']);
    $table->timestamps();
});

// database/migrations/create_social_posts_table.php
Schema::create('social_posts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('post_id')->nullable()->constrained()->onDelete('cascade');
    $table->foreignId('brand_id')->constrained()->onDelete('cascade');
    
    // Platform details
    $table->enum('platform', ['instagram', 'facebook', 'pinterest', 'linkedin', 'tiktok', 'twitter']);
    $table->enum('format', ['feed', 'story', 'reel', 'carousel', 'pin', 'thread'])->default('feed');
    
    // Content
    $table->text('content');
    $table->json('media')->nullable(); // Array of image/video paths
    $table->json('hashtags')->nullable();
    $table->string('link')->nullable();
    
    // Status
    $table->enum('status', ['draft', 'queued', 'scheduled', 'published', 'failed'])->default('draft');
    $table->timestamp('scheduled_at')->nullable();
    $table->timestamp('published_at')->nullable();
    
    // External tracking
    $table->string('external_id')->nullable();
    $table->json('analytics')->nullable();
    $table->text('failure_reason')->nullable();
    
    // AI tracking
    $table->boolean('ai_generated')->default(false);
    $table->boolean('user_edited')->default(false);
    
    $table->timestamps();
});

// database/migrations/create_newsletter_sends_table.php
Schema::create('newsletter_sends', function (Blueprint $table) {
    $table->id();
    $table->foreignId('post_id')->constrained()->onDelete('cascade');
    $table->foreignId('brand_id')->constrained()->onDelete('cascade');
    
    // Email details
    $table->string('subject_line');
    $table->string('preview_text')->nullable();
    
    // Provider tracking
    $table->enum('provider', ['built_in', 'mailchimp', 'flodesk', 'convertkit', 'klaviyo']);
    $table->string('external_campaign_id')->nullable();
    
    // Status
    $table->enum('status', ['draft', 'scheduled', 'sending', 'sent', 'failed'])->default('draft');
    $table->timestamp('scheduled_at')->nullable();
    $table->timestamp('sent_at')->nullable();
    
    // Analytics
    $table->integer('recipients_count')->default(0);
    $table->integer('opens')->default(0);
    $table->integer('unique_opens')->default(0);
    $table->integer('clicks')->default(0);
    $table->integer('unique_clicks')->default(0);
    $table->integer('unsubscribes')->default(0);
    
    $table->timestamps();
});

// database/migrations/create_subscribers_table.php
Schema::create('subscribers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('brand_id')->constrained()->onDelete('cascade');
    
    $table->string('email');
    $table->string('first_name')->nullable();
    $table->string('last_name')->nullable();
    
    $table->enum('status', ['active', 'unsubscribed', 'bounced', 'complained'])->default('active');
    $table->string('source')->nullable(); // Where they signed up
    
    $table->timestamp('subscribed_at');
    $table->timestamp('unsubscribed_at')->nullable();
    
    $table->unique(['brand_id', 'email']);
    $table->timestamps();
});

// database/migrations/create_content_sprints_table.php
Schema::create('content_sprints', function (Blueprint $table) {
    $table->id();
    $table->foreignId('brand_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    
    $table->string('title')->nullable();
    $table->json('inputs'); // User's brain dump, topics, goals
    $table->json('generated_content')->nullable(); // AI suggestions before user accepts
    
    $table->enum('status', ['in_progress', 'completed', 'abandoned'])->default('in_progress');
    $table->timestamp('completed_at')->nullable();
    
    $table->timestamps();
});

// database/migrations/create_ai_prompts_table.php
Schema::create('ai_prompts', function (Blueprint $table) {
    $table->id();
    
    $table->string('type'); // blog_draft, social_instagram, polish_writing, etc.
    $table->string('industry')->nullable(); // NULL = generic, otherwise industry-specific
    
    $table->text('system_prompt');
    $table->text('user_prompt_template'); // With {{variable}} placeholders
    
    $table->integer('version')->default(1);
    $table->boolean('active')->default(true);
    
    $table->timestamps();
});
```

## Core Models

Create Eloquent models with these key relationships:

```php
// app/Models/Brand.php
class Brand extends Model
{
    protected $casts = [
        'voice_settings' => 'array',
        'newsletter_credentials' => 'encrypted:array',
        'social_connections' => 'encrypted:array',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
    
    public function subscribers(): HasMany
    {
        return $this->hasMany(Subscriber::class);
    }
    
    public function socialPosts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }
    
    // Get the public URL for this brand
    public function getUrlAttribute(): string
    {
        if ($this->custom_domain && $this->domain_verified) {
            return "https://{$this->custom_domain}";
        }
        return config('app.url') . "/@{$this->slug}";
    }
}

// app/Models/Post.php
class Post extends Model
{
    protected $casts = [
        'content' => 'array',
        'tags' => 'array',
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'publish_to_blog' => 'boolean',
        'send_as_newsletter' => 'boolean',
        'generate_social' => 'boolean',
    ];
    
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
    
    public function socialPosts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }
    
    public function newsletterSend(): HasOne
    {
        return $this->hasOne(NewsletterSend::class);
    }
}
```

## Directory Structure

```
app/
├── Actions/
│   ├── Posts/
│   │   ├── CreatePost.php
│   │   ├── PublishPost.php
│   │   └── SchedulePost.php
│   ├── Social/
│   │   ├── GenerateSocialPosts.php
│   │   ├── PublishToInstagram.php
│   │   └── PublishToPinterest.php
│   ├── Newsletter/
│   │   ├── SendNewsletter.php
│   │   └── SyncSubscribers.php
│   └── AI/
│       ├── GenerateDraft.php
│       ├── PolishWriting.php
│       ├── SuggestOutline.php
│       └── AtomizeContent.php
├── Http/
│   ├── Controllers/
│   │   ├── DashboardController.php
│   │   ├── PostController.php
│   │   ├── SocialPostController.php
│   │   ├── BrandController.php
│   │   ├── SubscriberController.php
│   │   ├── ContentSprintController.php
│   │   ├── AIAssistantController.php
│   │   └── Public/
│   │       ├── BlogController.php
│   │       └── SubscribeController.php
│   └── Middleware/
│       └── ResolveBrandFromDomain.php
├── Services/
│   ├── AIService.php
│   ├── Newsletter/
│   │   ├── NewsletterServiceInterface.php
│   │   ├── BuiltInNewsletterService.php
│   │   ├── MailchimpService.php
│   │   └── FlodeskService.php
│   └── Social/
│       ├── SocialPublisherInterface.php
│       ├── InstagramPublisher.php
│       └── PinterestPublisher.php
├── Jobs/
│   ├── PublishScheduledPost.php
│   ├── SendScheduledNewsletter.php
│   ├── PublishScheduledSocialPost.php
│   └── GenerateContentSprint.php
└── Models/
    └── [as defined above]

resources/
├── js/
│   ├── app.js
│   ├── Pages/
│   │   ├── Dashboard.vue
│   │   ├── Posts/
│   │   │   ├── Index.vue
│   │   │   ├── Create.vue
│   │   │   ├── Edit.vue
│   │   │   └── PublishModal.vue
│   │   ├── Social/
│   │   │   ├── Index.vue
│   │   │   ├── Calendar.vue
│   │   │   └── Queue.vue
│   │   ├── Subscribers/
│   │   │   └── Index.vue
│   │   ├── ContentSprint/
│   │   │   └── Index.vue
│   │   ├── Settings/
│   │   │   ├── Brand.vue
│   │   │   ├── Newsletter.vue
│   │   │   ├── Social.vue
│   │   │   └── Export.vue
│   │   └── Public/
│   │       ├── Blog/
│   │       │   ├── Index.vue
│   │       │   └── Show.vue
│   │       └── Subscribe.vue
│   ├── Components/
│   │   ├── Editor/
│   │   │   ├── PostEditor.vue         # TipTap-based rich text editor
│   │   │   ├── AIAssistantPanel.vue   # Collapsible AI helper
│   │   │   └── AISuggestion.vue       # Individual suggestion component
│   │   ├── Calendar/
│   │   │   └── ContentCalendar.vue
│   │   ├── Social/
│   │   │   ├── SocialPostCard.vue
│   │   │   └── SocialPostEditor.vue
│   │   └── Layout/
│   │       ├── AppLayout.vue
│   │       ├── Sidebar.vue
│   │       └── PublicLayout.vue
│   └── Composables/
│       ├── useAI.js                   # AI interaction composable
│       ├── useAutosave.js
│       └── useSocialPreview.js
└── views/
    └── app.blade.php
```

## Key Features to Implement

### 1. Post Editor with AI Assistant

The editor is the heart of the product. Build a TipTap-based editor with a collapsible AI assistant panel.

```vue
<!-- resources/js/Components/Editor/PostEditor.vue -->
<template>
  <div class="flex gap-6">
    <!-- Main Editor -->
    <div class="flex-1">
      <input 
        v-model="title"
        type="text"
        placeholder="Post title..."
        class="w-full text-3xl font-bold border-0 focus:ring-0 mb-4"
      />
      
      <EditorContent :editor="editor" class="prose max-w-none" />
    </div>
    
    <!-- AI Assistant Panel (collapsible) -->
    <AIAssistantPanel 
      v-if="showAssistant"
      :content="editor?.getHTML()"
      :title="title"
      @insert="handleInsert"
      @replace="handleReplace"
      class="w-80 flex-shrink-0"
    />
  </div>
</template>
```

AI Assistant should offer these actions:
- "Help me continue..." - Continues from cursor position
- "Suggest an outline" - Creates structure for the post
- "Polish my writing" - Improves flow and clarity
- "Make it shorter/longer" - Adjusts length
- "Write me a draft" - Full draft from title/bullets
- "Change tone to..." - Adjusts voice
- Free-form question input

### 2. Publish Flow

When user clicks "Publish", show a modal with all distribution options:

```vue
<!-- resources/js/Pages/Posts/PublishModal.vue -->
<template>
  <Modal :show="show" @close="$emit('close')">
    <div class="p-6">
      <h2 class="text-xl font-semibold mb-6">
        Ready to publish "{{ post.title }}"
      </h2>
      
      <!-- Blog Section -->
      <section class="mb-6">
        <label class="flex items-center gap-3">
          <input type="checkbox" v-model="publishToBlog" />
          <span class="font-medium">Publish to your site</span>
        </label>
        <p class="text-sm text-gray-500 ml-7">
          {{ brand.url }}/{{ post.slug }}
        </p>
      </section>
      
      <!-- Newsletter Section -->
      <section class="mb-6">
        <label class="flex items-center gap-3">
          <input type="checkbox" v-model="sendAsNewsletter" />
          <span class="font-medium">
            Send to {{ subscriberCount }} subscribers
          </span>
        </label>
        <div v-if="sendAsNewsletter" class="ml-7 mt-3 space-y-3">
          <input v-model="subjectLine" placeholder="Subject line" />
          <input v-model="previewText" placeholder="Preview text" />
          <select v-model="sendTime">
            <option value="now">Send now</option>
            <option value="schedule">Schedule for later</option>
          </select>
        </div>
      </section>
      
      <!-- Social Section -->
      <section class="mb-6">
        <label class="flex items-center gap-3">
          <input type="checkbox" v-model="generateSocial" />
          <span class="font-medium">Generate social posts</span>
        </label>
        <div v-if="generateSocial" class="ml-7 mt-3">
          <p class="text-sm text-gray-500 mb-2">
            We'll create variations for your connected platforms
          </p>
          <button @click="previewSocialPosts" class="text-indigo-600 text-sm">
            Preview & Edit Social Posts →
          </button>
        </div>
      </section>
      
      <!-- Actions -->
      <div class="flex justify-end gap-3 mt-8">
        <button @click="$emit('close')" class="btn-secondary">
          Save as Draft
        </button>
        <button @click="publish" class="btn-primary">
          Publish Now
        </button>
      </div>
    </div>
  </Modal>
</template>
```

### 3. AI Service

Create a service that handles all AI interactions with proper prompt management:

```php
// app/Services/AIService.php
class AIService
{
    public function __construct(
        private Client $anthropic
    ) {}
    
    public function generateDraft(Brand $brand, string $title, ?string $bullets = null): string
    {
        $prompt = $this->getPrompt('blog_draft', $brand->industry);
        
        return $this->complete(
            systemPrompt: $this->buildSystemPrompt($prompt, $brand),
            userPrompt: $this->fillTemplate($prompt->user_prompt_template, [
                'title' => $title,
                'bullets' => $bullets,
                'industry' => $brand->industry,
            ])
        );
    }
    
    public function polishWriting(Brand $brand, string $content): string
    {
        // Polish while maintaining user's voice
    }
    
    public function atomizeToSocial(Brand $brand, Post $post, array $platforms): array
    {
        // Generate platform-specific social posts from the blog post
        // Returns array of social post content keyed by platform
    }
    
    public function continueWriting(Brand $brand, string $contentSoFar): string
    {
        // Continue from where user left off
    }
    
    private function buildSystemPrompt(AiPrompt $prompt, Brand $brand): string
    {
        $system = $prompt->system_prompt;
        
        // Inject brand voice if available
        if ($brand->voice_settings) {
            $system .= "\n\nBrand Voice Guidelines:\n";
            $system .= "Tone: {$brand->voice_settings['tone']}\n";
            $system .= "Style: {$brand->voice_settings['style']}\n";
            
            if (!empty($brand->voice_settings['sample_texts'])) {
                $system .= "\nExample of brand's writing style:\n";
                foreach ($brand->voice_settings['sample_texts'] as $sample) {
                    $system .= "---\n{$sample}\n---\n";
                }
            }
        }
        
        return $system;
    }
}
```

### 4. Content Sprint Workflow

A guided session to generate a month of content:

```php
// app/Http/Controllers/ContentSprintController.php
class ContentSprintController extends Controller
{
    public function store(Request $request)
    {
        $sprint = ContentSprint::create([
            'brand_id' => $request->brand_id,
            'user_id' => $request->user()->id,
            'inputs' => $request->validate([
                'topics' => 'required|array',
                'goals' => 'nullable|string',
                'timeframe' => 'required|in:week,month',
                'content_types' => 'required|array', // blog, social, newsletter
            ]),
            'status' => 'in_progress',
        ]);
        
        // Dispatch job to generate content suggestions
        GenerateContentSprint::dispatch($sprint);
        
        return back()->with('sprint', $sprint);
    }
}
```

### 5. Public Blog Routes

Handle both subdomain and custom domain routing:

```php
// routes/web.php

// Subdomain routing for brand blogs
Route::domain('{brand}.' . config('app.domain'))->group(function () {
    Route::get('/', [Public\BlogController::class, 'index']);
    Route::get('/{post:slug}', [Public\BlogController::class, 'show']);
    Route::post('/subscribe', [Public\SubscribeController::class, 'store']);
});

// Custom domain routing (handled via middleware)
Route::middleware('resolve.brand.domain')->group(function () {
    Route::get('/', [Public\BlogController::class, 'index']);
    Route::get('/{post:slug}', [Public\BlogController::class, 'show']);
    Route::post('/subscribe', [Public\SubscribeController::class, 'store']);
});

// Also support /@username paths on main domain
Route::get('/@{brand:slug}', [Public\BlogController::class, 'index']);
Route::get('/@{brand:slug}/{post:slug}', [Public\BlogController::class, 'show']);
```

### 6. Newsletter Provider Interface

Support built-in sending and external ESPs:

```php
// app/Services/Newsletter/NewsletterServiceInterface.php
interface NewsletterServiceInterface
{
    public function send(NewsletterSend $newsletter): void;
    public function schedule(NewsletterSend $newsletter, Carbon $sendAt): void;
    public function syncSubscribers(Brand $brand): void;
    public function importSubscribers(Brand $brand, array $subscribers): int;
    public function getAnalytics(NewsletterSend $newsletter): array;
}

// Factory to resolve correct implementation
class NewsletterServiceFactory
{
    public function make(Brand $brand): NewsletterServiceInterface
    {
        return match($brand->newsletter_provider) {
            'built_in' => new BuiltInNewsletterService(),
            'mailchimp' => new MailchimpService($brand->newsletter_credentials),
            'flodesk' => new FlodeskService($brand->newsletter_credentials),
            'convertkit' => new ConvertKitService($brand->newsletter_credentials),
            default => throw new InvalidArgumentException('Unknown provider'),
        };
    }
}
```

## Key Vue Components Needed

1. **PostEditor.vue** - TipTap editor with formatting toolbar
2. **AIAssistantPanel.vue** - Collapsible AI helper with action buttons
3. **AISuggestion.vue** - Shows AI suggestion with accept/reject/edit options
4. **ContentCalendar.vue** - Month view showing scheduled content across all channels
5. **SocialPostCard.vue** - Preview card for a social post with platform styling
6. **SocialPostEditor.vue** - Edit generated social posts before scheduling
7. **PublishModal.vue** - Multi-step publish flow
8. **SubscriberTable.vue** - List/manage subscribers with bulk actions
9. **AnalyticsDashboard.vue** - Overview of content performance

## API Endpoints

```php
// AI Assistant endpoints
Route::post('/ai/draft', [AIAssistantController::class, 'draft']);
Route::post('/ai/polish', [AIAssistantController::class, 'polish']);
Route::post('/ai/continue', [AIAssistantController::class, 'continue']);
Route::post('/ai/outline', [AIAssistantController::class, 'outline']);
Route::post('/ai/atomize', [AIAssistantController::class, 'atomize']);

// Posts
Route::resource('posts', PostController::class);
Route::post('/posts/{post}/publish', [PostController::class, 'publish']);
Route::post('/posts/{post}/schedule', [PostController::class, 'schedule']);
Route::post('/posts/{post}/unpublish', [PostController::class, 'unpublish']);

// Social
Route::resource('social-posts', SocialPostController::class);
Route::post('/social-posts/{socialPost}/publish', [SocialPostController::class, 'publish']);
Route::post('/social-posts/bulk-schedule', [SocialPostController::class, 'bulkSchedule']);

// Subscribers
Route::resource('subscribers', SubscriberController::class);
Route::post('/subscribers/import', [SubscriberController::class, 'import']);
Route::get('/subscribers/export', [SubscriberController::class, 'export']);

// Content Sprint
Route::resource('content-sprints', ContentSprintController::class);
Route::post('/content-sprints/{sprint}/accept', [ContentSprintController::class, 'accept']);

// Settings
Route::get('/settings/brand', [BrandController::class, 'edit']);
Route::put('/settings/brand', [BrandController::class, 'update']);
Route::post('/settings/brand/verify-domain', [BrandController::class, 'verifyDomain']);
Route::post('/settings/newsletter/connect', [NewsletterSettingsController::class, 'connect']);
Route::post('/settings/social/connect/{platform}', [SocialSettingsController::class, 'connect']);

// Data export (ownership!)
Route::get('/export/posts', [ExportController::class, 'posts']);
Route::get('/export/subscribers', [ExportController::class, 'subscribers']);
Route::get('/export/all', [ExportController::class, 'all']);
```

## Environment Variables

```env
# AI
ANTHROPIC_API_KEY=

# Newsletter (built-in)
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=

# Social Media APIs
INSTAGRAM_CLIENT_ID=
INSTAGRAM_CLIENT_SECRET=
PINTEREST_APP_ID=
PINTEREST_APP_SECRET=
FACEBOOK_APP_ID=
FACEBOOK_APP_SECRET=

# Storage
AWS_BUCKET=
AWS_URL=

# Queue
REDIS_HOST=127.0.0.1
QUEUE_CONNECTION=redis
```

## Starting Point

Begin implementation in this order:

1. **Database migrations and models** - Get the data structure in place
2. **Basic auth and brand setup** - User can create account and brand
3. **Post editor (no AI yet)** - TipTap editor, save drafts, basic CRUD
4. **Public blog display** - Show published posts on brand URL
5. **AI integration** - Add AI assistant panel to editor
6. **Publish flow with newsletter** - Built-in newsletter sending
7. **Social post generation** - AI atomizes content to social posts
8. **Social scheduling/publishing** - Connect to Instagram/Pinterest APIs
9. **Content calendar** - Visual overview of all scheduled content
10. **Content Sprint** - Guided batch content generation
11. **ESP integrations** - Mailchimp, Flodesk connectors
12. **Analytics dashboard** - Track performance across channels
13. **Custom domains** - Let users connect their own domain

## Design Principles

1. **AI is a collaborator, not a replacement** - Always show AI suggestions as options, never auto-replace user content
2. **User owns everything** - Export always available, data never locked in
3. **One source of truth** - The Post is canonical, everything else derives from it
4. **Minimize chrome** - The editor should feel like a blank page, not a cockpit
5. **Progressive disclosure** - Show simple by default, advanced when needed
6. **Fast feedback** - Autosave constantly, show status clearly

## UI/UX Notes

- Use a clean, minimal design similar to Notion or Substack
- Editor should be distraction-free with toolbar appearing on selection
- AI panel should be collapsible and feel like a helpful assistant sitting beside you
- Calendar view should show content across all channels with clear visual distinction
- Settings should emphasize user ownership ("Your data", "Your subscribers", "Export everything")
