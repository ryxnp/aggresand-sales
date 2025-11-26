// js/truck.js

window.TruckPage = (() => {

    function init() {
        const form = $("#truck-form");
        const formTitle = $("#truck-form-title");
        const actionField = $("#truck_form_action");
        const idField = $("#truck_id");

        const plate = $("#plate_no");
        const capacity = $("#capacity");
        const model = $("#truck_model");
        const status = $("#status");

        const submitBtn = $("#truck-submit-btn");
        const cancelBtn = $("#truck-cancel-edit-btn");

        function resetForm() {
            actionField.val("create");
            idField.val("");

            form.attr("action", "pages/truck.php");

            plate.val("");
            capacity.val("");
            model.val("");
            status.val("active");

            submitBtn.text("Save");
            formTitle.text("Add Truck");
            cancelBtn.addClass("d-none");
        }

        resetForm();

        $(".truck-btn-edit").on("click", function () {
            const row = $(this);

            actionField.val("update");
            idField.val(row.data("id"));
            form.attr("action", "pages/truck.php");

            plate.val(row.data("plate"));
            capacity.val(row.data("capacity"));
            model.val(row.data("model"));
            status.val(row.data("status"));

            submitBtn.text("Update");
            formTitle.text("Edit Truck #" + row.data("id"));
            cancelBtn.removeClass("d-none");

            window.scrollTo({ top: 0, behavior: "smooth" });
        });

        cancelBtn.on("click", resetForm);

        $(".truck-filter-form").on("submit", function (e) {
            e.preventDefault();
            window.loadPage("truck.php", $(this).serialize());
        });

        $(".pagination .page-link").on("click", function (e) {
            e.preventDefault();

            const href = $(this).attr("href");
            const query = href.split("?")[1] || "";

            window.loadPage("truck.php", query);
        });
    }

    return { init };

})();
