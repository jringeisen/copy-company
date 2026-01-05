<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    folders: Array,
    selectedIds: Array,
});

const emit = defineEmits(['close']);

const selectedFolderId = ref(null);
const isMoving = ref(false);

watch(() => props.show, (val) => {
    if (!val) {
        selectedFolderId.value = null;
    }
});

const move = () => {
    isMoving.value = true;
    router.post('/media/move', {
        ids: props.selectedIds,
        folder_id: selectedFolderId.value,
    }, {
        onSuccess: () => {
            emit('close');
        },
        onFinish: () => {
            isMoving.value = false;
        },
    });
};

const flattenFolders = (folders, level = 0) => {
    const result = [];
    for (const folder of folders || []) {
        result.push({ ...folder, level });
        if (folder.descendants?.length > 0) {
            result.push(...flattenFolders(folder.descendants, level + 1));
        }
    }
    return result;
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
            <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-screen items-center justify-center p-4">
                    <!-- Backdrop -->
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="emit('close')"></div>

                    <!-- Modal -->
                    <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                Move {{ selectedIds.length }} Image{{ selectedIds.length !== 1 ? 's' : '' }}
                            </h3>

                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                <!-- Root (No Folder) -->
                                <button
                                    @click="selectedFolderId = null"
                                    :class="[
                                        'w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition text-left',
                                        selectedFolderId === null ? 'bg-primary-50 text-primary-700 ring-2 ring-primary-500' : 'text-gray-700 hover:bg-gray-100'
                                    ]"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    Root (No Folder)
                                </button>

                                <!-- Folders -->
                                <button
                                    v-for="folder in flattenFolders(folders)"
                                    :key="folder.id"
                                    @click="selectedFolderId = folder.id"
                                    :style="{ paddingLeft: `${(folder.level + 1) * 12 + 12}px` }"
                                    :class="[
                                        'w-full flex items-center gap-2 py-2 pr-3 rounded-lg text-sm transition text-left',
                                        selectedFolderId === folder.id ? 'bg-primary-50 text-primary-700 ring-2 ring-primary-500' : 'text-gray-700 hover:bg-gray-100'
                                    ]"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                    </svg>
                                    {{ folder.name }}
                                </button>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-3 p-4 border-t border-gray-200 bg-gray-50 rounded-b-xl">
                            <button
                                type="button"
                                @click="emit('close')"
                                :disabled="isMoving"
                                class="flex-1 px-4 py-2 text-gray-700 font-medium bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition disabled:opacity-50"
                            >
                                Cancel
                            </button>
                            <button
                                @click="move"
                                :disabled="isMoving"
                                class="flex-1 px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition disabled:opacity-50"
                            >
                                Move Here
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
