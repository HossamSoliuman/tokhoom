const DURATION = 3500;

/**
 * In-app toasts. Livewire components dispatch a "toast" browser event with
 * { message, kind } where kind is "ok", "info" or "err".
 */
export default {
    items: [],
    nextId: 1,

    push({ message, kind = 'ok' }) {
        if (!message) {
            return;
        }

        const id = this.nextId++;

        this.items = [{ id, message, kind }];

        setTimeout(() => this.remove(id), DURATION);
    },

    remove(id) {
        this.items = this.items.filter((item) => item.id !== id);
    },
};
