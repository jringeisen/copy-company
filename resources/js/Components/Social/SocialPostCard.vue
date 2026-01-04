<script setup>
import { computed, ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    socialPost: {
        type: Object,
        required: true,
    },
    connectedPlatforms: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['edit', 'delete', 'queue', 'schedule']);

const isPublishing = ref(false);
const showDeleteModal = ref(false);
const isDeleting = ref(false);

const isPlatformConnected = computed(() => {
    return props.connectedPlatforms.includes(props.socialPost.platform);
});

const platformIcons = {
    instagram: 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z',
    twitter: 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z',
    facebook: 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z',
    linkedin: 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z',
    pinterest: 'M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.401.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.354-.629-2.758-1.379l-.749 2.848c-.269 1.045-1.004 2.352-1.498 3.146 1.123.345 2.306.535 3.55.535 6.607 0 11.985-5.365 11.985-11.987C23.97 5.39 18.592.026 11.985.026L12.017 0z',
    tiktok: 'M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z',
};

const platformColors = {
    instagram: 'bg-gradient-to-br from-purple-600 via-pink-500 to-orange-400',
    twitter: 'bg-black',
    facebook: 'bg-blue-600',
    linkedin: 'bg-blue-700',
    pinterest: 'bg-red-600',
    tiktok: 'bg-black',
};

const statusColors = {
    draft: 'bg-gray-100 text-gray-700',
    queued: 'bg-yellow-100 text-yellow-700',
    scheduled: 'bg-blue-100 text-blue-700',
    published: 'bg-green-100 text-green-700',
    failed: 'bg-red-100 text-red-700',
};

const truncatedContent = computed(() => {
    if (props.socialPost.content.length > 150) {
        return props.socialPost.content.substring(0, 150) + '...';
    }
    return props.socialPost.content;
});

const handleDelete = () => {
    isDeleting.value = true;
    router.delete(`/social-posts/${props.socialPost.id}`, {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
        },
    });
};

const handleQueue = () => {
    router.post(`/social-posts/${props.socialPost.id}/queue`);
};

const handlePublishNow = () => {
    if (!isPlatformConnected.value) return;
    isPublishing.value = true;
    router.post(`/social-posts/${props.socialPost.id}/publish-now`, {}, {
        onFinish: () => {
            isPublishing.value = false;
        },
    });
};

const handleRetry = () => {
    if (!isPlatformConnected.value) return;
    isPublishing.value = true;
    router.post(`/social-posts/${props.socialPost.id}/retry`, {}, {
        onFinish: () => {
            isPublishing.value = false;
        },
    });
};
</script>

<template>
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <div class="flex items-center space-x-3">
                <!-- Platform Icon -->
                <div
                    class="w-10 h-10 rounded-full flex items-center justify-center text-white"
                    :class="platformColors[socialPost.platform]"
                >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path :d="platformIcons[socialPost.platform]" />
                    </svg>
                </div>
                <div>
                    <div class="font-medium text-gray-900">{{ socialPost.platform_display }}</div>
                    <div class="text-xs text-gray-500">{{ socialPost.format }}</div>
                </div>
            </div>

            <!-- Status Badge -->
            <span
                class="px-2 py-1 text-xs font-medium rounded-full"
                :class="statusColors[socialPost.status]"
            >
                {{ socialPost.status.charAt(0).toUpperCase() + socialPost.status.slice(1) }}
            </span>
        </div>

        <!-- Connection Warning -->
        <div v-if="!isPlatformConnected && connectedPlatforms.length > 0" class="mx-4 mt-2 p-2 bg-amber-50 border border-amber-200 rounded-lg">
            <div class="flex items-center gap-2 text-xs text-amber-700">
                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <span>{{ socialPost.platform_display }} is not connected.</span>
                <Link href="/settings/social" class="font-medium underline hover:no-underline">Connect</Link>
            </div>
        </div>

        <!-- Content Preview -->
        <div class="p-4">
            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ truncatedContent }}</p>

            <!-- Hashtags -->
            <div v-if="socialPost.hashtags && socialPost.hashtags.length > 0" class="mt-3 flex flex-wrap gap-1">
                <span
                    v-for="hashtag in socialPost.hashtags.slice(0, 5)"
                    :key="hashtag"
                    class="text-xs text-primary-600 bg-primary-50 px-2 py-0.5 rounded"
                >
                    #{{ hashtag }}
                </span>
                <span v-if="socialPost.hashtags.length > 5" class="text-xs text-gray-500">
                    +{{ socialPost.hashtags.length - 5 }} more
                </span>
            </div>

            <!-- Source Post -->
            <div v-if="socialPost.post" class="mt-3 pt-3 border-t border-gray-100">
                <div class="flex items-center text-xs text-gray-500">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                    </svg>
                    From: {{ socialPost.post.title }}
                </div>
            </div>

            <!-- AI Badge -->
            <div v-if="socialPost.ai_generated" class="mt-2 flex items-center text-xs text-gray-500">
                <svg class="w-4 h-4 mr-1 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
                AI Generated{{ socialPost.user_edited ? ' (edited)' : '' }}
            </div>
        </div>

        <!-- Schedule Info -->
        <div v-if="socialPost.scheduled_at" class="px-4 pb-3">
            <div class="flex items-center text-xs text-blue-600">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Scheduled: {{ socialPost.scheduled_at }}
            </div>
        </div>

        <!-- Failure Reason -->
        <div v-if="socialPost.status === 'failed' && socialPost.failure_reason" class="mx-4 mb-3 p-2 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-start gap-2 text-xs text-red-700">
                <svg class="w-4 h-4 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <span>{{ socialPost.failure_reason }}</span>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between p-4 border-t border-gray-100 bg-gray-50 rounded-b-lg">
            <div class="flex items-center space-x-2">
                <button
                    @click="emit('edit', socialPost)"
                    class="text-sm text-gray-600 hover:text-gray-900 flex items-center"
                    :disabled="isPublishing"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit
                </button>

                <button
                    v-if="socialPost.status === 'draft'"
                    @click="handleQueue"
                    class="text-sm text-yellow-600 hover:text-yellow-700 flex items-center"
                    :disabled="isPublishing"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Queue
                </button>

                <button
                    v-if="['draft', 'queued'].includes(socialPost.status)"
                    @click="emit('schedule', socialPost)"
                    class="text-sm text-blue-600 hover:text-blue-700 flex items-center"
                    :disabled="isPublishing"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Schedule
                </button>

                <!-- Publish Now button -->
                <button
                    v-if="['draft', 'queued', 'scheduled'].includes(socialPost.status) && isPlatformConnected"
                    @click="handlePublishNow"
                    :disabled="isPublishing"
                    class="text-sm text-green-600 hover:text-green-700 flex items-center disabled:opacity-50"
                >
                    <svg v-if="isPublishing" class="w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    {{ isPublishing ? 'Publishing...' : 'Publish Now' }}
                </button>

                <!-- Retry button for failed posts -->
                <button
                    v-if="socialPost.status === 'failed' && isPlatformConnected"
                    @click="handleRetry"
                    :disabled="isPublishing"
                    class="text-sm text-orange-600 hover:text-orange-700 flex items-center disabled:opacity-50"
                >
                    <svg v-if="isPublishing" class="w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    {{ isPublishing ? 'Retrying...' : 'Retry' }}
                </button>
            </div>

            <button
                @click="showDeleteModal = true"
                class="text-sm text-red-600 hover:text-red-700"
                :disabled="isPublishing"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
        </div>

        <ConfirmModal
            :show="showDeleteModal"
            title="Delete Social Post"
            message="Are you sure you want to delete this social post? This action cannot be undone."
            confirm-text="Delete"
            :processing="isDeleting"
            @confirm="handleDelete"
            @cancel="showDeleteModal = false"
        />
    </div>
</template>
