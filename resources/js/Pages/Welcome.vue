<script setup>
import { Link } from '@inertiajs/vue3';
import SeoHead from '@/Components/SeoHead.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    appUrl: String,
});

const billingPeriod = ref('monthly');
const mobileMenuOpen = ref(false);

const jsonLd = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'Organization',
    name: 'Copy Company',
    url: props.appUrl,
    logo: `${props.appUrl}/images/logo.svg`,
    description: 'A creator-first content platform where your blog post becomes your newsletter, and AI helps you reach your audience on every channel.',
}));

const pricing = {
    starter: { monthly: 8, annual: 6 },
    creator: { monthly: 19, annual: 14 },
    pro: { monthly: 29, annual: 22 },
};

// Feature comparison data
const features = [
    { name: 'Subscribers', starter: 'Unlimited', creator: 'Unlimited', pro: 'Unlimited' },
    { name: 'Posts per month', starter: '5', creator: 'Unlimited', pro: 'Unlimited' },
    { name: 'Social accounts', starter: '2', creator: '5', pro: '15' },
    { name: 'AI Content Sprints', starter: '1/month', creator: '10/month', pro: 'Unlimited' },
    { name: 'Public blog page', starter: true, creator: true, pro: true },
    { name: 'Newsletter analytics', starter: false, creator: true, pro: true },
    { name: 'Custom email domain', starter: false, creator: false, pro: true },
    { name: 'Dedicated IP', starter: false, creator: false, pro: true },
    { name: 'Priority support', starter: false, creator: false, pro: true },
];
</script>

<template>
    <SeoHead
        title="Copy Company - Write Once, Publish Everywhere"
        description="A creator-first content platform where your blog post becomes your newsletter, and AI helps you reach your audience on every channel."
        :url="appUrl"
        :image="`${appUrl}/images/og-default.png`"
        type="website"
        :json-ld="jsonLd"
    />

    <div class="min-h-screen bg-[#fcfbf8]">
        <!-- Navigation -->
        <nav class="py-6 px-6 lg:px-12 relative">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <img src="/images/logo.svg" alt="Copy Company" class="h-10" />

                <!-- Desktop nav -->
                <div class="hidden md:flex items-center gap-8">
                    <a href="#features" class="text-sm text-[#0b1215]/70 hover:text-[#0b1215] transition">Features</a>
                    <a href="#pricing" class="text-sm text-[#0b1215]/70 hover:text-[#0b1215] transition">Pricing</a>
                    <Link href="/login" class="text-sm text-[#0b1215]/70 hover:text-[#0b1215] transition">Sign in</Link>
                    <Link
                        href="/register"
                        class="px-6 py-2.5 bg-[#0b1215] text-white text-sm rounded-full hover:bg-[#0b1215]/90 transition"
                    >
                        Get Started
                    </Link>
                </div>

                <!-- Mobile hamburger button -->
                <button
                    class="md:hidden p-2 text-[#0b1215]"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    aria-label="Toggle menu"
                >
                    <svg v-if="!mobileMenuOpen" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Mobile menu -->
            <div
                v-if="mobileMenuOpen"
                class="md:hidden absolute top-full left-0 right-0 bg-[#fcfbf8] border-t border-[#0b1215]/10 shadow-lg z-50"
            >
                <div class="flex flex-col px-6 py-4 gap-4">
                    <a href="#features" class="text-sm text-[#0b1215]/70 hover:text-[#0b1215] transition py-2" @click="mobileMenuOpen = false">Features</a>
                    <a href="#pricing" class="text-sm text-[#0b1215]/70 hover:text-[#0b1215] transition py-2" @click="mobileMenuOpen = false">Pricing</a>
                    <Link href="/login" class="text-sm text-[#0b1215]/70 hover:text-[#0b1215] transition py-2">Sign in</Link>
                    <Link
                        href="/register"
                        class="px-6 py-2.5 bg-[#0b1215] text-white text-sm rounded-full hover:bg-[#0b1215]/90 transition text-center"
                    >
                        Get Started
                    </Link>
                </div>
            </div>
        </nav>

        <main>
            <!-- Hero Section -->
            <div class="py-24 lg:py-32 px-6 lg:px-12">
                <div class="max-w-5xl mx-auto text-center">
                    <h1 class="font-serif text-4xl sm:text-5xl md:text-6xl lg:text-8xl font-light text-[#0b1215] mb-8 tracking-tight leading-none">
                        WRITE ONCE,<br />PUBLISH EVERYWHERE
                    </h1>
                    <p class="text-lg sm:text-xl lg:text-2xl text-[#0b1215]/60 max-w-2xl mx-auto mb-12 font-light leading-relaxed">
                        A creator-first content platform where your blog post becomes your newsletter,
                        and AI helps you reach your audience on every channel.
                    </p>
                    <div class="flex flex-col sm:flex-row justify-center gap-4">
                        <Link
                            href="/register"
                            class="px-10 py-4 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition text-sm tracking-wide"
                        >
                            Start Creating
                        </Link>
                        <a
                            href="#features"
                            class="px-10 py-4 border border-[#0b1215]/20 text-[#0b1215] font-medium rounded-full hover:bg-[#0b1215]/5 transition text-sm tracking-wide"
                        >
                            Learn More
                        </a>
                    </div>
                    <!-- Social Proof -->
                    <div class="mt-16 flex items-center justify-center gap-3">
                        <div class="flex -space-x-2">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-amber-400 to-amber-600 border-2 border-[#fcfbf8]"></div>
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 border-2 border-[#fcfbf8]"></div>
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-400 to-purple-600 border-2 border-[#fcfbf8]"></div>
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-green-400 to-green-600 border-2 border-[#fcfbf8]"></div>
                        </div>
                        <span class="text-sm text-[#0b1215]/50">Join 2,500+ creators</span>
                    </div>
                </div>
            </div>

            <!-- Problem Statement Section - Dark -->
            <div class="bg-[#0b1215] py-24 lg:py-32 px-6 lg:px-12">
                <div class="max-w-4xl mx-auto text-center">
                    <p class="text-[#a1854f] text-sm tracking-widest uppercase mb-6">The Problem</p>
                    <h2 class="font-serif text-4xl lg:text-6xl font-light text-white mb-8 leading-tight">
                        Tired of juggling multiple tools for your content?
                    </h2>
                    <p class="text-xl text-white/60 font-light leading-relaxed">
                        You write a blog post in one app, copy it to your newsletter tool, then rewrite it for social media.
                        Hours wasted. Consistency lost. Audience fragmented.
                    </p>
                </div>
            </div>

            <!-- Solution Section - Light -->
            <div class="bg-[#f7f7f7] py-24 lg:py-32 px-6 lg:px-12">
                <div class="max-w-4xl mx-auto text-center">
                    <p class="text-[#a1854f] text-sm tracking-widest uppercase mb-6">The Solution</p>
                    <h2 class="font-serif text-4xl lg:text-6xl font-light text-[#0b1215] mb-8 leading-tight">
                        One platform. One workflow. Every channel.
                    </h2>
                    <p class="text-xl text-[#0b1215]/60 font-light leading-relaxed">
                        Write once in our beautiful editor. Publish to your blog, send to your newsletter, and
                        let AI generate social posts — all from one place.
                    </p>
                </div>
            </div>

            <!-- Core Features -->
            <div id="features" class="bg-[#fcfbf8] py-24 lg:py-32 px-6 lg:px-12">
                <div class="max-w-7xl mx-auto">
                    <div class="text-center mb-20">
                        <p class="text-[#a1854f] text-sm tracking-widest uppercase mb-4">Features</p>
                        <h2 class="font-serif text-4xl lg:text-5xl font-light text-[#0b1215]">Everything You Need to Create</h2>
                    </div>

                    <div class="grid md:grid-cols-3 gap-8 lg:gap-12">
                        <!-- Blog Posts -->
                        <div class="p-8 bg-white rounded-2xl border border-[#0b1215]/5">
                            <div class="w-14 h-14 bg-[#0b1215] rounded-xl flex items-center justify-center mb-6">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-medium text-[#0b1215] mb-3">Rich Blog Editor</h3>
                            <p class="text-[#0b1215]/60 leading-relaxed">
                                A distraction-free writing experience with markdown support. Draft, schedule, and publish posts to your hosted blog.
                            </p>
                        </div>

                        <!-- Newsletter -->
                        <div class="p-8 bg-white rounded-2xl border border-[#0b1215]/5">
                            <div class="w-14 h-14 bg-[#0b1215] rounded-xl flex items-center justify-center mb-6">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-medium text-[#0b1215] mb-3">Built-in Newsletter</h3>
                            <p class="text-[#0b1215]/60 leading-relaxed">
                                Automatically send your blog posts to subscribers. Manage your list, import/export contacts, and track engagement.
                            </p>
                        </div>

                        <!-- Social Media -->
                        <div class="p-8 bg-white rounded-2xl border border-[#0b1215]/5">
                            <div class="w-14 h-14 bg-[#0b1215] rounded-xl flex items-center justify-center mb-6">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-medium text-[#0b1215] mb-3">Social Media Management</h3>
                            <p class="text-[#0b1215]/60 leading-relaxed">
                                Create, schedule, and queue social posts. Manage content for multiple platforms from one place.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Social Publishing Section -->
            <div class="bg-white py-24 lg:py-32 px-6 lg:px-12">
                <div class="max-w-7xl mx-auto">
                    <div class="grid lg:grid-cols-2 gap-16 items-center">
                        <div>
                            <p class="text-[#a1854f] text-sm tracking-widest uppercase mb-4">Integrations</p>
                            <h2 class="font-serif text-4xl lg:text-5xl font-light text-[#0b1215] mb-6 leading-tight">
                                Publish Directly to Social Media
                            </h2>
                            <p class="text-xl text-[#0b1215]/60 mb-10 font-light leading-relaxed">
                                Connect your social accounts and publish content directly from the platform. No more copying and pasting between apps.
                            </p>
                            <div class="flex flex-wrap gap-3">
                                <!-- Twitter/X -->
                                <div class="flex items-center gap-2 px-5 py-3 bg-[#f7f7f7] rounded-full">
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                    </svg>
                                    <span class="text-sm font-medium">X</span>
                                </div>
                                <!-- Facebook -->
                                <div class="flex items-center gap-2 px-5 py-3 bg-[#f7f7f7] rounded-full">
                                    <svg class="w-5 h-5 text-blue-600" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                    <span class="text-sm font-medium">Facebook</span>
                                </div>
                                <!-- Instagram -->
                                <div class="flex items-center gap-2 px-5 py-3 bg-[#f7f7f7] rounded-full">
                                    <svg class="w-5 h-5 text-pink-600" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                                    </svg>
                                    <span class="text-sm font-medium">Instagram</span>
                                </div>
                                <!-- LinkedIn -->
                                <div class="flex items-center gap-2 px-5 py-3 bg-[#f7f7f7] rounded-full">
                                    <svg class="w-5 h-5 text-blue-700" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                    </svg>
                                    <span class="text-sm font-medium">LinkedIn</span>
                                </div>
                                <!-- Pinterest -->
                                <div class="flex items-center gap-2 px-5 py-3 bg-[#f7f7f7] rounded-full">
                                    <svg class="w-5 h-5 text-red-600" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.401.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.354-.629-2.758-1.379l-.749 2.848c-.269 1.045-1.004 2.352-1.498 3.146 1.123.345 2.306.535 3.55.535 6.607 0 11.985-5.365 11.985-11.987C23.97 5.39 18.592.026 11.985.026L12.017 0z"/>
                                    </svg>
                                    <span class="text-sm font-medium">Pinterest</span>
                                </div>
                                <!-- TikTok -->
                                <div class="flex items-center gap-2 px-5 py-3 bg-[#f7f7f7] rounded-full">
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                                    </svg>
                                    <span class="text-sm font-medium">TikTok</span>
                                </div>
                            </div>
                        </div>
                        <div class="bg-[#f7f7f7] rounded-3xl p-8">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-12 h-12 bg-[#0b1215] rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-medium text-[#0b1215]">Connected</div>
                                    <div class="text-sm text-[#0b1215]/50">@yourbrand</div>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div class="p-4 bg-white rounded-2xl text-[#0b1215]/70">
                                    "Just published: 10 Tips for Better Content Marketing. Link in bio!"
                                </div>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-[#0b1215]/50">Scheduled for 9:00 AM</span>
                                    <span class="text-[#a1854f] font-medium">Ready to publish</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI Features Section - Dark -->
            <div class="bg-[#0b1215] py-24 lg:py-32 px-6 lg:px-12">
                <div class="max-w-7xl mx-auto">
                    <div class="grid lg:grid-cols-2 gap-16 items-center">
                        <div class="order-2 lg:order-1 bg-white/5 rounded-3xl p-8 border border-white/10">
                            <div class="flex items-center gap-2 mb-6">
                                <div class="w-10 h-10 bg-[#a1854f] rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                </div>
                                <span class="font-medium text-white">AI Assistant</span>
                            </div>
                            <div class="space-y-4 text-sm">
                                <div class="p-4 bg-[#a1854f]/20 rounded-2xl text-[#a1854f]">
                                    "Generate 5 blog post ideas about sustainable living"
                                </div>
                                <div class="p-4 bg-white/5 rounded-2xl text-white/70">
                                    <p class="font-medium mb-3 text-white/90">Here are 5 blog post ideas:</p>
                                    <ol class="list-decimal list-inside space-y-2 text-white/60">
                                        <li>Zero-Waste Kitchen Swaps</li>
                                        <li>Building a Capsule Wardrobe</li>
                                        <li>Composting for Beginners</li>
                                        <li>Eco-Friendly Travel Tips</li>
                                        <li>Sustainable Gift Guide</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        <div class="order-1 lg:order-2">
                            <p class="text-[#a1854f] text-sm tracking-widest uppercase mb-4">AI Powered</p>
                            <h2 class="font-serif text-4xl lg:text-5xl font-light text-white mb-6 leading-tight">
                                AI-Powered Content Creation
                            </h2>
                            <p class="text-xl text-white/60 mb-10 font-light leading-relaxed">
                                Get help when you need it. Our AI assistant helps you brainstorm ideas, improve your writing, and generate social posts from your content.
                            </p>
                            <ul class="space-y-4">
                                <li class="flex items-start gap-4">
                                    <div class="w-6 h-6 rounded-full bg-[#a1854f] flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <span class="text-white/70">Content Sprints for bulk idea generation</span>
                                </li>
                                <li class="flex items-start gap-4">
                                    <div class="w-6 h-6 rounded-full bg-[#a1854f] flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <span class="text-white/70">Writing assistant in the editor</span>
                                </li>
                                <li class="flex items-start gap-4">
                                    <div class="w-6 h-6 rounded-full bg-[#a1854f] flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <span class="text-white/70">Customizable brand voice settings</span>
                                </li>
                                <li class="flex items-start gap-4">
                                    <div class="w-6 h-6 rounded-full bg-[#a1854f] flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <span class="text-white/70">Auto-generate social posts from blog content</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- More Features Grid -->
            <div class="bg-[#f7f7f7] py-24 lg:py-32 px-6 lg:px-12">
                <div class="max-w-7xl mx-auto">
                    <div class="text-center mb-20">
                        <p class="text-[#a1854f] text-sm tracking-widest uppercase mb-4">And More</p>
                        <h2 class="font-serif text-4xl lg:text-5xl font-light text-[#0b1215]">Everything Else You Need</h2>
                    </div>
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Public Blog -->
                        <div class="p-8 bg-white rounded-2xl">
                            <div class="w-12 h-12 bg-[#0b1215] rounded-xl flex items-center justify-center mb-6">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-[#0b1215] mb-2">Hosted Public Blog</h3>
                            <p class="text-[#0b1215]/60">Your own blog page at /blog/yourbrand. Share your content with the world.</p>
                        </div>

                        <!-- Subscriber Management -->
                        <div class="p-8 bg-white rounded-2xl">
                            <div class="w-12 h-12 bg-[#0b1215] rounded-xl flex items-center justify-center mb-6">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-[#0b1215] mb-2">Subscriber Management</h3>
                            <p class="text-[#0b1215]/60">Import, export, and manage your email list. Track confirmed and unsubscribed users.</p>
                        </div>

                        <!-- Social Queue -->
                        <div class="p-8 bg-white rounded-2xl">
                            <div class="w-12 h-12 bg-[#0b1215] rounded-xl flex items-center justify-center mb-6">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-[#0b1215] mb-2">Social Post Queue</h3>
                            <p class="text-[#0b1215]/60">Queue up posts and let them publish automatically. Never worry about posting times again.</p>
                        </div>

                        <!-- Brand Settings -->
                        <div class="p-8 bg-white rounded-2xl">
                            <div class="w-12 h-12 bg-[#0b1215] rounded-xl flex items-center justify-center mb-6">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-[#0b1215] mb-2">Brand Customization</h3>
                            <p class="text-[#0b1215]/60">Set your brand colors, voice settings, and industry for personalized AI content.</p>
                        </div>

                        <!-- Content Sprints -->
                        <div class="p-8 bg-white rounded-2xl">
                            <div class="w-12 h-12 bg-[#0b1215] rounded-xl flex items-center justify-center mb-6">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-[#0b1215] mb-2">Content Sprints</h3>
                            <p class="text-[#0b1215]/60">Generate dozens of content ideas in one go. Perfect for planning your content strategy.</p>
                        </div>

                        <!-- Data Export -->
                        <div class="p-8 bg-white rounded-2xl">
                            <div class="w-12 h-12 bg-[#0b1215] rounded-xl flex items-center justify-center mb-6">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-[#0b1215] mb-2">You Own Your Data</h3>
                            <p class="text-[#0b1215]/60">Export your content and subscribers anytime. Your audience belongs to you.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Testimonials Section - Dark -->
            <div class="bg-[#0b1215] py-24 lg:py-32 px-6 lg:px-12">
                <div class="max-w-7xl mx-auto">
                    <div class="text-center mb-16">
                        <p class="text-[#a1854f] text-sm tracking-widest uppercase mb-4">Testimonials</p>
                        <h2 class="font-serif text-4xl lg:text-5xl font-light text-white">Loved by Creators</h2>
                    </div>
                    <div class="grid md:grid-cols-3 gap-8">
                        <!-- Testimonial 1 -->
                        <div class="bg-white/5 rounded-3xl p-8 border border-white/10">
                            <div class="flex items-center gap-1 mb-6">
                                <svg v-for="n in 5" :key="n" class="w-5 h-5 text-[#a1854f]" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            </div>
                            <p class="text-white/70 mb-8 leading-relaxed italic">
                                "This platform has completely transformed how I manage my content. I used to spend hours copying posts between platforms. Now I write once and publish everywhere in minutes."
                            </p>
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-amber-400 to-amber-600 rounded-full flex items-center justify-center text-white font-medium">
                                    SJ
                                </div>
                                <div>
                                    <div class="font-medium text-white">Sarah Johnson</div>
                                    <div class="text-sm text-white/50">Marketing Consultant</div>
                                </div>
                            </div>
                        </div>

                        <!-- Testimonial 2 -->
                        <div class="bg-white/5 rounded-3xl p-8 border border-white/10">
                            <div class="flex items-center gap-1 mb-6">
                                <svg v-for="n in 5" :key="n" class="w-5 h-5 text-[#a1854f]" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            </div>
                            <p class="text-white/70 mb-8 leading-relaxed italic">
                                "The Content Sprints feature is a game-changer. I generated a month's worth of blog ideas in 10 minutes. The AI actually understands my brand voice, which is rare."
                            </p>
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white font-medium">
                                    MC
                                </div>
                                <div>
                                    <div class="font-medium text-white">Marcus Chen</div>
                                    <div class="text-sm text-white/50">Tech Blogger</div>
                                </div>
                            </div>
                        </div>

                        <!-- Testimonial 3 -->
                        <div class="bg-white/5 rounded-3xl p-8 border border-white/10">
                            <div class="flex items-center gap-1 mb-6">
                                <svg v-for="n in 5" :key="n" class="w-5 h-5 text-[#a1854f]" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            </div>
                            <p class="text-white/70 mb-8 leading-relaxed italic">
                                "Finally, a platform that lets me own my subscriber list! I migrated from another service and the import was seamless. The built-in newsletter saves me $50/month."
                            </p>
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-purple-400 to-purple-600 rounded-full flex items-center justify-center text-white font-medium">
                                    ER
                                </div>
                                <div>
                                    <div class="font-medium text-white">Emily Rodriguez</div>
                                    <div class="text-sm text-white/50">Newsletter Creator</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pricing Section -->
            <div id="pricing" class="bg-[#fcfbf8] py-24 lg:py-32 px-6 lg:px-12">
                <div class="max-w-7xl mx-auto">
                    <div class="text-center mb-8">
                        <p class="text-[#a1854f] text-sm tracking-widest uppercase mb-4">Pricing</p>
                        <h2 class="font-serif text-4xl lg:text-5xl font-light text-[#0b1215] mb-4">Simple, Transparent Pricing</h2>
                        <p class="text-xl text-[#0b1215]/60 max-w-2xl mx-auto font-light">
                            Choose the plan that fits your needs. No hidden fees, no surprise charges.
                        </p>
                    </div>

                    <!-- Billing Toggle -->
                    <div class="flex justify-center items-center gap-4 mb-8">
                        <span :class="billingPeriod === 'monthly' ? 'text-[#0b1215] font-medium' : 'text-[#0b1215]/50'" class="text-sm">Monthly</span>
                        <button
                            @click="billingPeriod = billingPeriod === 'monthly' ? 'annual' : 'monthly'"
                            class="relative w-14 h-7 rounded-full transition-colors"
                            :class="billingPeriod === 'annual' ? 'bg-[#0b1215]' : 'bg-[#0b1215]/20'"
                        >
                            <span
                                class="absolute top-0.5 left-0.5 w-6 h-6 bg-white rounded-full shadow transition-transform"
                                :class="billingPeriod === 'annual' ? 'translate-x-7' : 'translate-x-0'"
                            ></span>
                        </button>
                        <span :class="billingPeriod === 'annual' ? 'text-[#0b1215] font-medium' : 'text-[#0b1215]/50'" class="text-sm">
                            Annual
                            <span class="ml-2 text-xs bg-[#a1854f] text-white px-2 py-1 rounded-full font-medium">Save 25%</span>
                        </span>
                    </div>

                    <!-- Pricing Model Explainer -->
                    <div class="max-w-2xl mx-auto mb-16">
                        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 sm:gap-6 bg-white rounded-2xl p-6 border border-[#0b1215]/10">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-[#0b1215] flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <div class="text-center sm:text-left">
                                    <div class="text-sm text-[#0b1215]/50">Platform fee</div>
                                    <div class="font-medium text-[#0b1215]">From $6/mo</div>
                                </div>
                            </div>
                            <div class="text-2xl font-light text-[#0b1215]/30">+</div>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-[#a1854f] flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="text-center sm:text-left">
                                    <div class="text-sm text-[#0b1215]/50">Email sending</div>
                                    <div class="font-medium text-[#0b1215]">$0.40 / 1,000 emails</div>
                                </div>
                            </div>
                        </div>
                        <p class="text-center text-sm text-[#0b1215]/50 mt-4">
                            Pay only for what you use. No subscriber limits, no hidden fees.
                        </p>
                    </div>

                    <div class="grid md:grid-cols-3 gap-6 max-w-5xl mx-auto">
                        <!-- Starter Tier -->
                        <div class="bg-white rounded-3xl p-8 border border-[#0b1215]/5">
                            <div class="text-center mb-6">
                                <h3 class="text-lg font-medium text-[#0b1215] mb-4">Starter</h3>
                                <div class="mb-1">
                                    <span class="text-5xl font-light text-[#0b1215]">${{ pricing.starter[billingPeriod] }}</span>
                                    <span class="text-[#0b1215]/50">/mo</span>
                                </div>
                                <p class="text-xs text-[#0b1215]/50 mb-3">Platform fee</p>
                                <Transition name="fade">
                                    <p v-if="billingPeriod === 'annual'" class="text-xs text-[#a1854f]">Billed annually (${{ pricing.starter.annual * 12 }}/year)</p>
                                </Transition>
                            </div>
                            <!-- Email Usage Box -->
                            <div class="bg-[#f7f7f7] rounded-xl p-3 mb-6 text-center">
                                <span class="text-sm text-[#0b1215]/70">+ <strong class="text-[#0b1215]">$0.40</strong> per 1,000 emails</span>
                            </div>
                            <ul class="space-y-3 mb-8">
                                <!-- Included features -->
                                <li class="flex items-start gap-3">
                                    <div class="w-5 h-5 rounded-full bg-[#0b1215] flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <span class="text-[#0b1215]/70"><strong class="text-[#0b1215]">Unlimited</strong> subscribers</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <div class="w-5 h-5 rounded-full bg-[#0b1215] flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <span class="text-[#0b1215]/70">5 posts per month</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <div class="w-5 h-5 rounded-full bg-[#0b1215] flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <span class="text-[#0b1215]/70">2 social accounts</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <div class="w-5 h-5 rounded-full bg-[#0b1215] flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <span class="text-[#0b1215]/70">1 AI Content Sprint/month</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <div class="w-5 h-5 rounded-full bg-[#0b1215] flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <span class="text-[#0b1215]/70">Public blog page</span>
                                </li>
                                <!-- Not included features -->
                                <li class="flex items-start gap-3 opacity-40">
                                    <div class="w-5 h-5 rounded-full bg-[#0b1215]/20 flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3 h-3 text-[#0b1215]/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </div>
                                    <span class="text-[#0b1215]/50">Newsletter analytics</span>
                                </li>
                                <li class="flex items-start gap-3 opacity-40">
                                    <div class="w-5 h-5 rounded-full bg-[#0b1215]/20 flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3 h-3 text-[#0b1215]/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </div>
                                    <span class="text-[#0b1215]/50">Custom email domain</span>
                                </li>
                                <li class="flex items-start gap-3 opacity-40">
                                    <div class="w-5 h-5 rounded-full bg-[#0b1215]/20 flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3 h-3 text-[#0b1215]/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </div>
                                    <span class="text-[#0b1215]/50">Dedicated IP</span>
                                </li>
                            </ul>
                            <Link
                                href="/register"
                                class="block w-full py-4 px-4 text-center border border-[#0b1215]/20 text-[#0b1215] font-medium rounded-full hover:bg-[#0b1215]/5 transition"
                            >
                                Start Free Trial
                            </Link>
                        </div>

                        <!-- Creator Tier -->
                        <div class="bg-[#0b1215] rounded-3xl p-8 relative">
                            <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                                <span class="bg-[#a1854f] text-white text-xs font-medium px-4 py-1.5 rounded-full tracking-wide">MOST POPULAR</span>
                            </div>
                            <div class="text-center mb-6">
                                <h3 class="text-lg font-medium text-white mb-4">Creator</h3>
                                <div class="mb-1">
                                    <span class="text-5xl font-light text-white">${{ pricing.creator[billingPeriod] }}</span>
                                    <span class="text-white/50">/mo</span>
                                </div>
                                <p class="text-xs text-white/50 mb-3">Platform fee</p>
                                <Transition name="fade">
                                    <p v-if="billingPeriod === 'annual'" class="text-xs text-[#a1854f]">Billed annually (${{ pricing.creator.annual * 12 }}/year)</p>
                                </Transition>
                            </div>
                            <!-- Email Usage Box -->
                            <div class="bg-white/10 rounded-xl p-3 mb-6 text-center">
                                <span class="text-sm text-white/70">+ <strong class="text-white">$0.40</strong> per 1,000 emails</span>
                            </div>
                            <ul class="space-y-3 mb-8">
                                <!-- Included features -->
                                <li class="flex items-start gap-3">
                                    <div class="w-5 h-5 rounded-full bg-[#a1854f] flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <span class="text-white/70"><strong class="text-white">Unlimited</strong> subscribers</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <div class="w-5 h-5 rounded-full bg-[#a1854f] flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <span class="text-white/70"><strong class="text-white">Unlimited</strong> posts</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <div class="w-5 h-5 rounded-full bg-[#a1854f] flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <span class="text-white/70">5 social accounts</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <div class="w-5 h-5 rounded-full bg-[#a1854f] flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <span class="text-white/70">10 AI Content Sprints/month</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <div class="w-5 h-5 rounded-full bg-[#a1854f] flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <span class="text-white/70">Newsletter analytics</span>
                                </li>
                                <!-- Not included features -->
                                <li class="flex items-start gap-3 opacity-40">
                                    <div class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3 h-3 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </div>
                                    <span class="text-white/40">Custom email domain</span>
                                </li>
                                <li class="flex items-start gap-3 opacity-40">
                                    <div class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3 h-3 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </div>
                                    <span class="text-white/40">Dedicated IP</span>
                                </li>
                            </ul>
                            <Link
                                href="/register"
                                class="block w-full py-4 px-4 text-center bg-white text-[#0b1215] font-medium rounded-full hover:bg-white/90 transition"
                            >
                                Start Free Trial
                            </Link>
                        </div>

                        <!-- Pro Tier -->
                        <div class="bg-white rounded-3xl p-8 border border-[#0b1215]/5">
                            <div class="text-center mb-6">
                                <h3 class="text-lg font-medium text-[#0b1215] mb-4">Pro</h3>
                                <div class="mb-1">
                                    <span class="text-5xl font-light text-[#0b1215]">${{ pricing.pro[billingPeriod] }}</span>
                                    <span class="text-[#0b1215]/50">/mo</span>
                                </div>
                                <p class="text-xs text-[#0b1215]/50 mb-3">Platform fee</p>
                                <Transition name="fade">
                                    <p v-if="billingPeriod === 'annual'" class="text-xs text-[#a1854f]">Billed annually (${{ pricing.pro.annual * 12 }}/year)</p>
                                </Transition>
                            </div>
                            <!-- Email Usage Box -->
                            <div class="bg-[#f7f7f7] rounded-xl p-3 mb-6 text-center">
                                <span class="text-sm text-[#0b1215]/70">+ <strong class="text-[#0b1215]">$0.40</strong> per 1,000 emails</span>
                            </div>
                            <ul class="space-y-3 mb-8">
                                <li class="flex items-start gap-3">
                                    <div class="w-5 h-5 rounded-full bg-[#0b1215] flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <span class="text-[#0b1215]/70"><strong class="text-[#0b1215]">Unlimited</strong> subscribers</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <div class="w-5 h-5 rounded-full bg-[#0b1215] flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <span class="text-[#0b1215]/70"><strong class="text-[#0b1215]">Unlimited</strong> posts</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <div class="w-5 h-5 rounded-full bg-[#0b1215] flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <span class="text-[#0b1215]/70">15 social accounts</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <div class="w-5 h-5 rounded-full bg-[#0b1215] flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <span class="text-[#0b1215]/70"><strong class="text-[#0b1215]">Unlimited</strong> AI Content Sprints</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <div class="w-5 h-5 rounded-full bg-[#0b1215] flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <span class="text-[#0b1215]/70">Newsletter analytics</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <div class="w-5 h-5 rounded-full bg-[#0b1215] flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <span class="text-[#0b1215]/70">Custom email domain</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <div class="w-5 h-5 rounded-full bg-[#0b1215] flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <span class="text-[#0b1215]/70">Dedicated IP for email</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <div class="w-5 h-5 rounded-full bg-[#0b1215] flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <span class="text-[#0b1215]/70">Priority support</span>
                                </li>
                            </ul>
                            <Link
                                href="/register"
                                class="block w-full py-4 px-4 text-center border border-[#0b1215]/20 text-[#0b1215] font-medium rounded-full hover:bg-[#0b1215]/5 transition"
                            >
                                Start Free Trial
                            </Link>
                        </div>
                    </div>

                    <!-- Feature Comparison Table -->
                    <div class="mt-16 max-w-4xl mx-auto">
                        <div class="text-center mb-8">
                            <h3 class="text-2xl font-medium text-[#0b1215]">Compare All Features</h3>
                        </div>
                        <div class="bg-white rounded-3xl border border-[#0b1215]/5 overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead>
                                        <tr class="border-b border-[#0b1215]/5">
                                            <th class="py-5 px-6 text-left text-sm font-medium text-[#0b1215]">Feature</th>
                                            <th class="py-5 px-4 text-center">
                                                <div class="text-sm font-medium text-[#0b1215]">Starter</div>
                                                <div class="text-xs text-[#0b1215]/50">${{ pricing.starter[billingPeriod] }}/mo</div>
                                            </th>
                                            <th class="py-5 px-4 text-center bg-[#0b1215]/5">
                                                <div class="text-sm font-medium text-[#0b1215]">Creator</div>
                                                <div class="text-xs text-[#a1854f]">${{ pricing.creator[billingPeriod] }}/mo</div>
                                            </th>
                                            <th class="py-5 px-4 text-center">
                                                <div class="text-sm font-medium text-[#0b1215]">Pro</div>
                                                <div class="text-xs text-[#0b1215]/50">${{ pricing.pro[billingPeriod] }}/mo</div>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(feature, index) in features" :key="feature.name" :class="index % 2 === 0 ? 'bg-[#f7f7f7]/50' : ''">
                                            <td class="py-4 px-6 text-sm text-[#0b1215]/70">{{ feature.name }}</td>
                                            <td class="py-4 px-4 text-center">
                                                <template v-if="typeof feature.starter === 'boolean'">
                                                    <svg v-if="feature.starter" class="w-5 h-5 text-[#0b1215] mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    <svg v-else class="w-5 h-5 text-[#0b1215]/20 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </template>
                                                <span v-else class="text-sm text-[#0b1215]">{{ feature.starter }}</span>
                                            </td>
                                            <td class="py-4 px-4 text-center bg-[#0b1215]/5">
                                                <template v-if="typeof feature.creator === 'boolean'">
                                                    <svg v-if="feature.creator" class="w-5 h-5 text-[#a1854f] mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    <svg v-else class="w-5 h-5 text-[#0b1215]/20 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </template>
                                                <span v-else class="text-sm font-medium text-[#0b1215]">{{ feature.creator }}</span>
                                            </td>
                                            <td class="py-4 px-4 text-center">
                                                <template v-if="typeof feature.pro === 'boolean'">
                                                    <svg v-if="feature.pro" class="w-5 h-5 text-[#0b1215] mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    <svg v-else class="w-5 h-5 text-[#0b1215]/20 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </template>
                                                <span v-else class="text-sm text-[#0b1215]">{{ feature.pro }}</span>
                                            </td>
                                        </tr>
                                        <!-- Email sending row -->
                                        <tr class="border-t border-[#0b1215]/10 bg-[#a1854f]/5">
                                            <td class="py-4 px-6 text-sm font-medium text-[#0b1215]">Email sending</td>
                                            <td colspan="3" class="py-4 px-4 text-center text-sm text-[#0b1215]/70">
                                                <strong class="text-[#0b1215]">$0.40</strong> per 1,000 emails <span class="text-[#0b1215]/50">(all plans)</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <p class="text-center text-sm text-[#0b1215]/50 mt-12">
                        All plans include a 14-day free trial. No credit card required. Cancel anytime.
                    </p>
                </div>
            </div>

            <!-- CTA Section -->
            <div class="bg-[#0b1215] py-24 lg:py-32 px-6 lg:px-12">
                <div class="max-w-3xl mx-auto text-center">
                    <h2 class="font-serif text-4xl lg:text-5xl font-light text-white mb-6 leading-tight">
                        Ready to Simplify Your Content Workflow?
                    </h2>
                    <p class="text-xl text-white/60 mb-10 font-light">
                        Join creators who are saving hours every week by managing all their content in one place.
                    </p>
                    <Link
                        href="/register"
                        class="inline-flex px-10 py-4 bg-white text-[#0b1215] font-medium rounded-full hover:bg-white/90 transition text-sm tracking-wide"
                    >
                        Get Started for Free
                    </Link>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-[#fcfbf8] border-t border-[#0b1215]/5 py-12 px-6 lg:px-12">
            <div class="max-w-7xl mx-auto">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="text-[#0b1215]/50 text-sm">
                        &copy; {{ new Date().getFullYear() }} Copy Company. All rights reserved.
                    </div>
                    <div class="flex items-center gap-6 text-sm">
                        <Link href="/privacy-policy" class="text-[#0b1215]/70 hover:text-[#0b1215] transition">Privacy Policy</Link>
                        <Link href="/terms-of-service" class="text-[#0b1215]/70 hover:text-[#0b1215] transition">Terms of Service</Link>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<style>
/* Serif font for display headings - using system serif stack */
.font-serif {
    font-family: 'Georgia', 'Times New Roman', serif;
}

/* Smooth scroll for anchor links */
html {
    scroll-behavior: smooth;
}

/* Fade transition for billing period text */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
