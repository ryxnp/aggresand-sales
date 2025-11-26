// js/materials.js

window.MaterialsPage = (() => {

    function initForm() {
        const form       = $("#materials-form");
        const formTitle  = $("#materials-form-title");
        const actionFld  = $("#materials_form_action");
        const idFld      = $("#material_id");

        const nameInput  = $("#material_name");
        const priceInput = $("#unit_price");
        const statusSel  = $("#materials_status");

        const submitBtn  = $("#materials-submit-btn");
        const cancelBtn  = $("#materials-cancel-edit-btn");

        function resetForm() {
            actionFld.val("create");
            idFld.val("");

            form.attr("action", "pages/materials.php");

            nameInput.val("");
            priceInput.val("");
            statusSel.val("active");

            formTitle.text("Add Material");
            submitBtn.text("Save");
            cancelBtn.addClass("d-none");
        }

        resetForm();

        // Edit buttons
        $(".materials-btn-edit").on("click", function () {
            const btn = $(this);

            actionFld.val("update");
            idFld.val(btn.data("id"));
            form.attr("action", "pages/materials.php");

            nameInput.val(btn.data("name") || "");
            priceInput.val(btn.data("price") || "");
            statusSel.val(btn.data("status") || "active");

            formTitle.text("Edit Material #" + btn.data("id"));
            submitBtn.text("Update");
            cancelBtn.removeClass("d-none");

            window.scrollTo({ top: 0, behavior: "smooth" });
        });

        cancelBtn.on("click", resetForm);
    }

    function initFiltersAndPagination() {
        // Filters form → SPA reload
        $(".materials-filter-form").on("submit", function (e) {
            e.preventDefault();
            const query = $(this).serialize();
            if (typeof window.loadPage === "function") {
                window.loadPage("materials.php", query);
            }
        });

        // Pagination links → SPA reload
        $(".pagination .page-link").on("click", function (e) {
            e.preventDefault();
            const href  = $(this).attr("href") || "";
            const parts = href.split("?");
            const query = parts[1] || "";
            if (typeof window.loadPage === "function") {
                window.loadPage("materials.php", query);
            }
        });
    }

    function init() {
        initForm();
        initFiltersAndPagination();
    }

    return { init };

})();
