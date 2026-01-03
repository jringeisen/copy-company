import { ref } from 'vue';
import axios from 'axios';

export function useAI() {
    const isLoading = ref(false);
    const error = ref(null);

    const generateDraft = async (title, bullets = null) => {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await axios.post('/ai/draft', { title, bullets });
            return response.data.content;
        } catch (e) {
            error.value = e.response?.data?.error || 'Failed to generate draft';
            throw e;
        } finally {
            isLoading.value = false;
        }
    };

    const polishWriting = async (content) => {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await axios.post('/ai/polish', { content });
            return response.data.content;
        } catch (e) {
            error.value = e.response?.data?.error || 'Failed to polish content';
            throw e;
        } finally {
            isLoading.value = false;
        }
    };

    const continueWriting = async (content) => {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await axios.post('/ai/continue', { content });
            return response.data.content;
        } catch (e) {
            error.value = e.response?.data?.error || 'Failed to continue writing';
            throw e;
        } finally {
            isLoading.value = false;
        }
    };

    const suggestOutline = async (title, notes = null) => {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await axios.post('/ai/outline', { title, notes });
            return response.data.content;
        } catch (e) {
            error.value = e.response?.data?.error || 'Failed to generate outline';
            throw e;
        } finally {
            isLoading.value = false;
        }
    };

    const changeTone = async (content, tone) => {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await axios.post('/ai/tone', { content, tone });
            return response.data.content;
        } catch (e) {
            error.value = e.response?.data?.error || 'Failed to change tone';
            throw e;
        } finally {
            isLoading.value = false;
        }
    };

    const makeItShorter = async (content) => {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await axios.post('/ai/shorter', { content });
            return response.data.content;
        } catch (e) {
            error.value = e.response?.data?.error || 'Failed to shorten content';
            throw e;
        } finally {
            isLoading.value = false;
        }
    };

    const makeItLonger = async (content) => {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await axios.post('/ai/longer', { content });
            return response.data.content;
        } catch (e) {
            error.value = e.response?.data?.error || 'Failed to expand content';
            throw e;
        } finally {
            isLoading.value = false;
        }
    };

    const askQuestion = async (content, question) => {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await axios.post('/ai/ask', { content, question });
            return response.data.content;
        } catch (e) {
            error.value = e.response?.data?.error || 'Failed to process question';
            throw e;
        } finally {
            isLoading.value = false;
        }
    };

    return {
        isLoading,
        error,
        generateDraft,
        polishWriting,
        continueWriting,
        suggestOutline,
        changeTone,
        makeItShorter,
        makeItLonger,
        askQuestion,
    };
}
