<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    user: Object,
    brand: Object,
    stats: Object,
});

const logoutForm = useForm({});

const logout = () => {
    logoutForm.post('/logout');
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="min-h-screen bg-gray-50">
        <!-- Navigation -->
        <nav class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <span class="text-xl font-semibold text-gray-900">Content Platform</span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-600">{{ user.name }}</span>
                        <button
                            @click="logout"
                            class="text-gray-500 hover:text-gray-700"
                        >
                            Log out
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Brand setup prompt if no brand exists -->
            <div v-if="!brand" class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-2">Welcome to Content Platform!</h2>
                <p class="text-gray-600 mb-4">Let's set up your brand to get started.</p>
                <Link
                    href="/brands/create"
                    class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition"
                >
                    Create Your Brand
                </Link>
            </div>

            <!-- Stats Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm font-medium text-gray-500">Published Posts</div>
                    <div class="mt-1 text-3xl font-semibold text-gray-900">{{ stats.postsCount }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm font-medium text-gray-500">Subscribers</div>
                    <div class="mt-1 text-3xl font-semibold text-gray-900">{{ stats.subscribersCount }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm font-medium text-gray-500">Drafts</div>
                    <div class="mt-1 text-3xl font-semibold text-gray-900">{{ stats.draftsCount }}</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <Link
                        href="/posts/create"
                        class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition"
                    >
                        <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900">Write New Post</div>
                            <div class="text-sm text-gray-500">Create a new blog post or newsletter</div>
                        </div>
                    </Link>
                    <Link
                        href="/posts"
                        class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition"
                    >
                        <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900">View All Posts</div>
                            <div class="text-sm text-gray-500">Manage your published and draft content</div>
                        </div>
                    </Link>
                    <Link
                        href="/social-posts"
                        class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition"
                    >
                        <div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-5 h-5 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900">Social Posts</div>
                            <div class="text-sm text-gray-500">Generate and schedule social content</div>
                        </div>
                    </Link>
                    <Link
                        href="/content-sprints"
                        class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition"
                    >
                        <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900">Content Sprint</div>
                            <div class="text-sm text-gray-500">Generate a month of content ideas</div>
                        </div>
                    </Link>
                    <Link
                        href="/calendar"
                        class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition"
                    >
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900">Content Calendar</div>
                            <div class="text-sm text-gray-500">View and schedule all content</div>
                        </div>
                    </Link>
                    <Link
                        href="/subscribers"
                        class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition"
                    >
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900">Subscribers</div>
                            <div class="text-sm text-gray-500">Manage your newsletter audience</div>
                        </div>
                    </Link>
                    <Link
                        href="/settings/brand"
                        class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition"
                    >
                        <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900">Brand Settings</div>
                            <div class="text-sm text-gray-500">Customize your brand and voice</div>
                        </div>
                    </Link>
                </div>
            </div>
        </main>
    </div>
</template>
