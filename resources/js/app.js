import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.data('documentShell', () => ({
    sidebarOpen: false,
    settingsOpen: false,
    compactTables: false,
    reduceMotion: false,
    init() {
        try {
            const preferences = JSON.parse(localStorage.getItem('ed-document-preferences') || '{}');
            this.compactTables = preferences.compactTables === true;
            this.reduceMotion = preferences.reduceMotion === true;
        } catch { /* Browser storage is optional. */ }
        this.$watch('settingsOpen', open => {
            if (open) {
                this.previousFocus = document.activeElement;
                this.$nextTick(() => requestAnimationFrame(() => this.$refs.settingsPanel?.focus()));
            } else this.previousFocus?.focus();
        });
    },
    savePreferences() {
        try {
            localStorage.setItem('ed-document-preferences', JSON.stringify({ compactTables: this.compactTables, reduceMotion: this.reduceMotion }));
        } catch { /* Preferences still work for the current page. */ }
    },
}));
document.addEventListener('keydown', event => {
    if (event.key === '/' && !event.ctrlKey && !event.metaKey && !event.target.closest('input, textarea, select, [contenteditable]')) {
        const search = document.querySelector('.ed-global-search input');
        if (search) { event.preventDefault(); search.focus(); }
    }
    if (event.key !== 'Tab') return;
    const panel = document.querySelector('.ed-dialog-backdrop:not([style*="display: none"]) [role="dialog"]');
    if (!panel || panel.closest('[x-cloak]')) return;
    const controls = [...panel.querySelectorAll('button, input, select, a[href]')].filter(element => !element.disabled && element.offsetParent !== null);
    const first = controls[0], last = controls.at(-1);
    if (event.shiftKey && (document.activeElement === first || document.activeElement === panel)) { event.preventDefault(); last?.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
});
Alpine.start();
