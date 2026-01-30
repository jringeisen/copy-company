<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, watch } from 'vue';

const props = defineProps({
    users: Array,
    pagination: Object,
    stats: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

let searchTimeout = null;
watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/users', { search: value || undefined }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 300);
});

const getStatusBadgeClass = (status) => {
    return {
        Active: 'bg-green-100 text-green-700',
        Trial: 'bg-yellow-100 text-yellow-700',
        Expired: 'bg-red-100 text-red-700',
        'No Account': 'bg-gray-100 text-gray-700',
    }[status] || 'bg-gray-100 text-gray-700';
};

const impersonate = (userId) => {
    router.post(`/admin/impersonate/${userId}`);
};
</script>

<template>
    <Head title="Users - Admin" />

    <AdminLayout current-page="users">
        <div class="max-w-6xl mx-auto">
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-[#0b1215]">Users</h1>
                    <p class="text-[#0b1215]/60 mt-1">{{ stats.total }} total users on the platform</p>
                </div>
            </div>

            <!-- Search -->
            <div class="mb-6">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search by name or email..."
                    class="w-full max-w-md px-4 py-2.5 bg-white border border-[#0b1215]/10 rounded-xl text-sm text-[#0b1215] placeholder-[#0b1215]/40 focus:outline-none focus:ring-2 focus:ring-[#a1854f] focus:border-transparent"
                />
            </div>

            <!-- Users List -->
            <div v-if="users.length > 0" class="bg-white rounded-2xl border border-[#0b1215]/10">
                <div class="px-6 py-4 border-b border-[#0b1215]/10">
                    <h2 class="text-lg font-semibold text-[#0b1215]">All Users</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-[#0b1215]/10">
                                <th class="px-6 py-3 text-left text-xs font-medium text-[#0b1215]/60 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[#0b1215]/60 uppercase tracking-wider">Account</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[#0b1215]/60 uppercase tracking-wider">Plan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[#0b1215]/60 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[#0b1215]/60 uppercase tracking-wider">Joined</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#0b1215]/10">
                            <tr v-for="user in users" :key="user.id" class="hover:bg-[#0b1215]/5">
                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-[#0b1215]">{{ user.name }}</p>
                                    <p class="text-xs text-[#0b1215]/50">{{ user.email }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-[#0b1215]">{{ user.account_name || '-' }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-[#0b1215]">{{ user.plan_label }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                        :class="getStatusBadgeClass(user.status)"
                                    >
                                        {{ user.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-[#0b1215]">{{ user.created_at }}</p>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button
                                        @click="impersonate(user.id)"
                                        class="text-sm font-medium text-[#a1854f] hover:text-[#a1854f]/80"
                                    >
                                        Impersonate
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="pagination.last_page > 1" class="px-6 py-4 border-t border-[#0b1215]/10 flex items-center justify-between">
                    <p class="text-sm text-[#0b1215]/60">
                        Page {{ pagination.current_page }} of {{ pagination.last_page }} ({{ pagination.total }} total)
                    </p>
                    <div class="flex gap-2">
                        <Link
                            v-if="pagination.current_page > 1"
                            :href="`/admin/users?page=${pagination.current_page - 1}${search ? '&search=' + search : ''}`"
                            class="px-3 py-1 text-sm border border-[#0b1215]/20 rounded-lg hover:bg-[#0b1215]/5"
                        >
                            Previous
                        </Link>
                        <Link
                            v-if="pagination.current_page < pagination.last_page"
                            :href="`/admin/users?page=${pagination.current_page + 1}${search ? '&search=' + search : ''}`"
                            class="px-3 py-1 text-sm border border-[#0b1215]/20 rounded-lg hover:bg-[#0b1215]/5"
                        >
                            Next
                        </Link>
                    </div>
                </div>
            </div>

            <!-- No Users -->
            <div v-else class="bg-white rounded-2xl border border-[#0b1215]/10 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-[#0b1215]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-[#0b1215]">No users found</h3>
                <p class="mt-2 text-sm text-[#0b1215]/60">
                    {{ search ? 'No users match your search criteria.' : 'No users have registered yet.' }}
                </p>
            </div>
        </div>
    </AdminLayout>
</template>
