(function () {
    const config = window.TastyFontsCanvas || {};
    const canvasContracts = window.TastyFontsCanvasContracts || {};
    const normalizeStylesheetUrls = typeof canvasContracts.normalizeStylesheetUrls === 'function'
        ? canvasContracts.normalizeStylesheetUrls
        : (runtimeConfig) => Array.isArray(runtimeConfig.stylesheetUrls)
            ? runtimeConfig.stylesheetUrls.filter(Boolean)
            : (runtimeConfig.stylesheetUrl ? [runtimeConfig.stylesheetUrl] : []);
    const getIframeDocument = typeof canvasContracts.getIframeDocument === 'function'
        ? canvasContracts.getIframeDocument
        : (iframe) => {
            if (!iframe || !iframe.contentDocument || !iframe.contentDocument.head) {
                return null;
            }

            return iframe.contentDocument;
        };
    const syncIframeStylesheets = typeof canvasContracts.syncIframeStylesheets === 'function'
        ? canvasContracts.syncIframeStylesheets
        : (doc, runtimeStylesheetUrls) => {
            let existingIndex = 0;

            for (const node of doc.querySelectorAll('link[data-tasty-fonts-runtime="1"]')) {
                if (existingIndex >= runtimeStylesheetUrls.length && node.parentNode) {
                    node.parentNode.removeChild(node);
                }

                existingIndex += 1;
            }

            for (const [index, stylesheetUrl] of runtimeStylesheetUrls.entries()) {
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
        };
    const stylesheetUrls = normalizeStylesheetUrls(config);

    if (!stylesheetUrls.length) {
        return;
    }

    const injectIntoIframe = (iframe) => {
        const doc = getIframeDocument(iframe);

        if (!doc) {
            return false;
        }

        return syncIframeStylesheets(doc, stylesheetUrls);
    };

    const bindIframe = (iframe) => {
        if (!iframe || iframe.dataset.tastyFontsBound === '1') {
            return;
        }

        iframe.dataset.tastyFontsBound = '1';

        iframe.addEventListener('load', () => {
            injectIntoIframe(iframe);
        });

        injectIntoIframe(iframe);
    };

    const bindAllIframes = () => {
        for (const iframe of document.querySelectorAll('iframe')) {
            bindIframe(iframe);
        }
    };

    let bindAllIframesTimeout = 0;

    const scheduleBindAllIframes = () => {
        window.clearTimeout(bindAllIframesTimeout);
        bindAllIframesTimeout = window.setTimeout(bindAllIframes, 100);
    };

    if (document.body) {
        const observer = new MutationObserver(scheduleBindAllIframes);
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    bindAllIframes();
})();
