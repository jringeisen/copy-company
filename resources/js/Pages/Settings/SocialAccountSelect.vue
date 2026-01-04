<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppNavigation from '@/Components/AppNavigation.vue';

const props = defineProps({
    platform: String,
    platformName: String,
    accountType: String,
    accounts: Array,
    currentSelection: String,
});

const selectedAccount = ref(props.currentSelection || null);

const form = useForm({
    account_id: '',
    account_name: '',
    access_token: '',
});

const selectAccount = (account) => {
    selectedAccount.value = account.id;
    form.account_id = account.id;
    form.account_name = account.name;
    form.access_token = account.access_token || '';
};

const submit = () => {
    form.post(`/settings/social/${props.platform}/select`);
};
</script>

<template>
    <Head :title="`Select ${platformName} ${accountType}`" />

    <div class="min-h-screen bg-gray-50">
        <AppNavigation current-page="settings" />

        <!-- Settings Sub-navigation -->
        <div class="bg-white border-b border-gray-200">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <nav class="flex space-x-8">
                    <Link
                        href="/settings/brand"
                        class="border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300"
                    >
                        Brand
                    </Link>
                    <Link
                        href="/settings/social"
                        class="border-b-2 border-primary-500 py-4 px-1 text-sm font-medium text-primary-600"
                    >
                        Social Connections
                    </Link>
                </nav>
            </div>
        </div>

        <main class="max-w-2xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <Link
                    href="/settings/social"
                    class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 mb-4"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to Social Connections
                </Link>
                <h1 class="text-2xl font-bold text-gray-900">Select a {{ accountType }}</h1>
                <p class="text-gray-600 mt-1">
                    Choose which {{ platformName }} {{ accountType }} you want to publish to.
                </p>
            </div>

            <!-- Account Selection -->
            <div class="space-y-3">
                <button
                    v-for="account in accounts"
                    :key="account.id"
                    @click="selectAccount(account)"
                    type="button"
                    class="w-full bg-white rounded-xl shadow-sm border-2 p-5 text-left transition-all"
                    :class="[
                        selectedAccount === account.id
                            ? 'border-primary-500 ring-2 ring-primary-100'
                            : 'border-gray-200 hover:border-gray-300'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <!-- Account Icon -->
                            <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center">
                                <svg v-if="accountType === 'page'" class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <svg v-else class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900">{{ account.name }}</h3>
                                <p class="text-sm text-gray-500">{{ accountType === 'page' ? 'Facebook Page' : 'Pinterest Board' }}</p>
                            </div>
                        </div>
                        <!-- Selection Indicator -->
                        <div
                            class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-colors"
                            :class="[
                                selectedAccount === account.id
                                    ? 'border-primary-500 bg-primary-500'
                                    : 'border-gray-300'
                            ]"
                        >
                            <svg
                                v-if="selectedAccount === account.id"
                                class="w-4 h-4 text-white"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </button>
            </div>

            <!-- Actions -->
            <div class="mt-8 flex gap-4">
                <Link
                    href="/settings/social"
                    class="flex-1 px-4 py-3 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition text-center"
                >
                    Cancel
                </Link>
                <button
                    @click="submit"
                    :disabled="!selectedAccount || form.processing"
                    class="flex-1 px-4 py-3 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {{ form.processing ? 'Saving...' : 'Save Selection' }}
                </button>
            </div>
        </main>
    </div>
</template>
