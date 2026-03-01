<?php
require 'admin_common.php';
require_admin();
require 'config.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

$msg = '';
$err = '';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS test_todos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(120) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    $pdo->exec("ALTER TABLE test_todos ADD UNIQUE KEY uniq_email (email)");
} catch (Exception $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_todo'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $err = 'Ungültiger Vorgang.';
    } elseif ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Name/E‑Mail ist ungültig.';
    } else {
        $check = $pdo->prepare("SELECT id FROM test_todos WHERE email = ? LIMIT 1");
        $check->execute([$email]);
        if ($check->fetch()) {
            $err = 'Bereits vorhanden.';
        } else {
            $ins = $pdo->prepare("INSERT INTO test_todos (name, email) VALUES (?, ?)");
            $ins->execute([$name, $email]);
            $msg = 'Eintrag hinzugefügt.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delId = (int)($_POST['delete_id'] ?? 0);
    $token = $_POST['csrf_token'] ?? '';
    if ($delId > 0 && hash_equals($_SESSION['csrf_token'], $token)) {
        $del = $pdo->prepare("DELETE FROM test_todos WHERE id = ?");
        $del->execute([$delId]);
        $msg = 'Eintrag gelöscht.';
    } else {
        $err = 'Löschen fehlgeschlagen.';
    }
}

$bdIds = $_POST['ids'] ?? [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete']) && is_array($bdIds)) {
    $token = $_POST['csrf_token'] ?? '';
    $ids = array_values(array_filter(array_map('intval', $bdIds), function($v){ return $v > 0; }));
    if ($ids && hash_equals($_SESSION['csrf_token'], $token)) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $del = $pdo->prepare("DELETE FROM test_todos WHERE id IN ($in)");
        $del->execute($ids);
        $msg = 'Ausgewählte Einträge gelöscht.';
    } else {
        $err = 'Löschen fehlgeschlagen.';
    }
}

$export = isset($_GET['export']) && $_GET['export'] === 'csv';
$exportType = $_GET['type'] ?? 'all';

$threshold = 70;
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (skey VARCHAR(64) PRIMARY KEY, svalue VARCHAR(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $sth = $pdo->prepare("SELECT svalue FROM settings WHERE skey = 'passing_threshold' LIMIT 1");
    $sth->execute();
    $rt = $sth->fetch();
    if ($rt && isset($rt['svalue'])) {
        $threshold = max(0, min(100, (int)$rt['svalue']));
    }
} catch (Exception $e) {}

$rows = $pdo->query("
    SELECT t.id, t.name, t.email, t.created_at,
           CASE WHEN ta.cnt IS NULL THEN 0 ELSE 1 END AS done
    FROM test_todos t
    LEFT JOIN (
        SELECT worker_identifier, COUNT(*) AS cnt
        FROM test_attempts
        WHERE worker_identifier IS NOT NULL AND worker_identifier <> '' AND score >= ".(int)$threshold."
        GROUP BY worker_identifier
    ) ta ON ta.worker_identifier COLLATE utf8mb4_unicode_ci = t.email COLLATE utf8mb4_unicode_ci
    ORDER BY t.created_at DESC
")->fetchAll();

$pending = array_values(array_filter($rows, function ($r) { return (int)$r['done'] === 0; }));
$done = array_values(array_filter($rows, function ($r) { return (int)$r['done'] === 1; }));

if ($export) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=todos.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['id','name','email','status']);
    $data = $rows;
    if ($exportType === 'pending') {
        $data = $pending;
    } elseif ($exportType === 'done') {
        $data = $done;
    }
    foreach ($data as $r) {
        fputcsv($out, [(int)$r['id'], $r['name'], $r['email'], ((int)$r['done'] === 1 ? 'done' : 'pending')]);
    }
    fclose($out);
    exit;
}

admin_header(__('nav_todos'));
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= htmlspecialchars(__('todos_title')) ?></h1>
    <a class="btn btn-sm btn-outline-secondary" href="admin_dashboard.php"><?= htmlspecialchars(__('back')) ?></a>
    </div>

<?php if ($msg): ?>
    <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
<?php endif; ?>

<div class="card p-4 shadow-sm bg-white mb-4">
    <h2 class="h6 mb-3"><?= htmlspecialchars(__('todos_add_title')) ?></h2>
    <form method="post" class="row g-2">
        <input type="hidden" name="add_todo" value="1">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <div class="col-md-5">
            <label class="form-label"><?= htmlspecialchars(__('label_name')) ?></label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="col-md-5">
            <label class="form-label"><?= htmlspecialchars(__('label_email')) ?></label>
            <input type="email" name="email" class="form-control" required autocomplete="email" inputmode="email">
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100"><?= htmlspecialchars(__('add')) ?></button>
        </div>
    </form>
    <div class="form-text"><?= htmlspecialchars(__('todos_hint')) ?></div>
<s></s></div>

<div class="d-flex gap-2 mb-3">
    <a href="admin_todos.php?export=csv&type=all" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(__('csv_export_all')) ?></a>
    <a href="admin_todos.php?export=csv&type=pending" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(__('csv_export_pending')) ?></a>
    <a href="admin_todos.php?export=csv&type=done" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(__('csv_export_done')) ?></a>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card p-3 shadow-sm">
            <h2 class="h6 mb-3"><?= htmlspecialchars(__('pending')) ?></h2>
            <?php if (!$pending): ?>
                <p class="text-muted mb-0"><?= htmlspecialchars(__('no_pending')) ?></p>
            <?php else: ?>
                <div class="table-responsive">
                    <form method="post" id="bulkFormPending">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                        <tr>
                            <th><input type="checkbox" id="chkAllPending"></th>
                            <th>#</th>
                            <th><?= htmlspecialchars(__('label_name')) ?></th>
                            <th><?= htmlspecialchars(__('label_email')) ?></th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($pending as $r): ?>
                            <tr>
                                <td><input type="checkbox" name="ids[]" value="<?= (int)$r['id'] ?>"></td>
                                <td><?= (int)$r['id'] ?></td>
                                <td><?= htmlspecialchars($r['name']) ?></td>
                                <td><?= htmlspecialchars($r['email']) ?></td>
                                <td class="text-end">
                                    <form method="post" class="d-inline" onsubmit="return confirm(<?= json_encode(__('confirm_delete')) ?>);">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="delete_id" value="<?= (int)$r['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><?= htmlspecialchars(__('delete')) ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </form>
                    <div class="mt-2">
                        <button class="btn btn-sm btn-outline-danger" id="bulkDeletePending"><?= htmlspecialchars(__('bulk_delete_selected')) ?></button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-3 shadow-sm">
            <h2 class="h6 mb-3"><?= htmlspecialchars(__('done')) ?></h2>
            <?php if (!$done): ?>
                <p class="text-muted mb-0"><?= htmlspecialchars(__('no_done')) ?></p>
            <?php else: ?>
                <div class="table-responsive">
                    <form method="post" id="bulkFormDone">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                        <tr>
                            <th><input type="checkbox" id="chkAllDone"></th>
                            <th>#</th>
                            <th><?= htmlspecialchars(__('label_name')) ?></th>
                            <th><?= htmlspecialchars(__('label_email')) ?></th>
                            <th><?= htmlspecialchars(__('status')) ?></th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($done as $r): ?>
                            <tr>
                                <td><input type="checkbox" name="ids[]" value="<?= (int)$r['id'] ?>"></td>
                                <td><?= (int)$r['id'] ?></td>
                                <td><?= htmlspecialchars($r['name']) ?></td>
                                <td><?= htmlspecialchars($r['email']) ?></td>
                                <td><span class="badge text-bg-success"><?= htmlspecialchars(__('done')) ?></span></td>
                                <td class="text-end">
                                    <form method="post" class="d-inline" onsubmit="return confirm(<?= json_encode(__('confirm_delete')) ?>);">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="delete_id" value="<?= (int)$r['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><?= htmlspecialchars(__('delete')) ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </form>
                    <div class="mt-2">
                        <button class="btn btn-sm btn-outline-danger" id="bulkDeleteDone"><?= htmlspecialchars(__('bulk_delete_selected')) ?></button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function bulkDelete(ids) {
  if (!ids.length) return;
  if (!confirm(<?= json_encode(__('confirm_delete')) ?>)) return;
  const form = document.createElement('form');
  form.method = 'post';
  form.action = 'admin_todos.php';
  const csrf = document.createElement('input');
  csrf.type = 'hidden'; csrf.name = 'csrf_token'; csrf.value = '<?= htmlspecialchars($_SESSION['csrf_token']) ?>';
  form.appendChild(csrf);
  const flag = document.createElement('input');
  flag.type = 'hidden'; flag.name = 'bulk_delete'; flag.value = '1';
  form.appendChild(flag);
  ids.forEach(function(id) {
    const inp = document.createElement('input');
    inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = id;
    form.appendChild(inp);
  });
  document.body.appendChild(form);
  form.submit();
}
document.getElementById('chkAllPending')?.addEventListener('change', function(e) {
  document.querySelectorAll('#bulkFormPending input[type=checkbox][name="ids[]"]').forEach(function(cb){ cb.checked = e.target.checked; });
});
document.getElementById('chkAllDone')?.addEventListener('change', function(e) {
  document.querySelectorAll('#bulkFormDone input[type=checkbox][name="ids[]"]').forEach(function(cb){ cb.checked = e.target.checked; });
});
document.getElementById('bulkDeletePending')?.addEventListener('click', function() {
  const ids = Array.from(document.querySelectorAll('#bulkFormPending input[name="ids[]"]:checked')).map(i=>i.value);
  bulkDelete(ids);
});
document.getElementById('bulkDeleteDone')?.addEventListener('click', function() {
  const ids = Array.from(document.querySelectorAll('#bulkFormDone input[name="ids[]"]:checked')).map(i=>i.value);
  bulkDelete(ids);
});
</script>
<?php admin_footer(); ?>
