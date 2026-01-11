<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    event: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['close']);

const typeLabel = computed(() => {
    if (props.event.type === 'post') return 'Blog Post';
    if (props.event.type === 'newsletter') return 'Newsletter';
    if (props.event.type === 'social') return `Social Post (${platformLabel.value})`;
    return 'Content';
});

const platformLabel = computed(() => {
    const platforms = {
        instagram: 'Instagram',
        twitter: 'X (Twitter)',
        facebook: 'Facebook',
        linkedin: 'LinkedIn',
        pinterest: 'Pinterest',
        tiktok: 'TikTok',
    };
    return platforms[props.event.platform] || props.event.platform;
});

const typeColor = computed(() => {
    if (props.event.type === 'post') return 'bg-[#0b1215]/10 text-[#0b1215]';
    if (props.event.type === 'newsletter') return 'bg-[#a1854f]/20 text-[#a1854f]';
    if (props.event.type === 'social') return 'bg-pink-100 text-pink-800';
    return 'bg-[#0b1215]/5 text-[#0b1215]/70';
});

const statusColor = computed(() => {
    const colors = {
        scheduled: 'bg-[#a1854f]/20 text-[#a1854f]',
        published: 'bg-green-100 text-green-800',
        sent: 'bg-green-100 text-green-800',
        draft: 'bg-[#0b1215]/10 text-[#0b1215]/70',
    };
    return colors[props.event.status] || 'bg-[#0b1215]/5 text-[#0b1215]/70';
});

const platformIcon = computed(() => {
    const icons = {
        instagram: 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z',
        twitter: 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z',
        facebook: 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z',
        linkedin: 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z',
        pinterest: 'M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.401.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.354-.629-2.758-1.379l-.749 2.848c-.269 1.045-1.004 2.352-1.498 3.146 1.123.345 2.306.535 3.55.535 6.607 0 11.985-5.365 11.985-11.987C23.97 5.39 18.592.026 11.985.026L12.017 0z',
        tiktok: 'M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z',
    };
    return icons[props.event.platform];
});
</script>

<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full">
                <!-- Header -->
                <div class="flex items-center justify-between p-4 border-b border-[#0b1215]/10">
                    <div class="flex items-center space-x-3">
                        <!-- Type badge -->
                        <span
                            :class="typeColor"
                            class="px-2 py-1 text-xs font-medium rounded-full"
                        >
                            {{ typeLabel }}
                        </span>
                        <!-- Status badge -->
                        <span
                            :class="statusColor"
                            class="px-2 py-1 text-xs font-medium rounded-full capitalize"
                        >
                            {{ event.status }}
                        </span>
                    </div>
                    <button @click="emit('close')" class="text-[#0b1215]/40 hover:text-[#0b1215]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Content -->
                <div class="p-4 space-y-4">
                    <!-- Platform icon for social posts -->
                    <div v-if="event.type === 'social' && platformIcon" class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-[#0b1215]/60" fill="currentColor" viewBox="0 0 24 24">
                            <path :d="platformIcon" />
                        </svg>
                        <span class="text-sm text-[#0b1215]/60">{{ platformLabel }}</span>
                    </div>

                    <!-- Title -->
                    <h3 class="text-lg font-semibold text-[#0b1215]">{{ event.title }}</h3>

                    <!-- Time -->
                    <div class="flex items-center text-sm text-[#0b1215]/60">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ event.date }} at {{ event.time }}
                    </div>

                    <!-- Source post for social/newsletter -->
                    <div v-if="event.post_title" class="flex items-center text-sm text-[#0b1215]/60">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                        From: {{ event.post_title }}
                    </div>

                    <!-- Recipients for newsletter -->
                    <div v-if="event.type === 'newsletter' && event.recipients" class="flex items-center text-sm text-[#0b1215]/60">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        {{ event.recipients }} recipients
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 p-4 border-t border-[#0b1215]/10 bg-[#f7f7f7] rounded-b-2xl">
                    <button
                        @click="emit('close')"
                        class="px-4 py-2 text-[#0b1215] font-medium hover:bg-[#0b1215]/5 rounded-xl transition"
                    >
                        Close
                    </button>
                    <Link
                        v-if="event.type === 'post' && event.url"
                        :href="event.url"
                        class="px-4 py-2 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition"
                    >
                        Edit Post
                    </Link>
                    <Link
                        v-else-if="event.type === 'social'"
                        href="/social-posts"
                        class="px-4 py-2 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition"
                    >
                        View Social Posts
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
