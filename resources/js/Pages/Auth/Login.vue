<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

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

    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-50">
        <div class="w-full sm:max-w-md">
            <h1 class="text-3xl font-bold text-center text-gray-900 mb-2">Content Platform</h1>
            <p class="text-center text-gray-600 mb-8">Welcome back! Sign in to continue.</p>
        </div>

        <div class="w-full sm:max-w-md px-6 py-8 bg-white shadow-md overflow-hidden rounded-lg">
            <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
                {{ status }}
            </div>

            <form @submit.prevent="submit">
                <div>
                    <label for="email" class="block font-medium text-sm text-gray-700">Email</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
                        required
                        autofocus
                        autocomplete="username"
                    />
                    <p v-if="form.errors.email" class="mt-2 text-sm text-red-600">{{ form.errors.email }}</p>
                </div>

                <div class="mt-4">
                    <label for="password" class="block font-medium text-sm text-gray-700">Password</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
                        required
                        autocomplete="current-password"
                    />
                    <p v-if="form.errors.password" class="mt-2 text-sm text-red-600">{{ form.errors.password }}</p>
                </div>

                <div class="block mt-4">
                    <label class="flex items-center">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500"
                        />
                        <span class="ml-2 text-sm text-gray-600">Remember me</span>
                    </label>
                </div>

                <div class="flex items-center justify-between mt-6">
                    <Link
                        v-if="canResetPassword"
                        href="/forgot-password"
                        class="text-sm text-gray-600 hover:text-gray-900 underline"
                    >
                        Forgot your password?
                    </Link>

                    <button
                        type="submit"
                        class="px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Log in
                    </button>
                </div>
            </form>

            <div class="mt-6 text-center">
                <span class="text-sm text-gray-600">Don't have an account?</span>
                <Link href="/register" class="ml-1 text-sm text-primary-600 hover:text-primary-900 underline">
                    Sign up
                </Link>
            </div>
        </div>
    </div>
</template>
