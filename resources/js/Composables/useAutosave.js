import { ref, watch, onMounted, onUnmounted } from 'vue';

export function useAutosave(options = {}) {
    const {
        key = 'autosave',
        debounceMs = 30000, // 30 seconds default
        onSave = async () => {},
        getData = () => ({}),
    } = options;

    const status = ref('idle'); // 'idle' | 'saving' | 'saved' | 'error'
    const lastSaved = ref(null);
    const error = ref(null);
    const hasUnsavedChanges = ref(false);

    let debounceTimer = null;
    let lastDataHash = null;

    // Simple hash function to detect changes
    const hashData = (data) => {
        return JSON.stringify(data);
    };

    // Save to localStorage as backup
    const saveToLocalStorage = (data) => {
        try {
            localStorage.setItem(`${key}_backup`, JSON.stringify({
                data,
                timestamp: Date.now(),
            }));
        } catch (e) {
            console.warn('Failed to save to localStorage:', e);
        }
    };

    // Get backup from localStorage
    const getLocalStorageBackup = () => {
        try {
            const stored = localStorage.getItem(`${key}_backup`);
            if (stored) {
                return JSON.parse(stored);
            }
        } catch (e) {
            console.warn('Failed to read from localStorage:', e);
        }
        return null;
    };

    // Clear localStorage backup
    const clearLocalStorageBackup = () => {
        try {
            localStorage.removeItem(`${key}_backup`);
        } catch (e) {
            console.warn('Failed to clear localStorage:', e);
        }
    };

    // Perform the actual save
    const save = async (force = false) => {
        const data = getData();
        const currentHash = hashData(data);

        // Skip if no changes (unless forced)
        if (!force && currentHash === lastDataHash) {
            return;
        }

        status.value = 'saving';
        error.value = null;

        try {
            await onSave(data);
            lastDataHash = currentHash;
            lastSaved.value = new Date();
            status.value = 'saved';
            hasUnsavedChanges.value = false;
            clearLocalStorageBackup();

            // Reset to idle after 3 seconds
            setTimeout(() => {
                if (status.value === 'saved') {
                    status.value = 'idle';
                }
            }, 3000);
        } catch (e) {
            status.value = 'error';
            error.value = e.message || 'Failed to save';
            // Save to localStorage as backup on error
            saveToLocalStorage(data);
        }
    };

    // Schedule a debounced save
    const scheduleSave = () => {
        hasUnsavedChanges.value = true;

        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }

        debounceTimer = setTimeout(() => {
            save();
        }, debounceMs);
    };

    // Mark content as changed (triggers debounced save)
    const markChanged = () => {
        const data = getData();
        saveToLocalStorage(data);
        scheduleSave();
    };

    // Force immediate save
    const saveNow = () => {
        if (debounceTimer) {
            clearTimeout(debounceTimer);
            debounceTimer = null;
        }
        return save(true);
    };

    // Check for unsaved backup on mount
    const checkForBackup = () => {
        const backup = getLocalStorageBackup();
        if (backup) {
            const ageMinutes = (Date.now() - backup.timestamp) / 1000 / 60;
            // Only consider backups less than 24 hours old
            if (ageMinutes < 1440) {
                return backup;
            } else {
                clearLocalStorageBackup();
            }
        }
        return null;
    };

    // Warn before leaving with unsaved changes
    const handleBeforeUnload = (e) => {
        if (hasUnsavedChanges.value) {
            e.preventDefault();
            e.returnValue = '';
        }
    };

    onMounted(() => {
        window.addEventListener('beforeunload', handleBeforeUnload);
        // Initialize hash with current data
        lastDataHash = hashData(getData());
    });

    onUnmounted(() => {
        window.removeEventListener('beforeunload', handleBeforeUnload);
        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }
    });

    return {
        status,
        lastSaved,
        error,
        hasUnsavedChanges,
        markChanged,
        saveNow,
        checkForBackup,
        clearLocalStorageBackup,
    };
}
