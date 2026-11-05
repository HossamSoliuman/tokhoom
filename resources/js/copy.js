/**
 * $copy(text, message): copy text to the clipboard and confirm with a toast.
 */
export default (Alpine) => async (text, message = 'تم النسخ') => {
    try {
        await navigator.clipboard.writeText(text);
        Alpine.store('toasts').push({ message, kind: 'info' });
    } catch (error) {
        Alpine.store('toasts').push({ message: 'تعذّر النسخ، انسخ النص يدوياً', kind: 'err' });
    }
};
