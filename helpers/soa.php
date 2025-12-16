<?php

function generate_soa_no(PDO $conn) {
    $year = date('Y');

    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM statement_of_account
        WHERE YEAR(date_created) = :year
    ");
    $stmt->execute([':year' => $year]);

    $seq = (int)$stmt->fetchColumn() + 1;

    return sprintf('SOA-%s-%04d', $year, $seq);
}
