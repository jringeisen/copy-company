<script setup>
import { ref, watch, computed, onBeforeUnmount } from 'vue';
import axios from 'axios';
import MediaUploader from './MediaUploader.vue';

const props = defineProps({
    show: Boolean,
    multiple: {
        type: Boolean,
        default: false,
    },
    maxItems: {
        type: Number,
        default: 1,
    },
});

const emit = defineEmits(['close', 'select', 'error']);

const media = ref([]);
const folders = ref([]);
const currentFolderId = ref(null);
const isLoading = ref(false);
const selectedItems = ref([]);
const showUploader = ref(false);
const search = ref('');
const searchTimeout = ref(null);

const canSelectMore = computed(() => {
    if (!props.multiple) return selectedItems.value.length === 0;
    return selectedItems.value.length < props.maxItems;
});

const fetchMedia = async () => {
    isLoading.value = true;
    try {
        const params = new URLSearchParams();
        if (currentFolderId.value) {
            params.append('folder_id', currentFolderId.value);
        }
        if (search.value) {
            params.append('search', search.value);
        }

        const response = await axios.get(`/media/list?${params.toString()}`);
        media.value = response.data.data || [];
    } catch (error) {
        console.error('Failed to fetch media:', error);
        emit('error', { message: 'Failed to load media library', error });
    } finally {
        isLoading.value = false;
    }
};

const fetchFolders = async () => {
    try {
        const response = await axios.get('/media/folders');
        folders.value = response.data.data || [];
    } catch (error) {
        console.error('Failed to fetch folders:', error);
        emit('error', { message: 'Failed to load folders', error });
    }
};

watch(() => props.show, async (val) => {
    if (val) {
        selectedItems.value = [];
        search.value = '';
        currentFolderId.value = null;
        await Promise.all([fetchMedia(), fetchFolders()]);
    }
});

// Folder changes trigger immediate fetch
watch(currentFolderId, () => {
    fetchMedia();
});

// Search uses debouncing to avoid excessive API calls
watch(search, () => {
    if (searchTimeout.value) {
        clearTimeout(searchTimeout.value);
    }
    searchTimeout.value = setTimeout(() => {
        fetchMedia();
    }, 300);
});

// Cleanup timeout on unmount
onBeforeUnmount(() => {
    if (searchTimeout.value) {
        clearTimeout(searchTimeout.value);
    }
});

const toggleSelection = (item) => {
    const index = selectedItems.value.findIndex(s => s.id === item.id);
    if (index > -1) {
        selectedItems.value.splice(index, 1);
    } else if (canSelectMore.value) {
        if (!props.multiple) {
            selectedItems.value = [item];
        } else {
            selectedItems.value.push(item);
        }
    }
};

const isSelected = (item) => selectedItems.value.some(s => s.id === item.id);

const selectItem = (item) => {
    // Prevent clicks on unselected items when at max selections
    if (!isSelected(item) && !canSelectMore.value) {
        return;
    }

    if (props.multiple) {
        toggleSelection(item);
    } else {
        emit('select', item);
    }
};

const confirmSelection = () => {
    if (props.multiple) {
        emit('select', selectedItems.value);
    } else if (selectedItems.value.length > 0) {
        emit('select', selectedItems.value[0]);
    }
};

const navigateToFolder = (folderId) => {
    currentFolderId.value = folderId;
};

const navigateToRoot = () => {
    currentFolderId.value = null;
};

const onUploadComplete = () => {
    showUploader.value = false;
    fetchMedia();
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-50 overflow-hidden">
                <div class="flex h-full items-center justify-center p-4">
                    <!-- Backdrop -->
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="emit('close')"></div>

                    <!-- Modal -->
                    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-4xl h-[80vh] flex flex-col">
                        <!-- Header -->
                        <div class="flex items-center justify-between p-4 border-b border-[#0b1215]/10">
                            <h3 class="text-lg font-semibold text-[#0b1215]">
                                {{ multiple ? `Select Images (${selectedItems.length}/${maxItems})` : 'Select Image' }}
                            </h3>
                            <div class="flex items-center gap-3">
                                <button
                                    @click="showUploader = true"
                                    class="px-3 py-1.5 text-sm bg-[#0b1215] text-white rounded-full hover:bg-[#0b1215]/90 transition"
                                >
                                    Upload New
                                </button>
                                <button @click="emit('close')" class="text-[#0b1215]/40 hover:text-[#0b1215]/60">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Search & Filters -->
                        <div class="px-4 py-3 border-b border-[#0b1215]/10 flex items-center gap-4">
                            <div class="relative grow">
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Search images..."
                                    class="w-full pl-10 pr-4 py-2 border border-[#0b1215]/20 rounded-xl focus:ring-[#a1854f]/30 focus:border-[#a1854f]"
                                />
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-[#0b1215]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>

                            <!-- Folder Filter -->
                            <select
                                v-model="currentFolderId"
                                class="px-3 py-2 border border-[#0b1215]/20 rounded-xl focus:ring-[#a1854f]/30 focus:border-[#a1854f]"
                            >
                                <option :value="null">All Folders</option>
                                <option v-for="folder in folders" :key="folder.id" :value="folder.id">
                                    {{ folder.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Content -->
                        <div class="grow overflow-y-auto p-4">
                            <!-- Loading -->
                            <div v-if="isLoading" class="flex items-center justify-center h-full">
                                <svg class="animate-spin h-8 w-8 text-[#0b1215]" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                </svg>
                            </div>

                            <!-- Empty State -->
                            <div v-else-if="media.length === 0" class="flex flex-col items-center justify-center h-full text-center">
                                <svg class="w-16 h-16 text-[#0b1215]/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <p class="mt-4 text-[#0b1215]/60">No images found</p>
                                <button
                                    @click="showUploader = true"
                                    class="mt-2 text-[#a1854f] hover:text-[#a1854f]/80"
                                >
                                    Upload images
                                </button>
                            </div>

                            <!-- Grid -->
                            <div v-else class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
                                <div
                                    v-for="item in media"
                                    :key="item.id"
                                    @click="selectItem(item)"
                                    :class="[
                                        'relative aspect-square bg-[#f7f7f7] rounded-xl overflow-hidden transition border-2',
                                        isSelected(item)
                                            ? 'border-[#a1854f] ring-2 ring-[#a1854f]/20 cursor-pointer'
                                            : 'border-transparent hover:border-[#0b1215]/20 cursor-pointer',
                                        !canSelectMore && !isSelected(item)
                                            ? 'opacity-50 cursor-not-allowed pointer-events-none'
                                            : ''
                                    ]"
                                >
                                    <img
                                        :src="item.thumbnail_url || item.url"
                                        :alt="item.alt_text || item.filename"
                                        class="w-full h-full object-cover"
                                        loading="lazy"
                                    />
                                    <div
                                        v-if="isSelected(item)"
                                        class="absolute top-2 left-2 w-6 h-6 bg-[#a1854f] text-white rounded-full flex items-center justify-center"
                                    >
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="flex items-center justify-between p-4 border-t border-[#0b1215]/10 bg-[#f7f7f7] rounded-b-2xl">
                            <div class="text-sm text-[#0b1215]/60">
                                {{ selectedItems.length }} selected
                            </div>
                            <div class="flex items-center gap-3">
                                <button
                                    @click="emit('close')"
                                    class="px-4 py-2 text-[#0b1215] font-medium bg-white border border-[#0b1215]/20 rounded-xl hover:bg-[#0b1215]/5 transition"
                                >
                                    Cancel
                                </button>
                                <button
                                    v-if="multiple"
                                    @click="confirmSelection"
                                    :disabled="selectedItems.length === 0"
                                    class="px-4 py-2 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition disabled:opacity-50"
                                >
                                    Select {{ selectedItems.length }} Image{{ selectedItems.length !== 1 ? 's' : '' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Uploader Modal -->
                <MediaUploader
                    :show="showUploader"
                    :folder-id="currentFolderId"
                    @close="onUploadComplete"
                />
            </div>
        </Transition>
    </Teleport>
</template>
