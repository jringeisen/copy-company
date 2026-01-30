<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const props = defineProps({
    email: String,
    token: String,
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/reset-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Reset Password" />

    <GuestLayout title="Reset your password" subtitle="Enter your new password below.">
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
                />
                <p v-if="form.errors.email" class="mt-2 text-sm text-red-600">{{ form.errors.email }}</p>
            </div>

            <div class="mt-5">
                <label for="password" class="block text-sm font-medium text-[#0b1215] mb-2">New Password</label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="w-full px-4 py-3 bg-[#f7f7f7] border-0 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#a1854f]/30 transition"
                    required
                    autocomplete="new-password"
                    placeholder="Enter new password"
                />
                <p v-if="form.errors.password" class="mt-2 text-sm text-red-600">{{ form.errors.password }}</p>
            </div>

            <div class="mt-5">
                <label for="password_confirmation" class="block text-sm font-medium text-[#0b1215] mb-2">Confirm Password</label>
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="w-full px-4 py-3 bg-[#f7f7f7] border-0 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#a1854f]/30 transition"
                    required
                    autocomplete="new-password"
                    placeholder="Confirm new password"
                />
                <p v-if="form.errors.password_confirmation" class="mt-2 text-sm text-red-600">{{ form.errors.password_confirmation }}</p>
            </div>

            <button
                type="submit"
                class="w-full mt-6 px-6 py-3.5 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition text-sm"
                :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                :disabled="form.processing"
            >
                Reset password
            </button>
        </form>
    </GuestLayout>
</template>
