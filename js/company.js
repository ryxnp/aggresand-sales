// js/company.js

window.CompanyPage = (() => {

    function init() {
        const form       = $("#company-form");
        const formTitle  = $("#company-form-title");
        const actionFld  = $("#company_form_action");
        const idFld      = $("#company_id");

        const nameInput    = $("#company_name");
        const addrInput    = $("#address");
        const contactInput = $("#contact_no");
        const emailInput   = $("#email");
        const statusSelect = $("#status");

        const submitBtn = $("#company-submit-btn");
        const cancelBtn = $("#company-cancel-edit-btn");

        function resetForm() {
            actionFld.val("create");
            idFld.val("");

            // always post back to this SPA page
            form.attr("action", "pages/company.php");

            nameInput.val("");
            addrInput.val("");
            contactInput.val("");
            emailInput.val("");
            statusSelect.val("active");

            formTitle.text("Add Company");
            submitBtn.text("Save");
            cancelBtn.addClass("d-none");
        }

        resetForm();

        // Edit button → populate form
        $(".company-btn-edit").on("click", function () {
            const btn = $(this);

            actionFld.val("update");
            idFld.val(btn.data("id"));
            form.attr("action", "pages/company.php");

            nameInput.val(btn.data("name") || "");
            addrInput.val(btn.data("address") || "");
            contactInput.val(btn.data("contact") || "");
            emailInput.val(btn.data("email") || "");
            statusSelect.val(btn.data("status") || "active");

            formTitle.text("Edit Company #" + btn.data("id"));
            submitBtn.text("Update");
            cancelBtn.removeClass("d-none");

            window.scrollTo({ top: 0, behavior: "smooth" });
        });

        // Cancel edit → back to create mode
        cancelBtn.on("click", resetForm);

        // Filters (SPA)
        $(".company-filter-form").on("submit", function (e) {
            e.preventDefault();
            const query = $(this).serialize();
            if (typeof window.loadPage === "function") {
                window.loadPage("company.php", query);
            }
        });

        // Pagination (SPA)
        $(".pagination .page-link").on("click", function (e) {
            e.preventDefault();
            const href  = $(this).attr("href") || "";
            const parts = href.split("?");
            const query = parts[1] || "";
            if (typeof window.loadPage === "function") {
                window.loadPage("company.php", query);
            }
        });
    }

    return { init };

})();
