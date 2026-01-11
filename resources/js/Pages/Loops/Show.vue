<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import MediaPickerModal from '@/Components/Media/MediaPickerModal.vue';
import draggable from 'vuedraggable';

const props = defineProps({
    loop: Object,
    availableSocialPosts: Array,
    platforms: Array,
    daysOfWeek: Array,
});

const showAddItemModal = ref(false);
const showImportModal = ref(false);
const showDeleteModal = ref(false);
const showEditModal = ref(false);
const showMediaPicker = ref(false);
const showEditMediaPicker = ref(false);
const itemToDelete = ref(null);
const itemToEdit = ref(null);
const isDeleting = ref(false);
const addItemMode = ref('existing'); // 'existing' or 'new'

const addItemForm = useForm({
    social_post_id: null,
    content: '',
    format: 'feed',
    hashtags: [],
    link: '',
    media: [],
});

const importForm = useForm({
    file: null,
});

const editItemForm = useForm({
    content: '',
    format: 'feed',
    hashtags: [],
    link: '',
    media: [],
});

const hashtagInput = ref('');
const editHashtagInput = ref('');

const items = ref(Array.isArray(props.loop.items) ? props.loop.items.map(item => ({ ...item })) : []);

// Watch for changes to props.loop.items and sync with local items ref
watch(() => props.loop.items, (newItems) => {
    items.value = Array.isArray(newItems) ? newItems.map(item => ({ ...item })) : [];
}, { deep: true });

const currentItem = computed(() => {
    if (!items.value.length) return null;
    return items.value.find(item => item.position === props.loop.current_position);
});

const platformLabels = {
    instagram: 'IG',
    facebook: 'FB',
    pinterest: 'Pin',
    linkedin: 'LI',
    tiktok: 'TT',
};

const getPlatformLabel = (platform) => {
    return platformLabels[platform] || platform.slice(0, 2).toUpperCase();
};

const itemHasWarning = (item) => {
    const qualified = item.qualified_platforms || [];
    const total = props.loop.platforms?.length || 0;
    return qualified.length < total && qualified.length > 0;
};

const itemHasError = (item) => {
    const qualified = item.qualified_platforms || [];
    return qualified.length === 0 && (props.loop.platforms?.length || 0) > 0;
};

const addHashtag = () => {
    if (hashtagInput.value.trim()) {
        const tag = hashtagInput.value.trim().replace(/^#/, '');
        if (!addItemForm.hashtags.includes(tag)) {
            addItemForm.hashtags.push(tag);
        }
        hashtagInput.value = '';
    }
};

const removeHashtag = (index) => {
    addItemForm.hashtags.splice(index, 1);
};

const handleMediaSelect = (selectedMedia) => {
    // Handle both single and multiple media selections
    const mediaItems = Array.isArray(selectedMedia) ? selectedMedia : [selectedMedia];
    addItemForm.media = mediaItems.map(item => ({
        id: item.id,
        url: item.url,
        thumbnail_url: item.thumbnail_url,
        alt_text: item.alt_text,
    }));
    showMediaPicker.value = false;
};

const removeMedia = (index) => {
    addItemForm.media.splice(index, 1);
};

const submitAddItem = () => {
    if (addItemMode.value === 'existing' && addItemForm.social_post_id) {
        addItemForm.post(`/loops/${props.loop.id}/items`, {
            onSuccess: () => {
                showAddItemModal.value = false;
                addItemForm.reset();
            },
        });
    } else if (addItemMode.value === 'new' && addItemForm.content) {
        addItemForm.post(`/loops/${props.loop.id}/items`, {
            onSuccess: () => {
                showAddItemModal.value = false;
                addItemForm.reset();
            },
        });
    }
};

const removeItem = (item) => {
    itemToDelete.value = item;
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    if (!itemToDelete.value) return;

    isDeleting.value = true;
    router.delete(`/loops/${props.loop.id}/items/${itemToDelete.value.id}`, {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
            itemToDelete.value = null;
        },
    });
};

const cancelDelete = () => {
    showDeleteModal.value = false;
    itemToDelete.value = null;
};

const openEditModal = (item) => {
    // Don't allow editing linked items
    if (item.is_linked) {
        return;
    }
    itemToEdit.value = item;
    editItemForm.content = item.content || '';
    editItemForm.format = item.format || 'feed';
    editItemForm.hashtags = item.hashtags || [];
    editItemForm.link = item.link || '';
    editItemForm.media = item.media || [];
    editHashtagInput.value = '';
    showEditModal.value = true;
};

const submitEditItem = () => {
    if (!itemToEdit.value) return;

    editItemForm.put(`/loops/${props.loop.id}/items/${itemToEdit.value.id}`, {
        onSuccess: () => {
            showEditModal.value = false;
            itemToEdit.value = null;
            editItemForm.reset();
        },
    });
};

const cancelEdit = () => {
    showEditModal.value = false;
    itemToEdit.value = null;
    editItemForm.reset();
};

const addEditHashtag = () => {
    const tag = editHashtagInput.value.trim().replace(/^#/, '');
    if (tag && !editItemForm.hashtags.includes(tag)) {
        editItemForm.hashtags.push(tag);
        editHashtagInput.value = '';
    }
};

const removeEditHashtag = (index) => {
    editItemForm.hashtags.splice(index, 1);
};

const handleEditMediaSelect = (selectedMedia) => {
    const mediaItems = Array.isArray(selectedMedia) ? selectedMedia : [selectedMedia];
    editItemForm.media = mediaItems.map(item => ({
        id: item.id,
        url: item.url,
        thumbnail_url: item.thumbnail_url,
        alt_text: item.alt_text,
    }));
    showEditMediaPicker.value = false;
};

const removeEditMedia = (index) => {
    editItemForm.media.splice(index, 1);
};

const onDragEnd = () => {
    const itemIds = items.value.map(item => item.id);
    router.post(`/loops/${props.loop.id}/reorder`, {
        items: itemIds,
    }, {
        preserveScroll: true,
    });
};

const submitImport = () => {
    importForm.post(`/loops/${props.loop.id}/import`, {
        forceFormData: true,
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
        },
    });
};

const handleFileChange = (event) => {
    importForm.file = event.target.files[0];
};

const toggleLoop = () => {
    router.post(`/loops/${props.loop.id}/toggle`);
};

const truncateContent = (content, length = 100) => {
    if (!content) return '';
    return content.length > length ? content.substring(0, length) + '...' : content;
};
</script>

<template>
    <Head :title="loop.name" />

    <AppLayout current-page="loops">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <Link href="/loops" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to Loops
                </Link>

                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-bold text-gray-900">{{ loop.name }}</h1>
                            <span
                                :class="[
                                    'px-2 py-1 text-xs font-medium rounded-full',
                                    loop.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'
                                ]"
                            >
                                {{ loop.is_active ? 'Active' : 'Paused' }}
                            </span>
                        </div>
                        <p v-if="loop.description" class="mt-1 text-sm text-gray-500">{{ loop.description }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            @click="toggleLoop"
                            :class="[
                                'px-4 py-2 text-sm font-medium rounded-xl transition-colors',
                                loop.is_active
                                    ? 'text-gray-700 bg-gray-100 hover:bg-gray-200'
                                    : 'text-green-700 bg-green-100 hover:bg-green-200'
                            ]"
                        >
                            {{ loop.is_active ? 'Pause' : 'Activate' }}
                        </button>
                        <Link
                            :href="`/loops/${loop.id}/edit`"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors"
                        >
                            Edit
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-4 gap-4 mb-8">
                <div class="bg-white rounded-2xl border border-gray-200 p-4 text-center">
                    <div class="text-2xl font-bold text-gray-900">{{ loop.items?.length || 0 }}</div>
                    <div class="text-sm text-gray-500">Items</div>
                </div>
                <div class="bg-white rounded-2xl border border-gray-200 p-4 text-center">
                    <div class="text-2xl font-bold text-gray-900">{{ loop.current_position }}</div>
                    <div class="text-sm text-gray-500">Current Position</div>
                </div>
                <div class="bg-white rounded-2xl border border-gray-200 p-4 text-center">
                    <div class="text-2xl font-bold text-gray-900">{{ loop.total_cycles_completed }}</div>
                    <div class="text-sm text-gray-500">Cycles Completed</div>
                </div>
                <div class="bg-white rounded-2xl border border-gray-200 p-4 text-center">
                    <div class="text-2xl font-bold text-gray-900">{{ loop.schedules?.length || 0 }}</div>
                    <div class="text-sm text-gray-500">Schedules</div>
                </div>
            </div>

            <!-- Schedule Overview -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6 mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Schedule</h2>
                <div v-if="loop.schedules?.length > 0" class="flex flex-wrap gap-2">
                    <div
                        v-for="schedule in loop.schedules"
                        :key="schedule.id"
                        class="px-3 py-2 bg-gray-50 rounded-xl text-sm"
                    >
                        <span class="font-medium">{{ schedule.day_of_week_display }}</span>
                        <span class="text-gray-500"> at </span>
                        <span class="font-medium">{{ schedule.time_display }}</span>
                        <span v-if="schedule.platform" class="text-gray-400 capitalize"> ({{ schedule.platform }})</span>
                    </div>
                </div>
                <p v-else class="text-sm text-gray-500 italic">No schedules set</p>
            </div>

            <!-- Items List -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Content Items</h2>
                    <div class="flex gap-2">
                        <button
                            @click="showImportModal = true"
                            class="px-4 py-2 text-sm font-medium text-gray-700 border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors"
                        >
                            Import CSV
                        </button>
                        <button
                            @click="showAddItemModal = true"
                            class="px-4 py-2 text-sm font-medium text-white bg-[#0b1215] rounded-xl hover:bg-[#0b1215]/90 transition-colors"
                        >
                            Add Item
                        </button>
                    </div>
                </div>

                <!-- Drag hint -->
                <p v-if="items.length > 1" class="text-xs text-gray-500 mb-4">Drag items to reorder</p>

                <!-- Items -->
                <draggable
                    v-if="items.length > 0"
                    v-model="items"
                    item-key="id"
                    handle=".drag-handle"
                    ghost-class="opacity-50"
                    @end="onDragEnd"
                    class="space-y-2"
                >
                    <template #item="{ element: item, index }">
                        <div
                            :class="[
                                'flex items-start gap-4 p-4 rounded-xl border transition-colors',
                                item.position === loop.current_position
                                    ? 'border-[#a1854f] bg-[#a1854f]/5'
                                    : 'border-gray-200 hover:border-gray-300'
                            ]"
                        >
                            <!-- Drag Handle -->
                            <div class="drag-handle cursor-grab text-gray-400 hover:text-gray-600 pt-1">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                </svg>
                            </div>

                            <!-- Position -->
                            <div class="shrink-0 w-8 h-8 flex items-center justify-center bg-gray-100 rounded-full text-sm font-medium text-gray-600">
                                {{ index + 1 }}
                            </div>

                            <!-- Media Thumbnail -->
                            <div v-if="item.media && item.media.length > 0" class="relative shrink-0 w-12 h-12 rounded-lg overflow-hidden border border-gray-200">
                                <img
                                    :src="item.media[0].thumbnail_url || item.media[0].url || item.media[0]"
                                    alt="Media"
                                    class="w-full h-full object-cover"
                                />
                                <div v-if="item.media.length > 1" class="absolute bottom-0.5 right-0.5 px-1 py-0.5 bg-black/70 text-white text-[10px] font-medium rounded">
                                    +{{ item.media.length - 1 }}
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900">{{ truncateContent(item.content) }}</p>
                                <div class="flex items-center gap-2 mt-2 flex-wrap">
                                    <span v-if="item.is_linked" class="px-2 py-0.5 text-xs bg-blue-100 text-blue-600 rounded-full">
                                        Linked Post
                                    </span>
                                    <span class="text-xs text-gray-400">
                                        Posted {{ item.times_posted }}x
                                    </span>
                                    <!-- Platform indicators -->
                                    <div class="flex items-center gap-1 ml-auto">
                                        <!-- Warning icon if not all platforms qualify -->
                                        <span
                                            v-if="itemHasWarning(item)"
                                            class="text-amber-500"
                                            title="This item won't post to all platforms"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                        </span>
                                        <!-- Error icon if no platforms qualify -->
                                        <span
                                            v-else-if="itemHasError(item)"
                                            class="text-red-500"
                                            title="This item won't post to any platforms"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </span>
                                        <template v-for="platform in loop.platforms" :key="platform">
                                            <span
                                                :class="[
                                                    'px-1.5 py-0.5 text-[10px] font-medium rounded',
                                                    item.qualified_platforms?.includes(platform)
                                                        ? 'bg-green-100 text-green-700'
                                                        : 'bg-gray-100 text-gray-400 line-through'
                                                ]"
                                                :title="item.disqualified_platforms?.[platform] || 'Ready to post'"
                                            >
                                                {{ getPlatformLabel(platform) }}
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- Current indicator -->
                            <div v-if="item.position === loop.current_position" class="shrink-0">
                                <span class="px-2 py-1 text-xs font-medium bg-[#a1854f] text-white rounded-full">
                                    Next
                                </span>
                            </div>

                            <!-- Edit (only for non-linked items) -->
                            <button
                                v-if="!item.is_linked"
                                @click="openEditModal(item)"
                                class="shrink-0 text-gray-400 hover:text-[#0b1215] transition-colors"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>

                            <!-- Remove -->
                            <button
                                @click="removeItem(item)"
                                class="shrink-0 text-gray-400 hover:text-red-500 transition-colors"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </template>
                </draggable>

                <!-- Empty state -->
                <div v-else class="text-center py-12">
                    <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No items yet</h3>
                    <p class="mt-2 text-sm text-gray-500">Add content to this loop to get started</p>
                </div>
            </div>
        </div>

        <!-- Add Item Modal -->
        <Teleport to="body">
            <div v-if="showAddItemModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" @click="showAddItemModal = false" />
                <div class="relative bg-white rounded-2xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Add Item to Loop</h3>

                        <!-- Mode tabs -->
                        <div class="flex gap-2 mb-4">
                            <button
                                @click="addItemMode = 'existing'"
                                :class="[
                                    'px-4 py-2 text-sm font-medium rounded-xl transition-colors',
                                    addItemMode === 'existing' ? 'bg-[#0b1215] text-white' : 'bg-gray-100 text-gray-700'
                                ]"
                            >
                                Existing Post
                            </button>
                            <button
                                @click="addItemMode = 'new'"
                                :class="[
                                    'px-4 py-2 text-sm font-medium rounded-xl transition-colors',
                                    addItemMode === 'new' ? 'bg-[#0b1215] text-white' : 'bg-gray-100 text-gray-700'
                                ]"
                            >
                                New Content
                            </button>
                        </div>

                        <!-- Existing Post Selection -->
                        <div v-if="addItemMode === 'existing'" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Select Social Post</label>
                                <select
                                    v-model="addItemForm.social_post_id"
                                    class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#a1854f] focus:border-transparent"
                                >
                                    <option :value="null">Choose a post...</option>
                                    <option v-for="post in availableSocialPosts" :key="post.id" :value="post.id">
                                        {{ truncateContent(post.content, 60) }} ({{ post.platform }})
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- New Content Form -->
                        <div v-else class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Content</label>
                                <textarea
                                    v-model="addItemForm.content"
                                    rows="4"
                                    class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#a1854f] focus:border-transparent"
                                    placeholder="Enter your post content..."
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Format</label>
                                <select
                                    v-model="addItemForm.format"
                                    class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#a1854f] focus:border-transparent"
                                >
                                    <option value="feed">Feed</option>
                                    <option value="story">Story</option>
                                    <option value="reel">Reel</option>
                                    <option value="carousel">Carousel</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Link (optional)</label>
                                <input
                                    v-model="addItemForm.link"
                                    type="url"
                                    class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#a1854f] focus:border-transparent"
                                    placeholder="https://..."
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Hashtags</label>
                                <div class="flex flex-wrap gap-1 mb-2">
                                    <span
                                        v-for="(tag, index) in addItemForm.hashtags"
                                        :key="index"
                                        class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded-full flex items-center gap-1"
                                    >
                                        #{{ tag }}
                                        <button @click="removeHashtag(index)" class="text-gray-400 hover:text-red-500">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </span>
                                </div>
                                <div class="flex gap-2">
                                    <input
                                        v-model="hashtagInput"
                                        type="text"
                                        class="flex-1 px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#a1854f] focus:border-transparent"
                                        placeholder="Add hashtag"
                                        @keydown.enter.prevent="addHashtag"
                                    />
                                    <button
                                        type="button"
                                        @click="addHashtag"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200"
                                    >
                                        Add
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Images</label>
                                <div v-if="addItemForm.media.length > 0" class="flex flex-wrap gap-2 mb-2">
                                    <div
                                        v-for="(item, index) in addItemForm.media"
                                        :key="item.id"
                                        class="relative w-20 h-20 rounded-lg overflow-hidden border border-gray-200"
                                    >
                                        <img
                                            :src="item.thumbnail_url || item.url"
                                            :alt="item.alt_text || 'Selected image'"
                                            class="w-full h-full object-cover"
                                        />
                                        <button
                                            type="button"
                                            @click="removeMedia(index)"
                                            class="absolute top-1 right-1 w-5 h-5 bg-black/60 text-white rounded-full flex items-center justify-center hover:bg-black/80"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="showMediaPicker = true"
                                    class="w-full px-4 py-3 border-2 border-dashed border-gray-200 rounded-xl text-sm text-gray-600 hover:border-[#a1854f] hover:text-[#a1854f] transition-colors flex items-center justify-center gap-2"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ addItemForm.media.length > 0 ? 'Change Images' : 'Add Images' }}
                                </button>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 mt-6">
                            <button
                                @click="showAddItemModal = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                @click="submitAddItem"
                                :disabled="addItemForm.processing"
                                class="px-4 py-2 text-sm font-medium text-white bg-[#0b1215] rounded-xl hover:bg-[#0b1215]/90 transition-colors disabled:opacity-50"
                            >
                                {{ addItemForm.processing ? 'Adding...' : 'Add Item' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Media Picker Modal -->
        <MediaPickerModal
            :show="showMediaPicker"
            :multiple="true"
            :max-items="10"
            @select="handleMediaSelect"
            @close="showMediaPicker = false"
        />

        <!-- Import Modal -->
        <Teleport to="body">
            <div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" @click="showImportModal = false" />
                <div class="relative bg-white rounded-2xl shadow-xl max-w-lg w-full">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Import from CSV</h3>

                        <div class="mb-4">
                            <p class="text-sm text-gray-600 mb-2">Upload a CSV file with the following columns:</p>
                            <code class="block p-3 bg-gray-100 rounded-xl text-xs text-gray-700">
                                content,format,hashtags,link,media_url
                            </code>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">CSV File</label>
                            <input
                                type="file"
                                accept=".csv,.txt"
                                @change="handleFileChange"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#a1854f] focus:border-transparent"
                            />
                            <p v-if="importForm.errors.file" class="mt-1 text-sm text-red-500">{{ importForm.errors.file }}</p>
                        </div>

                        <div class="flex justify-end gap-2">
                            <button
                                @click="showImportModal = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                @click="submitImport"
                                :disabled="importForm.processing || !importForm.file"
                                class="px-4 py-2 text-sm font-medium text-white bg-[#0b1215] rounded-xl hover:bg-[#0b1215]/90 transition-colors disabled:opacity-50"
                            >
                                {{ importForm.processing ? 'Importing...' : 'Import' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Delete Confirmation Modal -->
        <ConfirmModal
            :show="showDeleteModal"
            title="Remove Item"
            message="Are you sure you want to remove this item from the loop? This action cannot be undone."
            confirm-text="Remove"
            :processing="isDeleting"
            @confirm="confirmDelete"
            @cancel="cancelDelete"
        />

        <!-- Edit Item Modal -->
        <Teleport to="body">
            <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" @click="cancelEdit" />
                <div class="relative bg-white rounded-2xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Edit Item</h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Content</label>
                                <textarea
                                    v-model="editItemForm.content"
                                    rows="4"
                                    class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#0b1215]/20 focus:border-[#0b1215]/40"
                                    placeholder="Enter your post content..."
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Format</label>
                                <select
                                    v-model="editItemForm.format"
                                    class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#0b1215]/20 focus:border-[#0b1215]/40"
                                >
                                    <option value="feed">Feed</option>
                                    <option value="story">Story</option>
                                    <option value="reel">Reel</option>
                                    <option value="carousel">Carousel</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Link (optional)</label>
                                <input
                                    v-model="editItemForm.link"
                                    type="url"
                                    class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#0b1215]/20 focus:border-[#0b1215]/40"
                                    placeholder="https://..."
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Hashtags</label>
                                <div class="flex flex-wrap gap-1 mb-2">
                                    <span
                                        v-for="(tag, index) in editItemForm.hashtags"
                                        :key="index"
                                        class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded-full flex items-center gap-1"
                                    >
                                        #{{ tag }}
                                        <button @click="removeEditHashtag(index)" class="text-gray-400 hover:text-red-500">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </span>
                                </div>
                                <div class="flex gap-2">
                                    <input
                                        v-model="editHashtagInput"
                                        type="text"
                                        class="flex-1 px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#0b1215]/20 focus:border-[#0b1215]/40"
                                        placeholder="Add hashtag"
                                        @keydown.enter.prevent="addEditHashtag"
                                    />
                                    <button
                                        type="button"
                                        @click="addEditHashtag"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200"
                                    >
                                        Add
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Images</label>
                                <div v-if="editItemForm.media.length > 0" class="flex flex-wrap gap-2 mb-2">
                                    <div
                                        v-for="(mediaItem, index) in editItemForm.media"
                                        :key="mediaItem.id || index"
                                        class="relative w-20 h-20 rounded-lg overflow-hidden border border-gray-200"
                                    >
                                        <img
                                            :src="mediaItem.thumbnail_url || mediaItem.url || mediaItem"
                                            :alt="mediaItem.alt_text || 'Selected image'"
                                            class="w-full h-full object-cover"
                                        />
                                        <button
                                            type="button"
                                            @click="removeEditMedia(index)"
                                            class="absolute top-1 right-1 w-5 h-5 bg-black/60 text-white rounded-full flex items-center justify-center hover:bg-black/80"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="showEditMediaPicker = true"
                                    class="w-full px-4 py-3 border-2 border-dashed border-gray-200 rounded-xl text-sm text-gray-600 hover:border-[#0b1215] hover:text-[#0b1215] transition-colors flex items-center justify-center gap-2"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ editItemForm.media.length > 0 ? 'Change Images' : 'Add Images' }}
                                </button>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 mt-6">
                            <button
                                @click="cancelEdit"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                @click="submitEditItem"
                                :disabled="editItemForm.processing"
                                class="px-4 py-2 text-sm font-medium text-white bg-[#0b1215] rounded-xl hover:bg-[#0b1215]/90 transition-colors disabled:opacity-50"
                            >
                                {{ editItemForm.processing ? 'Saving...' : 'Save Changes' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Edit Media Picker Modal -->
        <MediaPickerModal
            :show="showEditMediaPicker"
            :multiple="true"
            :max-items="10"
            @select="handleEditMediaSelect"
            @close="showEditMediaPicker = false"
        />
    </AppLayout>
</template>
