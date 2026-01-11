<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
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

    <AppLayout current-page="media">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-[#0b1215]">Media Library</h1>
                    <p class="text-[#0b1215]/60">Manage your images and files</p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        v-if="canUploadMedia && selectedIds.length > 0"
                        @click="openMoveModal"
                        class="px-4 py-2.5 bg-[#0b1215]/10 text-[#0b1215] font-medium rounded-full hover:bg-[#0b1215]/20 transition text-sm"
                    >
                        Move ({{ selectedIds.length }})
                    </button>
                    <button
                        v-if="canDeleteMedia && selectedIds.length > 0"
                        @click="showDeleteModal = true"
                        class="px-4 py-2.5 bg-red-600 text-white font-medium rounded-full hover:bg-red-700 transition text-sm"
                    >
                        Delete ({{ selectedIds.length }})
                    </button>
                    <button
                        v-if="canUploadMedia"
                        @click="showCreateFolderModal = true"
                        class="px-4 py-2.5 border border-[#0b1215]/20 text-[#0b1215] font-medium rounded-full hover:bg-[#0b1215]/5 transition text-sm"
                    >
                        New Folder
                    </button>
                    <button
                        v-if="canUploadMedia"
                        @click="showUploader = true"
                        class="px-5 py-2.5 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition text-sm"
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
                    <div v-if="currentFolder" class="mb-4 flex items-center gap-2 text-sm text-[#0b1215]/60">
                        <button @click="navigateToRoot" class="hover:text-[#a1854f] transition-colors">
                            All Media
                        </button>
                        <span>/</span>
                        <span class="text-[#0b1215] font-medium">{{ currentFolder.name }}</span>
                    </div>

                    <!-- Selection header -->
                    <div v-if="media.data?.length > 0" class="mb-4 flex items-center gap-4">
                        <label v-if="canDeleteMedia" class="flex items-center gap-2 text-sm text-[#0b1215]/60">
                            <input
                                type="checkbox"
                                :checked="allSelected"
                                :indeterminate="someSelected"
                                @change="toggleAll"
                                class="rounded border-[#0b1215]/20 text-[#0b1215] focus:ring-[#0b1215]/20"
                            />
                            Select all
                        </label>
                        <span class="text-sm text-[#0b1215]/50">
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
                    <div v-else class="bg-white rounded-2xl border border-[#0b1215]/10 p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-[#0b1215]/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-[#0b1215]">No images yet</h3>
                        <p class="mt-2 text-[#0b1215]/50">
                            {{ currentFolder ? 'This folder is empty.' : (canUploadMedia ? 'Upload your first image to get started.' : 'No images have been uploaded yet.') }}
                        </p>
                        <button
                            v-if="canUploadMedia"
                            @click="showUploader = true"
                            class="mt-6 inline-flex items-center px-5 py-2.5 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition text-sm"
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
                                    'px-3 py-2 text-sm rounded-lg transition-colors',
                                    link.active
                                        ? 'bg-[#0b1215] text-white'
                                        : 'bg-white text-[#0b1215] hover:bg-[#0b1215]/5 border border-[#0b1215]/20'
                                ]"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="px-3 py-2 text-sm text-[#0b1215]/30"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>

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
    </AppLayout>
</template>
