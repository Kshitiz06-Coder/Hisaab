<?php
/**
 * CSV export for Income / Expenses.
 * GET params:
 *   type  = income | expense   (required)
 *   month = YYYY-MM             (optional — omit to export everything)
 */
require_once __DIR__ . '/includes/auth_check.php';

$type = ($_GET['type'] ?? '') === 'expense' ? 'expense' : 'income';
$table = $type === 'expense' ? 'expenses' : 'income';
$name_col = $type === 'expense' ? 'title' : 'source';
$month_filter = $_GET['month'] ?? '';
$search = trim($_GET['q'] ?? '');

$sql = "SELECT t.entry_date, t.$name_col AS name, COALESCE(c.name,'Uncategorized') AS category, t.amount, t.note
        FROM $table t LEFT JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ?";
$types = 'i';
$params = [$user['id']];

if ($month_filter) {
    $sql .= " AND DATE_FORMAT(t.entry_date, '%Y-%m') = ?";
    $types .= 's';
    $params[] = $month_filter;
}
if ($search !== '') {
    $sql .= " AND (t.$name_col LIKE ? OR t.note LIKE ?)";
    $types .= 'ss';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
}
$sql .= " ORDER BY t.entry_date DESC, t.id DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$rows = mysqli_stmt_get_result($stmt);

$filename = 'hisaab-' . $type . ($month_filter ? '-' . $month_filter : '') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Date', $type === 'expense' ? 'Title' : 'Source', 'Category', 'Amount', 'Note']);
while ($row = mysqli_fetch_assoc($rows)) {
    fputcsv($out, [
        $row['entry_date'],
        $row['name'],
        $row['category'],
        $row['amount'],
        $row['note'],
    ]);
}
fclose($out);
exit;
