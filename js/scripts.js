document.addEventListener("DOMContentLoaded", () => {
  const links = document.querySelectorAll(".sidebar-link");
  const mainContent = document.getElementById("main-content");
  const loadingSpinner = document.getElementById("loading-spinner");

  const pageTitles = {
    "trans_entry.php": "Transaction Entry",
    "reports.php": "Reports",
    "contractor.php": "Contractor Management",
    "site.php": "Site Management",
    "materials.php": "Materials",
    "truck.php": "Truck Information",
    "company.php": "Company",
    "accounts.php": "Accounts Settings",
    "backup.php": "Database Backup",
  };

  async function loadPage(page, link) {
    // Show spinner overlay
    loadingSpinner.style.display = "flex";
    loadingSpinner.style.opacity = "1";
    mainContent.style.opacity = "0.5";

    // Highlight active link
    links.forEach(l => l.classList.remove("active"));
    if (link) link.classList.add("active");

    try {
      const response = await fetch(`loader.php?page=${page}`, { cache: "no-store" });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const html = await response.text();
      mainContent.innerHTML = html;
      document.title = `Aggresand Dashboard - ${pageTitles[page] || "Dashboard"}`;

      // Update URL hash
      history.replaceState(null, "", `#${page}`);

      // If Accounts page is loaded, initialize its JS
      if (page === "accounts.php") {
        initAccountsPage();
      }

    } catch (error) {
      mainContent.innerHTML = `<div class="error-msg">❌ Error loading <b>${page}</b>: ${error.message}</div>`;
    } finally {
      // Hide spinner smoothly
      loadingSpinner.style.opacity = "0";
      setTimeout(() => {
        loadingSpinner.style.display = "none";
      }, 300);
      mainContent.style.opacity = "1";
    }
  }

  links.forEach(link => {
    link.addEventListener("click", e => {
      e.preventDefault();
      const page = link.getAttribute("data-page");
      loadPage(page, link);
    });
  });

  // Load page based on URL hash or fallback to default
  const initialPage = location.hash ? location.hash.substring(1) : "trans_entry.php";
  const initialLink = document.querySelector(`.sidebar-link[data-page="${initialPage}"]`);
  if (initialLink) loadPage(initialPage, initialLink);

  // --------------------------
  // Accounts page JS
  // --------------------------
  function initAccountsPage() {
    const $ = jQuery; // Ensure jQuery is available

    // Handle account creation via AJAX
    $('#createUserForm').off('submit').on('submit', function(e) {
      e.preventDefault();

      $.ajax({
        url: 'pages/accounts.php', // handles POST
        method: 'POST',
        data: $(this).serialize() + '&create_user=1',
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            alert('✅ User account created successfully!');
            $('#createUserModal').modal('hide');
            $('#createUserForm')[0].reset();
            reloadAccountsTable();
          } else {
            alert('❌ Error: ' + response.error);
          }
        },
        error: function(xhr) {
          alert('Request failed: ' + xhr.responseText);
        }
      });
    });

    // Reload accounts table
    function reloadAccountsTable() {
      $.ajax({
        url: 'pages/accounts.php',
        method: 'GET',
        success: function(html) {
          const newBody = $(html).find('#accountsTable').html();
          $('#accountsTable').html(newBody);
        }
      });
    }
  }
});

setTimeout(() => {
        let alertNode = document.querySelector('.alert');
        if (alertNode) {
            let alert = new bootstrap.Alert(alertNode);
            alert.close();
        }
    }, 3000);