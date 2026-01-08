<script setup>
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Coming Soon - Copy Company" />

    <div class="min-h-screen bg-linear-to-br from-primary-600 via-primary-700 to-indigo-800 flex flex-col">
        <!-- Main Content -->
        <main class="flex-1 flex items-center justify-center px-6 py-12">
            <div class="max-w-3xl mx-auto text-center">
                <!-- Logo/Brand -->
                <div class="mb-8">
                    <h1 class="text-4xl md:text-5xl font-bold text-white mb-2">
                        Copy Company
                    </h1>
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 rounded-full text-primary-100 text-sm font-medium">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-green-400"></span>
                        </span>
                        Coming Soon
                    </div>
                </div>

                <!-- Headline -->
                <h2 class="text-2xl md:text-4xl font-bold text-white mb-6">
                    Write Once, Publish Everywhere
                </h2>

                <!-- Description -->
                <p class="text-lg md:text-xl text-primary-100 mb-12 max-w-2xl mx-auto leading-relaxed">
                    The all-in-one content platform for creators. Write blog posts, send newsletters,
                    and manage social media — all from one beautiful dashboard.
                </p>

                <!-- Features Grid -->
                <div class="grid md:grid-cols-3 gap-6 mb-12">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 text-left">
                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <h3 class="text-white font-semibold mb-2">Rich Blog Editor</h3>
                        <p class="text-primary-200 text-sm">
                            Beautiful writing experience with markdown support and AI assistance.
                        </p>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 text-left">
                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-white font-semibold mb-2">Built-in Newsletter</h3>
                        <p class="text-primary-200 text-sm">
                            Send your posts directly to subscribers. No third-party tools needed.
                        </p>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 text-left">
                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="text-white font-semibold mb-2">AI-Powered</h3>
                        <p class="text-primary-200 text-sm">
                            Generate content ideas, improve your writing, and create social posts.
                        </p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="py-6 text-center">
            <p class="text-primary-300 text-sm">
                &copy; {{ new Date().getFullYear() }} Copy Company. All rights reserved.
            </p>
        </footer>
    </div>
</template>
