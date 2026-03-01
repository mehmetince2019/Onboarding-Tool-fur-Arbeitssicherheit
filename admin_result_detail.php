<?php
require 'admin_common.php';
require_admin();
require 'config.php';

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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die('Ungültige ID');
}

$attempt = $pdo->prepare("
    SELECT id, worker_name, worker_identifier, score, correct_count, total_questions, created_at
    FROM test_attempts
    WHERE id = ?
");
$attempt->execute([$id]);
$attempt = $attempt->fetch();
if (!$attempt) {
    die('Eintrag nicht gefunden');
}

$details = $pdo->prepare("
    SELECT taa.question_id, q.question_text, taa.answer_id, a.answer_text, taa.is_correct
    FROM test_attempt_answers taa
    JOIN questions q ON q.id = taa.question_id
    JOIN answers a ON a.id = taa.answer_id
    WHERE taa.attempt_id = ?
    ORDER BY taa.id ASC
");
$details->execute([$id]);
$details = $details->fetchAll();

admin_header(__('details'));
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= htmlspecialchars(__('details')) ?></h1>
    <a class="btn btn-sm btn-outline-secondary" href="admin_results.php"><?= htmlspecialchars(__('view_all')) ?></a>
</div>

<div class="card p-4 shadow-sm bg-white mb-3">
    <div class="row g-2">
        <div class="col-md-6"><strong><?= htmlspecialchars(__('employee')) ?>:</strong> <?= htmlspecialchars($attempt['worker_name']) ?></div>
        <div class="col-md-6"><strong><?= htmlspecialchars(__('th_email')) ?>:</strong> <?= htmlspecialchars($attempt['worker_identifier'] ?? '') ?></div>
        <div class="col-md-6"><strong><?= htmlspecialchars(__('th_score')) ?>:</strong> <?= (int)$attempt['score'] ?> / 100</div>
        <div class="col-md-6"><strong><?= htmlspecialchars(__('th_correct')) ?>:</strong> <?= (int)$attempt['correct_count'] ?> / <?= (int)$attempt['total_questions'] ?></div>
        <div class="col-md-6"><strong><?= htmlspecialchars(__('th_date')) ?>:</strong> <?= htmlspecialchars($attempt['created_at']) ?></div>
        <div class="col-md-6"><strong>Status:</strong>
            <span class="badge <?= ((int)$attempt['score'] >= (int)$threshold) ? 'text-bg-success' : 'text-bg-warning' ?>">
                <?= ((int)$attempt['score'] >= (int)$threshold) ? htmlspecialchars(__('passed')) : htmlspecialchars(__('failed')) ?>
            </span>
        </div>
    </div>
</div>

<div class="card p-4 shadow-sm bg-white">
    <h2 class="h6 mb-3"><?= htmlspecialchars(__('answers') ?? 'Antworten') ?></h2>
    <?php if (!$details): ?>
        <p class="text-muted mb-0"><?= htmlspecialchars(__('no_details') ?? 'Keine Details gefunden.') ?></p>
    <?php else: ?>
        <ol class="mb-0">
            <?php foreach ($details as $d): ?>
                <li class="mb-3">
                    <div class="fw-semibold"><?= htmlspecialchars($d['question_text']) ?></div>
                    <div>
                        <span class="badge <?= ((int)$d['is_correct'] === 1) ? 'text-bg-success' : 'text-bg-danger' ?>">
                            <?= ((int)$d['is_correct'] === 1) ? htmlspecialchars(__('correct')) : 'Falsch' ?>
                        </span>
                        <span class="ms-2"><strong><?= htmlspecialchars(__('selected') ?? 'Ausgewählt') ?>:</strong> </span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</div>

<?php admin_footer(); ?>

