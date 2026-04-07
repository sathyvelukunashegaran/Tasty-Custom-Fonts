(function (global, factory) {
    const contracts = factory();

    global.TastyFontsAdminContracts = contracts;

    if (typeof module === 'object' && module.exports) {
        module.exports = contracts;
    }
})(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    function slugify(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/[^a-z0-9\-_]+/g, '-')
            .replace(/^-+|-+$/g, '') || 'font';
    }

    function sanitizeFallback(fallback, defaultValue = 'sans-serif') {
        const sanitized = String(fallback || '')
            .trim()
            .replace(/[^a-zA-Z0-9,\- "'`]+/g, '')
            .replace(/\s*,\s*/g, ', ')
            .replace(/\s+/g, ' ')
            .replace(/^[,\s]+|[,\s]+$/g, '');

        return sanitized || defaultValue;
    }

    function escapeFontFamily(family) {
        return String(family || '').replace(/\\/g, '\\\\').replace(/"/g, '\\"');
    }

    function getTabNavigationTargetIndex(key, currentIndex, count) {
        if (typeof currentIndex !== 'number' || typeof count !== 'number' || count < 2 || currentIndex < 0 || currentIndex >= count) {
            return null;
        }

        switch (key) {
            case 'ArrowRight':
            case 'ArrowDown':
                return (currentIndex + 1) % count;
            case 'ArrowLeft':
            case 'ArrowUp':
                return (currentIndex - 1 + count) % count;
            case 'Home':
                return 0;
            case 'End':
                return count - 1;
            default:
                return null;
        }
    }

    return {
        escapeFontFamily,
        getTabNavigationTargetIndex,
        sanitizeFallback,
        slugify,
    };
});
