<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// ---------- LOOKUP DATA FOR FILTER DROPDOWNS ----------
$companies = $conn->query("
    SELECT company_id, company_name
    FROM company
    WHERE is_deleted = 0
    ORDER BY company_name
")->fetchAll(PDO::FETCH_ASSOC);

$contractors = $conn->query("
    SELECT contractor_id, contractor_name
    FROM contractor
    WHERE is_deleted = 0
    ORDER BY contractor_name
")->fetchAll(PDO::FETCH_ASSOC);

$sites = $conn->query("
    SELECT site_id, site_name, remarks
    FROM site
    WHERE is_deleted = 0
    ORDER BY site_name
")->fetchAll(PDO::FETCH_ASSOC);

$customers = $conn->query("
    SELECT customer_id, customer_name
    FROM customer
    WHERE is_deleted = 0
    ORDER BY customer_name
")->fetchAll(PDO::FETCH_ASSOC);

$materials = $conn->query("
    SELECT material_id, material_name
    FROM materials
    WHERE is_deleted = 0 AND status = 'active'
    ORDER BY material_name
")->fetchAll(PDO::FETCH_ASSOC);

// ---------- READ FILTERS FROM GET ----------
$customer_id     = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
$company_id      = isset($_GET['company_id']) ? (int)$_GET['company_id'] : 0;
$contractor_id   = isset($_GET['contractor_id']) ? (int)$_GET['contractor_id'] : 0;
$site_id         = isset($_GET['site_id']) ? (int)$_GET['site_id'] : 0;
$material_id     = isset($_GET['material_id']) ? (int)$_GET['material_id'] : 0;
$status_filter   = $_GET['status_filter'] ?? '';
$delivery_from   = $_GET['delivery_from'] ?? '';
$delivery_to     = $_GET['delivery_to'] ?? '';
$billing_from    = $_GET['billing_from'] ?? '';
$billing_to      = $_GET['billing_to'] ?? '';
$search_text     = trim($_GET['q'] ?? '');

$where  = "d.is_deleted = 0";
$params = [];

// customer
if ($customer_id > 0) {
    $where .= " AND c.customer_id = :customer_id";
    $params[':customer_id'] = $customer_id;
}

// company (via customer)
if ($company_id > 0) {
    $where .= " AND c.company_id = :company_id";
    $params[':company_id'] = $company_id;
}

// contractor (if you later relate it; for now via customer.contractor_id)
if ($contractor_id > 0) {
    $where .= " AND c.contractor_id = :contractor_id";
    $params[':contractor_id'] = $contractor_id;
}

// site
if ($site_id > 0) {
    $where .= " AND c.site_id = :site_id";
    $params[':site_id'] = $site_id;
}

// material (by name from materials table)
if ($material_id > 0) {
    // lookup material name
    $mStmt = $conn->prepare("SELECT material_name FROM materials WHERE material_id = :mid");
    $mStmt->execute([':mid' => $material_id]);
    $mRow = $mStmt->fetch(PDO::FETCH_ASSOC);
    if ($mRow) {
        $where .= " AND d.material = :material_name";
        $params[':material_name'] = $mRow['material_name'];
    }
}

// status
if ($status_filter !== '') {
    $where .= " AND d.status = :status";
    $params[':status'] = $status_filter;
}

// delivery date range
if ($delivery_from !== '') {
    $where .= " AND d.delivery_date >= :delivery_from";
    $params[':delivery_from'] = $delivery_from;
}
if ($delivery_to !== '') {
    $where .= " AND d.delivery_date <= :delivery_to";
    $params[':delivery_to'] = $delivery_to;
}

// billing date range
if ($billing_from !== '') {
    $where .= " AND d.billing_date >= :billing_from";
    $params[':billing_from'] = $billing_from;
}
if ($billing_to !== '') {
    $where .= " AND d.billing_date <= :billing_to";
    $params[':billing_to'] = $billing_to;
}

// free text search (DR, Material, Plate, etc.)
if ($search_text !== '') {
    $where .= " AND (
        d.dr_no LIKE :q
        OR d.material LIKE :q
        OR c.customer_name LIKE :q
        OR co.company_name LIKE :q
        OR s.site_name LIKE :q
        OR t.plate_no LIKE :q
    )";
    $params[':q'] = '%' . $search_text . '%';
}

// ---------- PAGINATION ----------
$per_page     = 10;
$current_page = isset($_GET['p']) && ctype_digit($_GET['p']) ? (int)$_GET['p'] : 1;
if ($current_page < 1) $current_page = 1;

// count
$countSql = "
    SELECT COUNT(*)
    FROM delivery d
    JOIN customer c ON d.customer_id = c.customer_id
    LEFT JOIN company co ON c.company_id = co.company_id
    LEFT JOIN site s ON c.site_id = s.site_id
    LEFT JOIN truck t ON d.truck_id = t.truck_id
    WHERE $where
";
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$total_records = (int) $countStmt->fetchColumn();

$total_pages = max(1, (int)ceil($total_records / $per_page));
if ($current_page > $total_pages) $current_page = $total_pages;

$offset = ($current_page - 1) * $per_page;

// ---------- MAIN LIST QUERY ----------
$listSql = "
    SELECT
        d.del_id,
        d.delivery_date,
        d.billing_date,
        d.dr_no,
        d.material,
        d.quantity,
        d.unit_price,
        d.status,
        c.customer_id,
        c.customer_name,
        co.company_name,
        s.site_name,
        t.plate_no
    FROM delivery d
    JOIN customer c ON d.customer_id = c.customer_id
    LEFT JOIN company co ON c.company_id = co.company_id
    LEFT JOIN site s ON c.site_id = s.site_id
    LEFT JOIN truck t ON d.truck_id = t.truck_id
    WHERE $where
    ORDER BY d.delivery_date ASC, d.dr_no ASC
    LIMIT :limit OFFSET :offset
";

$listStmt = $conn->prepare($listSql);
foreach ($params as $k => $v) {
    $listStmt->bindValue($k, $v);
}
$listStmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$rows = $listStmt->fetchAll(PDO::FETCH_ASSOC);

// ---------- TOTALS (for current filter, all rows, no pagination) ----------
$totalSql = "
    SELECT
        COALESCE(SUM(d.quantity), 0) AS total_qty,
        COALESCE(SUM(d.quantity * d.unit_price), 0) AS total_amount,
        COUNT(DISTINCT d.dr_no) AS total_dr
    FROM delivery d
    JOIN customer c ON d.customer_id = c.customer_id
    LEFT JOIN company co ON c.company_id = co.company_id
    LEFT JOIN site s ON c.site_id = s.site_id
    LEFT JOIN truck t ON d.truck_id = t.truck_id
    WHERE $where
";
$totalStmt = $conn->prepare($totalSql);
$totalStmt->execute($params);
$totals = $totalStmt->fetch(PDO::FETCH_ASSOC);

$queryForPagination = http_build_query([
    'customer_id'   => $customer_id,
    'company_id'    => $company_id,
    'contractor_id' => $contractor_id,
    'site_id'       => $site_id,
    'material_id'   => $material_id,
    'status_filter' => $status_filter,
    'delivery_from' => $delivery_from,
    'delivery_to'   => $delivery_to,
    'billing_from'  => $billing_from,
    'billing_to'    => $billing_to,
    'q'             => $search_text,
]);
?>

<div class="container-fluid">
    <h2 class="mb-4">Reports (All Transactions)</h2>

    <div class="row mb-3">
        <!-- ADVANCED SEARCH -->
        <div class="col-lg-8 mb-3">
            <div class="card">
                <div class="card-header">
                    Advanced Search
                </div>
                <div class="card-body">
                    <form class="row g-2 align-items-end reports-filter-form" method="GET" action="">
                        <div class="col-md-4">
                            <label class="form-label">Customer</label>
                            <select name="customer_id" class="form-select">
                                <option value="">All Customers</option>
                                <?php foreach ($customers as $cu): ?>
                                    <option value="<?= (int)$cu['customer_id'] ?>"
                                        <?= $customer_id == $cu['customer_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cu['customer_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Company</label>
                            <select name="company_id" class="form-select">
                                <option value="">All Companies</option>
                                <?php foreach ($companies as $co): ?>
                                    <option value="<?= (int)$co['company_id'] ?>"
                                        <?= $company_id == $co['company_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($co['company_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Site</label>
                            <select name="site_id" class="form-select">
                                <option value="">All Sites</option>
                                <?php foreach ($sites as $st):
                                    $label = $st['site_name'];
                                    if (!empty($st['remarks'])) {
                                        $label .= ' - ' . $st['remarks'];
                                    }
                                ?>
                                    <option value="<?= (int)$st['site_id'] ?>"
                                        <?= $site_id == $st['site_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Material</label>
                            <select name="material_id" class="form-select">
                                <option value="">All Materials</option>
                                <?php foreach ($materials as $m): ?>
                                    <option value="<?= (int)$m['material_id'] ?>"
                                        <?= $material_id == $m['material_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($m['material_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Delivery Date From</label>
                            <input type="date" name="delivery_from" class="form-control"
                                   value="<?= htmlspecialchars($delivery_from) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Delivery Date To</label>
                            <input type="date" name="delivery_to" class="form-control"
                                   value="<?= htmlspecialchars($delivery_to) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Billing Date From</label>
                            <input type="date" name="billing_from" class="form-control"
                                   value="<?= htmlspecialchars($billing_from) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Billing Date To</label>
                            <input type="date" name="billing_to" class="form-control"
                                   value="<?= htmlspecialchars($billing_to) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status_filter" class="form-select">
                                <option value="">All</option>
                                <option value="pending"   <?= $status_filter === 'pending'   ? 'selected' : '' ?>>Pending</option>
                                <option value="posted"    <?= $status_filter === 'posted'    ? 'selected' : '' ?>>Posted</option>
                                <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Search</label>
                            <input type="text" name="q" class="form-control"
                                   placeholder="DR / Material / Plate / Name"
                                   value="<?= htmlspecialchars($search_text) ?>">
                        </div>

                        <div class="col-md-4 mt-2">
                            <button class="btn btn-primary w-100">Apply Filters</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- CURRENT SELECTION / ACTIONS -->
        <div class="col-lg-4 mb-3">
            <div class="card mb-3">
                <div class="card-header">
                    Statement Info
                </div>
                <div class="card-body small">
                    <p><strong>Customer:</strong><br>
                        <?= $customer_id ? htmlspecialchars(array_values(array_filter($customers, fn($c) => $c['customer_id'] == $customer_id))[0]['customer_name'] ?? 'Selected') : 'All' ?>
                    </p>
                    <p><strong>Delivery Date:</strong><br>
                        <?= $delivery_from ? htmlspecialchars($delivery_from) : 'Any' ?>
                        &nbsp;to&nbsp;
                        <?= $delivery_to ? htmlspecialchars($delivery_to) : 'Any' ?>
                    </p>
                    <p><strong>Billing Date:</strong><br>
                        <?= $billing_from ? htmlspecialchars($billing_from) : 'Any' ?>
                        &nbsp;to&nbsp;
                        <?= $billing_to ? htmlspecialchars($billing_to) : 'Any' ?>
                    </p>
                    <p><strong>Status:</strong><br>
                        <?= $status_filter !== '' ? htmlspecialchars(ucfirst($status_filter)) : 'All' ?>
                    </p>
                </div>
            </div>

            <!-- <div class="card">
                <div class="card-header">
                    Actions
                </div>
                <div class="card-body d-flex flex-column gap-2">
                    <button type="button"
                            class="btn btn-outline-secondary reports-btn-view"
                            data-query="<?= htmlspecialchars($queryForPagination) ?>">
                        View (Print)
                    </button>
                    <button type="button"
                            class="btn btn-outline-success reports-btn-export"
                            data-query="<?= htmlspecialchars($queryForPagination) ?>">
                        Extract (CSV)
                    </button>
                </div>
            </div> -->
        </div>
    </div>

    <!-- RESULTS TABLE -->
    <div class="card">
        <div class="card-header d-flex align-items-center gap-2 flex-nowrap">
            <span class="me-auto">Deliveries</span>
            <span class="small text-muted">
                Records: <?= $total_records ?>
            </span>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-striped table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Billing Date</th>
                        <th>Customer</th>
                        <th>Company</th>
                        <th>Site</th>
                        <th>DR No.</th>
                        <th>Plate No.</th>
                        <th>Material</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Price</th>
                        <th class="text-end">Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$rows): ?>
                    <tr><td colspan="12" class="text-center">No records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $r):
                        $amount = (float)$r['quantity'] * (float)$r['unit_price'];
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($r['delivery_date']) ?></td>
                            <td><?= htmlspecialchars($r['billing_date']) ?></td>
                            <td><?= htmlspecialchars($r['customer_name']) ?></td>
                            <td><?= htmlspecialchars($r['company_name']) ?></td>
                            <td><?= htmlspecialchars($r['site_name']) ?></td>
                            <td><?= htmlspecialchars($r['dr_no']) ?></td>
                            <td><?= htmlspecialchars($r['plate_no']) ?></td>
                            <td><?= htmlspecialchars($r['material']) ?></td>
                            <td class="text-end"><?= number_format((float)$r['quantity'], 3) ?></td>
                            <td class="text-end"><?= number_format((float)$r['unit_price'], 2) ?></td>
                            <td class="text-end"><?= number_format($amount, 2) ?></td>
                            <td><?= htmlspecialchars($r['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="card-footer d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <div>
                <strong>Totals (for current filters):</strong><br>
                Qty: <?= number_format((float)$totals['total_qty'], 3) ?>
                &nbsp; | &nbsp;
                Amount: <?= number_format((float)$totals['total_amount'], 2) ?>
                &nbsp; | &nbsp;
                Total DR No.: <?= (int)$totals['total_dr'] ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <nav>
                    <ul class="pagination mb-0">
                        <li class="page-item <?= $current_page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link"
                               href="?<?= htmlspecialchars($queryForPagination) ?>&p=<?= $current_page - 1 ?>">&laquo;</a>
                        </li>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                <a class="page-link"
                                   href="?<?= htmlspecialchars($queryForPagination) ?>&p=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link"
                               href="?<?= htmlspecialchars($queryForPagination) ?>&p=<?= $current_page + 1 ?>">&raquo;</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>
