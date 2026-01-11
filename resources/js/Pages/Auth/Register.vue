<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Register" />

    <GuestLayout title="Create your account" subtitle="Get started with Copy Company today.">
        <form @submit.prevent="submit">
            <div>
                <label for="name" class="block text-sm font-medium text-[#0b1215] mb-2">Name</label>
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="w-full px-4 py-3 bg-[#f7f7f7] border-0 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#0b1215]/20 transition"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Your name"
                />
                <p v-if="form.errors.name" class="mt-2 text-sm text-red-600">{{ form.errors.name }}</p>
            </div>

            <div class="mt-5">
                <label for="email" class="block text-sm font-medium text-[#0b1215] mb-2">Email</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="w-full px-4 py-3 bg-[#f7f7f7] border-0 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#0b1215]/20 transition"
                    required
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
                    autocomplete="new-password"
                    placeholder="Create a password"
                />
                <p v-if="form.errors.password" class="mt-2 text-sm text-red-600">{{ form.errors.password }}</p>
            </div>

            <div class="mt-5">
                <label for="password_confirmation" class="block text-sm font-medium text-[#0b1215] mb-2">Confirm Password</label>
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="w-full px-4 py-3 bg-[#f7f7f7] border-0 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#0b1215]/20 transition"
                    required
                    autocomplete="new-password"
                    placeholder="Confirm your password"
                />
                <p v-if="form.errors.password_confirmation" class="mt-2 text-sm text-red-600">{{ form.errors.password_confirmation }}</p>
            </div>

            <button
                type="submit"
                class="w-full mt-6 px-6 py-3.5 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition text-sm"
                :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                :disabled="form.processing"
            >
                Create account
            </button>
        </form>

        <template #footer>
            <span class="text-sm text-[#0b1215]/60">Already have an account?</span>
            <Link href="/login" class="ml-1 text-sm text-[#0b1215] font-medium hover:underline">
                Sign in
            </Link>
        </template>
    </GuestLayout>
</template>
