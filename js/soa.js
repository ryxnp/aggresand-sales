// js/soa.js
window.SOAPage = (() => {

    function initCreate() {
        $('#soa-create-form').on('submit', function (e) {
            e.preventDefault();

            $.post('process/soa/create.php', $(this).serialize(), function (res) {
                if (res.success) {
                    window.loadPage('soa.php');
                } else {
                    alert(res.message);
                }
            }, 'json');
        });
    }

    function initFinalize() {
        $('.soa-btn-finalize').on('click', function () {
            if (!confirm('Finalize this SOA? This cannot be undone.')) return;

            const id = $(this).data('id');

            $.post('process/soa/finalize.php', { soa_id: id }, function (res) {
                if (res.success) {
                    window.loadPage('soa.php');
                } else {
                    alert(res.message);
                }
            }, 'json');
        });
    }

    function init() {
        initCreate();
        initFinalize();
    }

    return { init };
})();
