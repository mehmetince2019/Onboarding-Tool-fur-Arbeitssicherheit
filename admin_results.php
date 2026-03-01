<?php
require 'admin_common.php';
require_admin();
require 'config.php';

$message = '';
$error = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delId = (int)($_POST['delete_id'] ?? 0);
    $token = $_POST['csrf_token'] ?? '';
    if ($delId > 0 && hash_equals($_SESSION['csrf_token'], $token)) {
        $stmt = $pdo->prepare("DELETE FROM test_attempts WHERE id = ?");
        $stmt->execute([$delId]);
        $message = 'Eintrag wurde gelöscht.';
    } else {
        $error = 'Löschen fehlgeschlagen.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete']) && !empty($_POST['ids']) && is_array($_POST['ids'])) {
    $token = $_POST['csrf_token'] ?? '';
    $ids = array_values(array_filter(array_map('intval', $_POST['ids']), function($v){ return $v > 0; }));
    if ($ids && hash_equals($_SESSION['csrf_token'], $token)) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM test_attempts WHERE id IN ($in)");
        $stmt->execute($ids);
        $message = 'Ausgewählte Einträge wurden gelöscht.';
    } else {
        $error = 'Löschen fehlgeschlagen.';
    }
}

$rows = $pdo->query("
    SELECT id, worker_name, worker_identifier, score, correct_count, total_questions, created_at
    FROM test_attempts
    ORDER BY id DESC
    LIMIT 200
")->fetchAll();

admin_header(__('results_title'));
?>

<h1 class="h4 mb-3"><?= htmlspecialchars(__('results_title')) ?></h1>

<div class="card p-4 shadow-sm bg-white">
    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (!$rows): ?>
        <p class="text-muted mb-0"><?= htmlspecialchars(__('no_results_yet')) ?></p>
    <?php else: ?>
        <div class="table-responsive">
            <form method="post" id="bulkForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="bulk_delete" value="1">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="text-muted small"><?= htmlspecialchars(__('bulk_delete_label')) ?></div>
                <button type="submit" class="btn btn-sm btn-outline-danger" id="bulkDeleteBtn"><?= htmlspecialchars(__('bulk_delete_btn')) ?></button>
            </div>
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr>
                    <th><input type="checkbox" id="chkAll"></th>
                    <th>#</th>
                    <th><?= htmlspecialchars(__('th_employee')) ?></th>
                    <th><?= htmlspecialchars(__('th_email')) ?></th>
                    <th><?= htmlspecialchars(__('th_correct')) ?></th>
                    <th><?= htmlspecialchars(__('th_score')) ?></th>
                    <th><?= htmlspecialchars(__('th_date')) ?></th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="<?= (int)$r['id'] ?>"></td>
                        <td><?= (int)$r['id'] ?></td>
                        <td><?= htmlspecialchars($r['worker_name']) ?></td>
                        <td><?= htmlspecialchars($r['worker_identifier'] ?? '') ?></td>
                        <td><?= (int)$r['correct_count'] ?> / <?= (int)$r['total_questions'] ?></td>
                        <td><span class="badge <?= ((int)$r['score'] >= 70) ? 'text-bg-success' : 'text-bg-warning' ?>"><?= (int)$r['score'] ?></span></td>
                        <td class="text-muted"><?= htmlspecialchars($r['created_at']) ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="admin_result_detail.php?id=<?= (int)$r['id'] ?>"><?= htmlspecialchars(__('details')) ?></a>
                            <form method="post" class="d-inline" onsubmit="return confirm('Sicher löschen?');">
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
        </div>
        <script>
        (function(){
          var chkAll = document.getElementById('chkAll');
          if (chkAll) {
            chkAll.addEventListener('change', function(e){
              document.querySelectorAll('#bulkForm input[type=checkbox][name="ids[]"]').forEach(function(cb){ cb.checked = e.target.checked; });
            });
          }
        })();
        </script>
    <?php endif; ?>
</div>

<?php admin_footer(); ?>

