<?php
require 'config.php';
require_once __DIR__ . '/i18n.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['answers'])) {
    die('Ungültige Anfrage.');
}

$workerName = trim($_POST['worker_name'] ?? '');
$workerIdentifier = trim($_POST['worker_identifier'] ?? '');

if ($workerName === '') {
    die('Name ist erforderlich.');
}
if ($workerIdentifier === '' || !filter_var($workerIdentifier, FILTER_VALIDATE_EMAIL)) {
    die('Ungültige E‑Mail.');
}

$threshold = 70;
$testTitle = 'Arbeitssicherheit Tests';
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (skey VARCHAR(64) PRIMARY KEY, svalue VARCHAR(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $sth = $pdo->prepare("SELECT svalue FROM settings WHERE skey = 'passing_threshold' LIMIT 1");
    $sth->execute();
    $rt = $sth->fetch();
    if ($rt && isset($rt['svalue'])) {
        $threshold = max(0, min(100, (int)$rt['svalue']));
    }
    $stt = $pdo->prepare("SELECT svalue FROM settings WHERE skey = 'test_title' LIMIT 1");
    $stt->execute();
    $rtt = $stt->fetch();
    if ($rtt && isset($rtt['svalue']) && $rtt['svalue'] !== '') {
        $testTitle = $rtt['svalue'];
    }
} catch (Exception $e) {}

$userAnswers = $_POST['answers']; // [question_id => answer_id]

$answerIds = array_values($userAnswers);
$placeholders = implode(',', array_fill(0, count($answerIds), '?'));

$stmt = $pdo->prepare("
    SELECT 
      q.id AS qid,
      q.question_text,
      sa.id AS sel_id,
      sa.answer_text AS sel_text,
      sa.is_correct AS sel_correct,
      ca.answer_text AS correct_text
    FROM answers sa
    JOIN questions q ON q.id = sa.question_id
    LEFT JOIN answers ca ON ca.question_id = q.id AND ca.is_correct = 1
    WHERE sa.id IN ($placeholders)
    ORDER BY q.id
");
$stmt->execute($answerIds);
$selectedRows = $stmt->fetchAll();

$isCorrectByAnswerId = [];
foreach ($selectedRows as $row) {
    $isCorrectByAnswerId[$row['sel_id']] = (int)$row['sel_correct'];
}

$totalQuestions = count($userAnswers);
$correctCount   = 0;

foreach ($userAnswers as $questionId => $answerId) {
    if (!empty($isCorrectByAnswerId[$answerId]) && $isCorrectByAnswerId[$answerId] === 1) {
        $correctCount++;
    }
}

$score = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100) : 0;

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("
        INSERT INTO test_attempts (worker_name, worker_identifier, score, correct_count, total_questions)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $workerName,
        $workerIdentifier !== '' ? $workerIdentifier : null,
        $score,
        $correctCount,
        $totalQuestions,
    ]);
    $attemptId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare("
        INSERT INTO test_attempt_answers (attempt_id, question_id, answer_id, is_correct)
        VALUES (?, ?, ?, ?)
    ");
    foreach ($userAnswers as $questionId => $answerId) {
        $isCorrect = (!empty($isCorrectByAnswerId[$answerId]) && $isCorrectByAnswerId[$answerId] === 1) ? 1 : 0;
        $stmt->execute([$attemptId, (int)$questionId, (int)$answerId, $isCorrect]);
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    // Kayıt hatası olsa bile sonucu göstereceğiz
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars(__('result_title')) ?> – <?= htmlspecialchars($testTitle) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <img src="logo.php" alt="logo" style="height:48px">
        <div><?= render_lang_switcher() ?></div>
    </div>
    <h1 class="mb-4"><?= htmlspecialchars(__('result_title')) ?> – <?= htmlspecialchars($testTitle) ?></h1>

    <div class="card p-4 shadow-sm bg-white">
        <p><strong><?= htmlspecialchars(__('employee')) ?>:</strong> <?= htmlspecialchars($workerName) ?><?= $workerIdentifier !== '' ? ' ('.$workerIdentifier.')' : '' ?></p>
        <p><strong><?= htmlspecialchars(__('total_questions')) ?>:</strong> <?= $totalQuestions ?></p>
        <p><strong><?= htmlspecialchars(__('correct')) ?>:</strong> <?= $correctCount ?></p>
        <p><strong><?= htmlspecialchars(__('score')) ?>:</strong> <span class="<?= $score >= $threshold ? 'text-success fw-bold' : 'text-warning fw-bold' ?>"><?= $score ?> / 100</span></p>

        <?php if ($score >= $threshold): ?>
            <div class="alert alert-success mt-3">
                <h4 class="alert-heading mb-3">🎉 <?= htmlspecialchars(__('passed_msg')) ?></h4>
                <button class="btn btn-success w-100 mb-3" onclick="showCertificate()">
                    <i class="bi bi-award me-2"></i>Teilnehmerbestätigung Anzeigen
                </button>
            </div>
        <?php else: ?>
            <div class="alert alert-warning mt-3">
                <?= htmlspecialchars(__('failed_msg')) ?> <?= (int)$threshold ?>%.
            </div>
        <?php endif; ?>

        <hr class="my-4">
        <h2 class="h6 mb-3"><?= htmlspecialchars(__('your_answers')) ?></h2>
        <?php if (!$selectedRows): ?>
            <p class="text-muted mb-0"><?= htmlspecialchars(__('no_details')) ?></p>
        <?php else: ?>
            <ol class="mb-0">
                <?php foreach ($selectedRows as $r): ?>
                    <li class="mb-3">
                        <div class="fw-semibold"><?= htmlspecialchars($r['question_text']) ?></div>
                        <div class="mt-1">
                            <?php if ((int)$r['sel_correct'] === 1): ?>
                                <span class="badge text-bg-success">✓ <?= htmlspecialchars(__('correct')) ?></span>
                                <span class="ms-2"><strong><?= htmlspecialchars(__('selected')) ?>:</strong> <?= htmlspecialchars($r['sel_text']) ?></span>
                            <?php else: ?>
                                <span class="badge text-bg-danger">✗ <?= htmlspecialchars(__('incorrect')) ?></span>
                                <span class="ms-2"><strong><?= htmlspecialchars(__('selected')) ?>:</strong> <?= htmlspecialchars($r['sel_text']) ?></span>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>

        <?php $retryUrl = 'test.php?name='.urlencode($workerName).'&id='.urlencode($workerIdentifier); ?>
        <a href="<?= htmlspecialchars($retryUrl) ?>" class="btn btn-primary mt-4"><?= htmlspecialchars(__('retry')) ?></a>
    </div>

    <?php if ($score >= $threshold): ?>
    <!-- TEILNEHMERBESTÄTIGUNG MODAL -->
    <div class="modal fade" id="certModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h4 class="modal-title">Teilnehmerbestätigung</h4>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-4">
                        <h3 class="text-success fw-bold">Arbeitssicherheitsschulung für Mitarbeiter nach § 12 ArbSchG</h3>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-8 mx-auto">
                            <p class="lead">
                                Die Unterweisungspflicht wird entsprechend der Mitarbeiterbedürfnisse zum Sicherheits- und 
                                Gesundheitsschutz sowie dem entsprechenden Arbeitsplatz und dem Aufgabenbereich der Mitarbeiter 
                                ausgerichtet. Grundlage sind die relevanten Verordnungen, Gesetze und Informationen. 
                                Der Mitarbeiter wurde über die folgenden Themen belehrt:
                            </p>
                        </div>
                    </div>

                    <h5 class="fw-bold mb-3 text-primary">Themen der Schulung:</h5>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-2">• Umgang und Meldung von Unfällen</li>
                                <li class="mb-2">• Erste Hilfe: Standorte Verbandskästen, Pflasterspender, Augendusche, Defibrillator, Ersthelfer</li>
                                <li class="mb-2">• Brandverhütung und Brandbekämpfung</li>
                                <li class="mb-2">• Standorte und Nutzung von Feuerlöschern</li>
                                <li class="mb-2">• Erstickungsgefahr beim CO2-Feuerlöscher</li>
                                <li class="mb-2">• Brandschutztüren nie verstellen/verkeilen</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-2">• Flucht- und Rettungspläne und Notausstiegsfenster</li>
                                <li class="mb-2">• Sammelplatz</li>
                                <li class="mb-2">• Sonstige Evakuierungsgründe</li>
                                <li class="mb-2">• Schwangerschaft und Stillen</li>
                                <li class="mb-2">• Rechte und Pflichten Arbeitnehmer</li>
                                <li class="mb-2">• Bildschirmarbeitsplatz Ergonomie</li>
                                <li class="mb-2">• Sonstiges: Lüftung/Raumtemperatur/Leitern/Treppe/Autofahren</li>
                            </ul>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row mb-4">
                        <div class="col-md-8 mx-auto text-center">
                            <p class="fw-bold fs-5 mb-1">Die digitale Schulung wurde moderiert von:</p>
                            <p class="h4 text-primary mb-4">Sssss WWWW</p>
                        </div>
                    </div>

                    <div class="card border-primary shadow-sm mb-4">
                        <div class="card-body text-center p-4">
                            <h5 class="card-title text-primary mb-3">Teilnehmerdaten</h5>
                            <div class="row text-start">
                                <div class="col-md-6">
                                    <p class="mb-2"><strong>Mitarbeiter:</strong><br><?= htmlspecialchars($workerName) ?></p>
                                    <p class="mb-0"><strong>E-Mail:</strong><br><?= htmlspecialchars($workerIdentifier) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-0"><strong>Ergebnis:</strong><br>
                                        <span class="badge text-bg-success fs-5 px-3 py-2"><?= $score ?>%</span><br>
                                        <small class="text-success">(<?= $correctCount ?> / <?= $totalQuestions ?> richtig)</small>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-light p-4 rounded border">
                        <p class="small mb-3">
                            <strong>Bestätigung:</strong><br>
                            Mit meiner Unterschrift bestätige ich meine Teilnahme sowie das inhaltliche Verständnis der 
                            Arbeitssicherheitsschulung, welche sich auf die Themen Sicherheit und Gesundheitsschutz in Bezug 
                            auf meinen Arbeitsplatz und Aufgabenbereich bezogen hat. Ich bestätige hiermit das unterwiesene 
                            Wissen zu meinem persönlichen Schutz wie auch das meiner Kollegen anzuwenden und mir 
                            bereitgestellte Arbeits- und Hilfsmittel zweckmäßig einzusetzen.
                        </p>
                        <div class="text-center">
                            <p class="mb-2"><strong>Unterschrift:</strong> ___________________________</p>
                            <p class="small text-muted">Datum: <?= date('d.m.Y') ?></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Schließen</button>
                    <button type="button" class="btn btn-success" onclick="printCert()">
                        <i class="bi bi-printer me-2"></i>Drucken
                    </button>
                    <button type="button" class="btn btn-primary" onclick="downloadPDF()">
                        <i class="bi bi-download me-2"></i>PDF Download
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showCertificate() {
    var modal = new bootstrap.Modal(document.getElementById('certModal'));
    modal.show();
}

function printCert() {
    window.print();
}

function downloadPDF() {
    // jsPDF ile PDF oluşturma (isteğe bağlı)
    alert('PDF Download-Funktion wird implementiert.\n\nDruck-Funktion verwenden oder Screenshot machen.');
    // Gerçek implementasyon için jsPDF kütüphanesi eklenebilir
}
</script>
</body>
</html>
