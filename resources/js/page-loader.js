// Loading screen for pages without Alpine data (home, change log)
const LOADING_TIMEOUT_MS = 5000;

function pageLoader() {
    return {
        ready: false,

        init() {
            const done = () => { this.ready = true; };
            if (document.readyState === 'complete') {
                done();
                return;
            }
            window.addEventListener('load', done, { once: true });
            setTimeout(done, LOADING_TIMEOUT_MS);
        },
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('pageLoader', pageLoader);
});
