<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppNavigation from '@/Components/AppNavigation.vue';

defineProps({
    platforms: Array,
    brand: Object,
});

const platformIcons = {
    twitter: `<svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>`,
    facebook: `<svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>`,
    instagram: `<svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>`,
    linkedin: `<svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>`,
    pinterest: `<svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.401.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.354-.629-2.758-1.379l-.749 2.848c-.269 1.045-1.004 2.352-1.498 3.146 1.123.345 2.306.535 3.55.535 6.607 0 11.985-5.365 11.985-11.987C23.97 5.39 18.592.026 11.985.026L12.017 0z"/></svg>`,
    tiktok: `<svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>`,
};

const platformColors = {
    twitter: 'bg-black',
    facebook: 'bg-blue-600',
    instagram: 'bg-gradient-to-tr from-yellow-400 via-red-500 to-purple-500',
    linkedin: 'bg-blue-700',
    pinterest: 'bg-red-600',
    tiktok: 'bg-black',
};

const connect = (platform) => {
    window.location.href = `/settings/social/${platform}/redirect`;
};

const disconnect = (platform) => {
    if (confirm(`Are you sure you want to disconnect ${platform}?`)) {
        router.delete(`/settings/social/${platform}`);
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};
</script>

<template>
    <Head title="Social Connections" />

    <div class="min-h-screen bg-gray-50">
        <AppNavigation current-page="settings" />

        <!-- Settings Sub-navigation -->
        <div class="bg-white border-b border-gray-200">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <nav class="flex space-x-8">
                    <Link
                        href="/settings/brand"
                        class="border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300"
                    >
                        Brand
                    </Link>
                    <Link
                        href="/settings/social"
                        class="border-b-2 border-primary-500 py-4 px-1 text-sm font-medium text-primary-600"
                    >
                        Social Connections
                    </Link>
                    <Link
                        href="/settings/email-domain"
                        class="border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300"
                    >
                        Email Domain
                    </Link>
                </nav>
            </div>
        </div>

        <main class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">Social Connections</h1>
                <p class="text-gray-600 mt-1">Connect your social media accounts to publish content directly from the platform.</p>
            </div>

            <!-- Platform Grid -->
            <div class="grid gap-4 md:grid-cols-2">
                <div
                    v-for="platform in platforms"
                    :key="platform.identifier"
                    class="bg-white rounded-xl shadow-sm border border-gray-200 p-6"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-4">
                            <!-- Platform Icon -->
                            <div
                                class="w-12 h-12 rounded-xl flex items-center justify-center text-white"
                                :class="platformColors[platform.identifier]"
                                v-html="platformIcons[platform.identifier]"
                            ></div>
                            <div>
                                <h3 class="font-semibold text-gray-900">{{ platform.name }}</h3>
                                <div v-if="platform.connected" class="mt-1">
                                    <!-- Needs configuration warning -->
                                    <div v-if="platform.needs_configuration" class="flex items-center gap-1 text-sm text-amber-600">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                        Needs configuration
                                    </div>
                                    <!-- Fully connected -->
                                    <div v-else class="flex items-center gap-2">
                                        <span class="inline-flex items-center gap-1 text-sm text-green-600">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                            Connected
                                        </span>
                                        <span v-if="platform.account_name" class="text-sm text-gray-500">
                                            as {{ platform.account_name }}
                                        </span>
                                    </div>
                                    <!-- Publishing target info -->
                                    <p v-if="platform.configured_account" class="text-xs text-gray-500 mt-1">
                                        Publishing to: {{ platform.configured_account }}
                                    </p>
                                </div>
                                <p v-else class="text-sm text-gray-500 mt-1">Not connected</p>
                            </div>
                        </div>
                    </div>

                    <!-- Connection Info -->
                    <div v-if="platform.connected && platform.connected_at" class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-xs text-gray-500">
                            Connected {{ formatDate(platform.connected_at) }}
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="mt-4 flex gap-3">
                        <template v-if="platform.connected">
                            <!-- Configure button for platforms that need page/board selection -->
                            <Link
                                v-if="platform.needs_configuration"
                                :href="`/settings/social/${platform.identifier}/select`"
                                class="flex-1 px-4 py-2 text-sm font-medium text-white bg-amber-500 hover:bg-amber-600 rounded-lg transition text-center"
                            >
                                Configure
                            </Link>
                            <Link
                                v-else-if="platform.configured_account"
                                :href="`/settings/social/${platform.identifier}/select`"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition"
                            >
                                Change
                            </Link>
                            <button
                                v-if="!platform.needs_configuration"
                                @click="connect(platform.identifier)"
                                class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition"
                            >
                                Reconnect
                            </button>
                            <button
                                @click="disconnect(platform.identifier)"
                                class="px-4 py-2 text-sm font-medium text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition"
                            >
                                Disconnect
                            </button>
                        </template>
                        <template v-else>
                            <button
                                v-if="platform.supported"
                                @click="connect(platform.identifier)"
                                class="flex-1 px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition"
                            >
                                Connect {{ platform.name }}
                            </button>
                            <span
                                v-else
                                class="flex-1 px-4 py-2 text-sm font-medium text-gray-400 bg-gray-100 rounded-lg text-center cursor-not-allowed"
                            >
                                Coming Soon
                            </span>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Help Text -->
            <div class="mt-8 p-4 bg-blue-50 rounded-lg">
                <div class="flex gap-3">
                    <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <h4 class="text-sm font-medium text-blue-800">About Social Connections</h4>
                        <p class="text-sm text-blue-700 mt-1">
                            Connecting your accounts allows you to publish content directly to each platform.
                            We only request the permissions needed to publish on your behalf and never post without your approval.
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
