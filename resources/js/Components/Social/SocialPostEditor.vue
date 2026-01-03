<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

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
});

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
            <div class="fixed inset-0 bg-black bg-opacity-30" @click="emit('close')"></div>

            <div class="relative bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <!-- Header -->
                <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Edit Social Post</h2>
                        <p class="text-sm text-gray-500">{{ platformDisplayNames[socialPost.platform] }}</p>
                    </div>
                    <button @click="emit('close')" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Content -->
                <div class="p-6 space-y-6">
                    <!-- Format -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Format</label>
                        <select
                            v-model="form.format"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary-500 focus:border-primary-500"
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

                    <!-- Content -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">Content</label>
                            <span
                                :class="[
                                    'text-sm',
                                    isOverLimit ? 'text-red-600' : 'text-gray-500'
                                ]"
                            >
                                {{ characterCount }} / {{ characterLimit }}
                            </span>
                        </div>
                        <textarea
                            v-model="form.content"
                            rows="8"
                            :class="[
                                'w-full border rounded-lg px-3 py-2 focus:ring-primary-500 focus:border-primary-500',
                                isOverLimit ? 'border-red-300' : 'border-gray-300'
                            ]"
                            placeholder="Write your social post content..."
                        ></textarea>
                        <p v-if="isOverLimit" class="mt-1 text-sm text-red-600">
                            Content exceeds character limit for {{ platformDisplayNames[socialPost.platform] }}
                        </p>
                    </div>

                    <!-- Hashtags -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Hashtags</label>
                        <div class="flex flex-wrap gap-2 mb-2">
                            <span
                                v-for="(tag, index) in form.hashtags"
                                :key="index"
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-primary-100 text-primary-700"
                            >
                                #{{ tag }}
                                <button
                                    @click="removeHashtag(index)"
                                    class="ml-2 text-primary-500 hover:text-primary-700"
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
                                class="flex-1 border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary-500 focus:border-primary-500"
                            />
                            <button
                                @click="addHashtag"
                                type="button"
                                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition"
                            >
                                Add
                            </button>
                        </div>
                    </div>

                    <!-- Link -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Link (optional)</label>
                        <input
                            v-model="form.link"
                            type="url"
                            placeholder="https://..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary-500 focus:border-primary-500"
                        />
                    </div>
                </div>

                <!-- Footer -->
                <div class="sticky bottom-0 bg-gray-50 border-t border-gray-200 px-6 py-4 flex justify-end space-x-3">
                    <button
                        @click="emit('close')"
                        class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-lg transition"
                    >
                        Cancel
                    </button>
                    <button
                        @click="save"
                        :disabled="form.processing || isOverLimit"
                        class="px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition disabled:opacity-50"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
