<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($total_records)) $total_records = 0;
$limit = isset($limit) ? intval($limit) : 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$total_pages = max(1, ceil($total_records / $limit));
$offset = ($page - 1) * $limit;

$pagination = [
    'page' => $page,
    'limit' => $limit,
    'offset' => $offset,
    'total_pages' => $total_pages,
    'total_records' => $total_records
];

if ($total_pages > 1):
?>
<nav aria-label="Page navigation">
    <ul class="pagination justify-content-center mt-3">
        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $page - 1 ?>" tabindex="-1">Previous</a>
        </li>

        <?php
        $start = max(1, $page - 3);
        $end = min($total_pages, $page + 3);
        if ($start > 1): ?>
            <li class="page-item"><a class="page-link" href="?page=1">1</a></li>
            <?php if ($start > 2): ?>
                <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>

        <?php if ($end < $total_pages): ?>
            <?php if ($end < $total_pages - 1): ?>
                <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>
            <li class="page-item"><a class="page-link" href="?page=<?= $total_pages ?>"><?= $total_pages ?></a></li>
        <?php endif; ?>

        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
        </li>
    </ul>
</nav>
<?php endif; ?>
