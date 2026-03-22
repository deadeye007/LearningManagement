// Keep Bootstrap's theme aligned with system preference unless a manual override is stored.
document.addEventListener('DOMContentLoaded', function () {
    var mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

    function getStoredTheme() {
        try {
            return localStorage.getItem('theme');
        } catch (error) {
            return null;
        }
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
    }

    function syncTheme() {
        var storedTheme = getStoredTheme();
        if (storedTheme === 'light' || storedTheme === 'dark') {
            applyTheme(storedTheme);
            return;
        }

        applyTheme(mediaQuery.matches ? 'dark' : 'light');
    }

    syncTheme();

    if (typeof mediaQuery.addEventListener === 'function') {
        mediaQuery.addEventListener('change', syncTheme);
    } else if (typeof mediaQuery.addListener === 'function') {
        mediaQuery.addListener(syncTheme);
    }
});
