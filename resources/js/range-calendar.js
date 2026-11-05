const MONTHS = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];

const pad = (number) => String(number).padStart(2, '0');

const toIso = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;

const fromIso = (value) => {
    const [year, month, day] = value.split('-').map(Number);

    return new Date(year, month - 1, day);
};

/**
 * Month calendar for picking a date range. The first tap sets the start, the
 * second sets the end; future days are disabled. "apply" sends the range to
 * the Livewire component through the given method.
 */
export default ({ from, to, today, method = 'applyRange' }) => ({
    start: from,
    end: to,
    pickingEnd: false,
    view: fromIso(to),
    today,

    get title() {
        return `${MONTHS[this.view.getMonth()]} ${this.view.getFullYear()}`;
    },

    get cells() {
        const year = this.view.getFullYear();
        const month = this.view.getMonth();
        const leading = new Date(year, month, 1).getDay();
        const days = new Date(year, month + 1, 0).getDate();
        const cells = Array.from({ length: leading }, (_, index) => ({ key: `blank-${index}`, blank: true }));

        for (let day = 1; day <= days; day++) {
            const iso = toIso(new Date(year, month, day));

            cells.push({
                key: iso,
                day,
                iso,
                future: iso > this.today,
                today: iso === this.today,
                edge: iso === this.start || iso === this.end,
                inRange: iso >= this.start && iso <= this.end,
            });
        }

        return cells;
    },

    get canGoForward() {
        const next = new Date(this.view.getFullYear(), this.view.getMonth() + 1, 1);

        return toIso(next) <= this.today;
    },

    previousMonth() {
        this.view = new Date(this.view.getFullYear(), this.view.getMonth() - 1, 1);
    },

    nextMonth() {
        if (this.canGoForward) {
            this.view = new Date(this.view.getFullYear(), this.view.getMonth() + 1, 1);
        }
    },

    pick(cell) {
        if (cell.blank || cell.future) {
            return;
        }

        if (!this.pickingEnd) {
            this.start = cell.iso;
            this.end = cell.iso;
            this.pickingEnd = true;

            return;
        }

        if (cell.iso < this.start) {
            this.end = this.start;
            this.start = cell.iso;
        } else {
            this.end = cell.iso;
        }

        this.pickingEnd = false;
    },

    apply() {
        this.$wire[method](this.start, this.end);
    },
});
