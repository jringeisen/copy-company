<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppNavigation from '@/Components/AppNavigation.vue';

const props = defineProps({
    posts: Array,
    brand: Object,
});

const selectedIds = ref([]);
const isDeleting = ref(false);

const allSelected = computed(() => {
    return props.posts.length > 0 && selectedIds.value.length === props.posts.length;
});

const someSelected = computed(() => {
    return selectedIds.value.length > 0 && selectedIds.value.length < props.posts.length;
});

const toggleAll = () => {
    if (allSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = props.posts.map(post => post.id);
    }
};

const togglePost = (postId) => {
    const index = selectedIds.value.indexOf(postId);
    if (index > -1) {
        selectedIds.value.splice(index, 1);
    } else {
        selectedIds.value.push(postId);
    }
};

const deleteSelected = () => {
    if (!confirm(`Are you sure you want to delete ${selectedIds.value.length} post(s)? This action cannot be undone.`)) {
        return;
    }

    isDeleting.value = true;
    router.delete('/posts/bulk-delete', {
        data: { ids: selectedIds.value },
        onSuccess: () => {
            selectedIds.value = [];
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};

const getStatusColor = (status) => {
    const colors = {
        draft: 'bg-gray-100 text-gray-800',
        scheduled: 'bg-yellow-100 text-yellow-800',
        published: 'bg-green-100 text-green-800',
        archived: 'bg-red-100 text-red-800',
    };
    return colors[status] || colors.draft;
};
</script>

<template>
    <Head title="Posts" />

    <div class="min-h-screen bg-gray-50">
        <AppNavigation current-page="posts" />

        <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Posts</h1>
                    <p class="text-gray-600">Manage your blog posts and newsletters</p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        v-if="selectedIds.length > 0"
                        @click="deleteSelected"
                        :disabled="isDeleting"
                        class="px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition disabled:opacity-50"
                    >
                        {{ isDeleting ? 'Deleting...' : `Delete (${selectedIds.length})` }}
                    </button>
                    <Link
                        href="/posts/create"
                        class="px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition"
                    >
                        New Post
                    </Link>
                </div>
            </div>

            <!-- Posts List -->
            <div v-if="posts.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left">
                                <input
                                    type="checkbox"
                                    :checked="allSelected"
                                    :indeterminate="someSelected"
                                    @change="toggleAll"
                                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                />
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Title
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Views
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Updated
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="post in posts" :key="post.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <input
                                    type="checkbox"
                                    :checked="selectedIds.includes(post.id)"
                                    @change="togglePost(post.id)"
                                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                />
                            </td>
                            <td class="px-6 py-4">
                                <Link :href="`/posts/${post.id}/edit`" class="font-medium text-gray-900 hover:text-primary-600">
                                    {{ post.title }}
                                </Link>
                                <p v-if="post.excerpt" class="text-sm text-gray-500 truncate max-w-md">
                                    {{ post.excerpt }}
                                </p>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="getStatusColor(post.status)" class="px-2 py-1 text-xs font-medium rounded-full capitalize">
                                    {{ post.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ post.view_count }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ post.updated_at }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <Link :href="`/posts/${post.id}/edit`" class="text-primary-600 hover:text-primary-900">
                                    Edit
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white rounded-lg shadow p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No posts yet</h3>
                <p class="mt-2 text-gray-500">Get started by creating your first post.</p>
                <Link
                    href="/posts/create"
                    class="mt-4 inline-flex items-center px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition"
                >
                    Create Your First Post
                </Link>
            </div>
        </main>
    </div>
</template>
