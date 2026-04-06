(function (global, factory) {
    const contracts = factory();

    global.TastyFontsCanvasContracts = contracts;

    if (typeof module === 'object' && module.exports) {
        module.exports = contracts;
    }
})(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    function normalizeStylesheetUrls(config) {
        return Array.isArray(config.stylesheetUrls)
            ? config.stylesheetUrls.filter(Boolean)
            : (config.stylesheetUrl ? [config.stylesheetUrl] : []);
    }

    function getIframeDocument(iframe) {
        if (!iframe || !iframe.contentDocument || !iframe.contentDocument.head) {
            return null;
        }

        return iframe.contentDocument;
    }

    function syncIframeStylesheets(doc, stylesheetUrls) {
        let existingIndex = 0;

        for (const node of doc.querySelectorAll('link[data-tasty-fonts-runtime="1"]')) {
            if (existingIndex >= stylesheetUrls.length && node.parentNode) {
                node.parentNode.removeChild(node);
            }

            existingIndex += 1;
        }

        for (const [index, stylesheetUrl] of stylesheetUrls.entries()) {
            const current = doc.querySelector(`link[data-tasty-fonts-runtime="1"][data-tasty-fonts-runtime-index="${index}"]`);

            if (current) {
                if (current.href !== stylesheetUrl) {
                    current.href = stylesheetUrl;
                }

                continue;
            }

            const link = doc.createElement('link');
            link.rel = 'stylesheet';
            link.href = stylesheetUrl;
            link.setAttribute('data-tasty-fonts-runtime', '1');
            link.setAttribute('data-tasty-fonts-runtime-index', String(index));
            doc.head.appendChild(link);
        }

        return true;
    }

    return {
        getIframeDocument,
        normalizeStylesheetUrls,
        syncIframeStylesheets,
    };
});
