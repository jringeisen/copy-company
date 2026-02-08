<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/Button.vue';

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
                    class="w-full px-4 py-3 bg-[#f7f7f7] border-0 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#a1854f]/30 transition"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="you@example.com"
                />
                <p v-if="form.errors.email" class="mt-2 text-sm text-red-600">{{ form.errors.email }}</p>
            </div>

            <Button
                type="submit"
                size="lg"
                :loading="form.processing"
                loading-text="Sending..."
                class="w-full mt-6"
            >
                Send reset link
            </Button>
        </form>

        <template #footer>
            <Link href="/login" class="text-sm text-[#0b1215]/70 hover:text-[#0b1215] transition">
                Back to sign in
            </Link>
        </template>
    </GuestLayout>
</template>
