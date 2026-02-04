<?php

namespace Database\Seeders;

use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use Illuminate\Database\Seeder;

class KnowledgeBaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $category = KnowledgeBaseCategory::updateOrCreate(
            ['slug' => 'custom-domains'],
            [
                'name' => 'Custom Domains',
                'description' => 'Learn how to set up and verify custom domains for your brand\'s email delivery.',
                'icon' => 'globe',
                'sort_order' => 2,
            ]
        );

        $this->seedArticle($category, [
            'title' => 'Setting Up a Custom Email Domain',
            'slug' => 'setting-up-a-custom-email-domain',
            'excerpt' => 'Learn why a custom email domain matters and how to get started with the setup process.',
            'sort_order' => 0,
            'content_html' => <<<'HTML'
                <h2>Why Use a Custom Email Domain?</h2>
                <p>Sending newsletters from your own domain (e.g. <code>hello@yourbrand.com</code>) instead of a shared domain builds trust with your subscribers and improves email deliverability. Inbox providers like Gmail and Outlook give higher reputation scores to senders who authenticate their own domains.</p>

                <h3>Benefits at a Glance</h3>
                <ul>
                    <li><strong>Better deliverability</strong> &mdash; Authenticated domains are less likely to land in spam.</li>
                    <li><strong>Brand recognition</strong> &mdash; Subscribers see your domain, not a generic one.</li>
                    <li><strong>Reputation isolation</strong> &mdash; Your sending reputation is yours alone.</li>
                </ul>

                <h2>Prerequisites</h2>
                <p>Before you begin, make sure you have:</p>
                <ul>
                    <li>A domain name you own (e.g. <code>yourbrand.com</code>)</li>
                    <li>Access to your domain&rsquo;s DNS settings (usually through your registrar or hosting provider)</li>
                    <li>A Copy Company account on a paid plan</li>
                </ul>

                <h2>Getting Started</h2>
                <p>Navigate to <strong>Settings &rarr; Email Domain</strong> in your Copy Company dashboard. Enter the domain you&rsquo;d like to send from and click <strong>Start Setup</strong>. The system will generate the DNS records you need to add &mdash; we&rsquo;ll walk through those in the next article.</p>

                <div style="background: #fef9ee; border: 1px solid #f0e6ce; border-radius: 12px; padding: 16px 20px; margin: 24px 0;">
                    <strong>Tip:</strong> Use a subdomain like <code>mail.yourbrand.com</code> if you already send transactional email from your root domain. This keeps your newsletter reputation separate.
                </div>
            HTML,
        ]);

        $this->seedArticle($category, [
            'title' => 'Configuring Your DNS Records',
            'slug' => 'configuring-your-dns-records',
            'excerpt' => 'Step-by-step guide to adding DKIM, SPF, and verification TXT records to your domain.',
            'sort_order' => 1,
            'content_html' => <<<'HTML'
                <h2>Required DNS Records</h2>
                <p>After initiating domain setup, Copy Company provides three DNS records you need to add. Each record serves a different purpose in authenticating your email.</p>

                <h3>DKIM Record (CNAME)</h3>
                <p>DomainKeys Identified Mail (DKIM) lets receiving servers verify that an email was actually sent by your domain and hasn&rsquo;t been tampered with. Copy Company will give you a <code>CNAME</code> record to add:</p>
                <ul>
                    <li><strong>Type:</strong> CNAME</li>
                    <li><strong>Name:</strong> The selector provided (e.g. <code>cc._domainkey.yourbrand.com</code>)</li>
                    <li><strong>Value:</strong> The DKIM endpoint provided in your dashboard</li>
                </ul>

                <h3>SPF Record (TXT)</h3>
                <p>Sender Policy Framework (SPF) tells inbox providers which servers are allowed to send email on behalf of your domain.</p>
                <ul>
                    <li><strong>Type:</strong> TXT</li>
                    <li><strong>Name:</strong> <code>@</code> (or your subdomain)</li>
                    <li><strong>Value:</strong> The SPF string provided in your dashboard</li>
                </ul>

                <div style="background: #fef9ee; border: 1px solid #f0e6ce; border-radius: 12px; padding: 16px 20px; margin: 24px 0;">
                    <strong>Important:</strong> If you already have an SPF record, don&rsquo;t create a second one. Instead, merge the Copy Company include into your existing record. A domain should only have one SPF TXT record.
                </div>

                <h3>Verification TXT Record</h3>
                <p>This record proves you own the domain. It&rsquo;s a simple TXT record with a unique verification token.</p>
                <ul>
                    <li><strong>Type:</strong> TXT</li>
                    <li><strong>Name:</strong> <code>@</code> (or your subdomain)</li>
                    <li><strong>Value:</strong> The verification string shown in your dashboard</li>
                </ul>

                <h2>Provider-Specific Tips</h2>
                <h3>Cloudflare</h3>
                <p>When adding the CNAME record in Cloudflare, make sure the <strong>Proxy status</strong> is set to <strong>DNS only</strong> (grey cloud), not Proxied (orange cloud).</p>

                <h3>GoDaddy</h3>
                <p>GoDaddy may automatically append your domain to the record name. If the name should be <code>cc._domainkey.yourbrand.com</code>, you may only need to enter <code>cc._domainkey</code>.</p>

                <h3>Namecheap</h3>
                <p>Under <strong>Advanced DNS</strong>, add each record and allow up to 30 minutes for changes to take effect within Namecheap&rsquo;s system.</p>
            HTML,
        ]);

        $this->seedArticle($category, [
            'title' => 'Verifying Your Domain',
            'slug' => 'verifying-your-domain',
            'excerpt' => 'How to check your domain verification status and troubleshoot common issues.',
            'sort_order' => 2,
            'content_html' => <<<'HTML'
                <h2>Checking Verification Status</h2>
                <p>After adding all the required DNS records, return to <strong>Settings &rarr; Email Domain</strong> and click the <strong>Check Status</strong> button. Copy Company will query your DNS records and report back on each one.</p>

                <h3>Status Indicators</h3>
                <ul>
                    <li><strong>Pending</strong> &mdash; The record hasn&rsquo;t been detected yet. DNS changes can take up to 48 hours to propagate, though most complete within a few hours.</li>
                    <li><strong>Verified</strong> &mdash; The record has been found and is correctly configured.</li>
                    <li><strong>Failed</strong> &mdash; The record was found but the value doesn&rsquo;t match. Double-check the value in your DNS settings.</li>
                </ul>

                <p>Once all records show as <strong>Verified</strong>, your domain is ready and newsletters will be sent from your custom domain automatically.</p>

                <h2>Troubleshooting</h2>

                <h3>Records not detected after 48 hours</h3>
                <ul>
                    <li>Verify you added the records to the correct domain or subdomain.</li>
                    <li>Check for typos in the record name and value &mdash; copy and paste directly from the dashboard.</li>
                    <li>Confirm there are no conflicting records (e.g. duplicate SPF TXT records).</li>
                    <li>Try using a tool like <code>dig</code> or an online DNS checker to confirm the records are visible publicly.</li>
                </ul>

                <h3>DKIM record shows as failed</h3>
                <p>This usually means the CNAME value is incorrect or your DNS provider added extra characters. Ensure the value matches exactly what Copy Company provided, with no trailing dots or extra spaces.</p>

                <h3>SPF record shows as failed</h3>
                <p>If you have multiple services sending email from your domain, make sure all includes are in a single SPF record. For example:</p>
                <p><code>v=spf1 include:_spf.google.com include:spf.copycompany.com ~all</code></p>

                <div style="background: #fef9ee; border: 1px solid #f0e6ce; border-radius: 12px; padding: 16px 20px; margin: 24px 0;">
                    <strong>Need help?</strong> If you&rsquo;re still having trouble after following these steps, reach out to our support team at <code>support@modernmarket.co</code> and include a screenshot of your current DNS records.
                </div>
            HTML,
        ]);

        $this->seedGettingStartedCategory();
        $this->seedContentSprintsCategory();
        $this->seedPostsCategory();
    }

    private function seedGettingStartedCategory(): void
    {
        $category = KnowledgeBaseCategory::updateOrCreate(
            ['slug' => 'getting-started'],
            [
                'name' => 'Getting Started',
                'description' => 'Learn how to create and configure your brand on Copy Company.',
                'icon' => 'rocket',
                'sort_order' => 0,
            ]
        );

        $this->seedArticle($category, [
            'title' => 'Creating Your Brand',
            'slug' => 'creating-your-brand',
            'excerpt' => 'Learn what a brand is in Copy Company and how to create your first one.',
            'sort_order' => 0,
            'content_html' => <<<'HTML'
                <h2>What Is a Brand?</h2>
                <p>In Copy Company, a <strong>brand</strong> is the central entity that all your content belongs to. Every blog post, newsletter, social media post, and subscriber is tied to a brand. Think of it as your content workspace &mdash; everything you create lives under one brand.</p>

                <h2>Creating a New Brand</h2>
                <p>To create your first brand, click the <strong>Create Brand</strong> button from your dashboard. You&rsquo;ll be asked to fill in a few details about your brand.</p>

                <h3>Required Fields</h3>
                <ul>
                    <li><strong>Brand Name</strong> &mdash; The name of your brand as it will appear across Copy Company.</li>
                    <li><strong>URL Slug</strong> &mdash; A URL-friendly version of your brand name, auto-generated as you type. You can edit it manually. Only lowercase letters, numbers, and hyphens are allowed.</li>
                </ul>

                <h3>Optional Fields</h3>
                <ul>
                    <li><strong>Tagline</strong> &mdash; A brief one-liner that describes your brand.</li>
                    <li><strong>Description</strong> &mdash; A longer description of what your brand is about.</li>
                    <li><strong>Industry</strong> &mdash; Select an industry to help categorize your brand (e.g. Technology, Marketing, Finance).</li>
                    <li><strong>Brand Color</strong> &mdash; Choose a primary color that represents your brand. This is used throughout your public blog and newsletters.</li>
                </ul>

                <h3>Timezone</h3>
                <p>Your timezone is automatically detected from your browser when you create a brand. You can change it later in your brand settings.</p>

                <div style="background: #fef9ee; border: 1px solid #f0e6ce; border-radius: 12px; padding: 16px 20px; margin: 24px 0;">
                    <strong>Tip:</strong> Your URL slug becomes part of your public blog URL &mdash; for example, <code>/blog/your-slug</code>. Choose something short and memorable.
                </div>
            HTML,
        ]);

        $this->seedArticle($category, [
            'title' => 'Customizing Your Brand Settings',
            'slug' => 'customizing-your-brand-settings',
            'excerpt' => 'Configure your brand colors, AI voice settings, and sample writing to get the most out of Copy Company.',
            'sort_order' => 1,
            'content_html' => <<<'HTML'
                <h2>Accessing Brand Settings</h2>
                <p>Navigate to <strong>Settings &rarr; Brand</strong> from your dashboard to customize your brand. Here you can update everything from your brand name to how the AI generates content for you.</p>

                <h2>Brand Colors</h2>
                <p>Copy Company supports two brand colors that are used across your public blog, newsletters, and dashboard:</p>
                <ul>
                    <li><strong>Primary Color</strong> &mdash; Used for buttons, links, and key accents.</li>
                    <li><strong>Secondary Color</strong> &mdash; Used for hover states and supporting elements.</li>
                </ul>

                <h2>AI Voice Settings</h2>
                <p>Copy Company uses AI to help you generate content. You can fine-tune how the AI writes by configuring your voice settings.</p>

                <h3>Tone</h3>
                <p>Choose how the AI communicates with your audience:</p>
                <ul>
                    <li><strong>Professional</strong> &mdash; Polished and business-appropriate.</li>
                    <li><strong>Casual</strong> &mdash; Relaxed and approachable.</li>
                    <li><strong>Friendly</strong> &mdash; Warm and personable.</li>
                    <li><strong>Formal</strong> &mdash; Structured and authoritative.</li>
                    <li><strong>Persuasive</strong> &mdash; Engaging and action-oriented.</li>
                </ul>

                <h3>Style</h3>
                <p>Choose the overall writing style:</p>
                <ul>
                    <li><strong>Conversational</strong> &mdash; Like talking to a friend.</li>
                    <li><strong>Academic</strong> &mdash; Research-backed and detailed.</li>
                    <li><strong>Storytelling</strong> &mdash; Narrative-driven and engaging.</li>
                    <li><strong>Instructional</strong> &mdash; Step-by-step and educational.</li>
                </ul>

                <h3>Sample Writing</h3>
                <p>You can provide up to <strong>3 writing samples</strong> so the AI can learn your unique voice. Paste examples of content you&rsquo;ve written &mdash; blog posts, emails, social posts &mdash; and the AI will use these as a reference when generating new content.</p>

                <div style="background: #fef9ee; border: 1px solid #f0e6ce; border-radius: 12px; padding: 16px 20px; margin: 24px 0;">
                    <strong>Tip:</strong> The more specific your voice settings and writing samples, the better the AI-generated content will match your brand&rsquo;s style. Take a few minutes to set these up &mdash; it makes a noticeable difference.
                </div>
            HTML,
        ]);

        $this->seedArticle($category, [
            'title' => 'Switching Between Brands',
            'slug' => 'switching-between-brands',
            'excerpt' => 'Manage multiple brands from a single Copy Company account and switch between them easily.',
            'sort_order' => 2,
            'content_html' => <<<'HTML'
                <h2>Multi-Brand Support</h2>
                <p>Copy Company supports multiple brands under a single account. Whether you run several businesses, manage brands for different clients, or separate personal and professional content, you can do it all from one login.</p>

                <h2>Creating Additional Brands</h2>
                <p>To create another brand, click the <strong>brand switcher</strong> on your dashboard and select <strong>Create Brand</strong>. The process is the same as creating your first brand &mdash; give it a name, slug, and optional details.</p>

                <h2>Switching Your Active Brand</h2>
                <p>Use the <strong>brand switcher</strong> in the dashboard to change which brand you&rsquo;re currently working with. When you switch brands, the entire dashboard updates to show that brand&rsquo;s content, settings, and subscribers.</p>

                <h3>What Changes When You Switch</h3>
                <ul>
                    <li><strong>Posts</strong> &mdash; You&rsquo;ll see only the posts belonging to the active brand.</li>
                    <li><strong>Subscribers</strong> &mdash; The subscriber list is specific to each brand.</li>
                    <li><strong>Settings</strong> &mdash; Brand colors, voice settings, email domain, and all other configuration are per-brand.</li>
                    <li><strong>Social Connections</strong> &mdash; Each brand has its own connected social accounts.</li>
                </ul>

                <div style="background: #fef9ee; border: 1px solid #f0e6ce; border-radius: 12px; padding: 16px 20px; margin: 24px 0;">
                    <strong>Tip:</strong> Each brand is completely independent. Changes to one brand&rsquo;s settings, content, or subscribers won&rsquo;t affect any of your other brands.
                </div>
            HTML,
        ]);
    }

    private function seedContentSprintsCategory(): void
    {
        $category = KnowledgeBaseCategory::updateOrCreate(
            ['slug' => 'content-sprints'],
            [
                'name' => 'Content Sprints',
                'description' => 'Use AI-powered content sprints to generate blog post ideas in bulk.',
                'icon' => 'bolt',
                'sort_order' => 1,
            ]
        );

        $this->seedArticle($category, [
            'title' => 'What Are Content Sprints?',
            'slug' => 'what-are-content-sprints',
            'excerpt' => 'An overview of content sprints and how they help you plan your content calendar.',
            'sort_order' => 0,
            'content_html' => <<<'HTML'
                <h2>Overview</h2>
                <p>A <strong>content sprint</strong> is an AI-powered brainstorming session that generates a batch of blog post ideas tailored to your brand. Instead of staring at a blank page, you provide a few topics and the AI does the heavy lifting &mdash; producing titles, descriptions, key talking points, and estimated word counts for each idea.</p>

                <h2>What You Get</h2>
                <p>Each content sprint generates between 5 and 30 blog post ideas. Every idea includes:</p>
                <ul>
                    <li><strong>Title</strong> &mdash; A ready-to-use headline for the post.</li>
                    <li><strong>Description</strong> &mdash; A short summary of what the post should cover.</li>
                    <li><strong>Key Points</strong> &mdash; Specific talking points to include in the post.</li>
                    <li><strong>Estimated Word Count</strong> &mdash; A target length to help you plan your writing time.</li>
                </ul>

                <h2>When to Use a Content Sprint</h2>
                <p>Content sprints are ideal when you need to:</p>
                <ul>
                    <li>Plan a month&rsquo;s worth of blog content in one sitting.</li>
                    <li>Explore new topic areas for your brand.</li>
                    <li>Overcome writer&rsquo;s block with AI-generated starting points.</li>
                    <li>Build a backlog of draft posts to work through over time.</li>
                </ul>

                <div style="background: #fef9ee; border: 1px solid #f0e6ce; border-radius: 12px; padding: 16px 20px; margin: 24px 0;">
                    <strong>Tip:</strong> Content sprints use your brand&rsquo;s voice settings to tailor the ideas. Set up your tone, style, and writing samples in <strong>Settings &rarr; Brand</strong> before running your first sprint for the best results.
                </div>
            HTML,
        ]);

        $this->seedArticle($category, [
            'title' => 'Creating a Content Sprint',
            'slug' => 'creating-a-content-sprint',
            'excerpt' => 'Step-by-step guide to starting a content sprint and configuring your topics and goals.',
            'sort_order' => 1,
            'content_html' => <<<'HTML'
                <h2>Starting a New Sprint</h2>
                <p>Navigate to <strong>Content Sprints</strong> from your dashboard and click <strong>Create Sprint</strong> (or <strong>Start Your First Sprint</strong> if you haven&rsquo;t created one yet).</p>

                <h2>Configuring Your Sprint</h2>

                <h3>Topics</h3>
                <p>Add between 1 and 10 topics that you want the AI to generate ideas around. These can be broad themes or specific subjects &mdash; for example:</p>
                <ul>
                    <li><code>productivity</code></li>
                    <li><code>remote work tips</code></li>
                    <li><code>leadership for startups</code></li>
                </ul>
                <p>Click <strong>+ Add another topic</strong> to add more, or click the <strong>&times;</strong> button to remove one. You need at least one topic to start a sprint.</p>

                <h3>Goals (Optional)</h3>
                <p>Describe what you want to achieve with this batch of content. For example: &ldquo;Establish thought leadership in my industry&rdquo; or &ldquo;Drive newsletter signups from organic search.&rdquo; This helps the AI focus its suggestions. Maximum 500 characters.</p>

                <h3>Number of Ideas</h3>
                <p>Use the slider to choose how many ideas to generate, from 5 to 30 in increments of 5. The default is 20. More ideas means a longer generation time.</p>

                <h2>What Happens Next</h2>
                <p>Click <strong>Generate Ideas</strong> to start the sprint. You&rsquo;ll be taken to the sprint detail page where you can watch the progress. The AI typically finishes generating ideas within 30&ndash;60 seconds.</p>

                <h3>Sprint Statuses</h3>
                <ul>
                    <li><strong>Pending</strong> &mdash; Your sprint is queued and will begin processing shortly.</li>
                    <li><strong>Generating</strong> &mdash; The AI is actively creating your content ideas.</li>
                    <li><strong>Completed</strong> &mdash; Ideas are ready to review.</li>
                    <li><strong>Failed</strong> &mdash; Something went wrong. You can click <strong>Try Again</strong> to retry.</li>
                </ul>

                <div style="background: #fef9ee; border: 1px solid #f0e6ce; border-radius: 12px; padding: 16px 20px; margin: 24px 0;">
                    <strong>Tip:</strong> Be specific with your topics and goals. &ldquo;Email marketing for e-commerce&rdquo; will produce more targeted ideas than just &ldquo;marketing.&rdquo;
                </div>
            HTML,
        ]);

        $this->seedArticle($category, [
            'title' => 'Turning Ideas into Draft Posts',
            'slug' => 'turning-ideas-into-draft-posts',
            'excerpt' => 'Learn how to review your generated ideas and convert them into blog post drafts.',
            'sort_order' => 2,
            'content_html' => <<<'HTML'
                <h2>Reviewing Your Ideas</h2>
                <p>Once a sprint completes, you&rsquo;ll see all the generated ideas listed on the sprint detail page. Each idea card shows the title, description, key points, and estimated word count. Scroll through them to find the ones that fit your content plan.</p>

                <h2>Selecting Ideas</h2>
                <p>Click on any idea card to select it. Selected ideas are highlighted with a gold border. You can also use the <strong>Select All</strong> button at the top to select every unconverted idea at once.</p>
                <ul>
                    <li>Click a selected idea again to deselect it.</li>
                    <li>Ideas that have already been converted to posts are marked with a <strong>Created</strong> badge and cannot be selected again.</li>
                </ul>

                <h2>Creating Draft Posts</h2>
                <p>After selecting the ideas you want, click the <strong>Create Draft Posts</strong> button at the bottom of the page. Copy Company will create a new draft blog post for each selected idea, pre-filled with:</p>
                <ul>
                    <li>The idea&rsquo;s title as the post title.</li>
                    <li>The description as the post excerpt.</li>
                    <li>A structured outline in the post body including the description, key points, and target word count.</li>
                </ul>
                <p>You&rsquo;ll be redirected to your posts list where you can open any draft and start writing.</p>

                <h2>Managing Sprints</h2>
                <h3>Retrying a Failed Sprint</h3>
                <p>If a sprint fails, click the <strong>Try Again</strong> button on the sprint detail page. The AI will re-run the generation with the same topics, goals, and idea count.</p>

                <h3>Deleting a Sprint</h3>
                <p>To remove a sprint you no longer need, delete it from the sprint detail page. This does not affect any posts that were already created from the sprint&rsquo;s ideas.</p>

                <div style="background: #fef9ee; border: 1px solid #f0e6ce; border-radius: 12px; padding: 16px 20px; margin: 24px 0;">
                    <strong>Tip:</strong> You don&rsquo;t have to convert all ideas at once. Come back to a completed sprint anytime and pick up where you left off &mdash; the sprint tracks which ideas you&rsquo;ve already turned into posts.
                </div>
            HTML,
        ]);
    }

    private function seedPostsCategory(): void
    {
        $category = KnowledgeBaseCategory::updateOrCreate(
            ['slug' => 'posts'],
            [
                'name' => 'Posts',
                'description' => 'Write, publish, and schedule blog posts — and distribute them as newsletters and social content.',
                'icon' => 'pencil',
                'sort_order' => 3,
            ]
        );

        $this->seedArticle($category, [
            'title' => 'Writing Your First Post',
            'slug' => 'writing-your-first-post',
            'excerpt' => 'Learn how to create a new blog post using the editor, save drafts, and get help from the AI assistant.',
            'sort_order' => 0,
            'content_html' => <<<'HTML'
                <h2>Creating a New Post</h2>
                <p>Navigate to <strong>Posts</strong> from your dashboard and click <strong>New Post</strong>. This opens the post editor where you can write and format your content.</p>

                <h3>The Editor</h3>
                <p>Copy Company uses a rich text editor powered by TipTap. You can format text with headings, bold, italic, lists, links, images, and code blocks &mdash; all without writing any HTML. The editor supports standard keyboard shortcuts like <code>Ctrl+B</code> for bold and <code>Ctrl+I</code> for italic.</p>

                <h3>Post Fields</h3>
                <ul>
                    <li><strong>Title</strong> &mdash; Required. This becomes the headline of your blog post and the basis for its URL slug.</li>
                    <li><strong>Content</strong> &mdash; Required. The body of your post, written in the rich text editor.</li>
                    <li><strong>Excerpt</strong> &mdash; Optional. A short summary (up to 500 characters) shown in post listings and search results.</li>
                    <li><strong>Featured Image</strong> &mdash; Optional. An image displayed at the top of your post and in social previews.</li>
                    <li><strong>Tags</strong> &mdash; Optional. Categorize your post by topic to help readers find related content.</li>
                </ul>

                <h2>Saving Drafts</h2>
                <p>New posts are saved as <strong>drafts</strong> automatically. While you&rsquo;re writing, your content is also saved to your browser&rsquo;s local storage so you won&rsquo;t lose work if you accidentally close the tab. If you return to the create page within 24 hours, you&rsquo;ll be offered the option to restore your previous draft.</p>
                <p>You can also press <code>Cmd+S</code> (Mac) or <code>Ctrl+S</code> (Windows) to save your draft at any time.</p>

                <h2>Using the AI Assistant</h2>
                <p>The editor includes a built-in <strong>AI Assistant</strong> panel on the right side of the screen. You can ask it to help you brainstorm ideas, write sections, or refine your content. The AI uses your brand&rsquo;s voice settings (tone, style, and writing samples) to match your brand&rsquo;s voice.</p>
                <p>When the AI suggests content you like, click to copy it and paste it into your editor.</p>

                <div style="background: #fef9ee; border: 1px solid #f0e6ce; border-radius: 12px; padding: 16px 20px; margin: 24px 0;">
                    <strong>Tip:</strong> Set up your brand&rsquo;s tone, style, and writing samples in <strong>Settings &rarr; Brand</strong> before writing your first post. The AI assistant will produce much better suggestions when it knows your voice.
                </div>
            HTML,
        ]);

        $this->seedArticle($category, [
            'title' => 'Publishing and Scheduling Posts',
            'slug' => 'publishing-and-scheduling-posts',
            'excerpt' => 'Learn how to publish posts immediately or schedule them for a future date, and choose your distribution channels.',
            'sort_order' => 1,
            'content_html' => <<<'HTML'
                <h2>The Publish Flow</h2>
                <p>When your post is ready, click the <strong>Publish</strong> button in the editor to open the publish modal. Here you&rsquo;ll choose how and when your post goes live.</p>

                <h3>Distribution Options</h3>
                <p>Before publishing, decide where your post should appear:</p>
                <ul>
                    <li><strong>Publish to blog</strong> &mdash; Make the post visible on your public blog at <code>/blog/your-brand-slug/post-slug</code>.</li>
                    <li><strong>Send as newsletter</strong> &mdash; Email the post to your confirmed subscribers. This option requires an active subscription (not available on free trial).</li>
                    <li><strong>Generate social posts</strong> &mdash; Automatically create platform-specific social media versions of your content.</li>
                </ul>

                <h3>Publish Now</h3>
                <p>Select <strong>Publish now</strong> to make your post live immediately. The post status changes to <strong>Published</strong> and your selected distribution channels are triggered right away.</p>

                <h3>Schedule for Later</h3>
                <p>Select <strong>Schedule for later</strong> and pick a future date and time. Your post will be automatically published at the scheduled time. The post status changes to <strong>Scheduled</strong> until then.</p>
                <p>Scheduled posts are checked every minute and published as soon as the scheduled time arrives. If you also enabled the newsletter option, the email will be sent at the same time.</p>

                <h2>Newsletter Options</h2>
                <p>If you choose to send your post as a newsletter, you&rsquo;ll need to provide:</p>
                <ul>
                    <li><strong>Subject line</strong> &mdash; Required. The email subject your subscribers will see in their inbox.</li>
                    <li><strong>Preview text</strong> &mdash; Optional. A short line of text shown next to the subject in most email clients.</li>
                </ul>

                <div style="background: #fef9ee; border: 1px solid #f0e6ce; border-radius: 12px; padding: 16px 20px; margin: 24px 0;">
                    <strong>Tip:</strong> Write a compelling subject line that&rsquo;s different from your post title. A good subject line creates curiosity and drives higher open rates.
                </div>
            HTML,
        ]);

        $this->seedArticle($category, [
            'title' => 'Managing Your Posts',
            'slug' => 'managing-your-posts',
            'excerpt' => 'Understand post statuses, edit existing posts, configure SEO settings, and organize your content.',
            'sort_order' => 2,
            'content_html' => <<<'HTML'
                <h2>Post Statuses</h2>
                <p>Every post has a status that reflects where it is in your workflow:</p>
                <ul>
                    <li><strong>Draft</strong> &mdash; A work in progress. Only you can see it. You can edit it freely.</li>
                    <li><strong>Scheduled</strong> &mdash; Set to publish at a future date and time. It will go live automatically.</li>
                    <li><strong>Published</strong> &mdash; Live on your blog and/or sent as a newsletter.</li>
                    <li><strong>Archived</strong> &mdash; Removed from your active post list but not deleted.</li>
                </ul>

                <h2>Editing Posts</h2>
                <p>Open any draft post from your posts list to edit it. The editor <strong>auto-saves</strong> your changes every 30 seconds, so you don&rsquo;t need to manually save while writing. A status indicator in the editor shows when changes are being saved.</p>

                <div style="background: #fef9ee; border: 1px solid #f0e6ce; border-radius: 12px; padding: 16px 20px; margin: 24px 0;">
                    <strong>Important:</strong> Published and scheduled posts cannot be edited. If you need to make changes to a published post, you&rsquo;ll need to create a new version.
                </div>

                <h2>SEO Settings</h2>
                <p>Each post has optional SEO fields to help your content rank in search engines:</p>
                <ul>
                    <li><strong>SEO Title</strong> &mdash; A custom title tag for search results (up to 255 characters). If left blank, the post title is used.</li>
                    <li><strong>SEO Description</strong> &mdash; A meta description for search results (up to 500 characters). If left blank, the excerpt is used.</li>
                </ul>

                <h2>Featured Images</h2>
                <p>You can add or change a featured image from the post editor. The featured image appears at the top of your blog post and is used as the preview image when your post is shared on social media.</p>

                <h2>Deleting Posts</h2>
                <p>To delete a single post, open it in the editor and click the <strong>Delete</strong> option at the bottom of the page. To delete multiple posts at once, select them using the checkboxes on the posts list page and click <strong>Delete Selected</strong>.</p>
                <p>Deleting a post is permanent and cannot be undone. Any newsletter or social posts that were already created from it will not be affected.</p>

                <div style="background: #fef9ee; border: 1px solid #f0e6ce; border-radius: 12px; padding: 16px 20px; margin: 24px 0;">
                    <strong>Tip:</strong> Use tags to organize your posts by topic. This makes it easier to find related content later and can improve your blog&rsquo;s SEO by grouping similar content together.
                </div>
            HTML,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function seedArticle(KnowledgeBaseCategory $category, array $data): void
    {
        KnowledgeBaseArticle::updateOrCreate(
            [
                'category_id' => $category->id,
                'slug' => $data['slug'],
            ],
            [
                'title' => $data['title'],
                'excerpt' => $data['excerpt'],
                'content_html' => $data['content_html'],
                'is_published' => true,
                'sort_order' => $data['sort_order'],
            ]
        );
    }
}
