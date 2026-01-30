<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
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

    <AppLayout current-page="subscribers">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-[#0b1215]">Subscribers</h1>
                    <p class="text-[#0b1215]/60 mt-1">Manage your newsletter subscribers</p>
                </div>
                <div v-if="canExportSubscribers" class="flex space-x-3">
                    <a
                        href="/subscribers/export"
                        class="px-4 py-2 border border-[#0b1215]/20 text-[#0b1215] font-medium rounded-full hover:bg-[#0b1215]/5 transition"
                    >
                        Export CSV
                    </a>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <div class="bg-white rounded-2xl border border-[#0b1215]/10 p-6">
                    <div class="text-sm text-[#0b1215]/50">Total Subscribers</div>
                    <div class="text-3xl font-bold text-[#0b1215]">{{ stats.total }}</div>
                </div>
                <div class="bg-white rounded-2xl border border-[#0b1215]/10 p-6">
                    <div class="text-sm text-[#0b1215]/50">Confirmed</div>
                    <div class="text-3xl font-bold text-green-600">{{ stats.confirmed }}</div>
                </div>
                <div class="bg-white rounded-2xl border border-[#0b1215]/10 p-6">
                    <div class="text-sm text-[#0b1215]/50">Unsubscribed</div>
                    <div class="text-3xl font-bold text-[#0b1215]/40">{{ stats.unsubscribed }}</div>
                </div>
            </div>

            <!-- Import -->
            <div v-if="canExportSubscribers" class="bg-white rounded-2xl border border-[#0b1215]/10 p-6 mb-8">
                <h2 class="text-lg font-semibold text-[#0b1215] mb-4">Import Subscribers</h2>
                <p class="text-sm text-[#0b1215]/60 mb-4">Upload a CSV file with columns: email, name (optional)</p>
                <div class="flex items-center space-x-4">
                    <input
                        type="file"
                        accept=".csv,.txt"
                        @change="onFileChange"
                        class="text-sm text-[#0b1215]/50 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#a1854f]/10 file:text-[#a1854f] hover:file:bg-[#a1854f]/20"
                    />
                    <button
                        @click="handleImport"
                        :disabled="!importFile || isImporting"
                        class="px-4 py-2 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition disabled:opacity-50"
                    >
                        {{ isImporting ? 'Importing...' : 'Import' }}
                    </button>
                </div>
            </div>

            <!-- Subscribers Table -->
            <div class="bg-white rounded-2xl border border-[#0b1215]/10 overflow-hidden">
                <table class="min-w-full divide-y divide-[#0b1215]/10">
                    <thead class="bg-[#f7f7f7]">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-[#0b1215]/50 uppercase tracking-wider">
                                Email
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-[#0b1215]/50 uppercase tracking-wider">
                                Name
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-[#0b1215]/50 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-[#0b1215]/50 uppercase tracking-wider">
                                Subscribed
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-[#0b1215]/50 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-[#0b1215]/10">
                        <tr v-for="subscriber in subscribers.data" :key="subscriber.id">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-[#0b1215]">
                                {{ subscriber.email }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-[#0b1215]/50">
                                {{ subscriber.name || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-2 py-1 text-xs font-medium rounded-full"
                                    :class="{
                                        'bg-green-100 text-green-800': subscriber.status === 'confirmed',
                                        'bg-[#a1854f]/20 text-[#a1854f]': subscriber.status === 'pending',
                                        'bg-[#0b1215]/10 text-[#0b1215]/60': subscriber.status === 'unsubscribed',
                                    }"
                                >
                                    {{ subscriber.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-[#0b1215]/50">
                                {{ new Date(subscriber.created_at).toLocaleDateString() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm space-x-3">
                                <button
                                    v-if="canDeleteSubscribers"
                                    @click="editSubscriber(subscriber)"
                                    class="text-[#a1854f] hover:text-[#a1854f]/80"
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
                            <td colspan="5" class="px-6 py-12 text-center text-[#0b1215]/50">
                                No subscribers yet. Share your blog to start growing your list!
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="subscribers.last_page > 1" class="px-6 py-4 border-t border-[#0b1215]/10">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-[#0b1215]/70">
                            Showing {{ subscribers.from }} to {{ subscribers.to }} of {{ subscribers.total }} subscribers
                        </span>
                        <div class="flex space-x-2">
                            <Link
                                v-if="subscribers.prev_page_url"
                                :href="subscribers.prev_page_url"
                                class="px-3 py-1 border border-[#0b1215]/20 rounded-lg text-sm hover:bg-[#0b1215]/5"
                            >
                                Previous
                            </Link>
                            <Link
                                v-if="subscribers.next_page_url"
                                :href="subscribers.next_page_url"
                                class="px-3 py-1 border border-[#0b1215]/20 rounded-lg text-sm hover:bg-[#0b1215]/5"
                            >
                                Next
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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
                        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="cancelEdit"></div>

                        <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
                            <h3 class="text-lg font-semibold text-[#0b1215] mb-4">Edit Subscriber</h3>

                            <form @submit.prevent="saveSubscriber">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-[#0b1215] mb-1">Email</label>
                                    <p class="text-sm text-[#0b1215]/60">{{ subscriberToEdit?.email }}</p>
                                </div>

                                <div class="mb-6">
                                    <label for="edit-name" class="block text-sm font-medium text-[#0b1215] mb-1">Name</label>
                                    <input
                                        id="edit-name"
                                        v-model="editForm.name"
                                        type="text"
                                        class="w-full px-4 py-2 border border-[#0b1215]/20 rounded-xl focus:ring-2 focus:ring-[#a1854f]/30 focus:border-[#a1854f]"
                                        placeholder="Enter subscriber name"
                                    />
                                    <p v-if="editForm.errors.name" class="mt-1 text-sm text-red-600">{{ editForm.errors.name }}</p>
                                </div>

                                <div class="flex justify-end gap-3">
                                    <button
                                        type="button"
                                        @click="cancelEdit"
                                        class="px-4 py-2 text-[#0b1215] font-medium bg-white border border-[#0b1215]/20 rounded-xl hover:bg-[#0b1215]/5 transition"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="editForm.processing"
                                        class="px-4 py-2 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition disabled:opacity-50"
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
    </AppLayout>
</template>
