<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    title: String,
    subtitle: String,
});
</script>

<template>
    <div class="min-h-screen bg-[#fcfbf8] flex flex-col">
        <!-- Header -->
        <header class="py-6 px-6 lg:px-12">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <Link href="/">
                    <img src="/images/logo.svg" alt="Copy Company" class="h-10" />
                </Link>
                <div class="flex items-center gap-6">
                    <Link href="/login" class="text-sm text-[#0b1215]/70 hover:text-[#0b1215] transition">
                        Sign in
                    </Link>
                    <Link
                        href="/register"
                        class="px-6 py-2.5 bg-[#0b1215] text-white text-sm rounded-full hover:bg-[#0b1215]/90 transition"
                    >
                        Get Started
                    </Link>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col items-center justify-center px-6 py-12">
            <div class="w-full max-w-md">
                <!-- Title Section -->
                <div class="text-center mb-8" v-if="title">
                    <h1 class="font-serif text-3xl lg:text-4xl font-light text-[#0b1215] mb-3">
                        {{ title }}
                    </h1>
                    <p v-if="subtitle" class="text-[#0b1215]/60">
                        {{ subtitle }}
                    </p>
                </div>

                <!-- Form Card -->
                <div class="bg-white rounded-3xl p-8 border border-[#0b1215]/5 shadow-sm">
                    <slot />
                </div>

                <!-- Footer Slot -->
                <div class="mt-6 text-center">
                    <slot name="footer" />
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="py-6 px-6 lg:px-12 border-t border-[#0b1215]/5">
            <div class="max-w-7xl mx-auto text-center text-[#0b1215]/50 text-sm">
                &copy; {{ new Date().getFullYear() }} Copy Company. All rights reserved.
            </div>
        </footer>
    </div>
</template>

<style>
.font-serif {
    font-family: 'Georgia', 'Times New Roman', serif;
}
</style>
