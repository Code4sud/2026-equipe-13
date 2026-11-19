<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

/** N'accepte qu'un chemin interne vers une page PHP. */
function destination_sure(string $suite): string
{
    if ($suite === '' || str_contains($suite, '\\') || str_starts_with($suite, '//')) {
        return '';
    }
    $parties = parse_url($suite);
    if ($parties === false || isset($parties['scheme']) || isset($parties['host'])) {
        return '';
    }
    $chemin = $parties['path'] ?? '';
    if (!preg_match('#^/?(?:[\w-]+/)*[\w-]+\.php$#', $chemin)) {
        return '';
    }
    return $suite;
}

$utilisateur = utilisateur_actuel($pdo);
if ($utilisateur) {
    redirect('profil.php');
}

$erreur = '';
$email = '';
$suite = destination_sure((string) ($_POST['next'] ?? $_GET['next'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim((string) ($_POST['email'] ?? ''));
    $motDePasse = (string) ($_POST['mot_de_passe'] ?? '');

    if ($email === '' || $motDePasse === '') {
        $erreur = t('erreur.champs');
    } else {
        $requete = $pdo->prepare('SELECT * FROM utilisateurs WHERE email = ?');
        $requete->execute([$email]);
        $compte = $requete->fetch();

        if ($compte && password_verify($motDePasse, $compte['mot_de_passe'])) {
            session_regenerate_id(true);
            $_SESSION['utilisateur_id'] = (int) $compte['id'];

            if (password_needs_rehash($compte['mot_de_passe'], PASSWORD_DEFAULT)) {
                $miseAJour = $pdo->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?');
                $miseAJour->execute([password_hash($motDePasse, PASSWORD_DEFAULT), $compte['id']]);
            }

            flash('succes', t('connexion.succes', ['prenom' => $compte['prenom']]));
            if ($suite !== '') {
                redirect($suite);
            }
            $aDesInterets = interets_utilisateur($pdo, (int) $compte['id']) !== [];
            redirect($aDesInterets ? 'resultats.php' : 'profil.php');
        }

        $erreur = t('erreur.identifiants');
    }
}

$titre_page = t('connexion.titre');
$page_active = 'connexion';
require __DIR__ . '/includes/entete.php';
?>

<section class="section">
    <div class="conteneur conteneur--etroit">
        <div class="panneau panneau--formulaire">
            <p class="kicker"><?= e(t('connexion.kicker')) ?></p>
            <h1 class="titre-page"><?= e(t('connexion.titre')) ?></h1>
            <p class="chapeau"><?= e(t('connexion.texte')) ?></p>

            <?php if ($erreur !== ''): ?>
                <div class="alerte alerte--erreur" role="alert"><p><?= e($erreur) ?></p></div>
            <?php endif; ?>

            <form method="post" action="connexion.php" class="formulaire">
                <?= csrf_field() ?>
                <?php if ($suite !== ''): ?>
                    <input type="hidden" name="next" value="<?= e($suite) ?>">
                <?php endif; ?>

                <div class="champ">
                    <label for="email"><?= e(t('connexion.email')) ?></label>
                    <input type="email" id="email" name="email" value="<?= e($email) ?>" autocomplete="email" required>
                </div>

                <div class="champ">
                    <label for="mot_de_passe"><?= e(t('connexion.mot_de_passe')) ?></label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" autocomplete="current-password" required>
                </div>

                <button type="submit" class="btn btn--large"><?= e(t('connexion.valider')) ?></button>
            </form>

            <p class="formulaire__pied">
                <?= e(t('connexion.pas_compte')) ?>
                <a href="inscription.php"><?= e(t('connexion.lien_inscription')) ?></a>
            </p>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/pied.php'; ?>
