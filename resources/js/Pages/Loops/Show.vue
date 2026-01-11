<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import draggable from 'vuedraggable';

const props = defineProps({
    loop: Object,
    availableSocialPosts: Array,
    platforms: Array,
    daysOfWeek: Array,
});

const showAddItemModal = ref(false);
const showImportModal = ref(false);
const addItemMode = ref('existing'); // 'existing' or 'new'

const addItemForm = useForm({
    social_post_id: null,
    content: '',
    platform: null,
    format: 'feed',
    hashtags: [],
    link: '',
});

const importForm = useForm({
    file: null,
});

const hashtagInput = ref('');

const items = ref(Array.isArray(props.loop.items) ? props.loop.items.map(item => ({ ...item })) : []);

const currentItem = computed(() => {
    if (!items.value.length) return null;
    return items.value.find(item => item.position === props.loop.current_position);
});

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
    if (confirm('Remove this item from the loop?')) {
        router.delete(`/loops/${props.loop.id}/items/${item.id}`);
    }
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
                            class="px-4 py-2 text-sm font-medium text-white bg-[#a1854f] rounded-xl hover:bg-[#8a7243] transition-colors"
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

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900">{{ truncateContent(item.content) }}</p>
                                <div class="flex items-center gap-2 mt-2">
                                    <span v-if="item.platform" class="px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded-full capitalize">
                                        {{ item.platform }}
                                    </span>
                                    <span v-if="item.is_linked" class="px-2 py-0.5 text-xs bg-blue-100 text-blue-600 rounded-full">
                                        Linked Post
                                    </span>
                                    <span class="text-xs text-gray-400">
                                        Posted {{ item.times_posted }}x
                                    </span>
                                </div>
                            </div>

                            <!-- Current indicator -->
                            <div v-if="item.position === loop.current_position" class="shrink-0">
                                <span class="px-2 py-1 text-xs font-medium bg-[#a1854f] text-white rounded-full">
                                    Next
                                </span>
                            </div>

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
                                    addItemMode === 'existing' ? 'bg-[#a1854f] text-white' : 'bg-gray-100 text-gray-700'
                                ]"
                            >
                                Existing Post
                            </button>
                            <button
                                @click="addItemMode = 'new'"
                                :class="[
                                    'px-4 py-2 text-sm font-medium rounded-xl transition-colors',
                                    addItemMode === 'new' ? 'bg-[#a1854f] text-white' : 'bg-gray-100 text-gray-700'
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

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Platform (optional)</label>
                                    <select
                                        v-model="addItemForm.platform"
                                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#a1854f] focus:border-transparent"
                                    >
                                        <option :value="null">Use loop platforms</option>
                                        <option v-for="platform in platforms" :key="platform.value" :value="platform.value">
                                            {{ platform.label }}
                                        </option>
                                    </select>
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
                                class="px-4 py-2 text-sm font-medium text-white bg-[#a1854f] rounded-xl hover:bg-[#8a7243] transition-colors disabled:opacity-50"
                            >
                                {{ addItemForm.processing ? 'Adding...' : 'Add Item' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

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
                                content,platform,format,hashtags,link,media_url
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
                                class="px-4 py-2 text-sm font-medium text-white bg-[#a1854f] rounded-xl hover:bg-[#8a7243] transition-colors disabled:opacity-50"
                            >
                                {{ importForm.processing ? 'Importing...' : 'Import' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
