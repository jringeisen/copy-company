<script setup>
import { Link, usePage, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    currentPage: {
        type: String,
        default: '',
    },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const brand = computed(() => page.props.auth?.brand);

const showUserMenu = ref(false);
const logoutForm = useForm({});

const logout = () => {
    logoutForm.post('/logout');
};

const navLinks = [
    { name: 'Posts', href: '/posts', key: 'posts' },
    { name: 'Media', href: '/media', key: 'media' },
    { name: 'Social', href: '/social-posts', key: 'social' },
    { name: 'Subscribers', href: '/subscribers', key: 'subscribers' },
    { name: 'Calendar', href: '/calendar', key: 'calendar' },
    { name: 'Content Sprints', href: '/content-sprints', key: 'sprints' },
];

const isActive = (key) => props.currentPage === key;
</script>

<template>
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Left side: Logo and nav links -->
                <div class="flex items-center space-x-8">
                    <Link href="/dashboard" class="text-xl font-semibold text-gray-900">
                        Content Platform
                    </Link>
                    <div class="hidden md:flex space-x-4">
                        <Link
                            v-for="link in navLinks"
                            :key="link.key"
                            :href="link.href"
                            :class="[
                                isActive(link.key)
                                    ? 'text-primary-600 font-medium'
                                    : 'text-gray-600 hover:text-gray-900',
                            ]"
                        >
                            {{ link.name }}
                        </Link>
                    </div>
                </div>

                <!-- Right side: User dropdown -->
                <div class="flex items-center">
                    <div v-if="user" class="relative">
                        <button
                            @click="showUserMenu = !showUserMenu"
                            @blur="setTimeout(() => showUserMenu = false, 150)"
                            class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 rounded-lg hover:bg-gray-50 transition"
                        >
                            <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                                <span class="text-primary-600 font-medium text-sm">
                                    {{ user.name.charAt(0).toUpperCase() }}
                                </span>
                            </div>
                            <span class="hidden sm:block">{{ user.name }}</span>
                            <svg
                                class="w-4 h-4 transition-transform"
                                :class="{ 'rotate-180': showUserMenu }"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Dropdown menu -->
                        <div
                            v-show="showUserMenu"
                            class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50"
                        >
                            <!-- User info -->
                            <div class="px-4 py-3 border-b border-gray-100">
                                <p class="text-sm font-medium text-gray-900">{{ user.name }}</p>
                                <p class="text-sm text-gray-500 truncate">{{ user.email }}</p>
                                <p v-if="brand" class="text-xs text-primary-600 mt-1">{{ brand.name }}</p>
                            </div>

                            <!-- Menu items -->
                            <div class="py-1">
                                <Link
                                    href="/settings/team"
                                    class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    Team
                                </Link>
                                <Link
                                    href="/settings/brand"
                                    class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Brand Settings
                                </Link>
                                <Link
                                    href="/settings/social"
                                    class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                    </svg>
                                    Social Connections
                                </Link>
                            </div>

                            <!-- Logout -->
                            <div class="border-t border-gray-100 py-1">
                                <button
                                    @click="logout"
                                    class="flex items-center gap-2 w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Log out
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div class="md:hidden border-t border-gray-200 px-4 py-2">
            <div class="flex flex-wrap gap-2">
                <Link
                    v-for="link in navLinks"
                    :key="link.key"
                    :href="link.href"
                    :class="[
                        'px-3 py-1.5 rounded-lg text-sm',
                        isActive(link.key)
                            ? 'bg-primary-100 text-primary-700 font-medium'
                            : 'text-gray-600 hover:bg-gray-100',
                    ]"
                >
                    {{ link.name }}
                </Link>
            </div>
        </div>
    </nav>
</template>
