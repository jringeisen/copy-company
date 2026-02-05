<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    feedback: Array,
    pagination: Object,
    stats: Object,
    filters: Object,
    types: Object,
    priorities: Object,
    statuses: Object,
});

const searchQuery = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');
const selectedType = ref(props.filters.type || '');
const selectedPriority = ref(props.filters.priority || '');

let searchTimeout = null;

const applyFilters = () => {
    const params = {};
    if (searchQuery.value) params.search = searchQuery.value;
    if (selectedStatus.value) params.status = selectedStatus.value;
    if (selectedType.value) params.type = selectedType.value;
    if (selectedPriority.value) params.priority = selectedPriority.value;

    router.get('/admin/feedback', params, { preserveState: true });
};

const onSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
};

const clearFilters = () => {
    searchQuery.value = '';
    selectedStatus.value = '';
    selectedType.value = '';
    selectedPriority.value = '';
    router.get('/admin/feedback');
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
    <Head title="Feedback Management - Admin" />

    <AdminLayout current-page="feedback">
        <div class="max-w-7xl mx-auto">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-[#0b1215]">Feedback Management</h1>
                <p class="text-[#0b1215]/60 mt-1">Monitor and respond to user feedback</p>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white rounded-xl border border-[#0b1215]/10 p-4">
                    <p class="text-sm text-[#0b1215]/60">Total</p>
                    <p class="text-2xl font-bold text-[#0b1215]">{{ stats.total }}</p>
                </div>
                <div class="rounded-xl border border-blue-200 p-4 bg-blue-50">
                    <p class="text-sm text-blue-600">Open</p>
                    <p class="text-2xl font-bold text-blue-700">{{ stats.open }}</p>
                </div>
                <div class="rounded-xl border border-yellow-200 p-4 bg-yellow-50">
                    <p class="text-sm text-yellow-600">In Progress</p>
                    <p class="text-2xl font-bold text-yellow-700">{{ stats.in_progress }}</p>
                </div>
                <div class="rounded-xl border border-green-200 p-4 bg-green-50">
                    <p class="text-sm text-green-600">Resolved</p>
                    <p class="text-2xl font-bold text-green-700">{{ stats.resolved }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-2xl border border-[#0b1215]/10 p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-[#0b1215] mb-1">Search</label>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search feedback..."
                            class="w-full px-4 py-2 border border-[#0b1215]/20 rounded-xl focus:ring-2 focus:ring-[#a1854f]/30 focus:border-[#a1854f] text-sm"
                            @input="onSearchInput"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[#0b1215] mb-1">Status</label>
                        <select
                            v-model="selectedStatus"
                            @change="applyFilters"
                            class="w-full px-4 py-2 border border-[#0b1215]/20 rounded-xl focus:ring-2 focus:ring-[#a1854f]/30 focus:border-[#a1854f] text-sm"
                        >
                            <option value="">All Statuses</option>
                            <option v-for="(label, value) in statuses" :key="value" :value="value">{{ label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[#0b1215] mb-1">Type</label>
                        <select
                            v-model="selectedType"
                            @change="applyFilters"
                            class="w-full px-4 py-2 border border-[#0b1215]/20 rounded-xl focus:ring-2 focus:ring-[#a1854f]/30 focus:border-[#a1854f] text-sm"
                        >
                            <option value="">All Types</option>
                            <option v-for="(label, value) in types" :key="value" :value="value">{{ label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[#0b1215] mb-1">Priority</label>
                        <select
                            v-model="selectedPriority"
                            @change="applyFilters"
                            class="w-full px-4 py-2 border border-[#0b1215]/20 rounded-xl focus:ring-2 focus:ring-[#a1854f]/30 focus:border-[#a1854f] text-sm"
                        >
                            <option value="">All Priorities</option>
                            <option v-for="(label, value) in priorities" :key="value" :value="value">{{ label }}</option>
                        </select>
                    </div>
                </div>
                <div v-if="filters.search || filters.status || filters.type || filters.priority" class="mt-4">
                    <button
                        @click="clearFilters"
                        class="text-sm text-[#0b1215]/60 hover:text-[#0b1215] underline"
                    >
                        Clear all filters
                    </button>
                </div>
            </div>

            <!-- Feedback List -->
            <div v-if="feedback.length > 0" class="space-y-4">
                <Link
                    v-for="item in feedback"
                    :key="item.id"
                    :href="`/admin/feedback/${item.id}`"
                    class="block bg-white rounded-2xl border border-[#0b1215]/10 p-6 hover:shadow-md transition-all"
                >
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3 flex-1">
                            <span class="text-2xl">{{ typeIcons[item.type_icon] || '💬' }}</span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1 flex-wrap">
                                    <h3 class="text-lg font-semibold text-[#0b1215]">{{ item.type_label }}</h3>
                                    <span
                                        :class="getPriorityBadgeClass(item.priority_color)"
                                        class="px-2 py-0.5 rounded-full text-xs font-medium"
                                    >
                                        {{ item.priority_label }}
                                    </span>
                                    <span
                                        :class="getStatusBadgeClass(item.status_color)"
                                        class="px-2 py-0.5 rounded-full text-xs font-medium"
                                    >
                                        {{ item.status_label }}
                                    </span>
                                </div>
                                <p class="text-sm text-[#0b1215]/60">
                                    {{ item.user_name }} <span v-if="item.brand_name">- {{ item.brand_name }}</span> - {{ item.created_at_relative }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <p class="text-[#0b1215]/80 mb-2 line-clamp-2">{{ item.description }}</p>

                    <div class="flex items-center gap-4 text-sm text-[#0b1215]/50">
                        <div v-if="item.screenshot_url" class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>Screenshot</span>
                        </div>
                    </div>
                </Link>

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
                            :href="`/admin/feedback?page=${pagination.current_page - 1}`"
                            class="px-4 py-2 border border-[#0b1215]/20 rounded-xl hover:bg-[#0b1215]/5 text-sm font-medium"
                        >
                            Previous
                        </Link>
                        <Link
                            v-if="pagination.current_page < pagination.last_page"
                            :href="`/admin/feedback?page=${pagination.current_page + 1}`"
                            class="px-4 py-2 bg-[#0b1215] text-white rounded-xl hover:bg-[#0b1215]/90 text-sm font-medium"
                        >
                            Next
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white rounded-2xl border border-[#0b1215]/10 p-12 text-center">
                <svg class="mx-auto h-16 w-16 text-[#0b1215]/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-[#0b1215]">No feedback found</h3>
                <p class="mt-2 text-[#0b1215]/60">Try adjusting your filters or check back later.</p>
            </div>
        </div>
    </AdminLayout>
</template>
