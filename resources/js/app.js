import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import toasts from './toasts';
import rangeCalendar from './range-calendar';
import locationPicker from './location-picker';

Alpine.store('toasts', toasts);

Alpine.store('shell', {
    switcher: false,
    notifications: false,
    unread: false,
});

Alpine.magic('copy', () => async (text, message = 'تم النسخ') => {
    try {
        await navigator.clipboard.writeText(text);
        Alpine.store('toasts').push({ message, kind: 'info' });
    } catch (error) {
        Alpine.store('toasts').push({ message: 'تعذّر النسخ، انسخ النص يدوياً', kind: 'err' });
    }
});

Alpine.data('rangeCalendar', rangeCalendar);
Alpine.data('locationPicker', locationPicker);

window.addEventListener('toast', (event) => Alpine.store('toasts').push(event.detail));

Livewire.start();
