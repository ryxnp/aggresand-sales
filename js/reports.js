// js/reports.js

window.ReportsPage = (() => {

    function initFiltersAndPagination() {
        // Advanced search form → SPA reload
        $('.reports-filter-form').on('submit', function (e) {
            e.preventDefault();
            const query = $(this).serialize();
            if (typeof window.loadPage === 'function') {
                window.loadPage('reports.php', query);
            }
        });

        // Pagination links → SPA reload
        $('.pagination .page-link').on('click', function (e) {
            e.preventDefault();
            const href  = $(this).attr('href') || '';
            const parts = href.split('?');
            const query = parts[1] || '';
            if (typeof window.loadPage === 'function') {
                window.loadPage('reports.php', query);
            }
        });
    }

    function initActions() {
        // View (print-view) button
        $('.reports-btn-view').on('click', function () {
            const query = $(this).data('query') || '';
            const url   = 'pages/reports_print.php' + (query ? ('?' + query) : '');
            window.open(url, '_blank');
        });

        // Extract (CSV) button
        $('.reports-btn-export').on('click', function () {
            const query = $(this).data('query') || '';
            const url   = 'process/reports/export.php' + (query ? ('?' + query) : '');
            window.open(url, '_blank');
        });
    }

    function init() {
        initFiltersAndPagination();
        initActions();
    }

    return { init };

})();

document.addEventListener("DOMContentLoaded", () => {
    const dateInput = document.getElementById("billing_date");
    if (!dateInput) return;

    dateInput.addEventListener("change", () => {
        const date = dateInput.value;
        if (!date) return;

        // Update hash without reloading
        window.location.hash = `reports.php&billing_date=${date}`;
    });
});
