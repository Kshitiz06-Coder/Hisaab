<?php
$currency = $user['currency'] ?: 'Rs';

// ---- Handling form submissions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $source = trim($_POST['source'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $entry_date = $_POST['entry_date'] ?? date('Y-m-d');
        $note = trim($_POST['note'] ?? '');

        if ($source === '' || $amount <= 0) {
            flash('error', 'Please enter a valid source and amount.');
        } elseif ($action === 'add') {
            $stmt = mysqli_prepare($conn, "INSERT INTO income (user_id, category_id, source, amount, entry_date, note) VALUES (?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'iisdss', $user['id'], $category_id, $source, $amount, $entry_date, $note);
            mysqli_stmt_execute($stmt);
            flash('success', 'Income added.');
        } else {
            $id = (int)$_POST['id'];
            $stmt = mysqli_prepare($conn, "UPDATE income SET category_id=?, source=?, amount=?, entry_date=?, note=? WHERE id=? AND user_id=?");
            mysqli_stmt_bind_param($stmt, 'isdssii', $category_id, $source, $amount, $entry_date, $note, $id, $user['id']);
            mysqli_stmt_execute($stmt);
            flash('success', 'Income updated.');
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = mysqli_prepare($conn, "DELETE FROM income WHERE id=? AND user_id=?");
        mysqli_stmt_bind_param($stmt, 'ii', $id, $user['id']);
        mysqli_stmt_execute($stmt);
        flash('success', 'Income entry deleted.');
    }
    redirect('income.php' . (!empty($_POST['month_filter']) ? '?month=' . urlencode($_POST['month_filter']) : ''));
}

$month_filter = $_GET['month'] ?? '';
$sql = "SELECT i.*, c.name AS cat_name, c.icon AS cat_icon FROM income i LEFT JOIN categories c ON i.category_id = c.id WHERE i.user_id = ?";
$types = 'i'; $params = [$user['id']];
if ($month_filter) {
    $sql .= " AND DATE_FORMAT(i.entry_date, '%Y-%m') = ?";
    $types .= 's'; $params[] = $month_filter;
}
$sql .= " ORDER BY i.entry_date DESC, i.id DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$rows = mysqli_stmt_get_result($stmt);

$total = get_total($conn, 'income', $user['id'], $month_filter ?: null);
$categories = get_categories($conn, $user['id'], 'income');
$cat_list = [];
while ($c = mysqli_fetch_assoc($categories)) $cat_list[] = $c;

$page_title = 'Income';
$page_sub = 'All the money coming in';
?>


<?php?>
