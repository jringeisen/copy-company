<script setup>
import { ref } from 'vue';

const props = defineProps({
    folders: Array,
    currentFolderId: [Number, String],
});

const emit = defineEmits(['navigate', 'navigate-root']);

const expanded = ref({});

const toggleExpand = (folderId) => {
    expanded.value[folderId] = !expanded.value[folderId];
};
</script>

<template>
    <div class="bg-white rounded-lg shadow p-4">
        <h3 class="text-sm font-semibold text-gray-900 mb-3">Folders</h3>

        <!-- All Media (Root) -->
        <button
            @click="emit('navigate-root')"
            :class="[
                'w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition',
                !currentFolderId ? 'bg-primary-50 text-primary-700' : 'text-gray-700 hover:bg-gray-100'
            ]"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            All Media
        </button>

        <!-- Folder List -->
        <div v-if="folders?.length > 0" class="mt-2 space-y-1">
            <template v-for="folder in folders" :key="folder.id">
                <div class="flex items-center">
                    <!-- Expand Toggle -->
                    <button
                        v-if="folder.descendants?.length > 0"
                        @click="toggleExpand(folder.id)"
                        class="p-1 text-gray-400 hover:text-gray-600"
                    >
                        <svg
                            :class="['w-4 h-4 transition', expanded[folder.id] ? 'rotate-90' : '']"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                    <div v-else class="w-6"></div>

                    <!-- Folder Button -->
                    <button
                        @click="emit('navigate', folder.id)"
                        :class="[
                            'grow flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition',
                            currentFolderId === folder.id ? 'bg-primary-50 text-primary-700' : 'text-gray-700 hover:bg-gray-100'
                        ]"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                        {{ folder.name }}
                        <span v-if="folder.media_count" class="ml-auto text-xs text-gray-400">
                            {{ folder.media_count }}
                        </span>
                    </button>
                </div>

                <!-- Nested Folders -->
                <div v-if="expanded[folder.id] && folder.descendants?.length > 0" class="ml-4">
                    <template v-for="child in folder.descendants" :key="child.id">
                        <button
                            @click="emit('navigate', child.id)"
                            :class="[
                                'w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition',
                                currentFolderId === child.id ? 'bg-primary-50 text-primary-700' : 'text-gray-700 hover:bg-gray-100'
                            ]"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                            </svg>
                            {{ child.name }}
                            <span v-if="child.media_count" class="ml-auto text-xs text-gray-400">
                                {{ child.media_count }}
                            </span>
                        </button>
                    </template>
                </div>
            </template>
        </div>

        <!-- Empty State -->
        <p v-else class="mt-2 text-sm text-gray-500 px-3">
            No folders yet
        </p>
    </div>
</template>
