<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppNavigation from '@/Components/AppNavigation.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import MediaGrid from '@/Components/Media/MediaGrid.vue';
import MediaUploader from '@/Components/Media/MediaUploader.vue';
import FolderTree from '@/Components/Media/FolderTree.vue';
import CreateFolderModal from '@/Components/Media/CreateFolderModal.vue';
import EditAltTextModal from '@/Components/Media/EditAltTextModal.vue';
import MoveFolderModal from '@/Components/Media/MoveFolderModal.vue';
import { usePermissions } from '@/Composables/usePermissions';

const { canUploadMedia, canDeleteMedia } = usePermissions();

const props = defineProps({
    media: Object,
    folders: Array,
    currentFolder: Object,
});

const selectedIds = ref([]);
const showUploader = ref(false);
const showDeleteModal = ref(false);
const showCreateFolderModal = ref(false);
const showEditAltModal = ref(false);
const showMoveModal = ref(false);
const isDeleting = ref(false);
const editingMedia = ref(null);

const allSelected = computed(() => {
    return props.media.data?.length > 0 && selectedIds.value.length === props.media.data.length;
});

const someSelected = computed(() => {
    return selectedIds.value.length > 0 && selectedIds.value.length < props.media.data?.length;
});

const toggleAll = () => {
    if (allSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = props.media.data.map(m => m.id);
    }
};

const toggleMedia = (mediaId) => {
    const index = selectedIds.value.indexOf(mediaId);
    if (index > -1) {
        selectedIds.value.splice(index, 1);
    } else {
        selectedIds.value.push(mediaId);
    }
};

const deleteSelected = () => {
    isDeleting.value = true;
    router.post('/media/bulk-delete', {
        ids: selectedIds.value,
    }, {
        onSuccess: () => {
            selectedIds.value = [];
        },
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
        },
    });
};

const navigateToFolder = (folderId) => {
    router.get('/media', { folder_id: folderId }, { preserveState: true });
};

const navigateToRoot = () => {
    router.get('/media', {}, { preserveState: true });
};

const openEditAltModal = (media) => {
    editingMedia.value = media;
    showEditAltModal.value = true;
};

const openMoveModal = () => {
    showMoveModal.value = true;
};

const deleteMessage = computed(() => {
    const count = selectedIds.value.length;
    return `Are you sure you want to delete ${count} image${count !== 1 ? 's' : ''}? This action cannot be undone.`;
});
</script>

<template>
    <Head title="Media Library" />

    <div class="min-h-screen bg-gray-50">
        <AppNavigation current-page="media" />

        <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Media Library</h1>
                    <p class="text-gray-600">Manage your images and files</p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        v-if="canUploadMedia && selectedIds.length > 0"
                        @click="openMoveModal"
                        class="px-4 py-2 bg-gray-600 text-white font-medium rounded-lg hover:bg-gray-700 transition"
                    >
                        Move ({{ selectedIds.length }})
                    </button>
                    <button
                        v-if="canDeleteMedia && selectedIds.length > 0"
                        @click="showDeleteModal = true"
                        class="px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition"
                    >
                        Delete ({{ selectedIds.length }})
                    </button>
                    <button
                        v-if="canUploadMedia"
                        @click="showCreateFolderModal = true"
                        class="px-4 py-2 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition"
                    >
                        New Folder
                    </button>
                    <button
                        v-if="canUploadMedia"
                        @click="showUploader = true"
                        class="px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition"
                    >
                        Upload Images
                    </button>
                </div>
            </div>

            <div class="flex gap-6">
                <!-- Sidebar: Folder Tree -->
                <div class="w-64 shrink-0">
                    <FolderTree
                        :folders="folders"
                        :current-folder-id="currentFolder?.id"
                        @navigate="navigateToFolder"
                        @navigate-root="navigateToRoot"
                    />
                </div>

                <!-- Main Content -->
                <div class="grow">
                    <!-- Breadcrumb -->
                    <div v-if="currentFolder" class="mb-4 flex items-center gap-2 text-sm text-gray-600">
                        <button @click="navigateToRoot" class="hover:text-primary-600">
                            All Media
                        </button>
                        <span>/</span>
                        <span class="text-gray-900 font-medium">{{ currentFolder.name }}</span>
                    </div>

                    <!-- Selection header -->
                    <div v-if="media.data?.length > 0" class="mb-4 flex items-center gap-4">
                        <label v-if="canDeleteMedia" class="flex items-center gap-2 text-sm text-gray-600">
                            <input
                                type="checkbox"
                                :checked="allSelected"
                                :indeterminate="someSelected"
                                @change="toggleAll"
                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            />
                            Select all
                        </label>
                        <span class="text-sm text-gray-500">
                            {{ media.data.length }} image{{ media.data.length !== 1 ? 's' : '' }}
                        </span>
                    </div>

                    <!-- Media Grid -->
                    <MediaGrid
                        v-if="media.data?.length > 0"
                        :media="media.data"
                        :selected-ids="selectedIds"
                        @toggle="toggleMedia"
                        @edit-alt="openEditAltModal"
                    />

                    <!-- Empty State -->
                    <div v-else class="bg-white rounded-lg shadow p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">No images yet</h3>
                        <p class="mt-2 text-gray-500">
                            {{ currentFolder ? 'This folder is empty.' : (canUploadMedia ? 'Upload your first image to get started.' : 'No images have been uploaded yet.') }}
                        </p>
                        <button
                            v-if="canUploadMedia"
                            @click="showUploader = true"
                            class="mt-4 inline-flex items-center px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition"
                        >
                            Upload Images
                        </button>
                    </div>

                    <!-- Pagination -->
                    <div v-if="media.links && media.links.length > 3" class="mt-6 flex justify-center gap-2">
                        <template v-for="(link, index) in media.links" :key="index">
                            <button
                                v-if="link.url"
                                @click="router.get(link.url)"
                                :class="[
                                    'px-3 py-2 text-sm rounded',
                                    link.active
                                        ? 'bg-primary-600 text-white'
                                        : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                                ]"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="px-3 py-2 text-sm text-gray-400"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </main>

        <!-- Modals -->
        <MediaUploader
            :show="showUploader"
            :folder-id="currentFolder?.id"
            @close="showUploader = false"
        />

        <CreateFolderModal
            :show="showCreateFolderModal"
            :parent-id="currentFolder?.id"
            @close="showCreateFolderModal = false"
        />

        <EditAltTextModal
            :show="showEditAltModal"
            :media="editingMedia"
            @close="showEditAltModal = false; editingMedia = null"
        />

        <MoveFolderModal
            :show="showMoveModal"
            :folders="folders"
            :selected-ids="selectedIds"
            @close="showMoveModal = false; selectedIds = []"
        />

        <ConfirmModal
            :show="showDeleteModal"
            title="Delete Images"
            :message="deleteMessage"
            confirm-text="Delete"
            :processing="isDeleting"
            @confirm="deleteSelected"
            @cancel="showDeleteModal = false"
        />
    </div>
</template>
