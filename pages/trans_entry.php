<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/audit_fields.php';
require_once __DIR__ . '/../helpers/audit.php';

$admin       = $_SESSION['admin_id'] ?? null;
$redirectUrl = '/main.php#trans_entry.php';

/* ================== HANDLE POST (CUSTOMER + DELIVERY) ================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!$admin) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Not authenticated'];
        header("Location: $redirectUrl");
        exit;
    }

    $formType = $_POST['form_type'] ?? '';

    try {
        /* ---------- CUSTOMER FORM ---------- */
        if ($formType === 'customer') {

            $action = $_POST['action'] ?? 'create';
            $id     = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;

            $company_id    = isset($_POST['company_id']) ? (int)$_POST['company_id'] : null;
            $contractor_id = isset($_POST['contractor_id']) ? (int)$_POST['contractor_id'] : null;
            $site_id       = isset($_POST['site_id']) ? (int)$_POST['site_id'] : null;
            $customer_name = trim($_POST['customer_name'] ?? '');
            $contact_no    = trim($_POST['contact_no'] ?? '');
            $email         = trim($_POST['email'] ?? '');
            $address       = trim($_POST['address'] ?? '');
            $status        = $_POST['status'] ?? 'active';

            if ($customer_name === '') {
                throw new Exception('Customer name is required');
            }

            if ($action === 'create') {
                $audit = audit_on_create($admin);

                $stmt = $conn->prepare("
                    INSERT INTO customer
                        (company_id, contractor_id, site_id, customer_name, contact_no,
                         email, address, status, is_deleted,
                         date_created, date_edited, created_by, edited_by)
                    VALUES
                        (:company_id, :contractor_id, :site_id, :customer_name, :contact_no,
                         :email, :address, :status, 0,
                         :date_created, :date_edited, :created_by, :edited_by)
                ");

                $stmt->execute([
                    ':company_id'    => $company_id ?: null,
                    ':contractor_id' => $contractor_id ?: null,
                    ':site_id'       => $site_id ?: null,
                    ':customer_name' => $customer_name,
                    ':contact_no'    => $contact_no,
                    ':email'         => $email,
                    ':address'       => $address,
                    ':status'        => $status,
                    ':date_created'  => $audit['date_created'],
                    ':date_edited'   => $audit['date_edited'],
                    ':created_by'    => $audit['created_by'],
                    ':edited_by'     => $audit['edited_by'],
                ]);

                $newId = (int)$conn->lastInsertId();
                audit_log('customer', $newId, 'CREATE', null, $_POST, $admin);

                $_SESSION['alert'] = ['type' => 'success', 'message' => 'Customer created'];

            } elseif ($action === 'update') {

                if ($id <= 0) {
                    throw new Exception('Invalid customer ID');
                }

                $oldStmt = $conn->prepare("SELECT * FROM customer WHERE customer_id = :id AND is_deleted = 0");
                $oldStmt->execute([':id' => $id]);
                $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC);

                if (!$oldData) {
                    throw new Exception('Customer record not found or deleted');
                }

                $audit = audit_on_update($admin);

                $stmt = $conn->prepare("
                    UPDATE customer SET
                        company_id    = :company_id,
                        contractor_id = :contractor_id,
                        site_id       = :site_id,
                        customer_name = :customer_name,
                        contact_no    = :contact_no,
                        email         = :email,
                        address       = :address,
                        status        = :status,
                        date_edited   = :date_edited,
                        edited_by     = :edited_by
                    WHERE customer_id = :id AND is_deleted = 0
                ");

                $stmt->execute([
                    ':id'            => $id,
                    ':company_id'    => $company_id ?: null,
                    ':contractor_id' => $contractor_id ?: null,
                    ':site_id'       => $site_id ?: null,
                    ':customer_name' => $customer_name,
                    ':contact_no'    => $contact_no,
                    ':email'         => $email,
                    ':address'       => $address,
                    ':status'        => $status,
                    ':date_edited'   => $audit['date_edited'],
                    ':edited_by'     => $audit['edited_by'],
                ]);

                audit_log('customer', $id, 'UPDATE', $oldData, $_POST, $admin);

                $_SESSION['alert'] = ['type' => 'success', 'message' => 'Customer updated'];
            }
        }

        /* ---------- DELIVERY FORM ---------- */
        if ($formType === 'delivery') {

            $action = $_POST['action'] ?? 'create';
            $id     = isset($_POST['del_id']) ? (int)$_POST['del_id'] : 0;

            $customer_id   = isset($_POST['delivery_customer_id']) ? (int)$_POST['delivery_customer_id'] : 0;
            $delivery_date = $_POST['delivery_date'] ?? null;
            $dr_no         = trim($_POST['dr_no'] ?? '');
            $truck_id      = isset($_POST['truck_id']) ? (int)$_POST['truck_id'] : null;
            $billing_date  = $_POST['billing_date'] ?? null;
            $material_name = trim($_POST['material_name'] ?? '');
            $quantity      = (float)($_POST['quantity'] ?? 0);
            $unit_price    = (float)($_POST['unit_price'] ?? 0);
            $status        = $_POST['status'] ?? 'pending';

            if ($customer_id <= 0) {
                throw new Exception('Customer is required for delivery');
            }
            if ($delivery_date === null || $delivery_date === '') {
                throw new Exception('Delivery date is required');
            }

            if ($action === 'create') {
                $audit = audit_on_create($admin);

                $stmt = $conn->prepare("
                    INSERT INTO delivery
                        (customer_id, delivery_date, dr_no, truck_id, billing_date,
                         material, quantity, unit_price, status, is_deleted,
                         date_created, date_edited, created_by, edited_by)
                    VALUES
                        (:customer_id, :delivery_date, :dr_no, :truck_id, :billing_date,
                         :material, :quantity, :unit_price, :status, 0,
                         :date_created, :date_edited, :created_by, :edited_by)
                ");

                $stmt->execute([
                    ':customer_id'   => $customer_id,
                    ':delivery_date' => $delivery_date,
                    ':dr_no'         => $dr_no,
                    ':truck_id'      => $truck_id ?: null,
                    ':billing_date'  => $billing_date ?: null,
                    ':material'      => $material_name,
                    ':quantity'      => $quantity,
                    ':unit_price'    => $unit_price,
                    ':status'        => $status,
                    ':date_created'  => $audit['date_created'],
                    ':date_edited'   => $audit['date_edited'],
                    ':created_by'    => $audit['created_by'],
                    ':edited_by'     => $audit['edited_by'],
                ]);

                $newId = (int)$conn->lastInsertId();
                audit_log('delivery', $newId, 'CREATE', null, $_POST, $admin);

                $_SESSION['alert'] = ['type' => 'success', 'message' => 'Delivery created'];

            } elseif ($action === 'update') {

                if ($id <= 0) {
                    throw new Exception('Invalid delivery ID');
                }

                $oldStmt = $conn->prepare("SELECT * FROM delivery WHERE del_id = :id AND is_deleted = 0");
                $oldStmt->execute([':id' => $id]);
                $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC);

                if (!$oldData) {
                    throw new Exception('Delivery record not found or deleted');
                }

                $audit = audit_on_update($admin);

                $stmt = $conn->prepare("
                    UPDATE delivery SET
                        customer_id   = :customer_id,
                        delivery_date = :delivery_date,
                        dr_no         = :dr_no,
                        truck_id      = :truck_id,
                        billing_date  = :billing_date,
                        material      = :material,
                        quantity      = :quantity,
                        unit_price    = :unit_price,
                        status        = :status,
                        date_edited   = :date_edited,
                        edited_by     = :edited_by
                    WHERE del_id = :id AND is_deleted = 0
                ");

                $stmt->execute([
                    ':id'           => $id,
                    ':customer_id'  => $customer_id,
                    ':delivery_date'=> $delivery_date,
                    ':dr_no'        => $dr_no,
                    ':truck_id'     => $truck_id ?: null,
                    ':billing_date' => $billing_date ?: null,
                    ':material'     => $material_name,
                    ':quantity'     => $quantity,
                    ':unit_price'   => $unit_price,
                    ':status'       => $status,
                    ':date_edited'  => $audit['date_edited'],
                    ':edited_by'    => $audit['edited_by'],
                ]);

                audit_log('delivery', $id, 'UPDATE', $oldData, $_POST, $admin);

                $_SESSION['alert'] = ['type' => 'success', 'message' => 'Delivery updated'];

            } elseif ($action === 'delete') {

                if ($id <= 0) {
                    throw new Exception('Invalid delivery ID');
                }

                $oldStmt = $conn->prepare("SELECT * FROM delivery WHERE del_id = :id AND is_deleted = 0");
                $oldStmt->execute([':id' => $id]);
                $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC);

                if (!$oldData) {
                    throw new Exception('Delivery record not found or already deleted');
                }

                $audit = audit_on_update($admin);

                $stmt = $conn->prepare("
                    UPDATE delivery SET
                        is_deleted  = 1,
                        date_edited = :date_edited,
                        edited_by   = :edited_by
                    WHERE del_id = :id
                ");

                $stmt->execute([
                    ':id'          => $id,
                    ':date_edited' => $audit['date_edited'],
                    ':edited_by'   => $audit['edited_by'],
                ]);

                audit_log('delivery', $id, 'DELETE', $oldData, ['is_deleted' => 1], $admin);

                $_SESSION['alert'] = ['type' => 'success', 'message' => 'Delivery deleted'];
            }
        }

    } catch (Exception $e) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => $e->getMessage()];
    }

    header("Location: $redirectUrl");
    exit;
}

/* ================== LOOKUP DATA ================== */

// companies
$companies = $conn->query("
    SELECT company_id, company_name 
    FROM company 
    WHERE is_deleted = 0 
    ORDER BY company_name
")->fetchAll(PDO::FETCH_ASSOC);

// contractors
$contractors = $conn->query("
    SELECT contractor_id, contractor_name 
    FROM contractor 
    WHERE is_deleted = 0 
    ORDER BY contractor_name
")->fetchAll(PDO::FETCH_ASSOC);

// sites
$sites = $conn->query("
    SELECT site_id, site_name 
    FROM site 
    WHERE is_deleted = 0 
    ORDER BY site_name
")->fetchAll(PDO::FETCH_ASSOC);

// trucks
$trucks = $conn->query("
    SELECT truck_id, plate_no 
    FROM truck 
    WHERE is_deleted = 0 
    ORDER BY plate_no
")->fetchAll(PDO::FETCH_ASSOC);

// customers (for delivery dropdown)
$customers = $conn->query("
    SELECT customer_id, customer_name 
    FROM customer
    WHERE is_deleted = 0
    ORDER BY customer_name
")->fetchAll(PDO::FETCH_ASSOC);

// materials
$materials = $conn->query("
    SELECT material_id, material_name, unit_price
    FROM materials
    WHERE is_deleted = 0 
      AND status = 'active'
    ORDER BY material_name
")->fetchAll(PDO::FETCH_ASSOC);

/* ================== FILTERS + DELIVERY LIST ================== */

require_once __DIR__ . '/../helpers/alerts.php';

$q                = trim($_GET['q'] ?? '');
$statusFilter     = $_GET['status_filter'] ?? '';
$companyFilter    = isset($_GET['company_filter']) ? (int)$_GET['company_filter'] : 0;
$siteFilter       = isset($_GET['site_filter']) ? (int)$_GET['site_filter'] : 0;
$materialFilter   = $_GET['material_filter'] ?? '';
$dateFrom         = $_GET['del_date_from'] ?? '';
$dateTo           = $_GET['del_date_to'] ?? '';

$where  = "d.is_deleted = 0";
$params = [];

// date range
if ($dateFrom !== '') {
    $where .= " AND d.delivery_date >= :date_from";
    $params[':date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $where .= " AND d.delivery_date <= :date_to";
    $params[':date_to'] = $dateTo;
}

// status
if ($statusFilter !== '') {
    $where .= " AND d.status = :status";
    $params[':status'] = $statusFilter;
}

// company/site filter via customer
if ($companyFilter > 0) {
    $where .= " AND c.company_id = :company_id";
    $params[':company_id'] = $companyFilter;
}
if ($siteFilter > 0) {
    $where .= " AND c.site_id = :site_id";
    $params[':site_id'] = $siteFilter;
}

// material filter
if ($materialFilter !== '') {
    $where .= " AND d.material = :material_filter";
    $params[':material_filter'] = $materialFilter;
}

// free text search
if ($q !== '') {
    $where .= " AND (
        d.dr_no LIKE :q
        OR c.customer_name LIKE :q
        OR co.company_name LIKE :q
        OR s.site_name LIKE :q
        OR t.plate_no LIKE :q
        OR d.material LIKE :q
    )";
    $params[':q'] = '%' . $q . '%';
}

// pagination
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
$total_records = (int)$countStmt->fetchColumn();

$total_pages = max(1, (int)ceil($total_records / $per_page));
if ($current_page > $total_pages) $current_page = $total_pages;

$offset = ($current_page - 1) * $per_page;

$listSql = "
    SELECT
        d.del_id,
        d.delivery_date,
        d.dr_no,
        d.billing_date,
        d.material,
        d.quantity,
        d.unit_price,
        d.status,
        d.date_created,
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
    ORDER BY d.delivery_date DESC, d.del_id DESC
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

$queryBase = http_build_query([
    'q'             => $q,
    'status_filter' => $statusFilter,
    'company_filter'=> $companyFilter,
    'site_filter'   => $siteFilter,
    'material_filter'=> $materialFilter,
    'del_date_from' => $dateFrom,
    'del_date_to'   => $dateTo,
]);
?>

<div class="container-fluid">
    <h2 class="mb-4">Transaction Entry</h2>

    <?php if (!empty($_SESSION['alert'])) { ?>
        <div class="alert alert-<?= htmlspecialchars($_SESSION['alert']['type']) ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['alert']['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['alert']); ?>
    <?php } ?>

    <div class="row">
        <!-- CUSTOMER FORM -->
<div class="col-lg-6 mb-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span id="customer-form-title">Customer</span>
            <button class="btn btn-sm btn-outline-secondary collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#customerFormCollapse"
                    aria-expanded="false"
                    aria-controls="customerFormCollapse">
                Show form
            </button>
        </div>

        <div id="customerFormCollapse" class="collapse"><!-- initially closed -->
            <div class="card-body">
                <form id="customer-form" method="POST" action="pages/trans_entry.php">
                    <input type="hidden" name="form_type" value="customer">
                    <input type="hidden" name="action" id="customer_action" value="create">
                    <input type="hidden" name="customer_id" id="customer_id">

                    <div class="mb-3">
                        <label class="form-label">Company</label>
                        <select name="company_id" id="company_id" class="form-select select2-field">
                            <option value="">-- Select Company --</option>
                            <?php foreach ($companies as $co) { ?>
                                <option value="<?= (int)$co['company_id'] ?>"><?= htmlspecialchars($co['company_name']) ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Contractor</label>
                        <select name="contractor_id" id="contractor_id" class="form-select select2-field">
                            <option value="">-- Select Contractor --</option>
                            <?php foreach ($contractors as $ct) { ?>
                                <option value="<?= (int)$ct['contractor_id'] ?>"><?= htmlspecialchars($ct['contractor_name']) ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Site</label>
                        <select name="site_id" id="site_id" class="form-select select2-field">
                            <option value="">-- Select Site --</option>
                            <?php foreach ($sites as $st) { ?>
                                <option value="<?= (int)$st['site_id'] ?>"><?= htmlspecialchars($st['site_name']) ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Customer Name</label>
                        <input type="text" name="customer_name" id="customer_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Contact No</label>
                        <input type="text" name="contact_no" id="customer_contact_no" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="customer_email" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" id="customer_address" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="customer_status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary" id="customer-submit-btn">Save Customer</button>
                    <button type="button" class="btn btn-secondary d-none" id="customer-cancel-edit-btn">Cancel</button>
                </form>
            </div>
        </div>
    </div>
</div>

        <!-- DELIVERY + TOTAL -->

<div class="col-lg-6 mb-4">
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span id="delivery-form-title">Delivery</span>
            <button class="btn btn-sm btn-outline-secondary collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#deliveryFormCollapse"
                    aria-expanded="false"
                    aria-controls="deliveryFormCollapse">
                Show form
            </button>
        </div>

        <div id="deliveryFormCollapse" class="collapse"><!-- initially closed -->
            <div class="card-body">
                <form id="delivery-form" method="POST" action="pages/trans_entry.php">
                    <input type="hidden" name="form_type" value="delivery">
                    <input type="hidden" name="action" id="delivery_action" value="create">
                    <input type="hidden" name="del_id" id="del_id">

                    <div class="mb-3">
                        <label class="form-label">Customer</label>
                        <select name="delivery_customer_id" id="delivery_customer_id" class="form-select select2-field">
                            <option value="">-- Select Customer --</option>
                            <?php foreach ($customers as $cu) { ?>
                                <option value="<?= (int)$cu['customer_id'] ?>"><?= htmlspecialchars($cu['customer_name']) ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="row mb-3">
                        <div class="col">
                            <label class="form-label">Delivery Date</label>
                            <input type="date" name="delivery_date" id="delivery_date" class="form-control" required>
                        </div>
                        <div class="col">
                            <label class="form-label">Billing Date</label>
                            <input type="date" name="billing_date" id="billing_date" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">DR No</label>
                        <input type="text" name="dr_no" id="dr_no" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Truck</label>
                        <select name="truck_id" id="truck_id" class="form-select select2-field">
                            <option value="">-- Select Truck --</option>
                            <?php foreach ($trucks as $tr) { ?>
                                <option value="<?= (int)$tr['truck_id'] ?>"><?= htmlspecialchars($tr['plate_no']) ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Material</label>
                        <select name="material_id" id="material_id" class="form-select select2-field">
                            <option value="">-- Select Material --</option>
                            <?php foreach ($materials as $m) { ?>
                                <option value="<?= (int)$m['material_id'] ?>"
                                        data-unit-price="<?= htmlspecialchars($m['unit_price']) ?>"
                                        data-name="<?= htmlspecialchars($m['material_name'], ENT_QUOTES) ?>">
                                    <?= htmlspecialchars($m['material_name']) ?>
                                </option>
                            <?php } ?>
                        </select>
                        <input type="hidden" name="material_name" id="material_name">
                    </div>

                    <div class="row mb-3">
                        <div class="col">
                            <label class="form-label">Quantity</label>
                            <input type="number" step="0.01" name="quantity" id="quantity" class="form-control">
                        </div>
                        <div class="col">
                            <label class="form-label">Unit Price</label>
                            <input type="number" step="0.01" name="unit_price" id="unit_price" class="form-control" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="delivery_status" class="form-select">
                            <option value="pending">Pending</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success" id="delivery-submit-btn">Save Delivery</button>
                    <button type="button" class="btn btn-secondary d-none" id="delivery-cancel-edit-btn">Cancel</button>

                </form>
            </div>

            <!-- TOTAL CARD inside same collapse -->
            <div class="card border-0 border-top">
                <div class="card-header">Total</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Total Amount</label>
                        <input type="text" id="total_amount" class="form-control" readonly>
                    </div>
                    <div class="form-text">Total = Quantity × Unit Price (auto-calculated).</div>
                </div>
            </div>
        </div>
    </div>
</div>

    </div>

    <!-- FILTERS + TABLE -->
    <div class="card">
        <div class="card-header">
            <form class="row g-2 align-items-end trans-filter-form" method="GET" action="">
                <div class="col-md-2">
                    <label class="form-label">Delivery From</label>
                    <input type="date" name="del_date_from" class="form-control" value="<?= htmlspecialchars($dateFrom) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Delivery To</label>
                    <input type="date" name="del_date_to" class="form-control" value="<?= htmlspecialchars($dateTo) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Company</label>
                    <select name="company_filter" class="form-select">
                        <option value="">All</option>
                        <?php foreach ($companies as $co) { ?>
                            <option value="<?= (int)$co['company_id'] ?>" <?= $companyFilter == $co['company_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($co['company_name']) ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Site</label>
                    <select name="site_filter" class="form-select">
                        <option value="">All</option>
                        <?php foreach ($sites as $st) { ?>
                            <option value="<?= (int)$st['site_id'] ?>" <?= $siteFilter == $st['site_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($st['site_name']) ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Material</label>
                    <select name="material_filter" class="form-select">
                        <option value="">All</option>
                        <?php foreach ($materials as $m) { ?>
                            <option value="<?= htmlspecialchars($m['material_name']) ?>" <?= $materialFilter === $m['material_name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['material_name']) ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status_filter" class="form-select">
                        <option value="">All</option>
                        <option value="pending"   <?= $statusFilter === 'pending'   ? 'selected' : '' ?>>Pending</option>
                        <option value="delivered"    <?= $statusFilter === 'delivered'    ? 'selected' : '' ?>>Delivered</option>
                        <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>

                <div class="col-md-3 mt-2">
                    <label class="form-label">Search</label>
                    <input type="text" name="q" class="form-control"
                           value="<?= htmlspecialchars($q) ?>"
                           placeholder="DR / Customer / Company / Truck / Material">
                </div>

                <div class="col-md-2 mt-2">
                    <button class="btn btn-primary w-100">Apply Filters</button>
                </div>
            </form>
        </div>

        <div class="card-body table-responsive">
            <table class="table table-striped table-bordered mb-0" id="delivery-table">
                <thead class="table-light">
                    <tr>
                        <th>DR No</th>
                        <th>Delivery Date</th>
                        <th>Customer</th>
                        <th>Company</th>
                        <th>Site</th>
                        <th>Truck</th>
                        <th>Material</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$rows) { ?>
                    <tr><td colspan="12" class="text-center">No records found.</td></tr>
                <?php } else { ?>
                    <?php foreach ($rows as $r) {
                        $total = (float)$r['quantity'] * (float)$r['unit_price'];
                    ?>
                        <tr class="delivery-row"
                            data-del-id="<?= (int)$r['del_id'] ?>"
                            data-customer-id="<?= (int)$r['customer_id'] ?>"
                            data-delivery-date="<?= htmlspecialchars($r['delivery_date']) ?>"
                            data-billing-date="<?= htmlspecialchars($r['billing_date']) ?>"
                            data-dr-no="<?= htmlspecialchars($r['dr_no'], ENT_QUOTES) ?>"
                            data-material="<?= htmlspecialchars($r['material'], ENT_QUOTES) ?>"
                            data-quantity="<?= htmlspecialchars($r['quantity']) ?>"
                            data-unit-price="<?= htmlspecialchars($r['unit_price']) ?>"
                            data-status="<?= htmlspecialchars($r['status']) ?>"
                        >
                            <td><?= htmlspecialchars($r['dr_no']) ?></td>
                            <td><?= htmlspecialchars($r['delivery_date']) ?></td>
                            <td><?= htmlspecialchars($r['customer_name']) ?></td>
                            <td><?= htmlspecialchars($r['company_name']) ?></td>
                            <td><?= htmlspecialchars($r['site_name']) ?></td>
                            <td><?= htmlspecialchars($r['plate_no']) ?></td>
                            <td><?= htmlspecialchars($r['material']) ?></td>
                            <td><?= htmlspecialchars($r['quantity']) ?></td>
                            <td><?= number_format((float)$r['unit_price'], 2) ?></td>
                            <td><?= number_format($total, 2) ?></td>
                            <td><?= htmlspecialchars($r['status']) ?></td>
                            <td class="text-nowrap">
                                <button type="button"
                                        class="btn btn-sm btn-secondary trans-btn-edit-delivery">
                                    Edit
                                </button>
                                <form method="POST" action="pages/trans_entry.php" class="d-inline"
                                      onsubmit="return confirm('Delete this delivery?');">
                                    <input type="hidden" name="form_type" value="delivery">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="del_id" value="<?= (int)$r['del_id'] ?>">
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1) { ?>
            <div class="card-footer">
                <nav>
                    <ul class="pagination mb-0">
                        <li class="page-item <?= $current_page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link"
                               href="?<?= htmlspecialchars($queryBase) ?>&p=<?= $current_page - 1 ?>">&laquo;</a>
                        </li>

                        <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                            <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                <a class="page-link"
                                   href="?<?= htmlspecialchars($queryBase) ?>&p=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php } ?>

                        <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link"
                               href="?<?= htmlspecialchars($queryBase) ?>&p=<?= $current_page + 1 ?>">&raquo;</a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php } ?>
    </div>
</div>
