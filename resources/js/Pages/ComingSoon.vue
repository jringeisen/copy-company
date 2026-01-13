<script setup>
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Coming Soon - Copy Company" />

    <div class="min-h-screen bg-[#fcfbf8] flex flex-col">
        <!-- Navigation -->
        <nav class="py-6 px-6 lg:px-12">
            <div class="max-w-7xl mx-auto flex justify-center">
                <img src="/images/logo.svg" alt="Copy Company" class="h-10" />
            </div>
        </nav>

        <!-- Main Content -->
        <main class="flex-1 flex items-center justify-center px-6 py-12">
            <div class="max-w-4xl mx-auto text-center">
                <!-- Coming Soon Badge -->
                <div class="mb-8">
                    <div class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#0b1215]/5 rounded-full text-[#0b1215]/70 text-sm font-medium">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#a1854f] opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-[#a1854f]"></span>
                        </span>
                        Coming Soon
                    </div>
                </div>

                <!-- Headline -->
                <h1 class="font-serif text-5xl md:text-7xl font-light text-[#0b1215] mb-8 tracking-tight leading-none">
                    WRITE ONCE,<br />PUBLISH EVERYWHERE
                </h1>

                <!-- Description -->
                <p class="text-xl md:text-2xl text-[#0b1215]/60 mb-16 max-w-2xl mx-auto font-light leading-relaxed">
                    The all-in-one content platform for creators. Write blog posts, send newsletters,
                    and manage social media — all from one beautiful dashboard.
                </p>

                <!-- Features Grid -->
                <div class="grid md:grid-cols-3 gap-6 lg:gap-8">
                    <div class="p-8 bg-white rounded-2xl border border-[#0b1215]/5">
                        <div class="w-14 h-14 bg-[#0b1215] rounded-xl flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-medium text-[#0b1215] mb-3">Rich Blog Editor</h3>
                        <p class="text-[#0b1215]/60 leading-relaxed">
                            Beautiful writing experience with markdown support and AI assistance.
                        </p>
                    </div>

                    <div class="p-8 bg-white rounded-2xl border border-[#0b1215]/5">
                        <div class="w-14 h-14 bg-[#0b1215] rounded-xl flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-medium text-[#0b1215] mb-3">Built-in Newsletter</h3>
                        <p class="text-[#0b1215]/60 leading-relaxed">
                            Send your posts directly to subscribers. No third-party tools needed.
                        </p>
                    </div>

                    <div class="p-8 bg-white rounded-2xl border border-[#0b1215]/5">
                        <div class="w-14 h-14 bg-[#0b1215] rounded-xl flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-medium text-[#0b1215] mb-3">AI-Powered</h3>
                        <p class="text-[#0b1215]/60 leading-relaxed">
                            Generate content ideas, improve your writing, and create social posts.
                        </p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="py-8 text-center border-t border-[#0b1215]/5">
            <p class="text-[#0b1215]/50 text-sm">
                &copy; {{ new Date().getFullYear() }} Copy Company. All rights reserved.
            </p>
        </footer>
    </div>
</template>

<style>
/* Serif font for display headings - using system serif stack */
.font-serif {
    font-family: 'Georgia', 'Times New Roman', serif;
}
</style>
