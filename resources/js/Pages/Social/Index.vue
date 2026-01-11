<script setup>
import { Head, Link, router, useForm, usePoll } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import SocialPostCard from '@/Components/Social/SocialPostCard.vue';
import SocialPostEditor from '@/Components/Social/SocialPostEditor.vue';
import { usePermissions } from '@/Composables/usePermissions';

const { canManageSocial } = usePermissions();

const timezoneLabels = {
    'America/New_York': 'Eastern Time',
    'America/Chicago': 'Central Time',
    'America/Denver': 'Mountain Time',
    'America/Los_Angeles': 'Pacific Time',
    'America/Anchorage': 'Alaska Time',
    'Pacific/Honolulu': 'Hawaii Time',
    'America/Phoenix': 'Arizona',
    'America/Toronto': 'Toronto',
    'America/Vancouver': 'Vancouver',
    'Europe/London': 'London',
    'Europe/Paris': 'Paris',
    'Europe/Berlin': 'Berlin',
    'Asia/Tokyo': 'Tokyo',
    'Asia/Shanghai': 'Shanghai',
    'Asia/Singapore': 'Singapore',
    'Australia/Sydney': 'Sydney',
    'Australia/Melbourne': 'Melbourne',
    'UTC': 'UTC',
};

const props = defineProps({
    socialPosts: Object,
    posts: Array,
    brand: Object,
    filters: Object,
    platforms: Array,
    statuses: Array,
    connectedPlatforms: {
        type: Array,
        default: () => [],
    },
});

const timezoneDisplay = computed(() => {
    return timezoneLabels[props.brand?.timezone] || props.brand?.timezone || 'Eastern Time';
});

// Check if there are any posts in "active" states that might change
const hasActiveStatuses = computed(() => {
    const activeStatuses = ['queued', 'scheduled'];
    return props.socialPosts.data.some(post => activeStatuses.includes(post.status));
});

// Poll for updates when there are posts that might change status
const { stop: stopPolling, start: startPolling } = usePoll(5000, {
    only: ['socialPosts'],
}, {
    autoStart: false,
});

// Start polling if there are active statuses
watch(hasActiveStatuses, (hasActive) => {
    if (hasActive) {
        startPolling();
    } else {
        stopPolling();
    }
}, { immediate: true });

const selectedPlatform = ref(props.filters.platform);
const selectedStatus = ref(props.filters.status);
const showGenerateModal = ref(false);
const showEditorModal = ref(false);
const editingSocialPost = ref(null);
const showScheduleModal = ref(false);
const schedulingPost = ref(null);

const generateForm = useForm({
    post_id: '',
    platforms: [],
});

const scheduleForm = useForm({
    scheduled_at: '',
});

// Watch filters and update URL
watch([selectedPlatform, selectedStatus], () => {
    router.get('/social-posts', {
        platform: selectedPlatform.value,
        status: selectedStatus.value,
    }, {
        preserveState: true,
        replace: true,
    });
});

const handleEdit = (socialPost) => {
    editingSocialPost.value = socialPost;
    showEditorModal.value = true;
};

const handleSchedule = (socialPost) => {
    schedulingPost.value = socialPost;
    scheduleForm.scheduled_at = '';
    showScheduleModal.value = true;
};

const submitSchedule = () => {
    if (!schedulingPost.value) return;
    scheduleForm.post(`/social-posts/${schedulingPost.value.id}/schedule`, {
        onSuccess: () => {
            showScheduleModal.value = false;
            schedulingPost.value = null;
        },
    });
};

const isGenerating = ref(false);

const generateSocialPosts = async () => {
    if (!generateForm.post_id || generateForm.platforms.length === 0) return;

    isGenerating.value = true;

    try {
        const response = await axios.post('/ai/atomize', {
            post_id: generateForm.post_id,
            platforms: generateForm.platforms,
        });

        if (response.data.posts) {
            // Create social posts from generated content
            for (const [platform, postData] of Object.entries(response.data.posts)) {
                if (!postData.error) {
                    await axios.post('/social-posts', {
                        post_id: generateForm.post_id,
                        platform: platform,
                        content: postData.content,
                        hashtags: postData.hashtags || [],
                        ai_generated: true,
                        status: 'draft',
                    });
                }
            }

            // Refresh the page
            router.reload();
            showGenerateModal.value = false;
            generateForm.reset();
        }
    } catch (error) {
        console.error('Failed to generate social posts:', error);
        alert('Failed to generate social posts. Please try again.');
    } finally {
        isGenerating.value = false;
    }
};

const togglePlatform = (platform) => {
    const index = generateForm.platforms.indexOf(platform);
    if (index > -1) {
        generateForm.platforms.splice(index, 1);
    } else {
        generateForm.platforms.push(platform);
    }
};

const availablePlatformsForGenerate = [
    { value: 'instagram', label: 'Instagram', icon: 'instagram' },
    { value: 'twitter', label: 'X (Twitter)', icon: 'twitter' },
    { value: 'facebook', label: 'Facebook', icon: 'facebook' },
    { value: 'linkedin', label: 'LinkedIn', icon: 'linkedin' },
    { value: 'pinterest', label: 'Pinterest', icon: 'pinterest' },
    { value: 'tiktok', label: 'TikTok', icon: 'tiktok' },
];
</script>

<template>
    <Head title="Social Posts" />

    <AppLayout current-page="social">
        <div class="max-w-7xl mx-auto">
            <!-- Page Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-[#0b1215]">Social Posts</h1>
                    <p class="text-[#0b1215]/60">Manage your social media content</p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link
                        href="/social-posts/queue"
                        class="px-4 py-2 border border-[#0b1215]/20 text-[#0b1215] font-medium rounded-full hover:bg-[#0b1215]/5 transition"
                    >
                        View Queue
                    </Link>
                    <button
                        v-if="canManageSocial"
                        @click="showGenerateModal = true"
                        class="px-4 py-2 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition"
                    >
                        Generate from Post
                    </button>
                </div>
            </div>
            <!-- Filters -->
            <div class="mb-6 flex flex-wrap gap-4">
                <div>
                    <label class="block text-sm font-medium text-[#0b1215] mb-1">Platform</label>
                    <select
                        v-model="selectedPlatform"
                        class="border border-[#0b1215]/20 rounded-xl px-3 py-2 text-sm focus:ring-[#0b1215]/20 focus:border-[#0b1215]/40"
                    >
                        <option v-for="platform in platforms" :key="platform.value" :value="platform.value">
                            {{ platform.label }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#0b1215] mb-1">Status</label>
                    <select
                        v-model="selectedStatus"
                        class="border border-[#0b1215]/20 rounded-xl px-3 py-2 text-sm focus:ring-[#0b1215]/20 focus:border-[#0b1215]/40"
                    >
                        <option v-for="status in statuses" :key="status.value" :value="status.value">
                            {{ status.label }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Social Posts Grid -->
            <div v-if="socialPosts.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <SocialPostCard
                    v-for="socialPost in socialPosts.data"
                    :key="socialPost.id"
                    :social-post="socialPost"
                    :connected-platforms="connectedPlatforms"
                    @edit="handleEdit"
                    @schedule="handleSchedule"
                />
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-12 bg-white rounded-2xl border border-[#0b1215]/10">
                <svg class="mx-auto h-12 w-12 text-[#0b1215]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-[#0b1215]">No social posts yet</h3>
                <p class="mt-2 text-sm text-[#0b1215]/50">
                    {{ canManageSocial ? 'Generate social posts from your blog content to share across platforms.' : 'No social posts have been created yet.' }}
                </p>
                <button
                    v-if="canManageSocial"
                    @click="showGenerateModal = true"
                    class="mt-4 px-4 py-2 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition"
                >
                    Generate from Post
                </button>
            </div>

            <!-- Pagination -->
            <div v-if="socialPosts.links && socialPosts.links.length > 3" class="mt-8 flex justify-center">
                <nav class="flex space-x-2">
                    <Link
                        v-for="link in socialPosts.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        :class="[
                            'px-3 py-2 text-sm rounded-full',
                            link.active ? 'bg-[#0b1215] text-white' : 'bg-white text-[#0b1215]/70 hover:bg-[#0b1215]/5 border border-[#0b1215]/20',
                            !link.url && 'opacity-50 cursor-not-allowed'
                        ]"
                        v-html="link.label"
                    />
                </nav>
            </div>
        </div>

        <!-- Generate Modal -->
        <div v-if="showGenerateModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showGenerateModal = false"></div>

                <div class="relative bg-white rounded-2xl shadow-xl max-w-lg w-full p-6">
                    <h2 class="text-xl font-semibold text-[#0b1215] mb-4">Generate Social Posts</h2>

                    <!-- Select Post -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-[#0b1215] mb-2">Select a blog post</label>
                        <select
                            v-model="generateForm.post_id"
                            class="w-full border border-[#0b1215]/20 rounded-xl px-3 py-2 focus:ring-[#0b1215]/20 focus:border-[#0b1215]/40"
                        >
                            <option value="">Choose a post...</option>
                            <option v-for="post in posts" :key="post.id" :value="post.id">
                                {{ post.title }} ({{ post.status }})
                            </option>
                        </select>
                    </div>

                    <!-- Select Platforms -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-[#0b1215] mb-2">Select platforms</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button
                                v-for="platform in availablePlatformsForGenerate"
                                :key="platform.value"
                                @click="togglePlatform(platform.value)"
                                :class="[
                                    'flex items-center p-3 border rounded-xl text-sm font-medium transition',
                                    generateForm.platforms.includes(platform.value)
                                        ? 'border-[#a1854f] bg-[#a1854f]/10 text-[#a1854f]'
                                        : 'border-[#0b1215]/20 text-[#0b1215]/70 hover:bg-[#0b1215]/5'
                                ]"
                            >
                                <span>{{ platform.label }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-3">
                        <button
                            @click="showGenerateModal = false"
                            :disabled="isGenerating"
                            class="px-4 py-2 text-[#0b1215] font-medium hover:bg-[#0b1215]/5 rounded-xl transition disabled:opacity-50"
                        >
                            Cancel
                        </button>
                        <button
                            @click="generateSocialPosts"
                            :disabled="!generateForm.post_id || generateForm.platforms.length === 0 || isGenerating"
                            class="px-4 py-2 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition disabled:opacity-50 flex items-center"
                        >
                            <svg v-if="isGenerating" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ isGenerating ? 'Generating...' : 'Generate Posts' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Schedule Modal -->
        <div v-if="showScheduleModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showScheduleModal = false"></div>

                <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
                    <h2 class="text-xl font-semibold text-[#0b1215] mb-4">Schedule Post</h2>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-[#0b1215] mb-2">Schedule date and time</label>
                        <input
                            v-model="scheduleForm.scheduled_at"
                            type="datetime-local"
                            class="w-full border border-[#0b1215]/20 rounded-xl px-3 py-2 focus:ring-[#0b1215]/20 focus:border-[#0b1215]/40"
                        />
                        <p class="mt-1 text-sm text-[#0b1215]/50">
                            Times are in {{ timezoneDisplay }}
                        </p>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button
                            @click="showScheduleModal = false"
                            class="px-4 py-2 text-[#0b1215] font-medium hover:bg-[#0b1215]/5 rounded-xl transition"
                        >
                            Cancel
                        </button>
                        <button
                            @click="submitSchedule"
                            :disabled="!scheduleForm.scheduled_at"
                            class="px-4 py-2 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition disabled:opacity-50"
                        >
                            Schedule
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Editor Modal -->
        <SocialPostEditor
            v-if="showEditorModal && editingSocialPost"
            :social-post="editingSocialPost"
            @close="showEditorModal = false; editingSocialPost = null"
        />
    </AppLayout>
</template>
