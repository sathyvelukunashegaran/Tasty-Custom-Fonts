(function () {
    var config = window.EtchFontsCanvas || {};
    var stylesheetUrls = Array.isArray(config.stylesheetUrls)
        ? config.stylesheetUrls.filter(Boolean)
        : (config.stylesheetUrl ? [config.stylesheetUrl] : []);

    if (!stylesheetUrls.length) {
        return;
    }

    function getIframeDocument(iframe) {
        if (!iframe || !iframe.contentDocument || !iframe.contentDocument.head) {
            return null;
        }

        return iframe.contentDocument;
    }

    function injectIntoIframe(iframe) {
        var doc = getIframeDocument(iframe);

        if (!doc) {
            return false;
        }

        var existing = Array.from(doc.querySelectorAll('link[data-etch-fonts-runtime="1"]'));

        existing.forEach(function (node, index) {
            if (index >= stylesheetUrls.length && node.parentNode) {
                node.parentNode.removeChild(node);
            }
        });

        stylesheetUrls.forEach(function (stylesheetUrl, index) {
            var current = doc.querySelector('link[data-etch-fonts-runtime="1"][data-etch-fonts-runtime-index="' + index + '"]');

            if (current) {
                if (current.href !== stylesheetUrl) {
                    current.href = stylesheetUrl;
                }

                return;
            }

            var link = doc.createElement('link');
            link.rel = 'stylesheet';
            link.href = stylesheetUrl;
            link.setAttribute('data-etch-fonts-runtime', '1');
            link.setAttribute('data-etch-fonts-runtime-index', String(index));
            doc.head.appendChild(link);
        });

        return true;
    }

    function bindIframe(iframe) {
        if (!iframe || iframe.dataset.etchFontsBound === '1') {
            return;
        }

        iframe.dataset.etchFontsBound = '1';

        iframe.addEventListener('load', function () {
            injectIntoIframe(iframe);
        });

        injectIntoIframe(iframe);
    }

    function bindAllIframes() {
        document.querySelectorAll('iframe').forEach(bindIframe);
    }

    var observer = new MutationObserver(bindAllIframes);
    observer.observe(document.documentElement, {
        childList: true,
        subtree: true
    });

    bindAllIframes();

    var attempts = 0;
    var interval = window.setInterval(function () {
        bindAllIframes();
        attempts += 1;

        if (attempts > 40) {
            window.clearInterval(interval);
        }
    }, 500);
})();
