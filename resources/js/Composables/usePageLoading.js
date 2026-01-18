import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

export function usePageLoading() {
    const isLoading = ref(false);
    let removeStartListener = null;
    let removeFinishListener = null;

    onMounted(() => {
        removeStartListener = router.on('start', () => {
            isLoading.value = true;
        });

        removeFinishListener = router.on('finish', () => {
            isLoading.value = false;
        });
    });

    onUnmounted(() => {
        if (removeStartListener) removeStartListener();
        if (removeFinishListener) removeFinishListener();
    });

    return {
        isLoading,
    };
}
