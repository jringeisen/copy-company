<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    loop: Object,
    platforms: Array,
    daysOfWeek: Array,
});

const form = useForm({
    name: props.loop.name,
    description: props.loop.description || '',
    platforms: props.loop.platforms,
    is_active: props.loop.is_active,
    schedules: props.loop.schedules?.map(s => ({
        day_of_week: s.day_of_week,
        time_of_day: s.time_of_day,
        platform: s.platform,
    })) || [],
});

const showScheduleForm = ref(false);
const newSchedule = ref({
    day_of_week: 1,
    time_of_day: '09:00',
    platform: null,
});

const addSchedule = () => {
    form.schedules.push({ ...newSchedule.value });
    newSchedule.value = {
        day_of_week: 1,
        time_of_day: '09:00',
        platform: null,
    };
    showScheduleForm.value = false;
};

const removeSchedule = (index) => {
    form.schedules.splice(index, 1);
};

const submit = () => {
    form.put(`/loops/${props.loop.id}`);
};

const getDayName = (dayValue) => {
    const day = props.daysOfWeek.find(d => d.value === dayValue);
    return day ? day.label : '';
};

const formatTime = (time) => {
    if (!time) return '';
    const [hours, minutes] = time.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
};
</script>

<template>
    <Head :title="`Edit ${loop.name}`" />

    <AppLayout current-page="loops">
        <div class="max-w-2xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <Link :href="`/loops/${loop.id}`" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to Loop
                </Link>
                <h1 class="text-2xl font-bold text-gray-900">Edit Loop</h1>
                <p class="mt-1 text-sm text-gray-500">
                    Update your loop settings and schedule
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Info -->
                <div class="bg-white rounded-2xl border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#a1854f] focus:border-transparent"
                                placeholder="e.g., Weekly Tips"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#a1854f] focus:border-transparent"
                                placeholder="What's this loop for?"
                            />
                        </div>

                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                @click="form.is_active = !form.is_active"
                                :class="[
                                    'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                                    form.is_active ? 'bg-green-500' : 'bg-gray-200'
                                ]"
                            >
                                <span
                                    :class="[
                                        'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                                        form.is_active ? 'translate-x-6' : 'translate-x-1'
                                    ]"
                                />
                            </button>
                            <span class="text-sm text-gray-700">{{ form.is_active ? 'Active' : 'Paused' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Platforms -->
                <div class="bg-white rounded-2xl border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Target Platforms</h2>
                    <p class="text-sm text-gray-500 mb-4">Select which platforms this loop should post to</p>

                    <div class="flex flex-wrap gap-3">
                        <label
                            v-for="platform in platforms"
                            :key="platform.value"
                            class="relative"
                        >
                            <input
                                type="checkbox"
                                :value="platform.value"
                                v-model="form.platforms"
                                class="sr-only peer"
                            />
                            <div class="px-4 py-2.5 border border-gray-200 rounded-xl cursor-pointer transition-all peer-checked:border-[#a1854f] peer-checked:bg-[#a1854f]/5 peer-checked:text-[#a1854f] hover:border-gray-300">
                                {{ platform.label }}
                            </div>
                        </label>
                    </div>
                    <p v-if="form.errors.platforms" class="mt-2 text-sm text-red-500">{{ form.errors.platforms }}</p>
                </div>

                <!-- Schedule -->
                <div class="bg-white rounded-2xl border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Schedule</h2>
                            <p class="text-sm text-gray-500">When should content be posted?</p>
                        </div>
                        <button
                            type="button"
                            @click="showScheduleForm = true"
                            class="px-4 py-2 text-sm font-medium text-[#a1854f] border border-[#a1854f] rounded-xl hover:bg-[#a1854f]/5 transition-colors"
                        >
                            Add Time Slot
                        </button>
                    </div>

                    <!-- Existing Schedules -->
                    <div v-if="form.schedules.length > 0" class="space-y-2 mb-4">
                        <div
                            v-for="(schedule, index) in form.schedules"
                            :key="index"
                            class="flex items-center justify-between px-4 py-3 bg-gray-50 rounded-xl"
                        >
                            <div>
                                <span class="font-medium text-gray-900">{{ getDayName(schedule.day_of_week) }}</span>
                                <span class="text-gray-500"> at </span>
                                <span class="font-medium text-gray-900">{{ formatTime(schedule.time_of_day) }}</span>
                                <span v-if="schedule.platform" class="ml-2 px-2 py-0.5 text-xs bg-gray-200 text-gray-600 rounded-full capitalize">
                                    {{ schedule.platform }} only
                                </span>
                            </div>
                            <button
                                type="button"
                                @click="removeSchedule(index)"
                                class="text-gray-400 hover:text-red-500 transition-colors"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <p v-else class="text-sm text-gray-500 italic">No schedules added yet</p>

                    <!-- Add Schedule Form -->
                    <div v-if="showScheduleForm" class="mt-4 p-4 border border-gray-200 rounded-xl bg-gray-50">
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Day</label>
                                <select
                                    v-model="newSchedule.day_of_week"
                                    class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#a1854f] focus:border-transparent"
                                >
                                    <option v-for="day in daysOfWeek" :key="day.value" :value="day.value">
                                        {{ day.label }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Time</label>
                                <input
                                    v-model="newSchedule.time_of_day"
                                    type="time"
                                    class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#a1854f] focus:border-transparent"
                                />
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Platform Override (optional)</label>
                            <select
                                v-model="newSchedule.platform"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#a1854f] focus:border-transparent"
                            >
                                <option :value="null">All selected platforms</option>
                                <option v-for="platform in platforms" :key="platform.value" :value="platform.value">
                                    {{ platform.label }} only
                                </option>
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                @click="addSchedule"
                                class="px-4 py-2 text-sm font-medium text-white bg-[#a1854f] rounded-xl hover:bg-[#8a7243] transition-colors"
                            >
                                Add
                            </button>
                            <button
                                type="button"
                                @click="showScheduleForm = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-xl hover:bg-gray-300 transition-colors"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex items-center justify-end gap-4">
                    <Link :href="`/loops/${loop.id}`" class="px-4 py-2.5 text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors">
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 text-sm font-medium text-white bg-[#a1854f] rounded-xl hover:bg-[#8a7243] transition-colors disabled:opacity-50"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
