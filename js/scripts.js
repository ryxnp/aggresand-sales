// js/scripts.js

$(function () {
    const $spinner = $('#loading-spinner');
    const $content = $('#main-content');

    const ALLOWED_PAGES = [
        'trans_entry.php',
        'deliveries.php',
        'reports.php',
        'contractor.php',
        'site.php',
        'soa.php',
        'materials.php',
        'truck.php',
        'company.php',
        'accounts.php',
        'backup.php',
    ];

    // Highlight active item in sidebar
    function setActiveSidebar(page) {
        $('.sidebar-link').removeClass('active');
        $('.sidebar-link[data-page="' + page + '"]').addClass('active');
    }

    // Central SPA loader (GLOBAL)
    function loadPage(page, extraQuery) {
        if (!page || !ALLOWED_PAGES.includes(page)) {
            page = 'trans_entry.php';
        }

        $spinner.show();

        let url = 'loader.php?page=' + encodeURIComponent(page);
        if (extraQuery) {
            // extraQuery can be '?q=...' or 'q=...'
            if (extraQuery.charAt(0) === '?') {
                extraQuery = extraQuery.substring(1);
            }
            if (extraQuery.length > 0) {
                url += '&' + extraQuery;
            }
        }

        $.get(url, function (html) {
            $content.html(html);
            $spinner.hide();

            setActiveSidebar(page);
            initPageScripts(page);
        }).fail(function () {
            $spinner.hide();
            $content.html(
                '<div class="alert alert-danger mt-3">Failed to load page.</div>'
            );
        });
    }

    // Make globally accessible for other JS files (company.js, etc.)
    window.loadPage = loadPage;

    // Call page-specific initializers
    function initPageScripts(page) {
        // Company
        if (page === 'company.php' &&
            window.CompanyPage &&
            typeof window.CompanyPage.init === 'function') {
            window.CompanyPage.init();
        }

        // Contractor
        if (page === 'contractor.php' &&
            window.ContractorPage &&
            typeof window.ContractorPage.init === 'function') {
            window.ContractorPage.init();
        }

        // Site
        if (page === 'site.php' &&
            window.SitePage &&
            typeof window.SitePage.init === 'function') {
            window.SitePage.init();
        }

        // Truck
        if (page === 'truck.php' &&
            window.TruckPage &&
            typeof window.TruckPage.init === 'function') {
            window.TruckPage.init();
        }

        if (page === 'trans_entry.php' &&
            window.TransEntryPage &&
            typeof window.TransEntryPage.init === 'function') {
            window.TransEntryPage.init();
        }

        if (page === 'materials.php' &&
            window.MaterialsPage &&
            typeof window.MaterialsPage.init === 'function') {
            window.MaterialsPage.init();
        }

        if (page === 'reports.php' &&
            window.ReportsPage &&
            typeof window.ReportsPage.init === 'function') {
            window.ReportsPage.init();
        }

        if (page === 'deliveries.php' &&
            window.DeliveriesPage &&
            typeof window.DeliveriesPage.init === 'function') {
            window.DeliveriesPage.init();
        }

        // SOA
        if (page === 'soa.php' &&
            window.SoaPage &&
            typeof window.SoaPage.init === 'function') {
            window.SoaPage.init();
        }
    }

    function getInitialPage() {
        const hash = window.location.hash;
        if (hash && hash.startsWith('#')) {
            const page = hash.substring(1);
            if (ALLOWED_PAGES.includes(page)) {
                return page;
            }
        }
        // Default module
        return 'trans_entry.php';
    }

    // Sidebar click navigation
    $(document).on('click', '.sidebar-link[data-page]', function (e) {
        const page = $(this).data('page');

        if (page === 'logout.php') {
            window.location.href = 'logout.php';
            return;
        }

        e.preventDefault();
        loadPage(page);

        // Keep hash in sync so refresh/redirect knows what to load
        history.pushState({ page: page }, '', window.location.pathname + '#' + page);
    });

    // Handle browser back/forward buttons
    window.addEventListener('popstate', function (e) {
        if (e.state && e.state.page) {
            loadPage(e.state.page);
        } else {
            const page = getInitialPage();
            loadPage(page);
        }
    });

    // Initial load – respect the hash from PHP redirects (/main.php#company.php)
    const initialPage = getInitialPage();
    loadPage(initialPage);
    history.replaceState({ page: initialPage }, '', window.location.pathname + window.location.hash);

    // Auto-dismiss alerts after 3 seconds
setTimeout(() => {
    $('.alert').alert('close');
}, 3000);
});
