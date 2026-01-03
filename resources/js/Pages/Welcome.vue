<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Welcome" />

    <div class="min-h-screen bg-gradient-to-br from-primary-50 to-white">
        <nav class="p-6">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <span class="text-2xl font-bold text-gray-900">Content Platform</span>
                <div class="space-x-4">
                    <Link href="/login" class="text-gray-600 hover:text-gray-900">Log in</Link>
                    <Link
                        href="/register"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition"
                    >
                        Get Started
                    </Link>
                </div>
            </div>
        </nav>

        <main class="max-w-7xl mx-auto px-6 py-20">
            <div class="text-center">
                <h1 class="text-5xl font-bold text-gray-900 mb-6">
                    Write Once, Publish Everywhere
                </h1>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto mb-10">
                    A creator-first content platform where your blog post becomes your newsletter,
                    and AI helps you reach your audience on every channel.
                </p>
                <div class="flex justify-center gap-4">
                    <Link
                        href="/register"
                        class="px-8 py-3 bg-primary-600 text-white font-semibold rounded-lg hover:bg-primary-700 transition"
                    >
                        Start Creating
                    </Link>
                    <a
                        href="#features"
                        class="px-8 py-3 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition"
                    >
                        Learn More
                    </a>
                </div>
            </div>

            <div id="features" class="mt-32 grid md:grid-cols-3 gap-8">
                <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">One Source of Truth</h3>
                    <p class="text-gray-600">
                        Write your content once. Your blog post becomes your newsletter and generates social posts automatically.
                    </p>
                </div>

                <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">AI-Assisted, Not AI-First</h3>
                    <p class="text-gray-600">
                        AI helps as much or as little as you want. Get suggestions, polish your writing, or draft from scratch.
                    </p>
                </div>

                <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">You Own Everything</h3>
                    <p class="text-gray-600">
                        Export your content, subscribers, and data anytime. Your audience belongs to you, not us.
                    </p>
                </div>
            </div>
        </main>
    </div>
</template>
