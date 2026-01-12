<?php

function generate_soa_no(PDO $conn, int $company_id): string
{
    $stmt = $conn->prepare("
        SELECT company_name
        FROM company
        WHERE company_id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $company_id]);
    $companyName = (string)$stmt->fetchColumn();

    if ($companyName === '') {
        throw new Exception('Invalid company');
    }

    // Format company code (first 5 letters, uppercase, no spaces)
    $companyCode = strtoupper(substr(preg_replace('/\s+/', '', $companyName), 0, 5));

    // Year (last 2 digits)
    $year = date('y');

    // Get next sequence PER COMPANY + YEAR
    $stmt = $conn->prepare("
        SELECT COUNT(*)
        FROM statement_of_account
        WHERE company_id = :company_id
          AND soa_no LIKE :prefix
          AND is_deleted = 0
    ");

    $prefix = "{$companyCode}-{$year}%";

    $stmt->execute([
        ':company_id' => $company_id,
        ':prefix'     => $prefix
    ]);

    $seq = (int)$stmt->fetchColumn() + 1;

    return sprintf('%s-%s%04d', $companyCode, $year, $seq);
}


