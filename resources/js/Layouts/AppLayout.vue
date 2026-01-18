<script setup>
import { Link, usePage, useForm, router } from '@inertiajs/vue3';
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
const brands = computed(() => page.props.auth?.brands || []);

const mobileMenuOpen = ref(false);
const showBrandSwitcher = ref(false);
const expandedMenus = ref(['social']); // Social expanded by default when on social pages
const logoutForm = useForm({});

const logout = () => {
    logoutForm.post('/logout');
};

const switchBrand = (brandId) => {
    router.post(`/brands/${brandId}/switch`);
    showBrandSwitcher.value = false;
};

const toggleSubmenu = (key) => {
    const index = expandedMenus.value.indexOf(key);
    if (index > -1) {
        expandedMenus.value.splice(index, 1);
    } else {
        expandedMenus.value.push(key);
    }
};

const isParentActive = (link) => {
    if (link.children) {
        return link.children.some(child => props.currentPage === child.key);
    }
    return props.currentPage === link.key;
};

const navLinks = [
    { name: 'Dashboard', href: '/dashboard', key: 'dashboard', icon: 'home' },
    { name: 'Posts', href: '/posts', key: 'posts', icon: 'document' },
    { name: 'Media', href: '/media', key: 'media', icon: 'photo' },
    {
        name: 'Social',
        key: 'social',
        icon: 'share',
        children: [
            { name: 'All Posts', href: '/social-posts', key: 'social-posts' },
            { name: 'Queue', href: '/social-posts/queue', key: 'social-queue' },
            { name: 'Loops', href: '/loops', key: 'loops' },
        ]
    },
    { name: 'Subscribers', href: '/subscribers', key: 'subscribers', icon: 'users' },
    { name: 'Newsletters', href: '/newsletters', key: 'newsletters', icon: 'envelope' },
    { name: 'Calendar', href: '/calendar', key: 'calendar', icon: 'calendar' },
    { name: 'Content Sprints', href: '/content-sprints', key: 'sprints', icon: 'bolt' },
];

const settingsLinks = [
    { name: 'Billing', href: '/settings/billing', key: 'billing', icon: 'credit-card' },
    { name: 'Team', href: '/settings/team', key: 'team', icon: 'user-group' },
    { name: 'Brand Settings', href: '/settings/brand', key: 'brand-settings', icon: 'cog' },
    { name: 'Email Domain', href: '/settings/email-domain', key: 'email-domain', icon: 'mail' },
    { name: 'Social Connections', href: '/settings/social', key: 'social-settings', icon: 'link' },
];

const isActive = (key) => props.currentPage === key;

const closeMobileMenu = () => {
    mobileMenuOpen.value = false;
};
</script>

<template>
    <div class="min-h-screen flex bg-[#fcfbf8]">
        <!-- Desktop Sidebar -->
        <aside
            class="w-64 bg-[#0b1215] flex-col fixed inset-y-0 left-0 z-30 hidden lg:flex"
        >
            <!-- Logo -->
            <div class="h-16 flex items-center px-6 border-b border-white/10">
                <Link href="/dashboard">
                    <img src="/images/logo.svg" alt="Copy Company" class="h-8" />
                </Link>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <template v-for="link in navLinks" :key="link.key">
                    <!-- Link with children (expandable) -->
                    <div v-if="link.children">
                        <button
                            @click="toggleSubmenu(link.key)"
                            :class="[
                                'w-full flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors',
                                isParentActive(link)
                                    ? 'bg-white/10 text-white'
                                    : 'text-white/60 hover:bg-white/5 hover:text-white',
                            ]"
                        >
                            <span class="flex items-center gap-3">
                                <!-- Share icon for Social -->
                                <svg v-if="link.icon === 'share'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                </svg>
                                {{ link.name }}
                            </span>
                            <svg
                                :class="['w-4 h-4 transition-transform', expandedMenus.includes(link.key) && 'rotate-180']"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div v-show="expandedMenus.includes(link.key)" class="ml-8 mt-1 space-y-1">
                            <Link
                                v-for="child in link.children"
                                :key="child.key"
                                :href="child.href"
                                :class="[
                                    'block px-3 py-2 rounded-xl text-sm transition-colors',
                                    isActive(child.key)
                                        ? 'bg-white/10 text-white'
                                        : 'text-white/50 hover:bg-white/5 hover:text-white',
                                ]"
                            >
                                {{ child.name }}
                            </Link>
                        </div>
                    </div>
                    <!-- Regular link -->
                    <Link
                        v-else
                        :href="link.href"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors',
                            isActive(link.key)
                                ? 'bg-white/10 text-white'
                                : 'text-white/60 hover:bg-white/5 hover:text-white',
                        ]"
                    >
                        <!-- Home icon -->
                        <svg v-if="link.icon === 'home'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <!-- Document icon -->
                        <svg v-else-if="link.icon === 'document'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <!-- Photo icon -->
                        <svg v-else-if="link.icon === 'photo'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <!-- Users icon -->
                        <svg v-else-if="link.icon === 'users'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <!-- Calendar icon -->
                        <svg v-else-if="link.icon === 'calendar'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <!-- Bolt icon -->
                        <svg v-else-if="link.icon === 'bolt'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <!-- Envelope icon -->
                        <svg v-else-if="link.icon === 'envelope'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        {{ link.name }}
                    </Link>
                </template>
            </nav>

            <!-- Settings Section -->
            <div class="px-3 py-4 border-t border-white/10">
                <p class="px-3 mb-2 text-xs font-medium text-[#a1854f] uppercase tracking-wider">Settings</p>
                <Link
                    v-for="link in settingsLinks"
                    :key="link.key"
                    :href="link.href"
                    :class="[
                        'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors',
                        isActive(link.key)
                            ? 'bg-white/10 text-white'
                            : 'text-white/60 hover:bg-white/5 hover:text-white',
                    ]"
                >
                    <!-- Credit Card icon -->
                    <svg v-if="link.icon === 'credit-card'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    <!-- User Group icon -->
                    <svg v-else-if="link.icon === 'user-group'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <!-- Cog icon -->
                    <svg v-else-if="link.icon === 'cog'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <!-- Mail icon -->
                    <svg v-else-if="link.icon === 'mail'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                    </svg>
                    <!-- Link icon -->
                    <svg v-else-if="link.icon === 'link'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                    </svg>
                    {{ link.name }}
                </Link>
            </div>

            <!-- User/Brand Section -->
            <div class="border-t border-white/10 p-4">
                <!-- Brand Switcher -->
                <div v-if="brand" class="relative mb-3">
                    <button
                        @click="showBrandSwitcher = !showBrandSwitcher"
                        class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-medium text-white bg-white/10 rounded-xl hover:bg-white/15 transition-colors"
                    >
                        <span class="truncate">{{ brand.name }}</span>
                        <svg class="w-4 h-4 shrink-0 transition-transform text-white/60" :class="{ 'rotate-180': showBrandSwitcher }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Brand Dropdown -->
                    <div
                        v-show="showBrandSwitcher"
                        class="absolute bottom-full left-0 right-0 mb-2 bg-[#1a1f24] rounded-xl shadow-lg border border-white/10 py-1 max-h-48 overflow-y-auto"
                    >
                        <button
                            v-for="b in brands"
                            :key="b.id"
                            @click="switchBrand(b.id)"
                            :class="[
                                'flex items-center justify-between w-full px-3 py-2.5 text-sm text-left transition-colors',
                                b.id === brand?.id
                                    ? 'bg-white/10 text-white'
                                    : 'text-white/60 hover:bg-white/5 hover:text-white',
                            ]"
                        >
                            <span class="truncate">{{ b.name }}</span>
                            <svg v-if="b.id === brand?.id" class="w-4 h-4 text-[#a1854f] shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                        <Link
                            href="/brands/create"
                            class="flex items-center gap-2 px-3 py-2.5 text-sm text-[#a1854f] hover:bg-white/5"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Create new brand
                        </Link>
                    </div>
                </div>

                <!-- User Info & Actions -->
                <div v-if="user" class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 bg-[#a1854f] rounded-full flex items-center justify-center shrink-0">
                        <span class="text-white font-medium text-sm">
                            {{ user.name.charAt(0).toUpperCase() }}
                        </span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ user.name }}</p>
                        <p class="text-xs text-white/50 truncate">{{ user.email }}</p>
                    </div>
                </div>

                <!-- Logout -->
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

        <!-- Mobile Header -->
        <div class="lg:hidden fixed top-0 left-0 right-0 h-16 bg-[#0b1215] z-20 flex items-center px-4">
            <button
                @click="mobileMenuOpen = true"
                class="p-2 text-white/60 hover:text-white hover:bg-white/10 rounded-xl transition-colors"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <Link href="/dashboard" class="ml-4">
                <img src="/images/logo.svg" alt="Copy Company" class="h-7" />
            </Link>
        </div>

        <!-- Mobile Sidebar Overlay -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-300"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="mobileMenuOpen" class="lg:hidden fixed inset-0 z-40">
                    <!-- Backdrop -->
                    <div class="fixed inset-0 bg-black/60" @click="closeMobileMenu" />

                    <!-- Sidebar -->
                    <Transition
                        enter-active-class="transition-transform duration-300"
                        enter-from-class="-translate-x-full"
                        enter-to-class="translate-x-0"
                        leave-active-class="transition-transform duration-300"
                        leave-from-class="translate-x-0"
                        leave-to-class="-translate-x-full"
                    >
                        <aside
                            v-if="mobileMenuOpen"
                            class="fixed inset-y-0 left-0 w-64 bg-[#0b1215] flex flex-col shadow-xl"
                        >
                            <!-- Logo & Close -->
                            <div class="h-16 flex items-center justify-between px-6 border-b border-white/10">
                                <Link href="/dashboard" @click="closeMobileMenu">
                                    <img src="/images/logo.svg" alt="Copy Company" class="h-7" />
                                </Link>
                                <button
                                    @click="closeMobileMenu"
                                    class="p-1 text-white/40 hover:text-white transition-colors"
                                >
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Navigation -->
                            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                                <template v-for="link in navLinks" :key="link.key">
                                    <!-- Link with children (expandable) -->
                                    <div v-if="link.children">
                                        <button
                                            @click="toggleSubmenu(link.key)"
                                            :class="[
                                                'w-full flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors',
                                                isParentActive(link)
                                                    ? 'bg-white/10 text-white'
                                                    : 'text-white/60 hover:bg-white/5 hover:text-white',
                                            ]"
                                        >
                                            <span class="flex items-center gap-3">
                                                <svg v-if="link.icon === 'share'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                                </svg>
                                                {{ link.name }}
                                            </span>
                                            <svg
                                                :class="['w-4 h-4 transition-transform', expandedMenus.includes(link.key) && 'rotate-180']"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        <div v-show="expandedMenus.includes(link.key)" class="ml-8 mt-1 space-y-1">
                                            <Link
                                                v-for="child in link.children"
                                                :key="child.key"
                                                :href="child.href"
                                                @click="closeMobileMenu"
                                                :class="[
                                                    'block px-3 py-2 rounded-xl text-sm transition-colors',
                                                    isActive(child.key)
                                                        ? 'bg-white/10 text-white'
                                                        : 'text-white/50 hover:bg-white/5 hover:text-white',
                                                ]"
                                            >
                                                {{ child.name }}
                                            </Link>
                                        </div>
                                    </div>
                                    <!-- Regular link -->
                                    <Link
                                        v-else
                                        :href="link.href"
                                        @click="closeMobileMenu"
                                        :class="[
                                            'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors',
                                            isActive(link.key)
                                                ? 'bg-white/10 text-white'
                                                : 'text-white/60 hover:bg-white/5 hover:text-white',
                                        ]"
                                    >
                                        <!-- Icons same as desktop -->
                                        <svg v-if="link.icon === 'home'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                        <svg v-else-if="link.icon === 'document'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <svg v-else-if="link.icon === 'photo'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <svg v-else-if="link.icon === 'users'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                        <svg v-else-if="link.icon === 'calendar'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <svg v-else-if="link.icon === 'bolt'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        <svg v-else-if="link.icon === 'envelope'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        {{ link.name }}
                                    </Link>
                                </template>
                            </nav>

                            <!-- Settings Section -->
                            <div class="px-3 py-4 border-t border-white/10">
                                <p class="px-3 mb-2 text-xs font-medium text-[#a1854f] uppercase tracking-wider">Settings</p>
                                <Link
                                    v-for="link in settingsLinks"
                                    :key="link.key"
                                    :href="link.href"
                                    @click="closeMobileMenu"
                                    :class="[
                                        'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors',
                                        isActive(link.key)
                                            ? 'bg-white/10 text-white'
                                            : 'text-white/60 hover:bg-white/5 hover:text-white',
                                    ]"
                                >
                                    <svg v-if="link.icon === 'credit-card'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                    <svg v-else-if="link.icon === 'user-group'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <svg v-else-if="link.icon === 'cog'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <svg v-else-if="link.icon === 'mail'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                    </svg>
                                    <svg v-else-if="link.icon === 'link'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                    </svg>
                                    {{ link.name }}
                                </Link>
                            </div>

                            <!-- User/Brand Section (Mobile) -->
                            <div class="border-t border-white/10 p-4">
                                <!-- Brand Switcher -->
                                <div v-if="brand" class="mb-3">
                                    <select
                                        @change="(e) => switchBrand(e.target.value)"
                                        class="w-full px-3 py-2.5 text-sm font-medium text-white bg-white/10 border-0 rounded-xl focus:ring-2 focus:ring-[#a1854f]"
                                    >
                                        <option v-for="b in brands" :key="b.id" :value="b.id" :selected="b.id === brand?.id" class="bg-[#0b1215]">
                                            {{ b.name }}
                                        </option>
                                    </select>
                                </div>

                                <!-- User Info -->
                                <div v-if="user" class="flex items-center gap-3 mb-3">
                                    <div class="w-9 h-9 bg-[#a1854f] rounded-full flex items-center justify-center shrink-0">
                                        <span class="text-white font-medium text-sm">
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-white truncate">{{ user.name }}</p>
                                        <p class="text-xs text-white/50 truncate">{{ user.email }}</p>
                                    </div>
                                </div>

                                <!-- Logout -->
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
                    </Transition>
                </div>
            </Transition>
        </Teleport>

        <!-- Main Content -->
        <div class="flex-1 lg:ml-64">
            <main class="min-h-screen bg-[#fcfbf8] py-8 px-4 sm:px-6 lg:px-8 pt-24 lg:pt-8">
                <slot />
            </main>
        </div>
    </div>
</template>
