// js/deliveries.js

window.DeliveriesPage = (() => {

    function initFilters() {
        $(".deliveries-filter-form").on("submit", function (e) {
            e.preventDefault();
            const query = $(this).serialize();
            if (typeof window.loadPage === "function") {
                window.loadPage("deliveries.php", query);
            }
        });
    }

    function initRowClick() {
        $("#deliveries-table").on("click", ".deliveries-row", function () {

            $(".deliveries-row").removeClass("table-active");
            $(this).addClass("table-active");

            const dr       = $(this).data("dr");
            const total    = $(this).data("total");
            const status   = $(this).data("status");
            const date     = $(this).data("date");
            const si       = $(this).data("si");
            const check    = $(this).data("check");
            const paid     = $(this).data("paid");
            const billing  = $(this).data("billing");

            let badge = "bg-secondary";
            if (status === "PAID") badge = "bg-success";
            else if (status === "PARTIAL") badge = "bg-warning text-dark";

            $("#form_dr_no").val(dr);
            $("#form_dr_no_hidden").val(dr);
            $("#form_amount").val(parseFloat(total).toFixed(2));
            $("#form_status").val(status);
            $("#form_date_paid").val(date || "");
            $("#form_si_no").val(si || "");
            $("#form_check_no").val(check || "");
            $("#form_save_btn").prop("disabled", false);

            $("#dr-status-info").html(`
                <div class="mb-2">
                    <span class="badge ${badge}">${status}</span>
                </div>

                <div class="d-flex justify-content-between">
                    <div><strong>Date Paid:</strong> ${date || '-'}</div>
                    <div><strong>Amount Paid:</strong> ₱${parseFloat(paid || 0).toFixed(2)}</div>
                </div>

                <hr class="my-2">

                <div class="d-flex justify-content-between">
                    <div><strong>Billing Date:</strong> ${billing || '-'}</div>
                    <div><strong>SI No:</strong> ${si || '-'}</div>
                    <div><strong>Check No:</strong> ${check || '-'}</div>
                </div>
            `);

            window.scrollTo({ top: 0, behavior: "smooth" });
        });
    }

    function init() {
        initFilters();
        initRowClick();
    }

    return { init };

})();
