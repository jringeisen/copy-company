<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import axios from 'axios';
import SocialPostCard from '@/Components/Social/SocialPostCard.vue';
import SocialPostEditor from '@/Components/Social/SocialPostEditor.vue';

const props = defineProps({
    socialPosts: Object,
    posts: Array,
    brand: Object,
    filters: Object,
    platforms: Array,
    statuses: Array,
});

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

    <div class="min-h-screen bg-gray-50">
        <!-- Navigation -->
        <nav class="bg-white border-b border-gray-200 sticky top-0 z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center space-x-4">
                        <Link href="/dashboard" class="text-gray-500 hover:text-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <span class="text-gray-400">|</span>
                        <h1 class="text-lg font-semibold text-gray-900">Social Posts</h1>
                    </div>
                    <div class="flex items-center space-x-3">
                        <Link
                            href="/social-posts/queue"
                            class="px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition"
                        >
                            View Queue
                        </Link>
                        <button
                            @click="showGenerateModal = true"
                            class="px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition"
                        >
                            Generate from Post
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Filters -->
            <div class="mb-6 flex flex-wrap gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Platform</label>
                    <select
                        v-model="selectedPlatform"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-primary-500 focus:border-primary-500"
                    >
                        <option v-for="platform in platforms" :key="platform.value" :value="platform.value">
                            {{ platform.label }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select
                        v-model="selectedStatus"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-primary-500 focus:border-primary-500"
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
                    @edit="handleEdit"
                    @schedule="handleSchedule"
                />
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No social posts yet</h3>
                <p class="mt-2 text-sm text-gray-500">
                    Generate social posts from your blog content to share across platforms.
                </p>
                <button
                    @click="showGenerateModal = true"
                    class="mt-4 px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition"
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
                            'px-3 py-2 text-sm rounded-lg',
                            link.active ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300',
                            !link.url && 'opacity-50 cursor-not-allowed'
                        ]"
                        v-html="link.label"
                    />
                </nav>
            </div>
        </main>

        <!-- Generate Modal -->
        <div v-if="showGenerateModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black bg-opacity-30" @click="showGenerateModal = false"></div>

                <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Generate Social Posts</h2>

                    <!-- Select Post -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select a blog post</label>
                        <select
                            v-model="generateForm.post_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary-500 focus:border-primary-500"
                        >
                            <option value="">Choose a post...</option>
                            <option v-for="post in posts" :key="post.id" :value="post.id">
                                {{ post.title }} ({{ post.status }})
                            </option>
                        </select>
                    </div>

                    <!-- Select Platforms -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select platforms</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button
                                v-for="platform in availablePlatformsForGenerate"
                                :key="platform.value"
                                @click="togglePlatform(platform.value)"
                                :class="[
                                    'flex items-center p-3 border rounded-lg text-sm font-medium transition',
                                    generateForm.platforms.includes(platform.value)
                                        ? 'border-primary-500 bg-primary-50 text-primary-700'
                                        : 'border-gray-300 text-gray-700 hover:bg-gray-50'
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
                            class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-lg transition disabled:opacity-50"
                        >
                            Cancel
                        </button>
                        <button
                            @click="generateSocialPosts"
                            :disabled="!generateForm.post_id || generateForm.platforms.length === 0 || isGenerating"
                            class="px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition disabled:opacity-50 flex items-center"
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
                <div class="fixed inset-0 bg-black bg-opacity-30" @click="showScheduleModal = false"></div>

                <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Schedule Post</h2>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Schedule date and time</label>
                        <input
                            v-model="scheduleForm.scheduled_at"
                            type="datetime-local"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary-500 focus:border-primary-500"
                        />
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button
                            @click="showScheduleModal = false"
                            class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-lg transition"
                        >
                            Cancel
                        </button>
                        <button
                            @click="submitSchedule"
                            :disabled="!scheduleForm.scheduled_at"
                            class="px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition disabled:opacity-50"
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
    </div>
</template>
