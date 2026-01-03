<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    subscribers: Object,
    stats: Object,
});

const importFile = ref(null);
const isImporting = ref(false);

const deleteSubscriber = (subscriber) => {
    if (confirm(`Remove ${subscriber.email} from your list?`)) {
        router.delete(`/subscribers/${subscriber.id}`);
    }
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
        <!-- Navigation -->
        <nav class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <Link href="/dashboard" class="text-xl font-bold text-primary-600">
                            Wordsmith
                        </Link>
                        <div class="hidden sm:ml-10 sm:flex sm:space-x-8">
                            <Link href="/posts" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Posts
                            </Link>
                            <Link href="/subscribers" class="border-primary-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Subscribers
                            </Link>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <Link href="/settings/brand" class="text-gray-500 hover:text-gray-700">
                            Settings
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Subscribers</h1>
                    <p class="text-gray-600 mt-1">Manage your newsletter subscribers</p>
                </div>
                <div class="flex space-x-3">
                    <a
                        href="/subscribers/export"
                        class="px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition"
                    >
                        Export CSV
                    </a>
                </div>
            </div>

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
            <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
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
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <button
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
    </div>
</template>
