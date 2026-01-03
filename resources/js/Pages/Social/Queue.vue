<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    queuedPosts: Array,
    brand: Object,
});

const selectedPosts = ref([]);
const showBulkScheduleModal = ref(false);

const bulkScheduleForm = useForm({
    social_post_ids: [],
    scheduled_at: '',
    interval_minutes: 60,
});

const platformColors = {
    instagram: 'bg-gradient-to-br from-purple-600 via-pink-500 to-orange-400',
    twitter: 'bg-black',
    facebook: 'bg-blue-600',
    linkedin: 'bg-blue-700',
    pinterest: 'bg-red-600',
    tiktok: 'bg-black',
};

const platformIcons = {
    instagram: 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z',
    twitter: 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z',
    facebook: 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z',
    linkedin: 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z',
    pinterest: 'M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.401.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.354-.629-2.758-1.379l-.749 2.848c-.269 1.045-1.004 2.352-1.498 3.146 1.123.345 2.306.535 3.55.535 6.607 0 11.985-5.365 11.985-11.987C23.97 5.39 18.592.026 11.985.026L12.017 0z',
    tiktok: 'M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z',
};

const toggleSelect = (postId) => {
    const index = selectedPosts.value.indexOf(postId);
    if (index > -1) {
        selectedPosts.value.splice(index, 1);
    } else {
        selectedPosts.value.push(postId);
    }
};

const selectAll = () => {
    if (selectedPosts.value.length === props.queuedPosts.length) {
        selectedPosts.value = [];
    } else {
        selectedPosts.value = props.queuedPosts.map(p => p.id);
    }
};

const removeFromQueue = (postId) => {
    router.delete(`/social-posts/${postId}`);
};

const openBulkSchedule = () => {
    bulkScheduleForm.social_post_ids = [...selectedPosts.value];
    showBulkScheduleModal.value = true;
};

const submitBulkSchedule = () => {
    bulkScheduleForm.post('/social-posts/bulk-schedule', {
        onSuccess: () => {
            showBulkScheduleModal.value = false;
            selectedPosts.value = [];
        },
    });
};
</script>

<template>
    <Head title="Social Queue" />

    <div class="min-h-screen bg-gray-50">
        <!-- Navigation -->
        <nav class="bg-white border-b border-gray-200 sticky top-0 z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center space-x-4">
                        <Link href="/social-posts" class="text-gray-500 hover:text-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <span class="text-gray-400">|</span>
                        <h1 class="text-lg font-semibold text-gray-900">Queue</h1>
                        <span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full text-sm font-medium">
                            {{ queuedPosts.length }} posts
                        </span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button
                            v-if="selectedPosts.length > 0"
                            @click="openBulkSchedule"
                            class="px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition"
                        >
                            Schedule {{ selectedPosts.length }} Selected
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <main class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Select All -->
            <div v-if="queuedPosts.length > 0" class="mb-4 flex items-center">
                <label class="flex items-center cursor-pointer">
                    <input
                        type="checkbox"
                        :checked="selectedPosts.length === queuedPosts.length"
                        @change="selectAll"
                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    />
                    <span class="ml-2 text-sm text-gray-700">Select all</span>
                </label>
            </div>

            <!-- Queue List -->
            <div v-if="queuedPosts.length > 0" class="space-y-4">
                <div
                    v-for="(post, index) in queuedPosts"
                    :key="post.id"
                    class="bg-white rounded-lg border border-gray-200 shadow-sm"
                >
                    <div class="flex items-start p-4">
                        <!-- Checkbox -->
                        <div class="flex items-center mr-4">
                            <input
                                type="checkbox"
                                :checked="selectedPosts.includes(post.id)"
                                @change="toggleSelect(post.id)"
                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            />
                        </div>

                        <!-- Order Number -->
                        <div class="flex items-center justify-center w-8 h-8 bg-gray-100 rounded-full text-sm font-medium text-gray-600 mr-4">
                            {{ index + 1 }}
                        </div>

                        <!-- Platform Icon -->
                        <div
                            class="w-10 h-10 rounded-full flex items-center justify-center text-white mr-4 flex-shrink-0"
                            :class="platformColors[post.platform]"
                        >
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path :d="platformIcons[post.platform]" />
                            </svg>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-2 mb-1">
                                <span class="font-medium text-gray-900">{{ post.platform_display }}</span>
                                <span class="text-xs text-gray-500">{{ post.format }}</span>
                            </div>
                            <p class="text-sm text-gray-600 line-clamp-2">{{ post.content }}</p>
                            <div v-if="post.post" class="mt-2 text-xs text-gray-500">
                                From: {{ post.post.title }}
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center space-x-2 ml-4">
                            <button
                                @click="removeFromQueue(post.id)"
                                class="text-red-500 hover:text-red-700 p-2"
                                title="Remove from queue"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">Queue is empty</h3>
                <p class="mt-2 text-sm text-gray-500">
                    Add social posts to your queue to schedule them for publishing.
                </p>
                <Link
                    href="/social-posts"
                    class="mt-4 inline-block px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition"
                >
                    View All Social Posts
                </Link>
            </div>
        </main>

        <!-- Bulk Schedule Modal -->
        <div v-if="showBulkScheduleModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black bg-opacity-30" @click="showBulkScheduleModal = false"></div>

                <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        Schedule {{ bulkScheduleForm.social_post_ids.length }} Posts
                    </h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Start date and time</label>
                            <input
                                v-model="bulkScheduleForm.scheduled_at"
                                type="datetime-local"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary-500 focus:border-primary-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Interval between posts (minutes)
                            </label>
                            <input
                                v-model.number="bulkScheduleForm.interval_minutes"
                                type="number"
                                min="0"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary-500 focus:border-primary-500"
                            />
                            <p class="mt-1 text-sm text-gray-500">
                                Posts will be scheduled {{ bulkScheduleForm.interval_minutes }} minutes apart.
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            @click="showBulkScheduleModal = false"
                            class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-lg transition"
                        >
                            Cancel
                        </button>
                        <button
                            @click="submitBulkSchedule"
                            :disabled="!bulkScheduleForm.scheduled_at || bulkScheduleForm.processing"
                            class="px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition disabled:opacity-50"
                        >
                            {{ bulkScheduleForm.processing ? 'Scheduling...' : 'Schedule All' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
