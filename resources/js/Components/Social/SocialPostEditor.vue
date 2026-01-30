<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import MediaPickerModal from '@/Components/Media/MediaPickerModal.vue';

const props = defineProps({
    socialPost: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    content: props.socialPost.content,
    hashtags: props.socialPost.hashtags || [],
    link: props.socialPost.link || '',
    format: props.socialPost.format,
    media: props.socialPost.media || [],
});

const showMediaPicker = ref(false);

const hashtagInput = ref('');

const characterLimits = {
    twitter: 280,
    instagram: 2200,
    facebook: 63206,
    linkedin: 3000,
    pinterest: 500,
    tiktok: 2200,
};

const characterLimit = computed(() => characterLimits[props.socialPost.platform] || 2200);
const characterCount = computed(() => form.content.length);
const isOverLimit = computed(() => characterCount.value > characterLimit.value);

const platformDisplayNames = {
    instagram: 'Instagram',
    twitter: 'X (Twitter)',
    facebook: 'Facebook',
    linkedin: 'LinkedIn',
    pinterest: 'Pinterest',
    tiktok: 'TikTok',
};

const formatOptions = {
    instagram: ['feed', 'story', 'reel', 'carousel'],
    twitter: ['feed', 'thread'],
    facebook: ['feed', 'story'],
    linkedin: ['feed'],
    pinterest: ['pin'],
    tiktok: ['feed', 'story'],
};

// Media limits per platform and format
const mediaLimits = {
    instagram: { feed: 10, story: 1, reel: 0, carousel: 10 },
    facebook: { feed: 10, story: 1, reel: 0 },
    twitter: { feed: 4, thread: 4 },
    linkedin: { feed: 9 },
    pinterest: { pin: 5 },
    tiktok: { feed: 0, story: 0 },
};

const mediaLimit = computed(() => {
    const platformLimits = mediaLimits[props.socialPost.platform];
    return platformLimits?.[form.format] ?? 0;
});

const allowsMedia = computed(() => mediaLimit.value > 0);
const canAddMoreMedia = computed(() => form.media.length < mediaLimit.value);

const handleMediaSelect = (selectedMedia) => {
    // selectedMedia can be a single item or array (if multiple mode)
    const items = Array.isArray(selectedMedia) ? selectedMedia : [selectedMedia];

    for (const item of items) {
        if (form.media.length >= mediaLimit.value) break;
        if (!form.media.find(m => m.id === item.id)) {
            form.media.push(item);
        }
    }
    showMediaPicker.value = false;
};

const removeMedia = (index) => {
    form.media.splice(index, 1);
};

const addHashtag = () => {
    if (hashtagInput.value.trim()) {
        const tag = hashtagInput.value.trim().replace(/^#/, '');
        if (!form.hashtags.includes(tag)) {
            form.hashtags.push(tag);
        }
        hashtagInput.value = '';
    }
};

const removeHashtag = (index) => {
    form.hashtags.splice(index, 1);
};

const save = () => {
    form.put(`/social-posts/${props.socialPost.id}`, {
        onSuccess: () => {
            emit('close');
        },
    });
};
</script>

<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <!-- Header -->
                <div class="sticky top-0 bg-white border-b border-[#0b1215]/10 px-6 py-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-[#0b1215]">Edit Social Post</h2>
                        <p class="text-sm text-[#0b1215]/50">{{ platformDisplayNames[socialPost.platform] }}</p>
                    </div>
                    <button @click="emit('close')" class="text-[#0b1215]/40 hover:text-[#0b1215]/60">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Content -->
                <div class="p-6 space-y-6">
                    <!-- Format -->
                    <div>
                        <label class="block text-sm font-medium text-[#0b1215] mb-2">Format</label>
                        <select
                            v-model="form.format"
                            class="w-full border border-[#0b1215]/20 rounded-xl px-3 py-2 focus:ring-[#a1854f]/30 focus:border-[#a1854f]"
                        >
                            <option
                                v-for="formatOption in formatOptions[socialPost.platform]"
                                :key="formatOption"
                                :value="formatOption"
                            >
                                {{ formatOption.charAt(0).toUpperCase() + formatOption.slice(1) }}
                            </option>
                        </select>
                    </div>

                    <!-- Media -->
                    <div v-if="allowsMedia">
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-[#0b1215]">Media</label>
                            <span class="text-sm text-[#0b1215]/50">
                                {{ form.media.length }} / {{ mediaLimit }}
                            </span>
                        </div>

                        <!-- Media Grid -->
                        <div v-if="form.media.length > 0" class="grid grid-cols-4 gap-2 mb-3">
                            <div
                                v-for="(item, index) in form.media"
                                :key="item.id"
                                class="relative aspect-square bg-[#f7f7f7] rounded-xl overflow-hidden group"
                            >
                                <img
                                    :src="item.thumbnail_url || item.url"
                                    :alt="item.alt_text || item.filename"
                                    class="w-full h-full object-cover"
                                />
                                <button
                                    @click="removeMedia(index)"
                                    class="absolute top-1 right-1 w-6 h-6 bg-red-600 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Add Media Button -->
                        <button
                            v-if="canAddMoreMedia"
                            type="button"
                            @click="showMediaPicker = true"
                            class="flex items-center gap-2 px-3 py-2 border border-dashed border-[#0b1215]/20 rounded-xl text-[#0b1215]/60 hover:border-[#0b1215]/40 hover:text-[#0b1215]/80 transition"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Add Media
                        </button>
                    </div>
                    <div v-else class="bg-[#a1854f]/10 border border-[#a1854f]/20 rounded-xl p-3">
                        <p class="text-sm text-[#0b1215]/60">
                            This format does not support images (video only or not applicable).
                        </p>
                    </div>

                    <!-- Content -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-[#0b1215]">Content</label>
                            <span
                                :class="[
                                    'text-sm',
                                    isOverLimit ? 'text-red-600' : 'text-[#0b1215]/50'
                                ]"
                            >
                                {{ characterCount }} / {{ characterLimit }}
                            </span>
                        </div>
                        <textarea
                            v-model="form.content"
                            rows="8"
                            :class="[
                                'w-full border rounded-xl px-3 py-2 focus:ring-[#a1854f]/30 focus:border-[#a1854f]',
                                isOverLimit ? 'border-red-300' : 'border-[#0b1215]/20'
                            ]"
                            placeholder="Write your social post content..."
                        ></textarea>
                        <p v-if="isOverLimit" class="mt-1 text-sm text-red-600">
                            Content exceeds character limit for {{ platformDisplayNames[socialPost.platform] }}
                        </p>
                    </div>

                    <!-- Hashtags -->
                    <div>
                        <label class="block text-sm font-medium text-[#0b1215] mb-2">Hashtags</label>
                        <div class="flex flex-wrap gap-2 mb-2">
                            <span
                                v-for="(tag, index) in form.hashtags"
                                :key="index"
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-[#a1854f]/10 text-[#a1854f]"
                            >
                                #{{ tag }}
                                <button
                                    @click="removeHashtag(index)"
                                    class="ml-2 text-[#a1854f]/70 hover:text-[#a1854f]"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </span>
                        </div>
                        <div class="flex gap-2">
                            <input
                                v-model="hashtagInput"
                                @keyup.enter="addHashtag"
                                type="text"
                                placeholder="Add hashtag..."
                                class="flex-1 border border-[#0b1215]/20 rounded-xl px-3 py-2 focus:ring-[#a1854f]/30 focus:border-[#a1854f]"
                            />
                            <button
                                @click="addHashtag"
                                type="button"
                                class="px-4 py-2 bg-[#0b1215]/5 text-[#0b1215] rounded-xl hover:bg-[#0b1215]/10 transition"
                            >
                                Add
                            </button>
                        </div>
                    </div>

                    <!-- Link -->
                    <div>
                        <label class="block text-sm font-medium text-[#0b1215] mb-2">Link (optional)</label>
                        <input
                            v-model="form.link"
                            type="url"
                            placeholder="https://..."
                            class="w-full border border-[#0b1215]/20 rounded-xl px-3 py-2 focus:ring-[#a1854f]/30 focus:border-[#a1854f]"
                        />
                    </div>
                </div>

                <!-- Footer -->
                <div class="sticky bottom-0 bg-[#f7f7f7] border-t border-[#0b1215]/10 px-6 py-4 flex justify-end space-x-3">
                    <button
                        @click="emit('close')"
                        class="px-4 py-2 text-[#0b1215] font-medium hover:bg-[#0b1215]/5 rounded-xl transition"
                    >
                        Cancel
                    </button>
                    <button
                        @click="save"
                        :disabled="form.processing || isOverLimit"
                        class="px-4 py-2 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition disabled:opacity-50"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Media Picker Modal -->
        <MediaPickerModal
            :show="showMediaPicker"
            :multiple="mediaLimit > 1"
            :max-items="mediaLimit - form.media.length"
            @close="showMediaPicker = false"
            @select="handleMediaSelect"
        />
    </div>
</template>
