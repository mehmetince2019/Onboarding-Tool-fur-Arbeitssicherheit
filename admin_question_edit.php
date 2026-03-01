<?php
require 'admin_common.php';
require_admin();
require 'config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: admin_questions.php?error=Geçersiz soru ID.');
    exit;
}

$message = '';
$error = '';

function load_question(PDO $pdo, int $id): array {
    $stmt = $pdo->prepare("SELECT id, question_text FROM questions WHERE id = ?");
    $stmt->execute([$id]);
    $question = $stmt->fetch();
    if (!$question) {
        die('Frage nicht gefunden.');
    }

    $stmt = $pdo->prepare("
        SELECT id, answer_text, is_correct
        FROM answers
        WHERE question_id = ?
        ORDER BY id ASC
    ");
    $stmt->execute([$id]);
    $answers = $stmt->fetchAll();

    return [$question, $answers];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $questionText = trim($_POST['question_text'] ?? '');
    $correctAnswerId = isset($_POST['correct_answer_id']) ? (int)$_POST['correct_answer_id'] : 0;
    $answerTexts = $_POST['answer_text'] ?? [];

    if ($questionText === '') {
        $error = 'Fragetext darf nicht leer sein.';
    } elseif ($correctAnswerId <= 0) {
        $error = 'Bitte die richtige Option auswählen.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM answers WHERE question_id = ?");
        $stmt->execute([$id]);
        $validAnswerIds = array_map('intval', array_column($stmt->fetchAll(), 'id'));

        if (!in_array($correctAnswerId, $validAnswerIds, true)) {
            $error = 'Die gewählte richtige Option gehört nicht zu dieser Frage.';
        } else {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("UPDATE questions SET question_text = ? WHERE id = ?");
                $stmt->execute([$questionText, $id]);

                $stmt = $pdo->prepare("UPDATE answers SET is_correct = 0 WHERE question_id = ?");
                $stmt->execute([$id]);

                $stmt = $pdo->prepare("UPDATE answers SET answer_text = ? WHERE id = ? AND question_id = ?");
                foreach ($validAnswerIds as $aid) {
                    $t = trim((string)($answerTexts[$aid] ?? ''));
                    if ($t === '') {
                        throw new Exception('Antworttexte dürfen nicht leer sein.');
                    }
                    $stmt->execute([$t, $aid, $id]);
                }

                $stmt = $pdo->prepare("UPDATE answers SET is_correct = 1 WHERE id = ? AND question_id = ?");
                $stmt->execute([$correctAnswerId, $id]);

                $pdo->commit();
                $message = 'Frage wurde aktualisiert.';
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Fehler: ' . $e->getMessage();
            }
        }
    }
}

[$question, $answers] = load_question($pdo, $id);
admin_header('Frage bearbeiten');
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Frage bearbeiten</h1>
    <a class="btn btn-sm btn-outline-secondary" href="admin_questions.php">Zurück</a>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" class="card p-4 shadow-sm bg-white">
    <div class="mb-3">
        <label class="form-label">Fragetext</label>
        <textarea name="question_text" class="form-control" rows="3" required><?= htmlspecialchars($question['question_text']) ?></textarea>
    </div>

    <div class="mb-2">
        <div class="form-label">Antworten und richtige Option</div>
        <?php
        $labels = ['A', 'B', 'C', 'D', 'E', 'F'];
        foreach ($answers as $i => $a):
            $label = $labels[$i] ?? (string)($i + 1);
            ?>
            <div class="input-group mb-2">
                <div class="input-group-text">
                    <input
                        type="radio"
                        name="correct_answer_id"
                        value="<?= (int)$a['id'] ?>"
                        required
                        <?= ((int)$a['is_correct'] === 1) ? 'checked' : '' ?>
                    >
                </div>
                <span class="input-group-text"><?= htmlspecialchars($label) ?>)</span>
                <input
                    type="text"
                    class="form-control"
                    name="answer_text[<?= (int)$a['id'] ?>]"
                    value="<?= htmlspecialchars($a['answer_text']) ?>"
                    required
                >
            </div>
        <?php endforeach; ?>
        <div class="form-text">Mit dem linken Radio die richtige Option wählen.</div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Speichern</button>
        <button type="button" class="btn btn-danger" onclick="confirmDelete()">Soruyu Sil</button>
    </div>
</form>

<script>
function confirmDelete() {
    if (confirm('Bu soruyu silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.')) {
        window.location.href = 'admin_question_delete.php?id=<?= $id ?>';
    }
}
</script>

<?php admin_footer(); ?>

