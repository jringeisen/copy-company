<script setup>
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import SeoHead from '@/Components/SeoHead.vue';

const props = defineProps({
    category: Object,
    article: Object,
    siblingArticles: Array,
    appUrl: String,
});

const sidebarOpen = ref(false);
</script>

<template>
    <SeoHead
        :title="(article.seo_title || article.title) + ' - Help Center - Copy Company'"
        :description="article.seo_description || article.excerpt || ''"
        :url="`${appUrl}/help/${category.slug}/${article.slug}`"
    />

    <div class="min-h-screen bg-[#fcfbf8]">
        <!-- Navigation -->
        <nav class="py-6 px-6 lg:px-12 border-b border-[#0b1215]/5">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <Link href="/">
                    <img src="/images/logo.svg" alt="Copy Company" class="h-10" />
                </Link>
                <div class="flex items-center gap-8">
                    <Link href="/login" class="text-sm text-[#0b1215]/70 hover:text-[#0b1215] transition">Sign in</Link>
                    <Link
                        href="/register"
                        class="px-6 py-2.5 bg-[#0b1215] text-white text-sm rounded-full hover:bg-[#0b1215]/90 transition"
                    >
                        Get Started
                    </Link>
                </div>
            </div>
        </nav>

        <main class="py-16 lg:py-24 px-6 lg:px-12">
            <div class="max-w-6xl mx-auto">
                <!-- Breadcrumbs -->
                <nav class="mb-8 text-sm">
                    <ol class="flex items-center gap-2 text-[#0b1215]/50">
                        <li><Link href="/" class="hover:text-[#0b1215] transition">Home</Link></li>
                        <li>&rsaquo;</li>
                        <li><Link href="/help" class="hover:text-[#0b1215] transition">Help Center</Link></li>
                        <li>&rsaquo;</li>
                        <li class="text-[#0b1215]/70">{{ category.name }}</li>
                        <li>&rsaquo;</li>
                        <li class="text-[#0b1215]">{{ article.title }}</li>
                    </ol>
                </nav>

                <div class="flex flex-col lg:flex-row gap-10">
                    <!-- Sidebar -->
                    <aside class="lg:w-64 shrink-0">
                        <!-- Mobile toggle -->
                        <button
                            class="lg:hidden flex items-center gap-2 text-sm text-[#0b1215]/70 mb-4"
                            @click="sidebarOpen = !sidebarOpen"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            {{ category.name }} articles
                        </button>

                        <nav :class="['space-y-1', { hidden: !sidebarOpen, 'lg:block': true }]">
                            <p class="text-xs font-medium text-[#0b1215]/40 uppercase tracking-wider mb-3 hidden lg:block">{{ category.name }}</p>
                            <Link
                                v-for="sibling in siblingArticles"
                                :key="sibling.id"
                                :href="`/help/${category.slug}/${sibling.slug}`"
                                class="block px-3 py-2 rounded-lg text-sm transition"
                                :class="sibling.id === article.id
                                    ? 'bg-[#a1854f]/10 text-[#a1854f] font-medium'
                                    : 'text-[#0b1215]/60 hover:text-[#0b1215] hover:bg-[#f7f7f7]'"
                            >
                                {{ sibling.title }}
                            </Link>
                        </nav>
                    </aside>

                    <!-- Article Content -->
                    <div class="flex-1 min-w-0">
                        <div class="bg-white rounded-3xl p-8 lg:p-12 border border-[#0b1215]/5">
                            <h1 class="font-serif text-3xl lg:text-4xl font-light text-[#0b1215] mb-8">{{ article.title }}</h1>
                            <div class="prose prose-lg max-w-none" v-html="article.content_html"></div>
                        </div>

                        <div class="mt-8">
                            <Link href="/help" class="text-[#a1854f] hover:text-[#0b1215] transition text-sm">
                                &larr; Back to Help Center
                            </Link>
                        </div>
                    </div>
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
                        <Link href="/help" class="text-[#0b1215]/70 hover:text-[#0b1215] transition">Help</Link>
                        <Link href="/privacy-policy" class="text-[#0b1215]/70 hover:text-[#0b1215] transition">Privacy Policy</Link>
                        <Link href="/terms-of-service" class="text-[#0b1215]/70 hover:text-[#0b1215] transition">Terms of Service</Link>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<style>
.font-serif {
    font-family: 'Georgia', 'Times New Roman', serif;
}

.prose h2 {
    font-size: 1.5rem;
    font-weight: 500;
    color: #0b1215;
    margin-top: 2rem;
    margin-bottom: 1rem;
}

.prose h3 {
    font-size: 1.125rem;
    font-weight: 500;
    color: #0b1215;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
}

.prose p {
    color: rgb(11 18 21 / 0.7);
    line-height: 1.75;
    margin-bottom: 1rem;
}

.prose ul {
    list-style-type: disc;
    padding-left: 1.5rem;
    margin-bottom: 1rem;
}

.prose ul li {
    color: rgb(11 18 21 / 0.7);
    margin-bottom: 0.5rem;
}

.prose strong {
    color: #0b1215;
}

.prose code {
    background: #f7f7f7;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    color: #0b1215;
}
</style>
