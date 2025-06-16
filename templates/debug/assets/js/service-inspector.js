document.addEventListener("DOMContentLoaded", function () {
    const buttons = document.querySelectorAll('.chip-button');
    const details = document.querySelectorAll('.accordion-item');
    const searchInput = document.getElementById("search");
    let activeFilterType = null;

    const accordion = document.querySelector('.accordion');
    const originalItems = Array.from(accordion.children); // your data set

    let sortBy = 'name';          // 'name' or 'type'
    let sortDirection = 'asc';   // 'asc' or 'desc'

    // SORT-CHIP-FILTER HANDLING
    const sortNameBtn = document.querySelector('.chip-sort-name');
    const sortTypeBtn = document.querySelector('.chip-sort-type');

    sortNameBtn.addEventListener('click', () => {
        if (sortBy === 'name') {
            // Toggle direction
            sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            sortBy = 'name';
            sortDirection = 'asc';
            sortNameBtn.classList.add('active');
            sortTypeBtn.classList.remove('active');
        }
        filterAccordion();
    });

    sortTypeBtn.addEventListener('click', () => {
        if (sortBy === 'type') {
            // Toggle direction
            sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            sortBy = 'type';
            sortDirection = 'asc';
            sortTypeBtn.classList.add('active');
            sortNameBtn.classList.remove('active');
        }
        filterAccordion();
        updateSortIcons();
    });

    // CHIP-FILTER HANDLING (nur für Typ-Filter!)
    const filterButtons = document.querySelectorAll('.chip-converters, .chip-populator, .chip-factories');

    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            if (button.classList.contains('active')) {
                button.classList.remove('active');
                activeFilterType = null;
            } else {
                filterButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                activeFilterType = getTypeFromClass(button.classList);
            }
            filterAccordion();
            updateSortIcons();
        });
    });

    // RESET BUTTON HANDLING
    const resetButton = document.querySelector('.chip-reset');

    resetButton.addEventListener('click', () => {
        // Chips deaktivieren
        document.querySelectorAll('.chip-button.active').forEach(btn => btn.classList.remove('active'));

        // Zustände zurücksetzen
        activeFilterType = null;
        searchInput.value = '';
        sortBy = 'name';
        sortDirection = 'asc';

        // Ansicht neu berechnen
        filterAccordion();
        updateSortIcons();
    });

    // LIVE-SUCHE
    searchInput.addEventListener("keyup", filterAccordion);

    document.querySelectorAll('.class-copy').forEach(btn => {
        btn.addEventListener('click', () => {
            const className = btn.getAttribute('data-class');
            navigator.clipboard.writeText(className).then(() => {
                btn.innerHTML = '<i class="material-icons">check</i>';
                setTimeout(() => {
                    btn.innerHTML = '<i class="material-icons">content_copy</i>';
                }, 1500);
            });
        });
    });

    function getTypeFromClass(classList) {
        if (classList.contains('chip-converters')) return 'converter';
        if (classList.contains('chip-populator')) return 'populator';
        if (classList.contains('chip-factories')) return 'factory';
        return null;
    }

    function filterAccordion() {
        const input = searchInput.value.toLowerCase();
        const accordion = document.querySelector('.accordion');

        let items = Array.from(originalItems);

        items = items.filter(detail => {
            const summary = detail.querySelector("summary");
            const summaryText = summary.textContent.toLowerCase();
            const type = summary.getAttribute("type");

            const matchesSearch = summaryText.includes(input);
            const matchesType = !activeFilterType || type === activeFilterType;

            return matchesSearch && matchesType;
        });

        items.sort((a, b) => {
            const aSummary = a.querySelector('summary');
            const bSummary = b.querySelector('summary');

            let compare;
            if (sortBy === 'name') {
                compare = aSummary.textContent.localeCompare(bSummary.textContent);
            } else if (sortBy === 'type') {
                compare = aSummary.getAttribute('type').localeCompare(bSummary.getAttribute('type'));
            }

            return sortDirection === 'asc' ? compare : -compare;
        });

        accordion.innerHTML = '';
        items.forEach(item => accordion.appendChild(item));

        updateAccordionCount();
    }

    function updateSortIcons() {
        sortNameBtn.innerHTML = `<i class="material-icons">sort_by_alpha</i>Name ${sortBy === 'name' ? (sortDirection === 'asc' ? '▲' : '▼') : ''}`;
        sortTypeBtn.innerHTML = `<i class="material-icons">category</i>Type ${sortBy === 'type' ? (sortDirection === 'asc' ? '▲' : '▼') : ''}`;
    }

    function updateAccordionCount() {
        const visible = document.querySelectorAll('.accordion-item').length;
        const shown = Array.from(document.querySelectorAll('.accordion-item'))
            .filter(item => item.style.display !== "none").length;

        document.getElementById('accordion-count').textContent =
            `Displayed services: ${shown} out of ${visible} total`;
    }

    // internal links handling
    document.querySelectorAll('.accordion a[href^="#"]').forEach(link => {
        link.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href').substring(1);
            const targetSummary = document.getElementById(targetId);

            if (targetSummary) {
                e.preventDefault();

                // Filter zurücksetzen
                activeFilterType = null;
                buttons.forEach(btn => btn.classList.remove('active'));
                searchInput.value = '';

                // Alles anzeigen
                filterAccordion();

                const targetDetail = targetSummary.closest('details');
                if (targetDetail && !targetDetail.open) {
                    targetDetail.open = true;
                }

                // Sanft scrollen
                setTimeout(() => {
                    targetSummary.scrollIntoView({behavior: 'smooth', block: 'start'});
                    targetSummary.focus({preventScroll: true});
                }, 100);
            }
        });
    });

    // DATUM setzen
    const heute = new Date();
    const options = {day: '2-digit', month: 'long', year: 'numeric'};
    document.getElementById("datum").textContent = heute.toLocaleDateString("de-DE", options);

    // Init
    filterAccordion();
});

