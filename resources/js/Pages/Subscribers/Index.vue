<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppNavigation from '@/Components/AppNavigation.vue';
import FeatureEducationBanner from '@/Components/FeatureEducationBanner.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { usePermissions } from '@/Composables/usePermissions';

const { canExportSubscribers, canDeleteSubscribers, canViewSubscribers } = usePermissions();

const props = defineProps({
    subscribers: Object,
    stats: Object,
    brand: Object,
});

const importFile = ref(null);
const isImporting = ref(false);
const showDeleteModal = ref(false);
const subscriberToDelete = ref(null);
const isDeleting = ref(false);

// Edit subscriber state
const showEditModal = ref(false);
const subscriberToEdit = ref(null);
const editForm = useForm({
    name: '',
});

const showEducationalBanner = computed(() => {
    return props.stats.total === 0;
});

const editSubscriber = (subscriber) => {
    subscriberToEdit.value = subscriber;
    editForm.name = subscriber.name || '';
    showEditModal.value = true;
};

const saveSubscriber = () => {
    if (!subscriberToEdit.value) return;
    editForm.patch(`/subscribers/${subscriberToEdit.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            subscriberToEdit.value = null;
            editForm.reset();
        },
    });
};

const cancelEdit = () => {
    showEditModal.value = false;
    subscriberToEdit.value = null;
    editForm.reset();
};

const deleteSubscriber = (subscriber) => {
    subscriberToDelete.value = subscriber;
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    if (!subscriberToDelete.value) return;
    isDeleting.value = true;
    router.delete(`/subscribers/${subscriberToDelete.value.id}`, {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
            subscriberToDelete.value = null;
        },
    });
};

const handleImport = () => {
    if (!importFile.value) return;

    isImporting.value = true;
    const formData = new FormData();
    formData.append('file', importFile.value);

    router.post('/subscribers/import', formData, {
        onFinish: () => {
            isImporting.value = false;
            importFile.value = null;
        },
    });
};

const onFileChange = (e) => {
    importFile.value = e.target.files[0];
};
</script>

<template>
    <Head title="Subscribers" />

    <div class="min-h-screen bg-gray-50">
        <AppNavigation current-page="subscribers" />

        <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Subscribers</h1>
                    <p class="text-gray-600 mt-1">Manage your newsletter subscribers</p>
                </div>
                <div v-if="canExportSubscribers" class="flex space-x-3">
                    <a
                        href="/subscribers/export"
                        class="px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition"
                    >
                        Export CSV
                    </a>
                </div>
            </div>

            <!-- Educational Banner -->
            <FeatureEducationBanner
                v-if="showEducationalBanner"
                title="Build Your Audience"
                description="Subscribers receive your newsletter when you publish. Share your public blog to grow your list, or import existing subscribers from another platform."
                gradient="from-blue-500 to-cyan-600"
            >
                <template #extra>
                    <div class="mt-3 flex flex-wrap items-center gap-4 text-sm text-white/80">
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                            </svg>
                            Automatic newsletter delivery
                        </span>
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Import from CSV
                        </span>
                        <span v-if="brand" class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd"/>
                            </svg>
                            Public blog at /@{{ brand.slug }}
                        </span>
                    </div>
                </template>
            </FeatureEducationBanner>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="text-sm text-gray-500">Total Subscribers</div>
                    <div class="text-3xl font-bold text-gray-900">{{ stats.total }}</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="text-sm text-gray-500">Confirmed</div>
                    <div class="text-3xl font-bold text-green-600">{{ stats.confirmed }}</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="text-sm text-gray-500">Unsubscribed</div>
                    <div class="text-3xl font-bold text-gray-400">{{ stats.unsubscribed }}</div>
                </div>
            </div>

            <!-- Import -->
            <div v-if="canExportSubscribers" class="bg-white rounded-lg shadow-sm p-6 mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Import Subscribers</h2>
                <p class="text-sm text-gray-600 mb-4">Upload a CSV file with columns: email, name (optional)</p>
                <div class="flex items-center space-x-4">
                    <input
                        type="file"
                        accept=".csv,.txt"
                        @change="onFileChange"
                        class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
                    />
                    <button
                        @click="handleImport"
                        :disabled="!importFile || isImporting"
                        class="px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition disabled:opacity-50"
                    >
                        {{ isImporting ? 'Importing...' : 'Import' }}
                    </button>
                </div>
            </div>

            <!-- Subscribers Table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Email
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Name
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Subscribed
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="subscriber in subscribers.data" :key="subscriber.id">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ subscriber.email }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ subscriber.name || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-2 py-1 text-xs font-medium rounded-full"
                                    :class="{
                                        'bg-green-100 text-green-800': subscriber.status === 'confirmed',
                                        'bg-yellow-100 text-yellow-800': subscriber.status === 'pending',
                                        'bg-gray-100 text-gray-800': subscriber.status === 'unsubscribed',
                                    }"
                                >
                                    {{ subscriber.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ new Date(subscriber.created_at).toLocaleDateString() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm space-x-3">
                                <button
                                    v-if="canDeleteSubscribers"
                                    @click="editSubscriber(subscriber)"
                                    class="text-primary-600 hover:text-primary-800"
                                >
                                    Edit
                                </button>
                                <button
                                    v-if="canDeleteSubscribers"
                                    @click="deleteSubscriber(subscriber)"
                                    class="text-red-600 hover:text-red-800"
                                >
                                    Remove
                                </button>
                            </td>
                        </tr>
                        <tr v-if="subscribers.data.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                No subscribers yet. Share your blog to start growing your list!
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="subscribers.last_page > 1" class="px-6 py-4 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-700">
                            Showing {{ subscribers.from }} to {{ subscribers.to }} of {{ subscribers.total }} subscribers
                        </span>
                        <div class="flex space-x-2">
                            <Link
                                v-if="subscribers.prev_page_url"
                                :href="subscribers.prev_page_url"
                                class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50"
                            >
                                Previous
                            </Link>
                            <Link
                                v-if="subscribers.next_page_url"
                                :href="subscribers.next_page_url"
                                class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50"
                            >
                                Next
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <ConfirmModal
            :show="showDeleteModal"
            title="Remove Subscriber"
            :message="`Remove ${subscriberToDelete?.email || ''} from your list? They won't receive future newsletters.`"
            confirm-text="Remove"
            :processing="isDeleting"
            @confirm="confirmDelete"
            @cancel="showDeleteModal = false"
        />

        <!-- Edit Subscriber Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-screen items-center justify-center p-4">
                        <div class="fixed inset-0 bg-black/50" @click="cancelEdit"></div>

                        <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Edit Subscriber</h3>

                            <form @submit.prevent="saveSubscriber">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <p class="text-sm text-gray-600">{{ subscriberToEdit?.email }}</p>
                                </div>

                                <div class="mb-6">
                                    <label for="edit-name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                    <input
                                        id="edit-name"
                                        v-model="editForm.name"
                                        type="text"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                        placeholder="Enter subscriber name"
                                    />
                                    <p v-if="editForm.errors.name" class="mt-1 text-sm text-red-600">{{ editForm.errors.name }}</p>
                                </div>

                                <div class="flex justify-end gap-3">
                                    <button
                                        type="button"
                                        @click="cancelEdit"
                                        class="px-4 py-2 text-gray-700 font-medium bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="editForm.processing"
                                        class="px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition disabled:opacity-50"
                                    >
                                        {{ editForm.processing ? 'Saving...' : 'Save' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
