<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in" />

    <GuestLayout title="Welcome back" subtitle="Sign in to your account to continue.">
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

            <div class="mt-5">
                <label for="password" class="block text-sm font-medium text-[#0b1215] mb-2">Password</label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="w-full px-4 py-3 bg-[#f7f7f7] border-0 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#0b1215]/20 transition"
                    required
                    autocomplete="current-password"
                    placeholder="Enter your password"
                />
                <p v-if="form.errors.password" class="mt-2 text-sm text-red-600">{{ form.errors.password }}</p>
            </div>

            <div class="flex items-center justify-between mt-5">
                <label class="flex items-center">
                    <input
                        v-model="form.remember"
                        type="checkbox"
                        class="w-4 h-4 rounded border-[#0b1215]/20 text-[#0b1215] focus:ring-[#0b1215]/20"
                    />
                    <span class="ml-2 text-sm text-[#0b1215]/70">Remember me</span>
                </label>

                <Link
                    v-if="canResetPassword"
                    href="/forgot-password"
                    class="text-sm text-[#0b1215]/70 hover:text-[#0b1215] transition"
                >
                    Forgot password?
                </Link>
            </div>

            <button
                type="submit"
                class="w-full mt-6 px-6 py-3.5 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition text-sm"
                :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                :disabled="form.processing"
            >
                Sign in
            </button>
        </form>

        <template #footer>
            <span class="text-sm text-[#0b1215]/60">Don't have an account?</span>
            <Link href="/register" class="ml-1 text-sm text-[#0b1215] font-medium hover:underline">
                Create one
            </Link>
        </template>
    </GuestLayout>
</template>
