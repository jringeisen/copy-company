<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    brand: Object,
    defaultFromAddress: String,
});

// Form for initiating domain verification
const domainForm = useForm({
    domain: '',
    from_address: 'hello',
});

// Form for updating from address
const fromForm = useForm({
    from_address: props.brand.custom_email_from || 'hello',
});

const isChecking = ref(false);
const isRemoving = ref(false);

const hasCustomDomain = computed(() => props.brand.custom_email_domain !== null);
const isVerified = computed(() => props.brand.email_domain_verification_status === 'verified');
const isPending = computed(() => props.brand.email_domain_verification_status === 'pending');
const isFailed = computed(() => props.brand.email_domain_verification_status === 'failed');

const currentFromAddress = computed(() => {
    if (hasCustomDomain.value && isVerified.value) {
        return `${props.brand.custom_email_from || 'hello'}@${props.brand.custom_email_domain}`;
    }
    return props.defaultFromAddress;
});

const statusBadgeClass = computed(() => {
    if (isVerified.value) return 'bg-green-100 text-green-800';
    if (isPending.value) return 'bg-yellow-100 text-yellow-800';
    if (isFailed.value) return 'bg-red-100 text-red-800';
    return 'bg-gray-100 text-gray-800';
});

const statusText = computed(() => {
    if (isVerified.value) return 'Verified';
    if (isPending.value) return 'Pending Verification';
    if (isFailed.value) return 'Verification Failed';
    return 'Not Configured';
});

const submitDomain = () => {
    domainForm.post('/settings/email-domain', {
        preserveScroll: true,
    });
};

const checkStatus = () => {
    isChecking.value = true;
    router.post('/settings/email-domain/check', {}, {
        preserveScroll: true,
        onFinish: () => {
            isChecking.value = false;
        },
    });
};

const updateFrom = () => {
    fromForm.put('/settings/email-domain/from', {
        preserveScroll: true,
    });
};

const removeDomain = () => {
    if (!confirm('Are you sure you want to remove your custom email domain? Emails will be sent from the default address.')) {
        return;
    }
    isRemoving.value = true;
    router.delete('/settings/email-domain', {
        preserveScroll: true,
        onFinish: () => {
            isRemoving.value = false;
        },
    });
};

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
};
</script>

<template>
    <Head title="Email Domain Settings" />

    <AppLayout current-page="brand-settings">
        <div class="max-w-3xl mx-auto">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">Email Domain Settings</h1>
                <p class="text-gray-600 mt-1">Send newsletters from your own domain for better deliverability</p>
            </div>

            <!-- Current Status Card -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Current Sending Address</h3>
                        <p class="text-lg font-semibold text-gray-900 mt-1">{{ currentFromAddress }}</p>
                    </div>
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                        :class="statusBadgeClass"
                    >
                        {{ statusText }}
                    </span>
                </div>
            </div>

            <!-- Configure Custom Domain (when not configured) -->
            <div v-if="!hasCustomDomain" class="bg-white rounded-xl shadow-sm p-8">
                <div class="text-center mb-8">
                    <div class="mx-auto w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900">Add Your Custom Email Domain</h2>
                    <p class="text-gray-600 mt-2 max-w-md mx-auto">
                        Send newsletters from your own domain (e.g., hello@yourbrand.com) to improve deliverability and build trust with your subscribers.
                    </p>
                </div>

                <form @submit.prevent="submitDomain" class="space-y-6 max-w-md mx-auto">
                    <!-- Domain Input -->
                    <div>
                        <label for="domain" class="block text-sm font-medium text-gray-700">Domain Name</label>
                        <input
                            id="domain"
                            v-model="domainForm.domain"
                            type="text"
                            placeholder="yourbrand.com"
                            class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500"
                            required
                        />
                        <p class="mt-1 text-sm text-gray-500">Enter your domain without http:// or www</p>
                        <p v-if="domainForm.errors.domain" class="mt-1 text-sm text-red-600">{{ domainForm.errors.domain }}</p>
                    </div>

                    <!-- From Address Input -->
                    <div>
                        <label for="from_address" class="block text-sm font-medium text-gray-700">From Address</label>
                        <div class="mt-1 flex rounded-lg shadow-sm">
                            <input
                                id="from_address"
                                v-model="domainForm.from_address"
                                type="text"
                                placeholder="hello"
                                class="flex-1 block w-full px-4 py-3 border border-gray-300 rounded-l-lg focus:ring-primary-500 focus:border-primary-500"
                            />
                            <span class="inline-flex items-center px-4 rounded-r-lg border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                @{{ domainForm.domain || 'yourbrand.com' }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">This will be the "from" part of your email address</p>
                        <p v-if="domainForm.errors.from_address" class="mt-1 text-sm text-red-600">{{ domainForm.errors.from_address }}</p>
                    </div>

                    <button
                        type="submit"
                        class="w-full px-6 py-3 bg-primary-600 text-white font-semibold rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition"
                        :class="{ 'opacity-50': domainForm.processing }"
                        :disabled="domainForm.processing"
                    >
                        <span v-if="domainForm.processing">Starting Verification...</span>
                        <span v-else>Start Domain Verification</span>
                    </button>
                </form>
            </div>

            <!-- Domain Configured (pending or verified) -->
            <div v-else class="space-y-6">
                <!-- Domain Info Card -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ brand.custom_email_domain }}</h3>
                            <p class="text-sm text-gray-500 mt-1">
                                <span v-if="isVerified">Emails are sent from this domain</span>
                                <span v-else-if="isPending">Waiting for DNS verification</span>
                                <span v-else-if="isFailed">Verification failed - check DNS records</span>
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <button
                                v-if="isPending || isFailed"
                                @click="checkStatus"
                                class="px-4 py-2 text-sm font-medium text-primary-600 bg-primary-50 rounded-lg hover:bg-primary-100 transition"
                                :disabled="isChecking"
                            >
                                <span v-if="isChecking">Checking...</span>
                                <span v-else>Check Status</span>
                            </button>
                            <button
                                @click="removeDomain"
                                class="px-4 py-2 text-sm font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition"
                                :disabled="isRemoving"
                            >
                                <span v-if="isRemoving">Removing...</span>
                                <span v-else>Remove Domain</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- DNS Records (if pending) -->
                <div v-if="(isPending || isFailed) && brand.email_domain_dns_records" class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">DNS Records Required</h3>
                    <p class="text-sm text-gray-600 mb-6">Add these DNS records to your domain's DNS settings. Verification usually takes 15-60 minutes after records are added.</p>

                    <!-- Verification TXT Record -->
                    <div v-if="brand.email_domain_dns_records.verification" class="mb-6">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Domain Verification (TXT Record)</h4>
                        <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-xs text-gray-500">Type:</span>
                                    <span class="ml-2 text-sm font-mono">{{ brand.email_domain_dns_records.verification.type }}</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex-1 min-w-0">
                                    <span class="text-xs text-gray-500">Name:</span>
                                    <span class="ml-2 text-sm font-mono break-all">{{ brand.email_domain_dns_records.verification.name }}</span>
                                </div>
                                <button
                                    @click="copyToClipboard(brand.email_domain_dns_records.verification.name)"
                                    class="ml-2 p-1 text-gray-400 hover:text-gray-600"
                                    title="Copy to clipboard"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex-1 min-w-0">
                                    <span class="text-xs text-gray-500">Value:</span>
                                    <span class="ml-2 text-sm font-mono break-all">{{ brand.email_domain_dns_records.verification.value }}</span>
                                </div>
                                <button
                                    @click="copyToClipboard(brand.email_domain_dns_records.verification.value)"
                                    class="ml-2 p-1 text-gray-400 hover:text-gray-600"
                                    title="Copy to clipboard"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- DKIM Records -->
                    <div v-if="brand.email_domain_dns_records.dkim && brand.email_domain_dns_records.dkim.length > 0" class="mb-6">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">DKIM Records (3 CNAME Records)</h4>
                        <div class="space-y-3">
                            <div
                                v-for="(record, index) in brand.email_domain_dns_records.dkim"
                                :key="index"
                                class="bg-gray-50 rounded-lg p-4 space-y-2"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="text-xs text-gray-500">Type:</span>
                                        <span class="ml-2 text-sm font-mono">{{ record.type }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex-1 min-w-0">
                                        <span class="text-xs text-gray-500">Name:</span>
                                        <span class="ml-2 text-sm font-mono break-all">{{ record.name }}</span>
                                    </div>
                                    <button
                                        @click="copyToClipboard(record.name)"
                                        class="ml-2 p-1 text-gray-400 hover:text-gray-600"
                                        title="Copy to clipboard"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex-1 min-w-0">
                                        <span class="text-xs text-gray-500">Value:</span>
                                        <span class="ml-2 text-sm font-mono break-all">{{ record.value }}</span>
                                    </div>
                                    <button
                                        @click="copyToClipboard(record.value)"
                                        class="ml-2 p-1 text-gray-400 hover:text-gray-600"
                                        title="Copy to clipboard"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SPF Record -->
                    <div v-if="brand.email_domain_dns_records.spf">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">SPF Record (TXT Record)</h4>
                        <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-xs text-gray-500">Type:</span>
                                    <span class="ml-2 text-sm font-mono">{{ brand.email_domain_dns_records.spf.type }}</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex-1 min-w-0">
                                    <span class="text-xs text-gray-500">Name:</span>
                                    <span class="ml-2 text-sm font-mono break-all">{{ brand.email_domain_dns_records.spf.name }}</span>
                                </div>
                                <button
                                    @click="copyToClipboard(brand.email_domain_dns_records.spf.name)"
                                    class="ml-2 p-1 text-gray-400 hover:text-gray-600"
                                    title="Copy to clipboard"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex-1 min-w-0">
                                    <span class="text-xs text-gray-500">Value:</span>
                                    <span class="ml-2 text-sm font-mono break-all">{{ brand.email_domain_dns_records.spf.value }}</span>
                                </div>
                                <button
                                    @click="copyToClipboard(brand.email_domain_dns_records.spf.value)"
                                    class="ml-2 p-1 text-gray-400 hover:text-gray-600"
                                    title="Copy to clipboard"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500">
                            Note: If you already have an SPF record, add <code class="bg-gray-100 px-1 rounded">include:amazonses.com</code> to your existing record.
                        </p>
                    </div>
                </div>

                <!-- From Address Settings (if verified) -->
                <div v-if="isVerified" class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">From Address</h3>
                    <form @submit.prevent="updateFrom" class="space-y-4">
                        <div>
                            <label for="edit_from_address" class="block text-sm font-medium text-gray-700">Email Address</label>
                            <div class="mt-1 flex rounded-lg shadow-sm">
                                <input
                                    id="edit_from_address"
                                    v-model="fromForm.from_address"
                                    type="text"
                                    class="flex-1 block w-full px-4 py-3 border border-gray-300 rounded-l-lg focus:ring-primary-500 focus:border-primary-500"
                                />
                                <span class="inline-flex items-center px-4 rounded-r-lg border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                    @{{ brand.custom_email_domain }}
                                </span>
                            </div>
                            <p v-if="fromForm.errors.from_address" class="mt-1 text-sm text-red-600">{{ fromForm.errors.from_address }}</p>
                        </div>
                        <div class="flex justify-end">
                            <button
                                type="submit"
                                class="px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition"
                                :class="{ 'opacity-50': fromForm.processing }"
                                :disabled="fromForm.processing"
                            >
                                Update From Address
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Verified Success Message -->
                <div v-if="isVerified" class="bg-green-50 border border-green-200 rounded-xl p-6">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <h4 class="text-sm font-semibold text-green-800">Domain Verified</h4>
                            <p class="text-sm text-green-700 mt-1">
                                Your newsletters will now be sent from <strong>{{ brand.custom_email_from }}@{{ brand.custom_email_domain }}</strong>.
                                This improves deliverability and helps build trust with your subscribers.
                            </p>
                            <p v-if="brand.email_domain_verified_at" class="text-xs text-green-600 mt-2">
                                Verified on {{ new Date(brand.email_domain_verified_at).toLocaleDateString() }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
