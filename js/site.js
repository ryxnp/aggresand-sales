// js/site.js

window.SitePage = (() => {
    
    function init() {
        const form = $("#site-form");
        const formTitle = $("#site-form-title");
        const actionField = $("#site_form_action");
        const idField = $("#site_id");

        const name = $("#site_name");
        const remarks = $("#remarks");
        const location = $("#location");
        const status = $("#status");

        const submitBtn = $("#site-submit-btn");
        const cancelBtn = $("#site-cancel-edit-btn");

        function resetForm() {
            actionField.val("create");
            idField.val("");

            form.attr("action", "pages/site.php");

            name.val("");
            remarks.val("");
            location.val("");
            status.val("active");

            submitBtn.text("Save");
            formTitle.text("Add Site");
            cancelBtn.addClass("d-none");
        }

        resetForm();

        $(".site-btn-edit").on("click", function () {
            const row = $(this);

            actionField.val("update");
            idField.val(row.data("id"));
            form.attr("action", "pages/site.php");

            name.val(row.data("name"));
            remarks.val(row.data("remarks"));
            location.val(row.data("location"));
            status.val(row.data("status"));

            submitBtn.text("Update");
            formTitle.text("Edit Site #" + row.data("id"));
            cancelBtn.removeClass("d-none");

            window.scrollTo({ top: 0, behavior: "smooth" });
        });

        cancelBtn.on("click", resetForm);

        $(".site-filter-form").on("submit", function (e) {
            e.preventDefault();
            window.loadPage("site.php", $(this).serialize());
        });

        $(".pagination .page-link").on("click", function (e) {
            e.preventDefault();

            const href = $(this).attr("href");
            const query = href.split("?")[1] || "";

            window.loadPage("site.php", query);
        });
    }

    return { init };

})();
