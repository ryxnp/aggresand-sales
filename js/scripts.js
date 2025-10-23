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
    link.classList.add("active");

    try {
      const response = await fetch(`loader.php?page=${page}`, { cache: "no-store" });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const html = await response.text();
      mainContent.innerHTML = html;
      document.title = `Aggresand Dashboard - ${pageTitles[page] || "Dashboard"}`;
      history.replaceState(null, "", "main.php");
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

  // Default load (first page)
  const defaultPage = document.querySelector('.sidebar-link[data-page="trans_entry.php"]');
  if (defaultPage) defaultPage.click();
});
