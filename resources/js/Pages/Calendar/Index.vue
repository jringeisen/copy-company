<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ContentCalendar from '@/Components/Calendar/ContentCalendar.vue';
import CalendarEventModal from '@/Components/Calendar/CalendarEventModal.vue';

const props = defineProps({
    events: Array,
    currentMonth: String,
    brand: Object,
});

const selectedEvent = ref(null);
const showEventModal = ref(false);

const navigateMonth = (direction, targetMonth = null) => {
    let newMonth;

    if (direction === 'today' && targetMonth) {
        newMonth = targetMonth;
    } else {
        const current = new Date(props.currentMonth + '-01');
        if (direction === 'prev') {
            current.setMonth(current.getMonth() - 1);
        } else {
            current.setMonth(current.getMonth() + 1);
        }
        newMonth = current.toISOString().slice(0, 7);
    }

    router.get('/calendar', { month: newMonth }, { preserveState: true });
};

const handleSelectEvent = (event) => {
    selectedEvent.value = event;
    showEventModal.value = true;
};
</script>

<template>
    <Head title="Content Calendar" />

    <AppLayout current-page="calendar">
        <div class="max-w-7xl mx-auto">
            <!-- Legend -->
            <div class="mb-6 flex flex-wrap items-center gap-6 bg-white rounded-2xl border border-[#0b1215]/10 p-4">
                <span class="text-sm font-medium text-[#0b1215]">Content Types:</span>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-[#0b1215]"></span>
                    <span class="text-sm text-[#0b1215]/60">Blog Post</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-[#a1854f]"></span>
                    <span class="text-sm text-[#0b1215]/60">Newsletter</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-pink-500"></span>
                    <span class="text-sm text-[#0b1215]/60">Social (Scheduled)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-green-500"></span>
                    <span class="text-sm text-[#0b1215]/60">Social (Published)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-red-500"></span>
                    <span class="text-sm text-[#0b1215]/60">Social (Failed)</span>
                </div>
            </div>

            <!-- Calendar -->
            <ContentCalendar
                :events="events"
                :current-month="currentMonth"
                @navigate="navigateMonth"
                @select-event="handleSelectEvent"
            />

            <!-- Stats summary -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl border border-[#0b1215]/10 p-4">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-[#0b1215]/5 rounded-xl flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-[#0b1215]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-semibold text-[#0b1215]">
                                {{ events.filter(e => e.type === 'post').length }}
                            </div>
                            <div class="text-sm text-[#0b1215]/50">Blog Posts</div>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-[#0b1215]/10 p-4">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-[#a1854f]/10 rounded-xl flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-[#a1854f]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-semibold text-[#0b1215]">
                                {{ events.filter(e => e.type === 'newsletter').length }}
                            </div>
                            <div class="text-sm text-[#0b1215]/50">Newsletters</div>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-[#0b1215]/10 p-4">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-pink-50 rounded-xl flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-semibold text-[#0b1215]">
                                {{ events.filter(e => e.type === 'social').length }}
                            </div>
                            <div class="text-sm text-[#0b1215]/50">Social Posts</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Event Detail Modal -->
        <CalendarEventModal
            v-if="showEventModal"
            :event="selectedEvent"
            @close="showEventModal = false"
        />
    </AppLayout>
</template>
