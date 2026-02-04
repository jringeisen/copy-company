<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    strategy: Object,
    activeLoops: Array,
});

const pollInterval = ref(null);

const isGenerating = computed(() => ['pending', 'generating'].includes(props.strategy.status));
const isCompleted = computed(() => props.strategy.status === 'completed');
const isFailed = computed(() => props.strategy.status === 'failed');

const content = computed(() => props.strategy.strategy_content || {});
const converted = computed(() => props.strategy.converted_items || {});

const weekTheme = computed(() => content.value.week_theme || {});
const blogPosts = computed(() => content.value.blog_posts || []);
const newsletter = computed(() => content.value.newsletter || null);
const socialPosts = computed(() => content.value.social_posts || []);
const loopContent = computed(() => content.value.loop_content || []);
const talkingPoints = computed(() => content.value.talking_points || []);

const isBlogPostConverted = (index) => (converted.value.blog_posts || []).includes(index);
const isSocialPostConverted = (index) => (converted.value.social_posts || []).includes(index);
const isNewsletterConverted = computed(() => converted.value.newsletter === true);
const isLoopContentConverted = (index) => (converted.value.loop_content || []).includes(index);

const convertingBlogPost = ref(null);
const convertingSocialPost = ref(null);
const convertingNewsletter = ref(false);
const convertingLoop = ref(null);
const selectedLoopId = ref({});

const convertBlogPost = (index) => {
    convertingBlogPost.value = index;
    const form = useForm({ index });
    form.post(`/strategies/${props.strategy.id}/convert-blog-post`, {
        preserveScroll: true,
        onFinish: () => { convertingBlogPost.value = null; },
    });
};

const convertSocialPost = (index) => {
    convertingSocialPost.value = index;
    const form = useForm({ index });
    form.post(`/strategies/${props.strategy.id}/convert-social-post`, {
        preserveScroll: true,
        onFinish: () => { convertingSocialPost.value = null; },
    });
};

const convertNewsletter = () => {
    convertingNewsletter.value = true;
    router.post(`/strategies/${props.strategy.id}/convert-newsletter`, {}, {
        preserveScroll: true,
        onFinish: () => { convertingNewsletter.value = false; },
    });
};

const convertLoop = (index) => {
    const loopId = selectedLoopId.value[index];
    if (!loopId) return;

    convertingLoop.value = index;
    const form = useForm({ index, loop_id: loopId });
    form.post(`/strategies/${props.strategy.id}/convert-loop`, {
        preserveScroll: true,
        onFinish: () => { convertingLoop.value = null; },
    });
};

const retry = () => {
    router.post(`/strategies/${props.strategy.id}/retry`);
};

const platformLabel = (platform) => {
    return {
        instagram: 'Instagram',
        facebook: 'Facebook',
        linkedin: 'LinkedIn',
        pinterest: 'Pinterest',
        tiktok: 'TikTok',
    }[platform] || platform;
};

const platformColor = (platform) => {
    return {
        instagram: 'bg-pink-100 text-pink-700',
        facebook: 'bg-blue-100 text-blue-700',
        linkedin: 'bg-sky-100 text-sky-700',
        pinterest: 'bg-red-100 text-red-700',
        tiktok: 'bg-gray-100 text-gray-700',
    }[platform] || 'bg-gray-100 text-gray-700';
};

// Pre-select matching loops for loop content entries
onMounted(() => {
    const activeLoopIds = (props.activeLoops || []).map(l => l.id);
    loopContent.value.forEach((entry, index) => {
        if (entry.loop_id && activeLoopIds.includes(entry.loop_id)) {
            selectedLoopId.value[index] = entry.loop_id;
        }
    });
});

// Poll for updates if generating
onMounted(() => {
    if (isGenerating.value) {
        pollInterval.value = setInterval(() => {
            router.reload({ only: ['strategy'] });
        }, 3000);
    }
});

onUnmounted(() => {
    if (pollInterval.value) {
        clearInterval(pollInterval.value);
    }
});

watch(() => props.strategy.status, (newStatus) => {
    if (newStatus === 'completed' || newStatus === 'failed') {
        if (pollInterval.value) {
            clearInterval(pollInterval.value);
            pollInterval.value = null;
        }
    }
});
</script>

<template>
    <Head :title="`Strategy: ${strategy.week_start} - ${strategy.week_end}`" />

    <AppLayout current-page="strategy">
        <!-- Page Header -->
        <div class="bg-white border-b border-[#0b1215]/10 -mt-8 -mx-4 sm:-mx-6 lg:-mx-8 mb-8 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto py-4">
                <div class="flex items-center space-x-4">
                    <Link href="/strategies" class="text-[#0b1215]/50 hover:text-[#0b1215]/70">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <span class="text-[#0b1215]/30">|</span>
                    <div>
                        <h1 class="text-lg font-semibold text-[#0b1215]">
                            {{ strategy.week_start }} - {{ strategy.week_end }}
                        </h1>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-4xl mx-auto">
            <!-- Generating State -->
            <div v-if="isGenerating" class="text-center py-16">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-[#a1854f]/20 rounded-full mb-6">
                    <svg class="animate-spin h-8 w-8 text-[#a1854f]" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-[#0b1215] mb-2">Generating Your Strategy</h2>
                <p class="text-[#0b1215]/60 mb-4">
                    Our AI is crafting your personalized weekly marketing strategy...
                </p>
                <p class="text-sm text-[#0b1215]/40">
                    This usually takes 30-60 seconds...
                </p>
            </div>

            <!-- Failed State -->
            <div v-else-if="isFailed" class="text-center py-16">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-red-100 rounded-full mb-6">
                    <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-[#0b1215] mb-2">Generation Failed</h2>
                <p class="text-[#0b1215]/60 mb-6">
                    Something went wrong while generating your strategy. Please try again.
                </p>
                <button
                    @click="retry"
                    class="px-6 py-2 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition"
                >
                    Try Again
                </button>
            </div>

            <!-- Completed State -->
            <div v-else-if="isCompleted" class="space-y-8">
                <!-- Week Theme -->
                <div v-if="weekTheme.title" class="bg-gradient-to-br from-[#0b1215] to-[#1a2830] rounded-2xl p-6 text-white">
                    <p class="text-xs font-medium text-[#a1854f] uppercase tracking-wider mb-2">This Week's Theme</p>
                    <h2 class="text-xl font-bold mb-2">{{ weekTheme.title }}</h2>
                    <p class="text-white/70 text-sm">{{ weekTheme.description }}</p>
                </div>

                <!-- Blog Post Ideas -->
                <section v-if="blogPosts.length > 0">
                    <h3 class="text-lg font-semibold text-[#0b1215] mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#a1854f]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Blog Post Ideas
                    </h3>
                    <div class="space-y-4">
                        <div
                            v-for="(post, index) in blogPosts"
                            :key="index"
                            :class="[
                                'bg-white rounded-2xl border p-5 transition',
                                isBlogPostConverted(index) ? 'border-green-200 bg-green-50/50 opacity-75' : 'border-[#0b1215]/10',
                            ]"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h4 class="font-semibold text-[#0b1215]">{{ post.title }}</h4>
                                        <span v-if="isBlogPostConverted(index)" class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-medium rounded-full">Created</span>
                                    </div>
                                    <p class="text-sm text-[#0b1215]/60 mb-3">{{ post.description }}</p>
                                    <div v-if="post.key_points?.length" class="flex flex-wrap gap-2 mb-3">
                                        <span
                                            v-for="point in post.key_points"
                                            :key="point"
                                            class="px-2 py-1 bg-[#0b1215]/5 text-[#0b1215]/60 text-xs rounded-lg"
                                        >
                                            {{ point }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-4 text-xs text-[#0b1215]/50">
                                        <span v-if="post.suggested_day">{{ post.suggested_day }}</span>
                                        <span v-if="post.estimated_words">~{{ post.estimated_words }} words</span>
                                    </div>
                                    <p v-if="post.rationale" class="mt-2 text-xs text-[#a1854f] italic">{{ post.rationale }}</p>
                                </div>
                                <button
                                    v-if="!isBlogPostConverted(index)"
                                    @click="convertBlogPost(index)"
                                    :disabled="convertingBlogPost === index"
                                    class="shrink-0 px-4 py-2 bg-[#0b1215] text-white text-sm font-medium rounded-full hover:bg-[#0b1215]/90 transition disabled:opacity-50"
                                >
                                    {{ convertingBlogPost === index ? 'Creating...' : 'Create Draft' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Newsletter Plan -->
                <section v-if="newsletter">
                    <h3 class="text-lg font-semibold text-[#0b1215] mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#a1854f]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Newsletter Plan
                    </h3>
                    <div
                        :class="[
                            'bg-white rounded-2xl border p-5',
                            isNewsletterConverted ? 'border-green-200 bg-green-50/50 opacity-75' : 'border-[#0b1215]/10',
                        ]"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <h4 class="font-semibold text-[#0b1215]">{{ newsletter.subject_line }}</h4>
                                    <span v-if="isNewsletterConverted" class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-medium rounded-full">Created</span>
                                </div>
                                <p class="text-sm text-[#0b1215]/60 mb-3">{{ newsletter.topic }}</p>
                                <div v-if="newsletter.key_points?.length" class="flex flex-wrap gap-2 mb-3">
                                    <span
                                        v-for="point in newsletter.key_points"
                                        :key="point"
                                        class="px-2 py-1 bg-[#0b1215]/5 text-[#0b1215]/60 text-xs rounded-lg"
                                    >
                                        {{ point }}
                                    </span>
                                </div>
                                <span v-if="newsletter.suggested_day" class="text-xs text-[#0b1215]/50">{{ newsletter.suggested_day }}</span>
                            </div>
                            <button
                                v-if="!isNewsletterConverted"
                                @click="convertNewsletter"
                                :disabled="convertingNewsletter"
                                class="shrink-0 px-4 py-2 bg-[#0b1215] text-white text-sm font-medium rounded-full hover:bg-[#0b1215]/90 transition disabled:opacity-50"
                            >
                                {{ convertingNewsletter ? 'Creating...' : 'Create Draft' }}
                            </button>
                        </div>
                    </div>
                </section>

                <!-- Social Media Plan -->
                <section v-if="socialPosts.length > 0">
                    <h3 class="text-lg font-semibold text-[#0b1215] mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#a1854f]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                        </svg>
                        Social Media Plan
                    </h3>
                    <div class="space-y-4">
                        <div
                            v-for="(post, index) in socialPosts"
                            :key="index"
                            :class="[
                                'bg-white rounded-2xl border p-5',
                                isSocialPostConverted(index) ? 'border-green-200 bg-green-50/50 opacity-75' : 'border-[#0b1215]/10',
                            ]"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span :class="[platformColor(post.platform), 'px-2 py-0.5 text-xs font-medium rounded-full']">
                                            {{ platformLabel(post.platform) }}
                                        </span>
                                        <span v-if="post.post_type" class="px-2 py-0.5 bg-[#0b1215]/5 text-[#0b1215]/50 text-xs rounded-full capitalize">
                                            {{ post.post_type }}
                                        </span>
                                        <span v-if="post.suggested_day" class="text-xs text-[#0b1215]/40">{{ post.suggested_day }}</span>
                                        <span v-if="isSocialPostConverted(index)" class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-medium rounded-full">Created</span>
                                    </div>
                                    <p class="text-sm text-[#0b1215]/80 mb-2 whitespace-pre-line">{{ post.content }}</p>
                                    <div v-if="post.hashtags?.length" class="flex flex-wrap gap-1">
                                        <span
                                            v-for="tag in post.hashtags"
                                            :key="tag"
                                            class="text-xs text-[#a1854f]"
                                        >
                                            #{{ tag }}
                                        </span>
                                    </div>
                                </div>
                                <button
                                    v-if="!isSocialPostConverted(index)"
                                    @click="convertSocialPost(index)"
                                    :disabled="convertingSocialPost === index"
                                    class="shrink-0 px-4 py-2 bg-[#0b1215] text-white text-sm font-medium rounded-full hover:bg-[#0b1215]/90 transition disabled:opacity-50"
                                >
                                    {{ convertingSocialPost === index ? 'Creating...' : 'Create Draft' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Loop Content -->
                <section v-if="loopContent.length > 0">
                    <h3 class="text-lg font-semibold text-[#0b1215] mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#a1854f]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Loop Content
                    </h3>
                    <div class="space-y-4">
                        <div
                            v-for="(entry, index) in loopContent"
                            :key="index"
                            :class="[
                                'bg-white rounded-2xl border p-5',
                                isLoopContentConverted(index) ? 'border-green-200 bg-green-50/50 opacity-75' : 'border-[#0b1215]/10',
                            ]"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h4 class="font-semibold text-[#0b1215]">{{ entry.loop_name }}</h4>
                                        <span v-if="isLoopContentConverted(index)" class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-medium rounded-full">Added</span>
                                    </div>
                                    <div v-if="entry.suggested_items?.length" class="mb-3">
                                        <p class="text-xs font-medium text-[#0b1215]/50 mb-2">Suggested items:</p>
                                        <div class="space-y-2">
                                            <div
                                                v-for="(item, i) in entry.suggested_items"
                                                :key="i"
                                                class="bg-[#0b1215]/5 rounded-lg p-3"
                                            >
                                                <p class="text-sm text-[#0b1215]/70">{{ item.content }}</p>
                                                <div v-if="item.hashtags?.length" class="mt-1 flex flex-wrap gap-1">
                                                    <span v-for="tag in item.hashtags" :key="tag" class="text-xs text-[#a1854f]">#{{ tag }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="!isLoopContentConverted(index) && activeLoops?.length" class="mt-3">
                                        <label class="block text-xs font-medium text-[#0b1215]/50 mb-1">Add to loop:</label>
                                        <select
                                            v-model="selectedLoopId[index]"
                                            class="w-full max-w-xs border border-[#0b1215]/10 rounded-lg px-3 py-1.5 text-sm text-[#0b1215] focus:outline-none focus:ring-2 focus:ring-[#a1854f]/30"
                                        >
                                            <option :value="undefined" disabled>Select a loop...</option>
                                            <option v-for="loop in activeLoops" :key="loop.id" :value="loop.id">
                                                {{ loop.name }} ({{ loop.items_count }} items)
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <button
                                    v-if="!isLoopContentConverted(index)"
                                    @click="convertLoop(index)"
                                    :disabled="convertingLoop === index || !selectedLoopId[index]"
                                    class="shrink-0 px-4 py-2 bg-[#0b1215] text-white text-sm font-medium rounded-full hover:bg-[#0b1215]/90 transition disabled:opacity-50"
                                >
                                    {{ convertingLoop === index ? 'Adding...' : 'Add to Loop' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </section>
                <section v-else-if="!activeLoops?.length && isCompleted">
                    <div class="bg-white rounded-2xl border border-[#0b1215]/10 p-5 text-center">
                        <p class="text-sm text-[#0b1215]/50">Create a loop to receive AI-generated content suggestions.</p>
                    </div>
                </section>

                <!-- Talking Points -->
                <section v-if="talkingPoints.length > 0">
                    <h3 class="text-lg font-semibold text-[#0b1215] mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#a1854f]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        Talking Points
                    </h3>
                    <div class="bg-white rounded-2xl border border-[#0b1215]/10 p-5">
                        <ul class="space-y-3">
                            <li
                                v-for="(point, index) in talkingPoints"
                                :key="index"
                                class="flex items-start gap-3"
                            >
                                <span class="shrink-0 w-6 h-6 bg-[#a1854f]/10 text-[#a1854f] text-xs font-medium rounded-full flex items-center justify-center mt-0.5">
                                    {{ index + 1 }}
                                </span>
                                <p class="text-sm text-[#0b1215]/80">{{ point }}</p>
                            </li>
                        </ul>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
