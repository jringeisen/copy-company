<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import PostEditor from '@/Components/Editor/PostEditor.vue';
import AIAssistantPanel from '@/Components/Editor/AIAssistantPanel.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import MediaPickerModal from '@/Components/Media/MediaPickerModal.vue';
import { useAutosave } from '@/Composables/useAutosave';
import { ref, computed, watch } from 'vue';
import { marked } from 'marked';

const props = defineProps({
    post: Object,
    brand: Object,
});

const form = useForm({
    title: props.post.title,
    content: props.post.content || { type: 'doc', content: [] },
    content_html: props.post.content_html || '',
    excerpt: props.post.excerpt || '',
    featured_image: props.post.featured_image || '',
    publish_to_blog: props.post.publish_to_blog,
    send_as_newsletter: props.post.send_as_newsletter,
    generate_social: props.post.generate_social,
});

const showFeaturedImagePicker = ref(false);

const handleFeaturedImageSelect = (media) => {
    form.featured_image = media.url;
    showFeaturedImagePicker.value = false;
};

const removeFeaturedImage = () => {
    form.featured_image = '';
};

// Autosave setup
const {
    status: autosaveStatus,
    lastSaved: autosaveLastSaved,
    hasUnsavedChanges,
    markChanged,
    saveNow: autosaveNow,
} = useAutosave({
    key: `post_${props.post.id}`,
    debounceMs: 30000, // 30 seconds
    getData: () => ({
        title: form.title,
        content: form.content,
        content_html: form.content_html,
        excerpt: form.excerpt,
        featured_image: form.featured_image,
    }),
    onSave: async () => {
        return new Promise((resolve, reject) => {
            form.put(`/posts/${props.post.id}`, {
                preserveScroll: true,
                onSuccess: resolve,
                onError: reject,
            });
        });
    },
});

// Watch for form changes to trigger autosave
watch(
    () => [form.title, form.content, form.content_html, form.excerpt, form.featured_image],
    () => {
        markChanged();
    },
    { deep: true }
);

const editorRef = ref(null);
const savedSelection = ref(null);

// Save cursor position when user interacts with AI panel
const saveEditorSelection = () => {
    if (editorRef.value?.editor) {
        savedSelection.value = {
            from: editorRef.value.editor.state.selection.from,
            to: editorRef.value.editor.state.selection.to,
        };
    }
};

const handleApplySuggestion = (suggestion) => {
    // Convert markdown to HTML and set in editor
    const html = marked.parse(suggestion);
    if (editorRef.value?.editor) {
        editorRef.value.editor.commands.setContent(html);
        form.content = editorRef.value.editor.getJSON();
        form.content_html = editorRef.value.editor.getHTML();
    }
};

const handleInsertContent = (content) => {
    // Convert markdown to HTML and insert at saved cursor position
    const html = marked.parse(content);
    if (editorRef.value?.editor) {
        const editor = editorRef.value.editor;

        if (savedSelection.value) {
            // Restore cursor position and insert content there
            editor.chain()
                .focus()
                .setTextSelection(savedSelection.value.from)
                .insertContent(html)
                .run();
        } else {
            // Insert at end if no saved position
            editor.chain()
                .focus('end')
                .insertContent(html)
                .run();
        }

        form.content = editor.getJSON();
        form.content_html = editor.getHTML();
        savedSelection.value = null;
    }
};

const publishForm = useForm({
    publish_to_blog: true,
    send_as_newsletter: false,
    subject_line: props.post.title,
    preview_text: props.post.excerpt || '',
    schedule_mode: 'now', // 'now' or 'scheduled'
    scheduled_at: '',
});

// Set minimum datetime to now (for the datetime-local input)
const minDateTime = computed(() => {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    return now.toISOString().slice(0, 16);
});

const showPublishModal = ref(false);
const showDeleteModal = ref(false);
const isDeleting = ref(false);

const updateContent = (json) => {
    form.content = json;
};

const updateHtml = (html) => {
    form.content_html = html;
};

const save = async () => {
    await autosaveNow();
};

// Computed for save status display
const saveStatusText = computed(() => {
    if (autosaveStatus.value === 'saving' || form.processing) {
        return 'Saving...';
    }
    if (autosaveStatus.value === 'saved' && autosaveLastSaved.value) {
        return `Saved ${autosaveLastSaved.value.toLocaleTimeString()}`;
    }
    if (autosaveStatus.value === 'error') {
        return 'Save failed';
    }
    if (hasUnsavedChanges.value) {
        return 'Unsaved changes';
    }
    return null;
});

const saveStatusClass = computed(() => {
    if (autosaveStatus.value === 'error') {
        return 'text-red-500';
    }
    if (hasUnsavedChanges.value) {
        return 'text-amber-500';
    }
    return 'text-gray-500';
});

const publish = () => {
    publishForm.post(`/posts/${props.post.id}/publish`, {
        onSuccess: () => {
            showPublishModal.value = false;
        },
    });
};

const deletePost = () => {
    isDeleting.value = true;
    router.delete(`/posts/${props.post.id}`, {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
        },
    });
};

const isPublished = computed(() => props.post.status === 'published');
const isScheduled = computed(() => props.post.status === 'scheduled');
</script>

<template>
    <Head :title="post.title || 'Edit Post'" />

    <div class="min-h-screen bg-gray-50">
        <!-- Navigation -->
        <nav class="bg-white border-b border-gray-200 sticky top-0 z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center space-x-4">
                        <Link href="/posts" :preserve-state="false" class="text-gray-500 hover:text-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <span class="text-gray-400">|</span>
                        <span class="text-gray-600 truncate max-w-xs">{{ post.title || 'Untitled' }}</span>
                        <span v-if="isPublished" class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                            Published
                        </span>
                        <span v-else-if="isScheduled" class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                            Scheduled
                        </span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span v-if="saveStatusText" class="text-sm" :class="saveStatusClass">
                            {{ saveStatusText }}
                        </span>

                        <button
                            @click="save"
                            :disabled="form.processing || autosaveStatus === 'saving'"
                            class="px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition disabled:opacity-50"
                        >
                            Save
                        </button>

                        <button
                            v-if="!isPublished && !isScheduled"
                            @click="showPublishModal = true"
                            class="px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition"
                        >
                            Publish
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <div class="flex h-[calc(100vh-4rem)]">
            <!-- Main Editor Area -->
            <main class="flex-1 overflow-y-auto py-8 px-4">
                <div class="max-w-4xl mx-auto">
                    <div class="bg-white rounded-xl shadow-sm p-8">
                        <!-- Title -->
                        <div class="mb-6">
                            <input
                                v-model="form.title"
                                type="text"
                                placeholder="Post title..."
                                class="w-full text-4xl font-bold text-gray-900 border-0 border-b-2 border-transparent focus:border-primary-500 focus:ring-0 pb-2 placeholder-gray-300"
                            />
                            <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
                        </div>

                        <!-- Excerpt -->
                        <div class="mb-6">
                            <textarea
                                v-model="form.excerpt"
                                placeholder="Write a brief excerpt or summary (optional)..."
                                rows="2"
                                class="w-full text-gray-600 border-0 focus:ring-0 resize-none placeholder-gray-400"
                                maxlength="500"
                            ></textarea>
                            <div class="flex justify-between items-center mt-1">
                                <p v-if="form.errors.excerpt" class="text-sm text-red-600">{{ form.errors.excerpt }}</p>
                                <span class="text-xs text-gray-400">{{ form.excerpt?.length || 0 }}/500</span>
                            </div>
                        </div>

                        <!-- Editor -->
                        <div class="mb-6">
                            <PostEditor
                                ref="editorRef"
                                :model-value="form.content"
                                @update:model-value="updateContent"
                                @update:html="updateHtml"
                                placeholder="Start writing your post..."
                            />
                            <p v-if="form.errors.content" class="mt-1 text-sm text-red-600">{{ form.errors.content }}</p>
                        </div>

                        <!-- Featured Image -->
                        <div class="border-t border-gray-200 pt-6 mt-6">
                            <h3 class="text-sm font-medium text-gray-900 mb-4">Featured Image</h3>
                            <div v-if="form.featured_image" class="relative inline-block">
                                <img
                                    :src="form.featured_image"
                                    alt="Featured image"
                                    class="w-48 h-32 object-cover rounded-lg border border-gray-200"
                                />
                                <div class="absolute top-2 right-2 flex gap-1">
                                    <button
                                        type="button"
                                        @click="showFeaturedImagePicker = true"
                                        class="p-1.5 bg-white/90 text-gray-600 rounded-lg hover:bg-white transition shadow-sm"
                                        title="Change image"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        @click="removeFeaturedImage"
                                        class="p-1.5 bg-white/90 text-red-600 rounded-lg hover:bg-white transition shadow-sm"
                                        title="Remove image"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <button
                                v-else
                                type="button"
                                @click="showFeaturedImagePicker = true"
                                class="flex items-center gap-2 px-4 py-3 border-2 border-dashed border-gray-300 rounded-lg text-gray-600 hover:border-gray-400 hover:text-gray-700 transition"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Add Featured Image
                            </button>
                            <p class="mt-2 text-xs text-gray-500">
                                This image will appear at the top of your post and in social shares.
                            </p>
                        </div>

                        <!-- Distribution Options -->
                        <div class="border-t border-gray-200 pt-6 mt-6">
                            <h3 class="text-sm font-medium text-gray-900 mb-4">Distribution Options</h3>
                            <div class="space-y-3">
                                <label class="flex items-center">
                                    <input
                                        v-model="form.publish_to_blog"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">Publish to blog</span>
                                </label>
                                <label class="flex items-center">
                                    <input
                                        v-model="form.send_as_newsletter"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">Send as newsletter</span>
                                </label>
                                <label class="flex items-center">
                                    <input
                                        v-model="form.generate_social"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">Generate social posts</span>
                                </label>
                            </div>
                        </div>

                        <!-- Delete -->
                        <div class="border-t border-gray-200 pt-6 mt-6">
                            <button
                                @click="showDeleteModal = true"
                                class="text-sm text-red-600 hover:text-red-800"
                            >
                                Delete this post
                            </button>
                        </div>
                    </div>
                </div>
            </main>

            <!-- AI Assistant Panel -->
            <div @mousedown="saveEditorSelection" class="flex">
                <AIAssistantPanel
                    :content="form.content_html"
                    :title="form.title"
                    @apply-suggestion="handleApplySuggestion"
                    @insert-content="handleInsertContent"
                />
            </div>
        </div>

        <!-- Publish Modal -->
        <div v-if="showPublishModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showPublishModal = false"></div>

                <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        Publish "{{ post.title }}"
                    </h2>

                    <!-- Publish to Blog -->
                    <div class="mb-6">
                        <label class="flex items-center">
                            <input
                                v-model="publishForm.publish_to_blog"
                                type="checkbox"
                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            />
                            <span class="ml-2 font-medium text-gray-900">Publish to your blog</span>
                        </label>
                        <p class="ml-6 text-sm text-gray-500">
                            {{ brand.url }}/{{ post.slug }}
                        </p>
                    </div>

                    <!-- Send as Newsletter -->
                    <div class="mb-6">
                        <label class="flex items-center">
                            <input
                                v-model="publishForm.send_as_newsletter"
                                type="checkbox"
                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            />
                            <span class="ml-2 font-medium text-gray-900">Send as newsletter</span>
                        </label>

                        <div v-if="publishForm.send_as_newsletter" class="ml-6 mt-3 space-y-3">
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Subject line</label>
                                <input
                                    v-model="publishForm.subject_line"
                                    type="text"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Preview text</label>
                                <input
                                    v-model="publishForm.preview_text"
                                    type="text"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Schedule Options -->
                    <div class="mb-6 border-t border-gray-200 pt-6">
                        <label class="block text-sm font-medium text-gray-900 mb-3">When to publish?</label>
                        <div class="space-y-3">
                            <label class="flex items-center">
                                <input
                                    v-model="publishForm.schedule_mode"
                                    type="radio"
                                    value="now"
                                    class="border-gray-300 text-primary-600 focus:ring-primary-500"
                                />
                                <span class="ml-2 text-gray-700">Publish immediately</span>
                            </label>
                            <label class="flex items-center">
                                <input
                                    v-model="publishForm.schedule_mode"
                                    type="radio"
                                    value="scheduled"
                                    class="border-gray-300 text-primary-600 focus:ring-primary-500"
                                />
                                <span class="ml-2 text-gray-700">Schedule for later</span>
                            </label>
                        </div>

                        <div v-if="publishForm.schedule_mode === 'scheduled'" class="mt-3 ml-6">
                            <label class="block text-sm text-gray-700 mb-1">Schedule date and time</label>
                            <input
                                v-model="publishForm.scheduled_at"
                                type="datetime-local"
                                :min="minDateTime"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                            />
                            <p v-if="publishForm.errors.scheduled_at" class="mt-1 text-sm text-red-600">
                                {{ publishForm.errors.scheduled_at }}
                            </p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                        <button
                            @click="showPublishModal = false"
                            class="px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 rounded-lg transition"
                        >
                            Cancel
                        </button>
                        <button
                            @click="publish"
                            :disabled="publishForm.processing || (publishForm.schedule_mode === 'scheduled' && !publishForm.scheduled_at)"
                            class="px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition disabled:opacity-50"
                        >
                            {{ publishForm.schedule_mode === 'scheduled' ? 'Schedule' : 'Publish Now' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmModal
            :show="showDeleteModal"
            title="Delete Post"
            message="Are you sure you want to delete this post? This action cannot be undone."
            confirm-text="Delete"
            :processing="isDeleting"
            @confirm="deletePost"
            @cancel="showDeleteModal = false"
        />

        <!-- Featured Image Picker Modal -->
        <MediaPickerModal
            :show="showFeaturedImagePicker"
            @close="showFeaturedImagePicker = false"
            @select="handleFeaturedImageSelect"
        />
    </div>
</template>
