<?php
require 'admin_common.php';
require_admin();
require 'config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question = trim($_POST['question'] ?? '');
    $answers  = $_POST['answers'] ?? [];
    $correct  = $_POST['correct'] ?? null; // 0,1,2,3

    if ($question !== '' && count($answers) === 4 && $correct !== null) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO questions (question_text) VALUES (?)");
            $stmt->execute([$question]);
            $questionId = $pdo->lastInsertId();

            foreach ($answers as $index => $answerText) {
                $answerText = trim($answerText);
                if ($answerText === '') {
                    continue;
                }
                $isCorrect = ((int)$index === (int)$correct) ? 1 : 0;
                $stmt = $pdo->prepare(
                    "INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)"
                );
                $stmt->execute([$questionId, $answerText, $isCorrect]);
            }

            $pdo->commit();
            $message = 'Frage wurde hinzugefügt.';
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = 'Fehler: ' . $e->getMessage();
        }
    } else {
        $message = 'Bitte Frage und 4 Antwortoptionen ausfüllen und richtige Option markieren.';
    }
}

// Vorhandene Fragen mit richtiger Option anzeigen
$existingQuestions = $pdo->query("
    SELECT
        q.id,
        q.question_text,
        ca.correct_answer
    FROM questions q
    LEFT JOIN (
        SELECT question_id, MIN(answer_text) AS correct_answer
        FROM answers
        WHERE is_correct = 1
        GROUP BY question_id
    ) ca ON ca.question_id = q.id
    ORDER BY q.id DESC
    LIMIT 20
")->fetchAll();
?>
<?php admin_header(__('questions_title')); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= htmlspecialchars(__('questions_title')) ?></h1>
</div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post" class="card p-4 shadow-sm bg-white mb-4">
        <h2 class="h6 mb-3"><?= htmlspecialchars(__('question_add_title')) ?></h2>
        <div class="mb-3">
            <label class="form-label"><?= htmlspecialchars(__('question_text')) ?></label>
            <textarea name="question" class="form-control" rows="3" required></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label d-block"><?= htmlspecialchars(__('answer_options')) ?></label>
            <?php
            $labels = ['A', 'B', 'C', 'D'];
            foreach ($labels as $i => $label): ?>
                <div class="input-group mb-2">
                    <div class="input-group-text">
                        <input type="radio" name="correct" value="<?= $i ?>" required>
                    </div>
                    <span class="input-group-text"><?= $label ?>)</span>
                    <input type="text" name="answers[]" class="form-control" required>
                </div>
            <?php endforeach; ?>
            <div class="form-text"><?= htmlspecialchars(__('mark_correct_hint')) ?></div>
        </div>

        <button type="submit" class="btn btn-primary"><?= htmlspecialchars(__('save_question')) ?></button>
    </form>

    <div class="card p-4 shadow-sm bg-white">
        <h2 class="h6 mb-3"><?= htmlspecialchars(__('recent_questions')) ?></h2>
        <?php if ($existingQuestions): ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th><?= htmlspecialchars(__('question')) ?></th>
                        <th><?= htmlspecialchars(__('correct_option')) ?></th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($existingQuestions as $q): ?>
                        <tr>
                            <td><span class="text-muted">#<?= (int)$q['id'] ?></span></td>
                            <td><?= htmlspecialchars(mb_strimwidth($q['question_text'], 0, 140, '...')) ?></td>
                            <td><?= htmlspecialchars($q['correct_answer'] ?? '-') ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-secondary" href="admin_question_edit.php?id=<?= (int)$q['id'] ?>"><?= htmlspecialchars(__('edit')) ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted mb-0"><?= htmlspecialchars(__('no_questions')) ?></p>
        <?php endif; ?>
    </div>

<?php admin_footer(); ?>

