import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import toasts from './toasts';
import copy from './copy';
import rangeCalendar from './range-calendar';
import locationPicker from './location-picker';

Alpine.store('toasts', toasts);

Alpine.store('shell', {
    switcher: false,
    notifications: false,
    unread: false,
});

Alpine.magic('copy', () => copy(Alpine));

Alpine.data('rangeCalendar', rangeCalendar);
Alpine.data('locationPicker', locationPicker);

window.addEventListener('toast', (event) => Alpine.store('toasts').push(event.detail));

Livewire.start();
