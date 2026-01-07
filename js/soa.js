window.SoaPage = (() => {

    function init() {
        const form = $("#soa-form");
        const idField = $("#soa_id");

        const soaNo = $("#soa_no");
        const company = $("#company_name");
        const site = $("#site_name");
        const billing = $("#billing_date");

        const cancelBtn = $("#soa-cancel-btn");

        function resetForm() {
            idField.val("");
            soaNo.val("");
            company.val("");
            site.val("");
            billing.val("");

            cancelBtn.addClass("d-none");
        }

        resetForm();

        $(".soa-btn-edit").on("click", function () {
            const btn = $(this);

            idField.val(btn.data("id"));
            soaNo.val(btn.data("soa"));
            company.val(btn.data("company"));
            site.val(btn.data("site"));
            billing.val(btn.data("billing"));

            cancelBtn.removeClass("d-none");
            window.scrollTo({ top: 0, behavior: "smooth" });
        });

        cancelBtn.on("click", resetForm);

        $(".soa-filter-form").on("submit", function (e) {
            e.preventDefault();
            window.loadPage("soa.php", $(this).serialize());
        });
    }

    return { init };

})();
