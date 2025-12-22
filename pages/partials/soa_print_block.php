<?php
function render_soa_print(PDO $conn, int $soa_id, string $header_image, string $printed_by) {

    /* ---------- FETCH SOA ---------- */
    $soaStmt = $conn->prepare("
        SELECT s.soa_no, s.terms, s.status,
               co.company_name,
               si.site_name
        FROM statement_of_account s
        JOIN company co ON s.company_id = co.company_id
        JOIN site si ON s.site_id = si.site_id
        WHERE s.soa_id = :id AND s.is_deleted = 0
        LIMIT 1
    ");
    $soaStmt->execute([':id' => $soa_id]);
    $soa = $soaStmt->fetch(PDO::FETCH_ASSOC);

    if (!$soa || $soa['status'] !== 'finalized') {
        return;
    }

    /* ---------- FETCH DELIVERIES ---------- */
    $stmt = $conn->prepare("
        SELECT
            d.delivery_date,
            d.billing_date,
            d.dr_no,
            d.material,
            d.quantity,
            d.unit_price,
            d.po_number,
            t.plate_no
        FROM delivery d
        LEFT JOIN truck t ON d.truck_id = t.truck_id
        WHERE d.soa_id = :soa_id
          AND d.is_deleted = 0
        ORDER BY d.delivery_date ASC, d.dr_no ASC
    ");
    $stmt->execute([':soa_id' => $soa_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        return;
    }

    /* ---------- TOTALS ---------- */
    $total_qty = 0;
    $total_amount = 0;
    $po_list = [];

    foreach ($rows as $r) {
        $total_qty += $r['quantity'];
        $total_amount += $r['quantity'] * $r['unit_price'];
        if (!empty($r['po_number'])) {
            $po_list[$r['po_number']] = true;
        }
    }

    $po_numbers_display = implode(", ", array_keys($po_list));
    $terms_display = $soa['terms'];
    $billing_date = $rows[0]['billing_date'];

    /* ---------- OUTPUT HTML ---------- */
    ?>
    <!-- 🔽 YOUR EXISTING SOA PRINT HTML GOES HERE 🔽 -->
    <!-- (the exact layout you pasted earlier) -->
    <?php
} // ✅ THIS CLOSING BRACE WAS REQUIRED
?>