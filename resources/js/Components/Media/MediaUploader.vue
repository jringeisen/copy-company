<script setup>
import { ref, watch, onBeforeUnmount } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    folderId: [Number, String],
});

const emit = defineEmits(['close', 'error']);

const dragOver = ref(false);
const fileInput = ref(null);

// Track Object URLs to prevent memory leaks
const previewUrls = ref(new Map());

const form = useForm({
    images: [],
    folder_id: null,
});

watch(() => props.folderId, (val) => {
    form.folder_id = val;
}, { immediate: true });

/**
 * Revoke all existing Object URLs to free memory.
 */
const revokeAllUrls = () => {
    previewUrls.value.forEach((url) => {
        window.URL.revokeObjectURL(url);
    });
    previewUrls.value.clear();
};

/**
 * Get or create a preview URL for a file.
 */
const getPreviewUrl = (file) => {
    if (previewUrls.value.has(file)) {
        return previewUrls.value.get(file);
    }
    const url = window.URL.createObjectURL(file);
    previewUrls.value.set(file, url);
    return url;
};

const handleDrop = (e) => {
    dragOver.value = false;
    const files = Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('image/'));
    if (files.length > 0) {
        revokeAllUrls();
        form.images = files;
    }
};

const handleFileSelect = (e) => {
    const files = Array.from(e.target.files);
    if (files.length > 0) {
        revokeAllUrls();
        form.images = files;
    }
};

const removeFile = (index) => {
    const file = form.images[index];
    if (previewUrls.value.has(file)) {
        window.URL.revokeObjectURL(previewUrls.value.get(file));
        previewUrls.value.delete(file);
    }
    form.images = form.images.filter((_, i) => i !== index);
};

const upload = () => {
    form.post('/media', {
        forceFormData: true,
        onSuccess: () => {
            revokeAllUrls();
            form.reset();
            emit('close');
        },
        onError: (errors) => {
            emit('error', errors);
        },
    });
};

const close = () => {
    revokeAllUrls();
    form.reset();
    emit('close');
};

// Clean up on component unmount
onBeforeUnmount(() => {
    revokeAllUrls();
});

const formatFileSize = (bytes) => {
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
    if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
    return bytes + ' bytes';
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
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="close"></div>

                    <!-- Modal -->
                    <div class="relative bg-white rounded-xl shadow-xl max-w-2xl w-full">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Upload Images</h3>

                            <!-- Drop Zone -->
                            <div
                                @dragover.prevent="dragOver = true"
                                @dragleave="dragOver = false"
                                @drop.prevent="handleDrop"
                                @click="fileInput.click()"
                                :class="[
                                    'border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition',
                                    dragOver ? 'border-primary-500 bg-primary-50' : 'border-gray-300 hover:border-gray-400'
                                ]"
                            >
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-600">
                                    Drag and drop images here, or click to browse
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    JPG, PNG, GIF, WebP up to 10MB each
                                </p>
                                <input
                                    ref="fileInput"
                                    type="file"
                                    accept="image/jpeg,image/png,image/gif,image/webp"
                                    multiple
                                    class="hidden"
                                    @change="handleFileSelect"
                                />
                            </div>

                            <!-- Selected Files -->
                            <div v-if="form.images.length > 0" class="mt-4">
                                <h4 class="text-sm font-medium text-gray-700 mb-2">
                                    Selected ({{ form.images.length }})
                                </h4>
                                <div class="space-y-2 max-h-48 overflow-y-auto">
                                    <div
                                        v-for="(file, index) in form.images"
                                        :key="index"
                                        class="flex items-center gap-3 p-2 bg-gray-50 rounded-lg"
                                    >
                                        <img
                                            :src="getPreviewUrl(file)"
                                            class="w-10 h-10 object-cover rounded"
                                        />
                                        <div class="grow min-w-0">
                                            <p class="text-sm text-gray-900 truncate">{{ file.name }}</p>
                                            <p class="text-xs text-gray-500">{{ formatFileSize(file.size) }}</p>
                                        </div>
                                        <button
                                            @click="removeFile(index)"
                                            class="text-gray-400 hover:text-red-500 transition"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Error -->
                            <p v-if="form.errors.images" class="mt-2 text-sm text-red-600">
                                {{ form.errors.images }}
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-3 p-4 border-t border-gray-200 bg-gray-50 rounded-b-xl">
                            <button
                                @click="close"
                                :disabled="form.processing"
                                class="flex-1 px-4 py-2 text-gray-700 font-medium bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition disabled:opacity-50"
                            >
                                Cancel
                            </button>
                            <button
                                @click="upload"
                                :disabled="form.processing || form.images.length === 0"
                                class="flex-1 px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition disabled:opacity-50"
                            >
                                <span v-if="form.processing" class="flex items-center justify-center gap-2">
                                    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                    </svg>
                                    Uploading...
                                </span>
                                <span v-else>Upload {{ form.images.length }} Image{{ form.images.length !== 1 ? 's' : '' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
