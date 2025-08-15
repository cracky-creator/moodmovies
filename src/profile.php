<?php 
 session_start();
require_once 'config/config.php';
include 'includes/open.php';

// 1. Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// 2. Vérifier que l'utilisateur existe encore en base
$stmt = $pdo->prepare("SELECT id FROM users WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $user_id]);
$userExists = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$userExists) {
    // L'utilisateur n'existe plus → on détruit la session et on redirige
    session_destroy();
    header('Location: login.php?error=user_not_found');
    exit;
}

// Requête pour récupérer les films notés
$sql = "
    SELECT f.id, f.titre, f.annee, f.affiche_url, fn.note, fn.disliked, fn.date_note
    FROM film_notes fn
    INNER JOIN films f ON f.id = fn.film_id
    WHERE fn.user_id = :user_id
    ORDER BY fn.date_note DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $user_id]);
$films = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>Mes films notés</h1>

<?php if (empty($films)): ?>
    <p>Vous n'avez pas encore noté de films.</p>
<?php else: ?>
    <div class="film-grid">
        <?php foreach ($films as $film): ?>
            <div class="film-card">
                <img src="<?= htmlspecialchars($film['affiche_url']) ?>" alt="<?= htmlspecialchars($film['titre']) ?>" class="film-poster">
                <h3><?= htmlspecialchars($film['titre']) ?> (<?= $film['annee'] ?>)</h3>

                <?php if ($film['disliked']): ?>
                    <p class="disliked">❌ Pas aimé</p>
                <?php elseif ($film['note'] !== null): ?>
                    <p class="note">⭐ <?= $film['note'] ?>/3</p>
                <?php endif; ?>

                <small>Noté le <?= date("d/m/Y", strtotime($film['date_note'])) ?></small>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php 
include 'includes/close.php'; 
?>