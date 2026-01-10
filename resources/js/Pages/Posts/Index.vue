<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import FeatureEducationBanner from '@/Components/FeatureEducationBanner.vue';
import { usePermissions } from '@/Composables/usePermissions';

const props = defineProps({
    posts: Array,
    brand: Object,
});

const { canCreatePosts, canDeletePosts, canUpdatePosts } = usePermissions();

const selectedIds = ref([]);
const isDeleting = ref(false);
const showDeleteModal = ref(false);

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
    isDeleting.value = true;
    router.delete('/posts/bulk-delete', {
        data: { ids: selectedIds.value },
        onSuccess: () => {
            selectedIds.value = [];
        },
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
        },
    });
};

const deleteMessage = computed(() => {
    const count = selectedIds.value.length;
    return `Are you sure you want to delete ${count} post${count !== 1 ? 's' : ''}? This action cannot be undone.`;
});

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

    <AppLayout current-page="posts">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Posts</h1>
                    <p class="text-gray-600">Manage your blog posts and newsletters</p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        v-if="canDeletePosts && selectedIds.length > 0"
                        @click="showDeleteModal = true"
                        class="px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition"
                    >
                        Delete ({{ selectedIds.length }})
                    </button>
                    <Link
                        v-if="canCreatePosts"
                        href="/posts/create"
                        class="px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition"
                    >
                        New Post
                    </Link>
                </div>
            </div>

            <!-- Educational Banner (shown when no posts) -->
            <FeatureEducationBanner
                v-if="posts.length === 0"
                title="Your Content Hub"
                description="Posts are the heart of your content. Write blog posts, then share them as newsletters to subscribers or generate social media content automatically."
                gradient="from-primary-500 to-blue-600"
                :cta-text="canCreatePosts ? 'Create First Post' : null"
                :cta-href="canCreatePosts ? '/posts/create' : null"
            >
                <template #extra>
                    <div class="mt-3 flex flex-wrap items-center gap-4 text-sm text-white/80">
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                            </svg>
                            Publish to blog
                        </span>
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                            </svg>
                            Send as newsletter
                        </span>
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M15 8a3 3 0 10-2.977-2.63l-4.94 2.47a3 3 0 100 4.319l4.94 2.47a3 3 0 10.895-1.789l-4.94-2.47a3.027 3.027 0 000-.74l4.94-2.47C13.456 7.68 14.19 8 15 8z"/>
                            </svg>
                            Generate social posts
                        </span>
                    </div>
                </template>
            </FeatureEducationBanner>

            <!-- Posts List -->
            <div v-if="posts.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th v-if="canDeletePosts" class="px-6 py-3 text-left">
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
                            <td v-if="canDeletePosts" class="px-6 py-4">
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
                <p class="mt-2 text-gray-500">
                    {{ canCreatePosts ? 'Get started by creating your first post.' : 'No posts have been created yet.' }}
                </p>
                <Link
                    v-if="canCreatePosts"
                    href="/posts/create"
                    class="mt-4 inline-flex items-center px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition"
                >
                    Create Your First Post
                </Link>
            </div>
        </div>

        <ConfirmModal
            :show="showDeleteModal"
            title="Delete Posts"
            :message="deleteMessage"
            confirm-text="Delete"
            :processing="isDeleting"
            @confirm="deleteSelected"
            @cancel="showDeleteModal = false"
        />
    </AppLayout>
</template>
