<?php
require 'admin_common.php';
require_admin();
require 'config.php';

$passError = '';
$passOk = '';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$testLink = $scheme . '://' . $host . $basePath . '/test.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (skey VARCHAR(64) PRIMARY KEY, svalue VARCHAR(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (Exception $e) {}
$stmtTh = $pdo->prepare("SELECT svalue FROM settings WHERE skey = 'passing_threshold' LIMIT 1");
$stmtTh->execute();
$rowTh = $stmtTh->fetch();
$threshold = isset($rowTh['svalue']) ? max(0, min(100, (int)$rowTh['svalue'])) : 70;
if (!$rowTh) {
    $insTh = $pdo->prepare("INSERT IGNORE INTO settings (skey, svalue) VALUES ('passing_threshold', ?)");
    $insTh->execute([strval($threshold)]);
}
$thMsg = '';
$thErr = '';

$qCount = (int)$pdo->query("SELECT COUNT(*) AS c FROM questions")->fetch()['c'];
$aCount = (int)$pdo->query("SELECT COUNT(*) AS c FROM test_attempts")->fetch()['c'];
$latest = $pdo->query("
    SELECT id, worker_name, worker_identifier, score, created_at
    FROM test_attempts
    ORDER BY id DESC
    LIMIT 10
")->fetchAll();

admin_header(__('dashboard_title'));
?>

<div class="card p-3 shadow-sm mb-3">
    <div class="d-flex flex-column flex-md-row align-items-md-end gap-2">
        <div class="flex-grow-1">
            <div class="form-label mb-1"><?= htmlspecialchars(__('test_link_label')) ?></div>
            <input id="testLinkInput" type="text" class="form-control" value="<?= htmlspecialchars($testLink) ?>" readonly>
        </div>
        <div>
            <button id="copyBtn" class="btn btn-outline-primary"><?= htmlspecialchars(__('copy_link')) ?></button>
        </div>
    </div>
</div>

<h1 class="h4 mb-3"><?= htmlspecialchars(__('dashboard_title')) ?></h1>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card p-3 shadow-sm">
            <div class="text-muted small"><?= htmlspecialchars(__('total_q')) ?></div>
            <div class="fs-3 fw-bold"><?= $qCount ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 shadow-sm">
            <div class="text-muted small"><?= htmlspecialchars(__('total_attempts')) ?></div>
            <div class="fs-3 fw-bold"><?= $aCount ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 shadow-sm">
            <div class="text-muted small"><?= htmlspecialchars(__('passing_threshold_label')) ?></div>
            <div class="fs-3 fw-bold"><?= (int)$threshold ?>%</div>
        </div>
    </div>
</div>

<div class="card p-4 shadow-sm bg-white">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h6 mb-0"><?= htmlspecialchars(__('latest_attempts')) ?></h2>
        <a class="btn btn-sm btn-outline-secondary" href="admin_results.php"><?= htmlspecialchars(__('view_all')) ?></a>
    </div>

    <?php if (!$latest): ?>
        <p class="text-muted mb-0"><?= htmlspecialchars(__('no_results_yet')) ?></p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                <tr>
                    <th>#</th>
                    <th><?= htmlspecialchars(__('th_employee')) ?></th>
                    <th><?= htmlspecialchars(__('th_email')) ?></th>
                    <th><?= htmlspecialchars(__('th_score')) ?></th>
                    <th><?= htmlspecialchars(__('th_date')) ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($latest as $r): ?>
                    <tr>
                        <td><?= (int)$r['id'] ?></td>
                        <td><?= htmlspecialchars($r['worker_name']) ?></td>
                        <td><?= htmlspecialchars($r['worker_identifier'] ?? '') ?></td>
                        <td><span class="badge <?= ((int)$r['score'] >= (int)$threshold) ? 'text-bg-success' : 'text-bg-warning' ?>"><?= (int)$r['score'] ?></span></td>
                        <td class="text-muted"><?= htmlspecialchars($r['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

 

<script>
const copyBtn = document.getElementById('copyBtn');
if (copyBtn) {
  copyBtn.addEventListener('click', function (e) {
    e.preventDefault();
    const inp = document.getElementById('testLinkInput');
    const val = inp ? inp.value : '';
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(val).then(function () {
        copyBtn.textContent = <?= json_encode(__('copied')) ?>;
        setTimeout(() => copyBtn.textContent = <?= json_encode(__('copy_link')) ?>, 1500);
      });
    } else {
      if (inp) {
        inp.select();
        document.execCommand('copy');
        copyBtn.textContent = <?= json_encode(__('copied')) ?>;
        setTimeout(() => copyBtn.textContent = <?= json_encode(__('copy_link')) ?>, 1500);
      }
    }
  });
}
</script>

<?php admin_footer(); ?>

