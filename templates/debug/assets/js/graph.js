document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('details.accordion-item').forEach((details, index) => {
        details.addEventListener('toggle', function () {
            if (details.open) {
                const mermaidEl = details.querySelector('.mermaid:not([data-rendered])');
                if (mermaidEl) {
                    mermaid.initialize({ startOnLoad: false });
                    mermaid.init(undefined, mermaidEl);
                    mermaidEl.setAttribute('data-rendered', 'true');
                }
            }
        });
    });
});
