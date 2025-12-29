<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/audit_fields.php';
require_once __DIR__ . '/../helpers/audit.php';
require_once __DIR__ . '/../helpers/soa.php';

$admin       = $_SESSION['admin_id'] ?? null;
$redirectUrl = '/main.php#trans_entry.php';

$soa_id = 0;

if (isset($_GET['soa_id']) && ctype_digit((string)$_GET['soa_id'])) {
    $soa_id = (int)$_GET['soa_id'];
}

/* ============================================================
   SOA CONTEXT (single source of truth)
   ============================================================ */
$soa_id = (isset($_GET['soa_id']) && ctype_digit((string)$_GET['soa_id'])) ? (int)$_GET['soa_id'] : 0;
$soa = null;

if ($soa_id > 0) {
    $stmt = $conn->prepare("
        SELECT soa_id, soa_no, company_id, site_id, terms
        FROM statement_of_account
        WHERE soa_id = :id AND is_deleted = 0
        LIMIT 1
    ");
    $stmt->execute([':id' => $soa_id]);
    $soa = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$soa) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Invalid SOA'];
        header('Location: /main.php#reports.php');
        exit;
    }
}

/* ============================================================
   HANDLE POST (SOA + CUSTOMER + DELIVERY)
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!$admin) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Not authenticated'];
        header("Location: $redirectUrl");
        exit;
    }

    $formType = $_POST['form_type'] ?? '';

    try {

        /* ===================== SOA (CREATE / FINALIZE) ===================== */
        if ($formType === 'soa') {
            $action = $_POST['action'] ?? 'create';

            if ($action !== 'create') {
                throw new Exception('Invalid SOA action');
            }

            $company_id = (int)($_POST['company_id'] ?? 0);
            $site_id    = (int)($_POST['site_id'] ?? 0);

            if ($company_id <= 0 || $site_id <= 0) {
                throw new Exception('Company and Site are required');
            }

            // Terms handling
            $terms_select = $_POST['terms_select'] ?? '*';
            if ($terms_select === 'custom') {
                $terms_custom = (int)($_POST['terms_custom'] ?? 0);
                if ($terms_custom <= 0) {
                    throw new Exception('Custom terms must be greater than 0');
                }
                $terms = (string)$terms_custom;
            } else {
                $terms = $terms_select;
            }

            // Generate SOA number
            $soa_no = generate_soa_no($conn);

            $audit = audit_on_create($admin);
            $billing_date = date('Y-m-d');
            $stmt = $conn->prepare("
        INSERT INTO statement_of_account (
            soa_no,
            company_id,
            site_id,
            billing_date,
            terms,
            is_deleted,
            date_created,
            date_edited,
            created_by,
            edited_by
        ) VALUES (
            :soa_no,
            :company_id,
            :site_id,
            :billing_date,
            :terms,
            'draft',
            0,
            :date_created,
            :date_edited,
            :created_by,
            :edited_by
        )
    ");

            $stmt->execute([
                ':soa_no'        => $soa_no,
                ':company_id'    => $company_id,
                ':site_id'       => $site_id,
                ':billing_date'  => $billing_date,
                ':terms'         => $terms,
                ':date_created'  => $audit['date_created'],
                ':date_edited'   => $audit['date_edited'],
                ':created_by'    => $audit['created_by'],
                ':edited_by'     => $audit['edited_by'],
            ]);

            $newSoaId = (int)$conn->lastInsertId();

            audit_log('statement_of_account', $newSoaId, 'CREATE', null, $_POST, $admin);

            $_SESSION['alert'] = [
                'type'    => 'success',
                'message' => 'SOA created'
            ];

            header("Location: /main.php#trans_entry.php?soa_id=" . $newSoaId);
            exit;
        }

        /* ===================== DELIVERY ===================== */
        if ($formType === 'delivery') {

            $action = $_POST['action'] ?? 'create';
            $id     = (int)($_POST['del_id'] ?? 0);

            $soa_id_post = (int)($_POST['soa_id'] ?? 0);
            if ($soa_id_post <= 0) {
                throw new Exception('Please select an SOA first');
            }

            // ✅ ONLY validate delivery fields for CREATE / UPDATE
            if (in_array($action, ['create', 'update'], true)) {

                $delivery_date = trim($_POST['delivery_date'] ?? '');
                if ($delivery_date === '') {
                    throw new Exception('Delivery date is required');
                }

                $dr_no      = trim($_POST['dr_no'] ?? '');
                $po_number  = trim($_POST['po_number'] ?? '') ?: null;
                $truck_id   = (int)($_POST['truck_id'] ?? 0) ?: null;
                $material   = trim($_POST['material_name'] ?? '');
                $quantity   = (float)($_POST['quantity'] ?? 0);
                $unit_price = (float)($_POST['unit_price'] ?? 0);
                $status     = $_POST['status'] ?? 'pending';
            }

            // SOA validation (must exist)
            $soaStmt = $conn->prepare("
                SELECT soa_id, company_id
                FROM statement_of_account
                WHERE soa_id = :id AND is_deleted = 0
                LIMIT 1
            ");
            $soaStmt->execute([':id' => $soa_id_post]);
            $soaRow = $soaStmt->fetch(PDO::FETCH_ASSOC);
            if (!$soaRow) throw new Exception('Invalid SOA selected');

            $company_id = (int)$soaRow['company_id'];

            if (in_array($action, ['create', 'update'], true)) {
                $dr_no = trim((string)$dr_no);
            }


            // ================= DR NUMBER UNIQUENESS CHECK =================
            if ($action === 'create') {

                if ($dr_no !== '') {
                    $chk = $conn->prepare("
                        SELECT COUNT(*)
                        FROM delivery
                        WHERE TRIM(dr_no) = :dr_no
                        AND is_deleted = 0
                    ");
                    $chk->execute([':dr_no' => $dr_no]);

                    if ((int)$chk->fetchColumn() > 0) {
                        throw new Exception('DR Number already exists. Please use a unique DR Number.');
                    }
                }
            } elseif ($action === 'update') {

                if ($id <= 0) throw new Exception('Invalid delivery ID');

                // fetch old row early (needed for comparison)
                $old = $conn->prepare("SELECT * FROM delivery WHERE del_id=:id AND is_deleted=0");
                $old->execute([':id' => $id]);
                $oldData = $old->fetch(PDO::FETCH_ASSOC);
                if (!$oldData) throw new Exception('Delivery not found');

                $old_dr = trim((string)($oldData['dr_no'] ?? ''));

                // only check if DR was changed
                if ($dr_no !== '' && $dr_no !== $old_dr) {
                    $chk = $conn->prepare("
                        SELECT COUNT(*)
                        FROM delivery
                        WHERE TRIM(dr_no) = :dr_no
                        AND del_id != :del_id
                        AND is_deleted = 0
                    ");
                    $chk->execute([
                        ':dr_no'  => $dr_no,
                        ':del_id' => $id
                    ]);

                    if ((int)$chk->fetchColumn() > 0) {
                        throw new Exception('DR Number already exists. Please use a unique DR Number.');
                    }
                }

                // ✅ IMPORTANT: reuse $oldData later in update block
            }

            if ($action === 'create') {
                $audit = audit_on_create($admin);

                $stmt = $conn->prepare("
                    INSERT INTO delivery (
                        soa_id, company_id, delivery_date, dr_no,
                        truck_id, material,
                        quantity, unit_price, po_number,
                        status, is_deleted,
                        date_created, date_edited, created_by, edited_by
                    )
                    VALUES
                        (:soa_id, :company_id, :delivery_date, :dr_no,
                         :truck_id, :material,
                         :quantity, :unit_price, :po_number,
                         :status, 0,
                         :date_created, :date_edited, :created_by, :edited_by)
                ");
                $stmt->execute([
                    ':soa_id'        => $soa_id_post,
                    ':company_id'    => $company_id,
                    ':delivery_date' => $delivery_date,
                    ':dr_no'         => $dr_no,
                    ':truck_id'      => $truck_id,
                    ':material'      => $material,
                    ':quantity'      => $quantity,
                    ':unit_price'    => $unit_price,
                    ':po_number'     => $po_number,
                    ':status'        => $status,
                    ':date_created'  => $audit['date_created'],
                    ':date_edited'   => $audit['date_edited'],
                    ':created_by'    => $audit['created_by'],
                    ':edited_by'     => $audit['edited_by'],
                ]);

                if ($stmt->rowCount() === 0) {
                    throw new Exception('Delivery insert failed (rowCount=0)');
                }

                $newDelId = (int)$conn->lastInsertId();
                audit_log('delivery', $newDelId, 'CREATE', null, $_POST, $admin);

                $_SESSION['alert'] = ['type' => 'success', 'message' => 'Delivery created'];

                $insertMode = (int)($_POST['insert_mode'] ?? 0);

                if ($insertMode === 1) {
                    // stay on page, keep form values
                    header("Location: /main.php#trans_entry.php?soa_id={$soa_id_post}&refresh=1");
                } else {
                    // normal save behavior
                    header("Location: /main.php#trans_entry.php?soa_id={$soa_id_post}&refresh=1");
                }
                exit;
            }

            if ($action === 'update') {
                if ($id <= 0) throw new Exception('Invalid delivery ID');

                $audit = audit_on_update($admin);

                $stmt = $conn->prepare("
                    UPDATE delivery SET
                        soa_id = :soa_id,
                        company_id = :company_id,
                        delivery_date = :delivery_date,
                        dr_no = :dr_no,
                        truck_id = :truck_id,
                        material = :material,
                        quantity = :quantity,
                        unit_price = :unit_price,
                        po_number = :po_number,
                        status = :status,
                        date_edited = :date_edited,
                        edited_by = :edited_by
                    WHERE del_id = :id
                ");
                $stmt->execute([
                    ':id'            => $id,
                    ':soa_id'        => $soa_id_post,
                    ':company_id'    => $company_id,
                    ':delivery_date' => $delivery_date,
                    ':dr_no'         => $dr_no,
                    ':truck_id'      => $truck_id,
                    ':material'      => $material,
                    ':quantity'      => $quantity,
                    ':unit_price'    => $unit_price,
                    ':po_number'     => $po_number,
                    ':status'        => $status,
                    ':date_edited'   => $audit['date_edited'],
                    ':edited_by'     => $audit['edited_by'],
                ]);
                audit_log('delivery', $id, 'UPDATE', $oldData, $_POST, $admin);
                $_SESSION['alert'] = ['type' => 'success', 'message' => 'Delivery updated'];
                header("Location: /main.php#trans_entry.php?soa_id={$soa_id_post}&refresh=1");
                exit;
            }

            if ($action === 'delete') {
                if ($id <= 0) throw new Exception('Invalid delivery ID');

                $old = $conn->prepare("SELECT * FROM delivery WHERE del_id=:id AND is_deleted=0");
                $old->execute([':id' => $id]);
                $oldData = $old->fetch(PDO::FETCH_ASSOC);
                if (!$oldData) throw new Exception('Delivery not found');

                $audit = audit_on_update($admin);

                $stmt = $conn->prepare("
        UPDATE delivery SET
            is_deleted  = 1,
            date_edited = :date_edited,
            edited_by   = :edited_by
        WHERE del_id = :id
    ");
                $stmt->execute([
                    ':id'         => $id,
                    ':date_edited' => $audit['date_edited'],
                    ':edited_by'  => $audit['edited_by'],
                ]);

                audit_log('delivery', $id, 'DELETE', $oldData, ['is_deleted' => 1], $admin);

                $_SESSION['alert'] = ['type' => 'success', 'message' => 'Delivery deleted'];
                header("Location: /main.php#trans_entry.php?soa_id={$soa_id_post}&refresh=1");
                exit;
            }
        }
    } catch (Exception $e) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => $e->getMessage()];

        $query = [];
        if ($soa_id > 0) $query[] = 'soa_id=' . $soa_id;
        if (!empty($_POST['insert_mode'])) $query[] = 'keep=1';

        $qs = $query ? '?' . implode('&', $query) : '';

        header("Location: {$redirectUrl}{$qs}");
        exit;
    }

    // fallback
    header("Location: $redirectUrl");
    exit;
}

/* ============================================================
   LOOKUP DATA
   ============================================================ */

// SOA list
$soas = $conn->query("
    SELECT
        s.soa_id,
        s.soa_no,
        co.company_name,
        si.site_name
    FROM statement_of_account s
    JOIN company co ON s.company_id = co.company_id
    JOIN site si ON s.site_id = si.site_id
    WHERE s.is_deleted = 0
    ORDER BY s.date_created DESC, s.soa_id DESC
")->fetchAll(PDO::FETCH_ASSOC);

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
    SELECT site_id, site_name, remarks
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

// customers (filtered if SOA exists)
if ($soa) {
    $stmt = $conn->prepare("
        SELECT customer_id, customer_name
        FROM customer
        WHERE is_deleted = 0
          AND company_id = :company_id
          AND site_id = :site_id
        ORDER BY customer_name
    ");
    $stmt->execute([
        ':company_id' => $soa['company_id'],
        ':site_id'    => $soa['site_id']
    ]);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $customers = $conn->query("
        SELECT customer_id, customer_name
        FROM customer
        WHERE is_deleted = 0
        ORDER BY customer_name
    ")->fetchAll(PDO::FETCH_ASSOC);
}

// materials
$materials = $conn->query("
    SELECT material_id, material_name, unit_price
    FROM materials
    WHERE is_deleted = 0
      AND status = 'active'
    ORDER BY material_name
")->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
   FILTERS + DELIVERY LIST
   ============================================================ */

require_once __DIR__ . '/../helpers/alerts.php';

$q              = trim($_GET['q'] ?? '');
$statusFilter   = $_GET['status_filter'] ?? '';
$siteFilter     = isset($_GET['site_filter']) ? (int)$_GET['site_filter'] : 0;
$materialFilter = $_GET['material_filter'] ?? '';
$truckFilter = isset($_GET['truck_filter']) ? (int)$_GET['truck_filter'] : 0;

$where  = "d.is_deleted = 0";
$params = [];

// STRICT: deliveries only show when SOA is selected
if ($soa_id > 0) {
    $where .= " AND d.soa_id = :soa_id";
    $params[':soa_id'] = $soa_id;
} else {
    // explicitly block listing
    $where .= " AND 0 = 1";
}

// status
if ($statusFilter !== '') {
    $where .= " AND d.status = :status";
    $params[':status'] = $statusFilter;
}


// material filter
if ($materialFilter !== '') {
    $where .= " AND d.material = :material_filter";
    $params[':material_filter'] = $materialFilter;
}

// truck filter
if ($truckFilter > 0) {
    $where .= " AND d.truck_id = :truck_id";
    $params[':truck_id'] = $truckFilter;
}

// free text
if ($q !== '') {
    $where .= " AND (
        d.dr_no LIKE :q
        OR co.company_name LIKE :q
        OR s.site_name LIKE :q
        OR t.plate_no LIKE :q
        OR d.material LIKE :q
        OR d.po_number LIKE :q
    )";
    $params[':q'] = '%' . $q . '%';
}

// pagination
$per_page     = 5;
$current_page = (isset($_GET['p']) && ctype_digit((string)$_GET['p'])) ? (int)$_GET['p'] : 1;
if ($current_page < 1) $current_page = 1;

// count
$countSql = "
    SELECT COUNT(*)
    FROM delivery d
    JOIN company co ON d.company_id = co.company_id
    JOIN statement_of_account s0 ON d.soa_id = s0.soa_id
    JOIN site s ON s0.site_id = s.site_id
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
        d.truck_id,
        d.material,
        d.quantity,
        d.unit_price,
        d.status,
        d.po_number,
        co.company_name,
        s.site_name,
        t.plate_no
    FROM delivery d
    JOIN company co ON d.company_id = co.company_id
    JOIN statement_of_account s0 ON d.soa_id = s0.soa_id
    JOIN site s ON s0.site_id = s.site_id
    LEFT JOIN truck t ON d.truck_id = t.truck_id
    WHERE $where
    ORDER BY d.delivery_date DESC, d.del_id DESC
    LIMIT :limit OFFSET :offset
";


$listStmt = $conn->prepare($listSql);
foreach ($params as $k => $v) $listStmt->bindValue($k, $v);
$listStmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$rows = $listStmt->fetchAll(PDO::FETCH_ASSOC);

$queryBase = http_build_query([
    'soa_id'         => $soa_id,
    'q'              => $q,
    'status_filter'  => $statusFilter,
    'material_filter' => $materialFilter,
    'truck_filter'   => $truckFilter,
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

    <!-- ===================== SOA BAR ===================== -->
    <div class="row mb-4">

        <!-- LEFT: DELIVERY FORM (65%) -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">Delivery</div>
                <div class="card-body">

                    <div class="card-body">
                        <form id="delivery-form" method="POST" action="pages/trans_entry.php">
                            <fieldset id="delivery-fieldset"
                                <?= !$soa ? 'disabled' : '' ?>>
                                <input type="hidden" name="form_type" value="delivery">
                                <input type="hidden" name="action" id="delivery_action" value="create">
                                <input type="hidden" name="del_id" id="del_id">
                                <input type="hidden" name="soa_id" id="soa_id" value="<?= (int)$soa_id ?>">
                                <input type="hidden" name="insert_mode" id="insert_mode" value="0">
                                <input type="hidden" name="keep" id="delivery_keep" value="0">

                                <div class="mb-3">
                                </div>

                                <!-- <div class="row mb-3">
                                    <div class="col">
                                        <label class="form-label">Delivery Date</label>
                                        <input type="date" name="delivery_date" id="delivery_date" class="form-control" required>
                                    </div>
                                </div> -->

                                <div class="row mb-3">
                                    <div class="col">
                                        <label class="form-label">Delivery Date</label>
                                        <input type="date" name="delivery_date" id="delivery_date" class="form-control" required>
                                    </div>
                                    <div class="col">
                                        <label class="form-label">DR No</label>
                                        <input type="text" name="dr_no" id="dr_no" class="form-control">
                                    </div>
                                    <div class="col">
                                        <label class="form-label">PO Number</label>
                                        <input type="text" name="po_number" id="po_number" class="form-control">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col">
                                        <label class="form-label">Truck</label>
                                        <select id="truck_id" name="truck_id" class="form-select select2-field">
                                            <option value="">-- Select Truck --</option>
                                            <?php foreach ($trucks as $t): ?>
                                                <option value="<?= $t['truck_id'] ?>">
                                                    <?= htmlspecialchars($t['plate_no']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col">
                                        <label class="form-label">Material</label>
                                        <select name="material_id" id="material_id" class="form-select select2-field">
                                            <option value="">-- Select Material --</option>
                                            <?php foreach ($materials as $m): ?>
                                                <option value="<?= (int)$m['material_id'] ?>"><?= htmlspecialchars($m['material_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="hidden" name="material_name" id="material_name">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col">
                                        <label class="form-label">Quantity</label>
                                        <input type="number" step="0.01" name="quantity" id="quantity" class="form-control">
                                    </div>
                                    <div class="col">
                                        <label class="form-label">Unit Price</label>
                                        <input type="number" step="0.01" name="unit_price" id="unit_price" class="form-control">
                                    </div>
                                    <div class="col">
                                        <label class="form-label">Status</label>
                                        <select name="status" id="delivery_status" class="form-select">
                                            <option value="pending">Pending</option>
                                            <option value="delivered">Delivered</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" id="delivery_status" class="form-select">
                                        <option value="pending">Pending</option>
                                        <option value="delivered">Delivered</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div> -->

                                <button type="submit" class="btn btn-success" id="delivery-submit-btn">
                                    Save Delivery
                                </button>
                                <button type="button"
                                    class="btn btn-outline-primary ms-2"
                                    id="delivery-insert-btn">
                                    Insert
                                </button>
                                <button type="button" class="btn btn-secondary d-none" id="delivery-cancel-edit-btn">Cancel</button>
                            </fieldset>
                        </form>
                    </div>

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

        <!-- RIGHT: SOA PANEL (35%) -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">Statement of Account</div>
                <div class="card-body">

                    <label class="form-label">SOA</label>
                    <select id="soa_select" class="form-select select2-field mb-3">
                        <option value="">-- Select SOA --</option>
                        <?php foreach ($soas as $s): ?>
                            <option value="<?= $s['soa_id'] ?>" <?= $soa_id == $s['soa_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['soa_no']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <small class="text-muted d-block mb-3">
                        Please select a SOA first to enable delivery entry and printing.
                    </small>

                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#soaCreateModal"
                            <?= $soa ? 'disabled' : '' ?>>
                            Create New SOA
                        </button>

                        <a class="btn btn-success"
                            target="_blank"
                            href="<?= $soa ? 'pages/reports_print.php?soa_id=' . $soa_id : '#' ?>"
                            <?= !$soa ? 'style="pointer-events:none;opacity:.6"' : '' ?>>
                            Print SOA
                        </a>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

<!-- FILTERS + TABLE -->
<div class="card">
    <div class="card-header">
        <form class="row g-2 align-items-end trans-filter-form flex-nowrap" method="GET" action="">
            <input type="hidden" name="soa_id" value="<?= (int)$soa_id ?>">
            <input type="hidden" name="form_type" value="filter">
            <!-- SEARCH (big) -->
            <div class="col-lg-5 col-md-12">
                <label class="form-label">Search</label>
                <input type="text" name="q" class="form-control"
                    value="<?= htmlspecialchars($q) ?>"
                    placeholder="DR / Customer / Material / Truck / PO">
            </div>

            <!-- MATERIAL -->
            <div class="col-lg-2 col-md-4">
                <label class="form-label">Material</label>
                <select name="material_filter" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($materials as $m): ?>
                        <option value="<?= htmlspecialchars($m['material_name']) ?>"
                            <?= $materialFilter === $m['material_name'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['material_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- TRUCK -->
            <div class="col-lg-2 col-md-4">
                <label class="form-label">Truck</label>
                <select name="truck_filter" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($trucks as $t): ?>
                        <option value="<?= (int)$t['truck_id'] ?>"
                            <?= $truckFilter === (int)$t['truck_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['plate_no']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- STATUS -->
            <div class="col-lg-2 col-md-4">
                <label class="form-label">Status</label>
                <select name="status_filter" class="form-select">
                    <option value="">All</option>
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                    <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>

            <!-- APPLY -->
            <div class="col-lg-1">
                <button class="btn btn-primary w-100">Apply</button>
            </div>
        </form>
    </div>

    <div class="card-body table-responsive">
        <table class="table table-striped table-bordered mb-0" id="delivery-table">
            <thead class="table-light">
                <tr>
                    <th>DR No</th>
                    <th>Delivery Date</th>
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
                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="12" class="text-center">No records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $r):
                        $total = (float)$r['quantity'] * (float)$r['unit_price'];
                    ?>
                        <tr class="delivery-row"
                            data-del-id="<?= (int)$r['del_id'] ?>"
                            data-delivery-date="<?= htmlspecialchars($r['delivery_date']) ?>"
                            data-truck-id="<?= (int)$r['truck_id'] ?>"
                            data-dr-no="<?= htmlspecialchars($r['dr_no'], ENT_QUOTES) ?>"
                            data-material="<?= htmlspecialchars($r['material'], ENT_QUOTES) ?>"
                            data-quantity="<?= htmlspecialchars($r['quantity']) ?>"
                            data-unit-price="<?= htmlspecialchars($r['unit_price']) ?>"
                            data-status="<?= htmlspecialchars($r['status']) ?>"
                            data-po="<?= htmlspecialchars($r['po_number'] ?? '', ENT_QUOTES) ?>">
                            <td><?= htmlspecialchars($r['dr_no']) ?></td>
                            <td><?= htmlspecialchars($r['delivery_date']) ?></td>
                            <td><?= htmlspecialchars($r['company_name']) ?></td>
                            <td><?= htmlspecialchars($r['site_name']) ?></td>
                            <td class="truck-plate"><?= htmlspecialchars($r['plate_no'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['material']) ?></td>
                            <td><?= htmlspecialchars($r['quantity']) ?></td>
                            <td><?= number_format((float)$r['unit_price'], 2) ?></td>
                            <td><?= number_format($total, 2) ?></td>
                            <td><?= htmlspecialchars($r['status']) ?></td>
                            <td class="text-nowrap">
                                <button type="button" class="btn btn-sm btn-secondary trans-btn-edit-delivery">
                                    Edit
                                </button>
                                <form method="POST" action="pages/trans_entry.php" class="d-inline"
                                    onsubmit="return confirm('Delete this delivery?');">
                                    <input type="hidden" name="form_type" value="delivery">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="del_id" value="<?= (int)$r['del_id'] ?>">
                                    <input type="hidden" name="soa_id" value="<?= (int)$soa_id ?>">
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
        <div class="card-footer">
            <nav>
                <ul class="pagination mb-0">
                    <li class="page-item <?= $current_page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= htmlspecialchars($queryBase) ?>&p=<?= $current_page - 1 ?>">&laquo;</a>
                    </li>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= htmlspecialchars($queryBase) ?>&p=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= htmlspecialchars($queryBase) ?>&p=<?= $current_page + 1 ?>">&raquo;</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>

    <!-- SOA CREATE MODAL (BACKEND ENABLED) -->
    <div class="modal fade" id="soaCreateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" id="soa-create-form" method="POST" action="pages/trans_entry.php">
                <input type="hidden" name="form_type" value="soa">
                <input type="hidden" name="action" value="create">

                <div class="modal-header">
                    <h5 class="modal-title">Create New SOA</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Company</label>
                        <select name="company_id" class="form-select" required>
                            <option value="">-- Select Company --</option>
                            <?php foreach ($companies as $co): ?>
                                <option value="<?= (int)$co['company_id'] ?>"><?= htmlspecialchars($co['company_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Site</label>
                        <select name="site_id" class="form-select" required>
                            <option value="">-- Select Site --</option>
                            <?php foreach ($sites as $st):
                                $label = $st['site_name'];
                                if (!empty($st['remarks'])) $label .= ' - ' . $st['remarks'];
                            ?>
                                <option value="<?= (int)$st['site_id'] ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Terms of Payment</label>

                        <select name="terms_select" id="terms_select" class="form-select " required>
                            <option value="*" selected>* (No cash payment)</option>
                            <option value="15">15 Days</option>
                            <option value="30">30 Days</option>
                            <option value="45">45 Days</option>
                            <option value="custom">Custom</option>
                        </select>

                        <input type="number"
                            name="terms_custom"
                            id="terms_custom"
                            class="form-control mt-2 d-none"
                            placeholder="Enter number of days"
                            min="0"
                            max="365">
                    </div>


                    <div class="alert alert-secondary mb-0">
                        SOA number is auto-generated.
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
                    <button class="btn btn-primary" type="submit">Create SOA</button>
                </div>
            </form>
        </div>
    </div>

</div>
</div>

<script>
    window.currentSOAId = <?= json_encode($soa_id) ?>;

    document.addEventListener('shown.bs.modal', function(event) {
        if (event.target.id !== 'soaCreateModal') return;

        const termsSelect = document.getElementById('terms_select');
        const termsCustom = document.getElementById('terms_custom');

        if (!termsSelect || !termsCustom) return;

        function toggleCustomTerms() {
            if (termsSelect.value === 'custom') {
                termsCustom.classList.remove('d-none');
                termsCustom.required = true;
            } else {
                termsCustom.classList.add('d-none');
                termsCustom.required = false;
                termsCustom.value = '';
            }
        }

        // initial state when modal opens
        toggleCustomTerms();

        // avoid double binding
        termsSelect.removeEventListener('change', toggleCustomTerms);
        termsSelect.addEventListener('change', toggleCustomTerms);
    });
</script>