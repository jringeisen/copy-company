import { ref, watch, onMounted } from 'vue';

const STORAGE_KEY = 'dark-mode';

// Global state shared across all components
const isDark = ref(false);

export function useDarkMode() {
    /**
     * Initialize dark mode from localStorage or system preference
     */
    const initDarkMode = () => {
        const stored = localStorage.getItem(STORAGE_KEY);

        if (stored !== null) {
            isDark.value = stored === 'true';
        } else {
            // Check system preference
            isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
        }

        applyDarkMode();
    };

    /**
     * Apply dark mode class to the document
     */
    const applyDarkMode = () => {
        if (isDark.value) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    };

    /**
     * Toggle dark mode
     */
    const toggleDark = () => {
        isDark.value = !isDark.value;
        localStorage.setItem(STORAGE_KEY, isDark.value.toString());
        applyDarkMode();
    };

    /**
     * Set dark mode explicitly
     */
    const setDark = (value) => {
        isDark.value = value;
        localStorage.setItem(STORAGE_KEY, value.toString());
        applyDarkMode();
    };

    // Watch for changes to sync across components
    watch(isDark, applyDarkMode);

    // Initialize on mount
    onMounted(() => {
        initDarkMode();

        // Listen for system preference changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            // Only auto-update if user hasn't explicitly set a preference
            if (localStorage.getItem(STORAGE_KEY) === null) {
                isDark.value = e.matches;
            }
        });
    });

    return {
        isDark,
        toggleDark,
        setDark,
        initDarkMode,
    };
}
