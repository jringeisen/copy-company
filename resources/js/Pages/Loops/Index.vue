<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

defineProps({
    loops: Array,
});

const showDeleteModal = ref(false);
const deletingLoop = ref(null);
const isDeleting = ref(false);

const toggleLoop = (loop) => {
    router.post(`/loops/${loop.id}/toggle`);
};

const confirmDeleteLoop = (loop) => {
    deletingLoop.value = loop;
    showDeleteModal.value = true;
};

const deleteLoop = () => {
    if (!deletingLoop.value) return;

    isDeleting.value = true;
    router.delete(`/loops/${deletingLoop.value.id}`, {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
            deletingLoop.value = null;
        },
    });
};
</script>

<template>
    <Head title="Loops" />

    <AppLayout current-page="loops">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Loops</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Create recurring content schedules that post automatically
                    </p>
                </div>
                <Link
                    href="/loops/create"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#0b1215] text-white text-sm font-medium rounded-xl hover:bg-[#0b1215]/90 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create Loop
                </Link>
            </div>

            <!-- Empty State -->
            <div v-if="loops.length === 0" class="text-center py-16 bg-white rounded-2xl border border-gray-200">
                <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No loops yet</h3>
                <p class="mt-2 text-sm text-gray-500 max-w-md mx-auto">
                    Create your first loop to automatically post content on a recurring schedule.
                </p>
                <Link
                    href="/loops/create"
                    class="mt-6 inline-flex items-center gap-2 px-4 py-2.5 bg-[#0b1215] text-white text-sm font-medium rounded-xl hover:bg-[#0b1215]/90 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create your first loop
                </Link>
            </div>

            <!-- Loops Grid -->
            <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="loop in loops"
                    :key="loop.id"
                    class="bg-white rounded-2xl border border-gray-200 p-6 hover:shadow-md transition-shadow"
                >
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1 min-w-0">
                            <Link :href="`/loops/${loop.id}`" class="text-lg font-semibold text-gray-900 hover:text-[#a1854f] transition-colors truncate block">
                                {{ loop.name }}
                            </Link>
                            <p v-if="loop.description" class="mt-1 text-sm text-gray-500 line-clamp-2">
                                {{ loop.description }}
                            </p>
                        </div>
                        <button
                            @click="toggleLoop(loop)"
                            :class="[
                                'ml-4 shrink-0 relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                                loop.is_active ? 'bg-green-500' : 'bg-gray-200'
                            ]"
                            :aria-label="loop.is_active ? 'Deactivate loop' : 'Activate loop'"
                            role="switch"
                            :aria-checked="loop.is_active"
                        >
                            <span
                                :class="[
                                    'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                                    loop.is_active ? 'translate-x-6' : 'translate-x-1'
                                ]"
                            />
                        </button>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-4 mb-4 text-center">
                        <div>
                            <div class="text-xl font-bold text-gray-900">{{ loop.items_count || 0 }}</div>
                            <div class="text-xs text-gray-500">Items</div>
                        </div>
                        <div>
                            <div class="text-xl font-bold text-gray-900">{{ loop.total_cycles_completed || 0 }}</div>
                            <div class="text-xs text-gray-500">Cycles</div>
                        </div>
                        <div>
                            <div class="text-xl font-bold text-gray-900">{{ loop.current_position || 0 }}</div>
                            <div class="text-xs text-gray-500">Position</div>
                        </div>
                    </div>

                    <!-- Platforms -->
                    <div class="flex flex-wrap gap-1 mb-4">
                        <span
                            v-for="platform in loop.platforms"
                            :key="platform"
                            class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded-full capitalize"
                        >
                            {{ platform }}
                        </span>
                    </div>

                    <!-- Last Posted -->
                    <p v-if="loop.last_posted_at" class="text-xs text-gray-500 mb-4">
                        Last posted: {{ loop.last_posted_at }}
                    </p>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 pt-4 border-t border-gray-100">
                        <Link
                            :href="`/loops/${loop.id}`"
                            class="flex-1 px-3 py-2 text-sm font-medium text-center text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors"
                        >
                            Manage
                        </Link>
                        <Link
                            :href="`/loops/${loop.id}/edit`"
                            class="px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors"
                            aria-label="Edit loop"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </Link>
                        <button
                            @click="confirmDeleteLoop(loop)"
                            class="px-3 py-2 text-sm font-medium text-red-500 hover:text-red-700 transition-colors"
                            aria-label="Delete loop"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmModal
            :show="showDeleteModal"
            title="Delete Loop"
            :message="`Are you sure you want to delete '${deletingLoop?.name}'? All items and schedules will be removed. This action cannot be undone.`"
            confirm-text="Delete"
            :processing="isDeleting"
            @confirm="deleteLoop"
            @cancel="showDeleteModal = false; deletingLoop = null"
        />
    </AppLayout>
</template>
