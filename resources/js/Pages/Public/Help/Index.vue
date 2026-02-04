<script setup>
import { ref, computed, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import SeoHead from '@/Components/SeoHead.vue';

const props = defineProps({
    categories: Array,
    appUrl: String,
});

const searchQuery = ref('');
const currentPage = ref(1);
const perPage = 9;

watch(searchQuery, () => {
    currentPage.value = 1;
});

const filteredCategories = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.categories;
    }

    const query = searchQuery.value.toLowerCase();

    return props.categories.filter((category) => {
        return (
            category.name.toLowerCase().includes(query) ||
            (category.description && category.description.toLowerCase().includes(query))
        );
    });
});

const totalPages = computed(() => Math.ceil(filteredCategories.value.length / perPage));

const paginatedCategories = computed(() => {
    const start = (currentPage.value - 1) * perPage;

    return filteredCategories.value.slice(start, start + perPage);
});

function getFirstArticleUrl(category) {
    if (category.articles.length > 0) {
        return `/help/${category.slug}/${category.articles[0].slug}`;
    }

    return `/help`;
}

function clearSearch() {
    searchQuery.value = '';
}
</script>

<template>
    <SeoHead
        title="Help Center - Copy Company"
        description="Find guides and tutorials to help you get the most out of Copy Company."
        :url="`${appUrl}/help`"
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
                <!-- Header -->
                <div class="text-center mb-12">
                    <p class="text-[#a1854f] text-sm tracking-widest uppercase mb-4">Help Center</p>
                    <h1 class="font-serif text-4xl lg:text-5xl font-light text-[#0b1215] mb-4">How can we help?</h1>
                    <p class="text-[#0b1215]/60">Guides and tutorials to help you get the most out of Copy Company.</p>
                </div>

                <!-- Search -->
                <div class="max-w-xl mx-auto mb-12">
                    <div class="relative">
                        <svg class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-[#0b1215]/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search categories..."
                            class="w-full pl-13 pr-5 py-3.5 bg-white border border-[#0b1215]/10 rounded-full text-sm text-[#0b1215] placeholder-[#0b1215]/40 focus:outline-none focus:border-[#a1854f]/40 focus:ring-2 focus:ring-[#a1854f]/20 transition"
                        />
                    </div>
                </div>

                <!-- Category Grid -->
                <div v-if="paginatedCategories.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Link
                        v-for="category in paginatedCategories"
                        :key="category.id"
                        :href="getFirstArticleUrl(category)"
                        class="group bg-white rounded-3xl p-8 border border-[#0b1215]/5 hover:border-[#a1854f]/20 hover:shadow-lg transition-all"
                    >
                        <!-- Icon -->
                        <div class="w-12 h-12 bg-[#a1854f]/10 rounded-2xl flex items-center justify-center mb-5">
                            <!-- Rocket -->
                            <svg v-if="category.icon === 'rocket'" class="w-6 h-6 text-[#a1854f]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                            </svg>
                            <!-- Bolt -->
                            <svg v-else-if="category.icon === 'bolt'" class="w-6 h-6 text-[#a1854f]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                            </svg>
                            <!-- Pencil -->
                            <svg v-else-if="category.icon === 'pencil'" class="w-6 h-6 text-[#a1854f]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                            </svg>
                            <!-- Loop (arrow-path) -->
                            <svg v-else-if="category.icon === 'loop'" class="w-6 h-6 text-[#a1854f]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182M21.015 4.353v4.992" />
                            </svg>
                            <!-- Share -->
                            <svg v-else-if="category.icon === 'share'" class="w-6 h-6 text-[#a1854f]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z" />
                            </svg>
                            <!-- Globe (default) -->
                            <svg v-else class="w-6 h-6 text-[#a1854f]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 003 12c0-1.605.42-3.113 1.157-4.418" />
                            </svg>
                        </div>

                        <!-- Title -->
                        <h2 class="text-lg font-medium text-[#0b1215] group-hover:text-[#a1854f] transition mb-2">
                            {{ category.name }}
                        </h2>

                        <!-- Description -->
                        <p v-if="category.description" class="text-sm text-[#0b1215]/60 mb-4">
                            {{ category.description }}
                        </p>

                        <!-- Article Count -->
                        <div class="flex items-center gap-1.5 text-xs text-[#a1854f]">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                            {{ category.articles.length }} {{ category.articles.length === 1 ? 'article' : 'articles' }}
                        </div>
                    </Link>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-16">
                    <svg class="w-12 h-12 text-[#0b1215]/20 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    <p class="text-[#0b1215]/60 mb-4">No categories match your search.</p>
                    <button
                        @click="clearSearch"
                        class="text-sm text-[#a1854f] hover:text-[#0b1215] transition"
                    >
                        Clear Search
                    </button>
                </div>

                <!-- Pagination -->
                <div v-if="totalPages > 1" class="flex items-center justify-center gap-2 mt-10">
                    <button
                        @click="currentPage--"
                        :disabled="currentPage === 1"
                        class="px-4 py-2 text-sm rounded-full border border-[#0b1215]/10 text-[#0b1215]/70 hover:border-[#a1854f]/30 hover:text-[#0b1215] transition disabled:opacity-30 disabled:cursor-not-allowed"
                    >
                        Previous
                    </button>

                    <button
                        v-for="page in totalPages"
                        :key="page"
                        @click="currentPage = page"
                        :class="[
                            'w-10 h-10 text-sm rounded-full transition',
                            currentPage === page
                                ? 'bg-[#0b1215] text-white'
                                : 'text-[#0b1215]/70 hover:bg-[#0b1215]/5'
                        ]"
                    >
                        {{ page }}
                    </button>

                    <button
                        @click="currentPage++"
                        :disabled="currentPage === totalPages"
                        class="px-4 py-2 text-sm rounded-full border border-[#0b1215]/10 text-[#0b1215]/70 hover:border-[#a1854f]/30 hover:text-[#0b1215] transition disabled:opacity-30 disabled:cursor-not-allowed"
                    >
                        Next
                    </button>
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
</style>
