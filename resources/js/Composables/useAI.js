import { ref } from 'vue';
import axios from 'axios';

export function useAI() {
    const isLoading = ref(false);
    const error = ref(null);

    /**
     * Factory function to create AI methods with consistent error handling.
     * @param {string} endpoint - The API endpoint to call
     * @param {string} errorMsg - Default error message if request fails
     * @returns {Function} Async function that makes the AI request
     */
    const createAIMethod = (endpoint, errorMsg) => async (params) => {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await axios.post(endpoint, params);
            return response.data.content;
        } catch (e) {
            error.value = e.response?.data?.error || errorMsg;
            throw e;
        } finally {
            isLoading.value = false;
        }
    };

    // Content generation methods
    const generateDraft = async (title, bullets = null) =>
        createAIMethod('/ai/draft', 'Failed to generate draft')({ title, bullets });

    const polishWriting = async (content) =>
        createAIMethod('/ai/polish', 'Failed to polish content')({ content });

    const continueWriting = async (content) =>
        createAIMethod('/ai/continue', 'Failed to continue writing')({ content });

    const suggestOutline = async (title, notes = null) =>
        createAIMethod('/ai/outline', 'Failed to generate outline')({ title, notes });

    const changeTone = async (content, tone) =>
        createAIMethod('/ai/tone', 'Failed to change tone')({ content, tone });

    const makeItShorter = async (content) =>
        createAIMethod('/ai/shorter', 'Failed to shorten content')({ content });

    const makeItLonger = async (content) =>
        createAIMethod('/ai/longer', 'Failed to expand content')({ content });

    const askQuestion = async (content, question) =>
        createAIMethod('/ai/ask', 'Failed to process question')({ content, question });

    // Selection-based AI tools
    const fixGrammar = async (text) =>
        createAIMethod('/ai/selection/fix-grammar', 'Failed to fix grammar')({ text });

    const simplify = async (text) =>
        createAIMethod('/ai/selection/simplify', 'Failed to simplify text')({ text });

    const rephrase = async (text) =>
        createAIMethod('/ai/selection/rephrase', 'Failed to rephrase text')({ text });

    const toList = async (text) =>
        createAIMethod('/ai/selection/to-list', 'Failed to convert to list')({ text });

    const addExamples = async (text) =>
        createAIMethod('/ai/selection/add-examples', 'Failed to add examples')({ text });

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
        // Selection-based tools
        fixGrammar,
        simplify,
        rephrase,
        toList,
        addExamples,
    };
}
