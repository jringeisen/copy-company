<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

defineProps({
    status: String,
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post('/forgot-password');
};
</script>

<template>
    <Head title="Forgot Password" />

    <GuestLayout title="Forgot your password?" subtitle="No problem. Enter your email and we'll send you a reset link.">
        <div v-if="status" class="mb-6 p-4 bg-green-50 rounded-2xl text-sm text-green-700">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <label for="email" class="block text-sm font-medium text-[#0b1215] mb-2">Email</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="w-full px-4 py-3 bg-[#f7f7f7] border-0 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#0b1215]/20 transition"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="you@example.com"
                />
                <p v-if="form.errors.email" class="mt-2 text-sm text-red-600">{{ form.errors.email }}</p>
            </div>

            <button
                type="submit"
                class="w-full mt-6 px-6 py-3.5 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition text-sm"
                :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                :disabled="form.processing"
            >
                Send reset link
            </button>
        </form>

        <template #footer>
            <Link href="/login" class="text-sm text-[#0b1215]/70 hover:text-[#0b1215] transition">
                Back to sign in
            </Link>
        </template>
    </GuestLayout>
</template>
