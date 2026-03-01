<?php
require 'admin_common.php';
require_admin();
require 'config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: admin_questions.php?error=Geçersiz soru ID.');
    exit;
}

// Sorunun varlığını kontrol et
$stmt = $pdo->prepare("SELECT id FROM questions WHERE id = ?");
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    die('Frage nicht gefunden.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->beginTransaction();
    try {
        // Önce cevapları sil
        $stmt = $pdo->prepare("DELETE FROM answers WHERE question_id = ?");
        $stmt->execute([$id]);
        
        // Sonra soruyu sil
        $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
        $stmt->execute([$id]);
        
        $pdo->commit();
        
        // Başarılı silme sonrası soru listesine yönlendir
        header('Location: admin_questions.php?message=Frage wurde gelöscht.');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        die('Fehler beim Löschen: ' . $e->getMessage());
    }
}

admin_header('Frage löschen');
?>

<div class="card p-4 shadow-sm bg-white">
    <h2 class="h4 mb-3">Frage löschen</h2>
    
    <div class="alert alert-warning">
        <strong>Achtung!</strong> Möchten Sie diese Frage wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.
    </div>
    
    <form method="post" class="d-flex gap-2">
        <button type="submit" class="btn btn-danger">Ja, löschen</button>
        <a href="admin_question_edit.php?id=<?= $id ?>" class="btn btn-secondary">Abbrechen</a>
    </form>
</div>

<?php admin_footer(); ?>
