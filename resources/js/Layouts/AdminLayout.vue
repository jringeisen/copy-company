<script setup>
import { Link, usePage, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import ToastContainer from '@/Components/ToastContainer.vue';

const props = defineProps({
    currentPage: {
        type: String,
        default: '',
    },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const logoutForm = useForm({});

const logout = () => {
    logoutForm.post('/logout');
};

const navLinks = [
    { name: 'Disputes', href: '/admin/disputes', key: 'disputes', icon: 'exclamation' },
    { name: 'Users', href: '/admin/users', key: 'users', icon: 'users' },
];

const isActive = (key) => props.currentPage === key;
</script>

<template>
    <div class="min-h-screen flex bg-[#fcfbf8]">
        <!-- Desktop Sidebar -->
        <aside class="w-64 bg-[#1a1f24] flex-col fixed inset-y-0 left-0 z-30 hidden lg:flex">
            <!-- Logo -->
            <div class="h-16 flex items-center px-6 border-b border-white/10">
                <Link href="/admin/disputes" class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-red-500 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                    </div>
                    <span class="text-white font-bold">Admin</span>
                </Link>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-3 py-4 space-y-1">
                <Link
                    v-for="link in navLinks"
                    :key="link.key"
                    :href="link.href"
                    :class="[
                        'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors',
                        isActive(link.key)
                            ? 'bg-white/10 text-white'
                            : 'text-white/60 hover:bg-white/5 hover:text-white',
                    ]"
                >
                    <svg v-if="link.icon === 'exclamation'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <svg v-else-if="link.icon === 'users'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    {{ link.name }}
                </Link>
            </nav>

            <!-- Back to App -->
            <div class="px-3 py-4 border-t border-white/10">
                <Link
                    href="/dashboard"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-white/60 hover:bg-white/5 hover:text-white transition-colors"
                >
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
                    </svg>
                    Back to App
                </Link>
            </div>

            <!-- User Section -->
            <div class="border-t border-white/10 p-4">
                <div v-if="user" class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 bg-red-500 rounded-full flex items-center justify-center shrink-0">
                        <span class="text-white font-medium text-sm">
                            {{ user.name.charAt(0).toUpperCase() }}
                        </span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ user.name }}</p>
                        <p class="text-xs text-red-400 truncate">Admin</p>
                    </div>
                </div>

                <button
                    @click="logout"
                    class="w-full flex items-center justify-center gap-2 px-3 py-2.5 text-sm font-medium text-white/60 bg-white/5 rounded-xl hover:bg-white/10 hover:text-white transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Sign out
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 lg:ml-64">
            <main class="min-h-screen bg-[#fcfbf8] py-8 px-4 sm:px-6 lg:px-8">
                <slot />
            </main>
        </div>

        <!-- Toast Notifications -->
        <ToastContainer />
    </div>
</template>
