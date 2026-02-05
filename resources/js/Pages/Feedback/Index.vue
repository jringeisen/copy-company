<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    feedback: Array,
    pagination: Object,
    filters: Object,
    statuses: Object,
});

const selectedStatus = ref(props.filters.status || '');

const applyFilter = () => {
    router.get('/feedback', {
        status: selectedStatus.value || undefined,
    }, {
        preserveState: true,
    });
};

const clearFilter = () => {
    selectedStatus.value = '';
    router.get('/feedback');
};

const getStatusBadgeClass = (color) => {
    const classes = {
        blue: 'bg-blue-100 text-blue-700',
        yellow: 'bg-yellow-100 text-yellow-700',
        green: 'bg-green-100 text-green-700',
        gray: 'bg-gray-100 text-gray-700',
    };
    return classes[color] || 'bg-gray-100 text-gray-700';
};

const getPriorityBadgeClass = (color) => {
    const classes = {
        gray: 'bg-gray-100 text-gray-700',
        blue: 'bg-blue-100 text-blue-700',
        yellow: 'bg-yellow-100 text-yellow-700',
        red: 'bg-red-100 text-red-700',
    };
    return classes[color] || 'bg-gray-100 text-gray-700';
};

const typeIcons = {
    bug: '🐛',
    lightbulb: '💡',
    'arrow-up': '⬆️',
    'paint-brush': '🎨',
    bolt: '⚡',
    chat: '💬',
};
</script>

<template>
    <Head title="My Feedback" />

    <AppLayout current-page="feedback">
        <div class="max-w-5xl mx-auto">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-[#0b1215]">My Feedback</h1>
                    <p class="text-[#0b1215]/60 mt-1">View your feedback submissions and their status</p>
                </div>

                <!-- Status Filter -->
                <div class="flex items-center gap-2">
                    <select
                        v-model="selectedStatus"
                        @change="applyFilter"
                        class="px-4 py-2 border border-[#0b1215]/20 rounded-xl focus:ring-2 focus:ring-[#a1854f]/30 focus:border-[#a1854f] text-sm"
                    >
                        <option value="">All Statuses</option>
                        <option v-for="(label, value) in statuses" :key="value" :value="value">{{ label }}</option>
                    </select>
                    <button
                        v-if="selectedStatus"
                        @click="clearFilter"
                        class="text-sm text-[#0b1215]/60 hover:text-[#0b1215] underline"
                    >
                        Clear
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="feedback.length === 0" class="bg-white rounded-2xl border border-[#0b1215]/10 p-12 text-center">
                <svg class="mx-auto h-16 w-16 text-[#0b1215]/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-[#0b1215]">No feedback yet</h3>
                <p class="mt-2 text-[#0b1215]/60">Click the feedback button in the bottom-right to submit your first feedback!</p>
            </div>

            <!-- Feedback List -->
            <div v-else class="space-y-4">
                <div
                    v-for="item in feedback"
                    :key="item.id"
                    class="bg-white rounded-2xl border border-[#0b1215]/10 p-6 hover:shadow-md transition-shadow"
                >
                    <!-- Header -->
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">{{ typeIcons[item.type_icon] || '💬' }}</span>
                            <div>
                                <h3 class="text-lg font-semibold text-[#0b1215]">{{ item.type_label }}</h3>
                                <p class="text-sm text-[#0b1215]/50">{{ item.created_at_relative }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span
                                :class="getPriorityBadgeClass(item.priority_color)"
                                class="px-3 py-1 rounded-full text-xs font-medium"
                            >
                                {{ item.priority_label }}
                            </span>
                            <span
                                :class="getStatusBadgeClass(item.status_color)"
                                class="px-3 py-1 rounded-full text-xs font-medium"
                            >
                                {{ item.status_label }}
                            </span>
                        </div>
                    </div>

                    <!-- Description -->
                    <p class="text-[#0b1215]/80 mb-3">{{ item.description }}</p>

                    <!-- Screenshot -->
                    <div v-if="item.screenshot_url" class="mb-3">
                        <a :href="item.screenshot_url" target="_blank" class="inline-block">
                            <img
                                :src="item.screenshot_url"
                                alt="Screenshot"
                                class="max-w-sm rounded-lg border border-[#0b1215]/10 hover:opacity-90 transition-opacity"
                            />
                        </a>
                    </div>

                    <!-- Brand & Page URL -->
                    <div class="text-xs text-[#0b1215]/40 space-y-1">
                        <p v-if="item.brand_name"><strong>Brand:</strong> {{ item.brand_name }}</p>
                        <p><strong>Page:</strong> {{ item.page_url }}</p>
                    </div>

                    <!-- Admin Response -->
                    <div v-if="item.admin_notes" class="mt-4 p-4 bg-[#a1854f]/5 border border-[#a1854f]/20 rounded-xl">
                        <p class="text-xs font-medium text-[#a1854f] mb-2">Response from our team:</p>
                        <p class="text-sm text-[#0b1215]">{{ item.admin_notes }}</p>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="pagination.last_page > 1" class="flex items-center justify-between mt-6">
                    <p class="text-sm text-[#0b1215]/60">
                        Showing {{ ((pagination.current_page - 1) * pagination.per_page) + 1 }} to
                        {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} of
                        {{ pagination.total }} items
                    </p>
                    <div class="flex gap-2">
                        <Link
                            v-if="pagination.current_page > 1"
                            :href="`/feedback?page=${pagination.current_page - 1}`"
                            class="px-4 py-2 border border-[#0b1215]/20 rounded-xl hover:bg-[#0b1215]/5 transition-colors text-sm font-medium"
                        >
                            Previous
                        </Link>
                        <Link
                            v-if="pagination.current_page < pagination.last_page"
                            :href="`/feedback?page=${pagination.current_page + 1}`"
                            class="px-4 py-2 bg-[#0b1215] text-white rounded-xl hover:bg-[#0b1215]/90 transition-colors text-sm font-medium"
                        >
                            Next
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
