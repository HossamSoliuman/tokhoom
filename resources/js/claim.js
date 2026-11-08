/**
 * The site address the visitor is picking on the landing page. The address
 * forms write to it; the preview, steps, address section and plan buttons read it.
 */
export default {
    slug: '',
    kind: 'office',
    state: 'empty',
    source: null,
    loaded: false,

    get name() {
        return this.slug || 'alrashid';
    },

    get host() {
        return `${this.name}.${document.body.dataset.siteDomain}`;
    },

    /**
     * The first form to start fills the store with what the server remembered.
     */
    load(slug, kind, state) {
        if (this.loaded) {
            return;
        }

        Object.assign(this, { slug, kind, state, loaded: true });
    },

    /**
     * Mirrors App\Support\SiteAddress::clean().
     */
    clean(value) {
        let slug = value.trim().toLowerCase();

        if (/[./]/.test(slug)) {
            slug = slug.replace(/^[a-z][a-z0-9+.-]*:\/\//, '').split('/')[0].split('.')[0];
        }

        return slug.replace(/[^a-z0-9-]/g, '').slice(0, 30);
    },

    /**
     * A form's checked result. Answers for an address the visitor has since changed are dropped.
     */
    update({ slug, kind, state }) {
        this.kind = kind;

        if (this.state !== 'checking' || slug === this.slug) {
            this.slug = slug;
            this.state = state;
        }
    },

    signupUrl() {
        return `${document.body.dataset.signupUrl}?${new URLSearchParams({ slug: this.slug, kind: this.kind })}`;
    },

    /**
     * Bring the first address form into view and put the cursor in it.
     */
    focus() {
        const field = document.querySelector('#start input[name="slug"]');

        if (! field) {
            return false;
        }

        field.closest('form').scrollIntoView({ behavior: 'smooth', block: 'center' });
        field.focus({ preventScroll: true });

        return true;
    },

    /**
     * Start buttons: go to signup with a free address, otherwise ask for one first.
     */
    start(event) {
        if (this.state === 'available') {
            event.currentTarget.href = this.signupUrl();

            return;
        }

        if (this.focus()) {
            event.preventDefault();
        }
    },
};
