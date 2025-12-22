// js/accounts.js

window.AccountsPage = (() => {

    function init() {

        const form      = $("#account-form");
        const title     = $("#account-form-title");
        const actionFld = $("#account_action");
        const idFld     = $("#admin_id");

        const username = $("#username");
        const email    = $("#email");
        const role     = $("#role");
        const status   = $("#status");
        const password = $("#password");

        const submitBtn = $("#account-submit-btn");
        const cancelBtn = $("#account-cancel-btn");

        function resetForm() {
            actionFld.val("create");
            idFld.val("");
            form.attr("action", "pages/accounts.php");

            username.val("");
            email.val("");
            role.val("Admin");
            status.val("Active");
            password.val("");

            title.text("Add User");
            submitBtn.text("Save");
            cancelBtn.addClass("d-none");
        }

        // reset on load
        resetForm();

        // ✅ DELEGATED EDIT BUTTON (FIX)
        $(document).on("click", ".account-edit-btn", function () {
            const btn = $(this);

            actionFld.val("update");
            idFld.val(btn.data("id"));

            username.val(btn.data("username"));
            email.val(btn.data("email"));
            role.val(btn.data("role"));
            status.val(btn.data("status"));
            password.val("");

            title.text("Edit User #" + btn.data("id"));
            submitBtn.text("Update");
            cancelBtn.removeClass("d-none");

            window.scrollTo({ top: 0, behavior: "smooth" });
        });

        // cancel
        cancelBtn.on("click", function () {
            resetForm();
        });
    }

    return { init };

})();
