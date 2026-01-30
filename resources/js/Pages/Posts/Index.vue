<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import UpgradeModal from '@/Components/UpgradeModal.vue';
import SkeletonLoader from '@/Components/SkeletonLoader.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { useSubscription } from '@/Composables/useSubscription';
import { usePageLoading } from '@/Composables/usePageLoading';

const { isLoading } = usePageLoading();

const props = defineProps({
    posts: Object,
    brand: Object,
});

const { canCreatePosts, canDeletePosts, canUpdatePosts } = usePermissions();
const { canCreatePost, getRequiredPlan } = useSubscription();

const showUpgradeModal = ref(false);

const selectedIds = ref([]);
const isDeleting = ref(false);
const showDeleteModal = ref(false);

const allSelected = computed(() => {
    return props.posts.data.length > 0 && selectedIds.value.length === props.posts.data.length;
});

const someSelected = computed(() => {
    return selectedIds.value.length > 0 && selectedIds.value.length < props.posts.data.length;
});

const toggleAll = () => {
    if (allSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = props.posts.data.map(post => post.id);
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
        draft: 'bg-[#0b1215]/10 text-[#0b1215]',
        scheduled: 'bg-[#a1854f]/20 text-[#a1854f]',
        published: 'bg-green-100 text-green-700',
        archived: 'bg-red-100 text-red-700',
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
                    <h1 class="text-2xl font-bold text-[#0b1215]">Posts</h1>
                    <p class="text-[#0b1215]/60">Manage your blog posts and newsletters</p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        v-if="canDeletePosts && selectedIds.length > 0"
                        @click="showDeleteModal = true"
                        class="px-4 py-2.5 bg-red-600 text-white font-medium rounded-full hover:bg-red-700 transition text-sm"
                    >
                        Delete ({{ selectedIds.length }})
                    </button>
                    <Link
                        v-if="canCreatePosts && canCreatePost"
                        href="/posts/create"
                        class="px-5 py-2.5 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition text-sm"
                    >
                        New Post
                    </Link>
                    <button
                        v-else-if="canCreatePosts && !canCreatePost"
                        @click="showUpgradeModal = true"
                        class="px-5 py-2.5 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition text-sm"
                    >
                        New Post
                    </button>
                </div>
            </div>

            <!-- Posts List -->
            <div v-if="posts.data.length > 0" class="bg-white rounded-2xl border border-[#0b1215]/10 overflow-hidden">
                <table class="min-w-full divide-y divide-[#0b1215]/10">
                    <thead class="bg-[#0b1215]/[0.03]">
                        <tr>
                            <th v-if="canDeletePosts" class="px-6 py-4 text-left">
                                <input
                                    type="checkbox"
                                    :checked="allSelected"
                                    :indeterminate="someSelected"
                                    @change="toggleAll"
                                    class="rounded border-[#0b1215]/20 text-[#a1854f] focus:ring-[#a1854f]/30"
                                />
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-[#0b1215]/50 uppercase tracking-wider">
                                Title
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-[#0b1215]/50 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-[#0b1215]/50 uppercase tracking-wider">
                                Views
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-[#0b1215]/50 uppercase tracking-wider">
                                Updated
                            </th>
                            <th class="px-6 py-4 text-right text-xs font-medium text-[#0b1215]/50 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-[#0b1215]/5">
                        <!-- Loading skeleton rows -->
                        <template v-if="isLoading">
                            <SkeletonLoader v-for="i in 5" :key="i" type="table-row" />
                        </template>
                        <tr v-else v-for="post in posts.data" :key="post.id" class="hover:bg-[#0b1215]/[0.02] transition-colors">
                            <td v-if="canDeletePosts" class="px-6 py-4">
                                <input
                                    type="checkbox"
                                    :checked="selectedIds.includes(post.id)"
                                    @change="togglePost(post.id)"
                                    class="rounded border-[#0b1215]/20 text-[#a1854f] focus:ring-[#a1854f]/30"
                                />
                            </td>
                            <td class="px-6 py-4">
                                <Link :href="`/posts/${post.id}/edit`" class="font-medium text-[#0b1215] hover:text-[#a1854f] transition-colors">
                                    {{ post.title }}
                                </Link>
                                <p v-if="post.excerpt" class="text-sm text-[#0b1215]/50 truncate max-w-md">
                                    {{ post.excerpt }}
                                </p>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="getStatusColor(post.status)" class="px-2.5 py-1 text-xs font-medium rounded-full capitalize">
                                    {{ post.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-[#0b1215]/50">
                                {{ post.view_count }}
                            </td>
                            <td class="px-6 py-4 text-sm text-[#0b1215]/50">
                                {{ post.updated_at }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <Link :href="`/posts/${post.id}/edit`" class="text-[#a1854f] hover:text-[#0b1215] font-medium transition-colors">
                                    Edit
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="posts.last_page > 1" class="px-6 py-4 border-t border-[#0b1215]/10">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-[#0b1215]/70">
                            Showing {{ posts.from }} to {{ posts.to }} of {{ posts.total }} posts
                        </span>
                        <div class="flex space-x-2">
                            <Link
                                v-if="posts.prev_page_url"
                                :href="posts.prev_page_url"
                                class="px-3 py-1 border border-[#0b1215]/20 rounded-lg text-sm hover:bg-[#0b1215]/5"
                            >
                                Previous
                            </Link>
                            <Link
                                v-if="posts.next_page_url"
                                :href="posts.next_page_url"
                                class="px-3 py-1 border border-[#0b1215]/20 rounded-lg text-sm hover:bg-[#0b1215]/5"
                            >
                                Next
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white rounded-2xl border border-[#0b1215]/10 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-[#0b1215]/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-[#0b1215]">No posts yet</h3>
                <p class="mt-2 text-[#0b1215]/50">
                    {{ canCreatePosts ? 'Get started by creating your first post.' : 'No posts have been created yet.' }}
                </p>
                <Link
                    v-if="canCreatePosts && canCreatePost"
                    href="/posts/create"
                    class="mt-6 inline-flex items-center px-5 py-2.5 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition text-sm"
                >
                    Create Your First Post
                </Link>
                <button
                    v-else-if="canCreatePosts && !canCreatePost"
                    @click="showUpgradeModal = true"
                    class="mt-6 inline-flex items-center px-5 py-2.5 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition text-sm"
                >
                    Create Your First Post
                </button>
            </div>
        </div>

        <UpgradeModal
            :show="showUpgradeModal"
            title="Post Limit Reached"
            message="You've used all your posts for this month. Upgrade to create unlimited posts."
            feature="posts"
            :required-plan="getRequiredPlan('posts')"
            @close="showUpgradeModal = false"
        />

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
