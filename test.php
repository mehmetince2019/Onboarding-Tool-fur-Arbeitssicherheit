<?php
require 'config.php';
require_once __DIR__ . '/i18n.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (skey VARCHAR(64) PRIMARY KEY, svalue VARCHAR(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $st = $pdo->prepare("SELECT svalue FROM settings WHERE skey = 'test_title' LIMIT 1");
    $st->execute();
    $r = $st->fetch();
    $testTitle = ($r && isset($r['svalue']) && $r['svalue'] !== '') ? $r['svalue'] : 'Arbeitssicherheit – Test';
} catch (Exception $e) {
    $testTitle = 'Arbeitssicherheit – Test';
}

// Mitarbeiterdaten (optional) – für Ergebnisprotokoll
$workerName = trim($_GET['name'] ?? '');
$workerId = trim($_GET['id'] ?? '');

// Zufällige 5 Fragen holen
$stmt = $pdo->query("
    SELECT q.id, q.question_text
    FROM questions q
    ORDER BY RAND()
    LIMIT 5
");
$questions = $stmt->fetchAll();

if (!$questions) {
    die('Es wurden noch keine Fragen hinzugefügt. Bitte zuerst im Adminbereich Fragen anlegen.');
}

$questionIds = array_column($questions, 'id');
$inPlaceholders = implode(',', array_fill(0, count($questionIds), '?'));

$stmt = $pdo->prepare("
    SELECT a.id, a.question_id, a.answer_text
    FROM answers a
    WHERE a.question_id IN ($inPlaceholders)
    ORDER BY RAND()
");
$stmt->execute($questionIds);
$answers = $stmt->fetchAll();

$answersByQuestion = [];
foreach ($answers as $ans) {
    $answersByQuestion[$ans['question_id']][] = $ans;
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($testTitle) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <img src="logo.php" alt="logo" style="height:68px">
        <div><?= render_lang_switcher() ?></div>
    </div>
    <h1 class="mb-4"><?= htmlspecialchars($testTitle) ?></h1>

    <form method="post" action="test_result.php" class="card p-4 shadow-sm bg-white">
        <div class="row g-3 mb-4">
            <div class="col-md-7">
                <label class="form-label"><?= htmlspecialchars(__('label_name')) ?></label>
                <input type="text" name="worker_name" class="form-control" required value="<?= htmlspecialchars($workerName) ?>">
            </div>
            <div class="col-md-5">
                <label class="form-label"><?= htmlspecialchars(__('label_email')) ?></label>
                <input
                    type="email"
                    name="worker_identifier"
                    class="form-control"
                    required
                    autocomplete="email"
                    inputmode="email"
                    value="<?= htmlspecialchars($workerId) ?>"
                >
            </div>
        </div>

        <?php foreach ($questions as $index => $q): ?>
            <div class="mb-4">
                <p class="fw-bold">
                    <?= ($index + 1) ?>. <?= htmlspecialchars($q['question_text']) ?>
                </p>
                <?php if (!empty($answersByQuestion[$q['id']])): ?>
                    <?php foreach ($answersByQuestion[$q['id']] as $ans): ?>
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="radio"
                                   name="answers[<?= $q['id'] ?>]"
                                   value="<?= $ans['id'] ?>"
                                   required>
                            <label class="form-check-label">
                                <?= htmlspecialchars($ans['answer_text']) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-danger"><?= htmlspecialchars(__('no_options')) ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <button type="submit" class="btn btn-success"><?= htmlspecialchars(__('btn_submit')) ?></button>
    </form>
</div>
</body>
</html>

