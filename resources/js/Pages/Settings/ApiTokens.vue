<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    tokens: Array,
});

const page = usePage();
const newToken = computed(() => page.props.flash?.newToken);
const copied = ref(false);

const form = useForm({
    name: '',
});

const createToken = () => {
    form.post('/settings/api-tokens', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};

const deleteToken = (tokenId) => {
    if (confirm('Are you sure you want to delete this token? Any applications using it will lose access.')) {
        useForm({}).delete(`/settings/api-tokens/${tokenId}`, {
            preserveScroll: true,
        });
    }
};

const copyToken = async () => {
    if (newToken.value) {
        await navigator.clipboard.writeText(newToken.value);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    }
};

const formatDate = (dateString) => {
    if (!dateString) return 'Never';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head title="API Tokens" />

    <AppLayout current-page="api-tokens">
        <div class="max-w-3xl mx-auto">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-[#0b1215]">API Tokens</h1>
                <p class="text-[#0b1215]/60 mt-1">Manage API tokens for external applications like Claude Desktop or Cursor</p>
            </div>

            <!-- New Token Display -->
            <div v-if="newToken" class="mb-6 bg-green-50 border border-green-200 rounded-2xl p-6">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-green-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-green-800">Token Created Successfully</h3>
                        <p class="text-sm text-green-700 mt-1">Copy this token now. You won't be able to see it again.</p>
                        <div class="mt-3 flex items-center gap-2">
                            <code class="flex-1 bg-white px-3 py-2 rounded-lg text-sm font-mono text-[#0b1215] border border-green-200 truncate">
                                {{ newToken }}
                            </code>
                            <button
                                @click="copyToken"
                                class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition shrink-0"
                            >
                                {{ copied ? 'Copied!' : 'Copy' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Create Token Form -->
            <div class="bg-white rounded-2xl border border-[#0b1215]/10 p-6 mb-6">
                <h2 class="text-lg font-semibold text-[#0b1215] mb-4">Create New Token</h2>
                <form @submit.prevent="createToken" class="flex gap-4">
                    <div class="flex-1">
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="Token name (e.g., Claude Desktop)"
                            class="w-full px-4 py-3 border border-[#0b1215]/20 rounded-xl focus:ring-[#0b1215]/20 focus:border-[#0b1215]/40"
                            required
                        />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                    </div>
                    <button
                        type="submit"
                        class="px-6 py-3 bg-[#0b1215] text-white font-semibold rounded-xl hover:bg-[#0b1215]/90 transition shrink-0"
                        :class="{ 'opacity-50': form.processing }"
                        :disabled="form.processing"
                    >
                        Create Token
                    </button>
                </form>
            </div>

            <!-- Existing Tokens -->
            <div class="bg-white rounded-2xl border border-[#0b1215]/10 p-6">
                <h2 class="text-lg font-semibold text-[#0b1215] mb-4">Existing Tokens</h2>

                <div v-if="tokens.length === 0" class="text-center py-8 text-[#0b1215]/50">
                    <svg class="w-12 h-12 mx-auto mb-3 text-[#0b1215]/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                    <p>No API tokens yet</p>
                    <p class="text-sm mt-1">Create a token to connect external AI clients</p>
                </div>

                <div v-else class="divide-y divide-[#0b1215]/10">
                    <div
                        v-for="token in tokens"
                        :key="token.id"
                        class="py-4 first:pt-0 last:pb-0 flex items-center justify-between gap-4"
                    >
                        <div class="min-w-0">
                            <h3 class="font-medium text-[#0b1215] truncate">{{ token.name }}</h3>
                            <div class="flex gap-4 text-sm text-[#0b1215]/50 mt-1">
                                <span>Created {{ formatDate(token.created_at) }}</span>
                                <span v-if="token.last_used_at">Last used {{ formatDate(token.last_used_at) }}</span>
                                <span v-else class="text-[#0b1215]/30">Never used</span>
                            </div>
                        </div>
                        <button
                            @click="deleteToken(token.id)"
                            class="text-red-600 hover:text-red-700 text-sm font-medium shrink-0"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>

            <!-- Usage Instructions -->
            <div class="mt-6 bg-[#f7f7f7] rounded-2xl p-6">
                <h3 class="font-semibold text-[#0b1215] mb-3">How to Use</h3>
                <ol class="text-sm text-[#0b1215]/70 space-y-2">
                    <li class="flex gap-2">
                        <span class="font-semibold text-[#0b1215]">1.</span>
                        <span>Create a new token with a descriptive name</span>
                    </li>
                    <li class="flex gap-2">
                        <span class="font-semibold text-[#0b1215]">2.</span>
                        <span>Copy the token immediately (it won't be shown again)</span>
                    </li>
                    <li class="flex gap-2">
                        <span class="font-semibold text-[#0b1215]">3.</span>
                        <span>Configure your AI client with the MCP server URL and token</span>
                    </li>
                </ol>
                <div class="mt-4 p-4 bg-white rounded-xl border border-[#0b1215]/10">
                    <p class="text-xs font-medium text-[#0b1215]/50 uppercase tracking-wide mb-2">MCP Server URL</p>
                    <code class="text-sm text-[#0b1215] font-mono">{{ $page.props.appUrl }}/api/mcp/copy-company</code>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
