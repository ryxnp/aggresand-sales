// js/scripts.js

$(function () {
    const $spinner = $('#loading-spinner');
    const $content = $('#main-content');

    // Highlight active item in sidebar
    function setActiveSidebar(page) {
        $('.sidebar-link').removeClass('active');
        $('.sidebar-link[data-page="' + page + '"]').addClass('active');
    }

    // Central SPA loader (GLOBAL)
    function loadPage(page, extraQuery) {
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

        // (Later you can add MaterialsPage, AccountsPage, etc.)
    }

    // Sidebar click navigation
    $(document).on('click', '.sidebar-link[data-page]', function (e) {
        const page = $(this).data('page');

        // Logout goes to normal URL
        if (page === 'logout.php') {
            window.location.href = 'logout.php';
            return;
        }

        e.preventDefault();
        loadPage(page);

        // Keep browser URL "clean" (e.g., http://aggressand.com/)
        history.pushState({ page: page }, '', '/');
    });

    // Handle browser back/forward buttons
    window.addEventListener('popstate', function (e) {
        if (e.state && e.state.page) {
            loadPage(e.state.page);
        } else {
            // No page in state: show default welcome
            $content.html(
                '<h2>Welcome!</h2><p>Select a module from the sidebar.</p>'
            );
        }
    });

    // Initial load – choose any default page you like
    loadPage('trans_entry.php');
});
