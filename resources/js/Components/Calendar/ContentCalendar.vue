<script setup>
import { computed } from 'vue';

const props = defineProps({
    events: {
        type: Array,
        default: () => [],
    },
    currentMonth: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(['navigate', 'selectEvent']);

// Parse the current month
const currentDate = computed(() => {
    const [year, month] = props.currentMonth.split('-');
    return new Date(parseInt(year), parseInt(month) - 1, 1);
});

const monthLabel = computed(() => {
    return currentDate.value.toLocaleDateString('en-US', {
        month: 'long',
        year: 'numeric',
    });
});

// Generate calendar days (42 days = 6 weeks)
const calendarDays = computed(() => {
    const days = [];
    const year = currentDate.value.getFullYear();
    const month = currentDate.value.getMonth();

    // Get first day of the month and what day of week it is
    const firstDayOfMonth = new Date(year, month, 1);
    const startDayOfWeek = firstDayOfMonth.getDay();

    // Get last day of the month
    const lastDayOfMonth = new Date(year, month + 1, 0);
    const daysInMonth = lastDayOfMonth.getDate();

    // Get today for comparison
    const today = new Date();
    const todayStr = today.toISOString().slice(0, 10);

    // Fill in days from previous month
    const prevMonth = new Date(year, month, 0);
    const daysInPrevMonth = prevMonth.getDate();

    for (let i = startDayOfWeek - 1; i >= 0; i--) {
        const dayNumber = daysInPrevMonth - i;
        const date = new Date(year, month - 1, dayNumber);
        days.push({
            date: date.toISOString().slice(0, 10),
            dayNumber,
            isCurrentMonth: false,
            isToday: false,
        });
    }

    // Fill in days of current month
    for (let day = 1; day <= daysInMonth; day++) {
        const date = new Date(year, month, day);
        const dateStr = date.toISOString().slice(0, 10);
        days.push({
            date: dateStr,
            dayNumber: day,
            isCurrentMonth: true,
            isToday: dateStr === todayStr,
        });
    }

    // Fill in remaining days from next month
    const remainingDays = 42 - days.length;
    for (let day = 1; day <= remainingDays; day++) {
        const date = new Date(year, month + 1, day);
        days.push({
            date: date.toISOString().slice(0, 10),
            dayNumber: day,
            isCurrentMonth: false,
            isToday: false,
        });
    }

    return days;
});

const getEventsForDay = (date) => {
    return props.events.filter(e => e.date === date);
};

const getEventClasses = (event) => {
    if (event.type === 'post') {
        return 'bg-blue-100 text-blue-800 hover:bg-blue-200';
    } else if (event.type === 'newsletter') {
        return 'bg-purple-100 text-purple-800 hover:bg-purple-200';
    } else if (event.type === 'social') {
        return 'bg-pink-100 text-pink-800 hover:bg-pink-200';
    }
    return 'bg-gray-100 text-gray-800';
};

const goToToday = () => {
    const today = new Date();
    const month = today.toISOString().slice(0, 7);
    emit('navigate', 'today', month);
};
</script>

<template>
    <div class="bg-white rounded-lg shadow">
        <!-- Header with month navigation -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200">
            <button
                @click="emit('navigate', 'prev')"
                class="p-2 rounded-lg hover:bg-gray-100 transition"
            >
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <div class="flex items-center space-x-4">
                <h2 class="text-lg font-semibold text-gray-900">{{ monthLabel }}</h2>
                <button
                    @click="goToToday"
                    class="px-3 py-1 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                >
                    Today
                </button>
            </div>

            <button
                @click="emit('navigate', 'next')"
                class="p-2 rounded-lg hover:bg-gray-100 transition"
            >
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>

        <!-- Day headers -->
        <div class="grid grid-cols-7 border-b border-gray-200">
            <div
                v-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']"
                :key="day"
                class="p-3 text-center text-sm font-medium text-gray-500 bg-gray-50"
            >
                {{ day }}
            </div>
        </div>

        <!-- Calendar grid -->
        <div class="grid grid-cols-7">
            <div
                v-for="day in calendarDays"
                :key="day.date"
                :class="[
                    'min-h-28 p-2 border-b border-r border-gray-100',
                    day.isCurrentMonth ? 'bg-white' : 'bg-gray-50',
                    day.isToday && 'bg-blue-50'
                ]"
            >
                <!-- Day number -->
                <div
                    :class="[
                        'text-sm mb-1',
                        day.isToday ? 'font-bold text-blue-600' : day.isCurrentMonth ? 'text-gray-900' : 'text-gray-400'
                    ]"
                >
                    {{ day.dayNumber }}
                </div>

                <!-- Event chips -->
                <div class="space-y-1">
                    <div
                        v-for="event in getEventsForDay(day.date).slice(0, 3)"
                        :key="event.id"
                        @click="emit('selectEvent', event)"
                        :class="getEventClasses(event)"
                        class="text-xs px-2 py-1 rounded truncate cursor-pointer transition"
                    >
                        {{ event.title }}
                    </div>
                    <div
                        v-if="getEventsForDay(day.date).length > 3"
                        class="text-xs text-gray-500 px-2"
                    >
                        +{{ getEventsForDay(day.date).length - 3 }} more
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
