window.ContractorPage = (() => {

    function init() {
        const form = $("#contractor-form");
        const formTitle = $("#contractor-form-title");
        const actionField = $("#contractor_form_action");
        const idField = $("#contractor_id");

        const name = $("#contractor_name");
        const person = $("#contact_person");
        const contact = $("#contact_no");
        const email = $("#email");
        const status = $("#status");

        const submitBtn = $("#contractor-submit-btn");
        const cancelBtn = $("#contractor-cancel-edit-btn");

        function resetForm() {
            actionField.val("create");
            idField.val("");

            form.attr("action", "pages/contractor.php");

            name.val("");
            person.val("");
            contact.val("");
            email.val("");
            status.val("active");

            submitBtn.text("Save");
            formTitle.text("Add Contractor");
            cancelBtn.addClass("d-none");
        }

        resetForm();

        $(".contractor-btn-edit").on("click", function () {
            const row = $(this);

            actionField.val("update");
            idField.val(row.data("id"));
            form.attr("action", "pages/contractor.php");

            name.val(row.data("name"));
            person.val(row.data("person"));
            contact.val(row.data("contact"));
            email.val(row.data("email"));
            status.val(row.data("status"));

            submitBtn.text("Update");
            formTitle.text("Edit Contractor #" + row.data("id"));
            cancelBtn.removeClass("d-none");

            window.scrollTo({ top: 0, behavior: "smooth" });
        });

        cancelBtn.on("click", resetForm);

        $(".contractor-filter-form").on("submit", function (e) {
            e.preventDefault();
            window.loadPage("contractor.php", $(this).serialize());
        });

        $(".pagination .page-link").on("click", function (e) {
            e.preventDefault();

            const href = $(this).attr("href");
            const query = href.split("?")[1] || "";

            window.loadPage("contractor.php", query);
        });
    }

    return { init };

})();
