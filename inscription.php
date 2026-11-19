<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

if (utilisateur_actuel($pdo)) {
    redirect('profil.php');
}

$erreurs = [];
$valeurs = [
    'prenom' => '',
    'nom' => '',
    'age' => '18',
    'email' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    foreach (['prenom', 'nom', 'email'] as $champ) {
        $valeurs[$champ] = trim((string) ($_POST[$champ] ?? ''));
    }
    $ageBrut = trim((string) ($_POST['age'] ?? ''));
    $motDePasse = (string) ($_POST['mot_de_passe'] ?? '');

    if ($valeurs['prenom'] === '' || $valeurs['nom'] === '' || $valeurs['email'] === '' || $ageBrut === '' || $motDePasse === '') {
        $erreurs[] = t('erreur.champs');
    }

    $age = filter_var($ageBrut, FILTER_VALIDATE_INT, ['options' => ['min_range' => 15, 'max_range' => 25]]);
    if ($age === false) {
        $erreurs[] = t('erreur.age');
    } else {
        $valeurs['age'] = (string) $age;
    }

    if ($valeurs['email'] !== '' && !filter_var($valeurs['email'], FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = t('erreur.email');
    }
    if (mb_strlen($valeurs['prenom']) > 80 || mb_strlen($valeurs['nom']) > 80) {
        $erreurs[] = t('erreur.champs');
    }
    if ($motDePasse !== '' && mb_strlen($motDePasse) < 8) {
        $erreurs[] = t('erreur.mot_de_passe');
    }

    if (!$erreurs) {
        try {
            $requete = $pdo->prepare(
                'INSERT INTO utilisateurs (prenom, nom, age, email, mot_de_passe) VALUES (?, ?, ?, ?, ?)'
            );
            $requete->execute([
                $valeurs['prenom'],
                $valeurs['nom'],
                $age,
                $valeurs['email'],
                password_hash($motDePasse, PASSWORD_DEFAULT),
            ]);

            session_regenerate_id(true);
            $_SESSION['utilisateur_id'] = (int) $pdo->lastInsertId();
            flash('succes', t('inscription.succes', ['prenom' => $valeurs['prenom']]));
            redirect('profil.php');
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $erreurs[] = t('erreur.email_existe');
            } else {
                error_log('FindMyWay2Work : inscription impossible — ' . $exception->getMessage());
                $erreurs[] = t('erreur.champs');
            }
        }
    }
}

$titre_page = t('inscription.titre');
$page_active = 'inscription';
require __DIR__ . '/includes/entete.php';
?>

<section class="section">
    <div class="conteneur grille-auth">
        <div class="panneau panneau--formulaire">
            <p class="kicker"><?= e(t('inscription.kicker')) ?></p>
            <h1 class="titre-page"><?= e(t('inscription.titre')) ?></h1>
            <p class="chapeau"><?= e(t('inscription.texte')) ?></p>

            <?php if ($erreurs): ?>
                <div class="alerte alerte--erreur" role="alert">
                    <?php foreach (array_unique($erreurs) as $erreur): ?>
                        <p><?= e($erreur) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="inscription.php" class="formulaire" novalidate>
                <?= csrf_field() ?>

                <div class="champ-double">
                    <div class="champ">
                        <label for="prenom"><?= e(t('inscription.prenom')) ?> <span aria-hidden="true">*</span></label>
                        <input type="text" id="prenom" name="prenom" value="<?= e($valeurs['prenom']) ?>" maxlength="80" autocomplete="given-name" required>
                    </div>
                    <div class="champ">
                        <label for="nom"><?= e(t('inscription.nom')) ?> <span aria-hidden="true">*</span></label>
                        <input type="text" id="nom" name="nom" value="<?= e($valeurs['nom']) ?>" maxlength="80" autocomplete="family-name" required>
                    </div>
                </div>

                <div class="champ">
                    <div class="champ__ligne">
                        <label for="age"><?= e(t('inscription.age')) ?> <span aria-hidden="true">*</span></label>
                        <output class="curseur__valeur" for="age" data-valeur="age" data-suffixe="<?= e(t('inscription.ans')) ?>"><?= e($valeurs['age']) ?> <?= e(t('inscription.ans')) ?></output>
                    </div>
                    <input type="range" id="age" name="age" min="15" max="25" step="1" value="<?= e($valeurs['age']) ?>" data-curseur="age" required>
                    <div class="curseur__bornes" aria-hidden="true"><span>15</span><span>25</span></div>
                    <p class="champ__aide"><?= e(t('inscription.age_aide')) ?></p>
                </div>

                <div class="champ">
                    <label for="email"><?= e(t('inscription.email')) ?> <span aria-hidden="true">*</span></label>
                    <input type="email" id="email" name="email" value="<?= e($valeurs['email']) ?>" autocomplete="email" required>
                </div>

                <div class="champ">
                    <label for="mot_de_passe"><?= e(t('inscription.mot_de_passe')) ?> <span aria-hidden="true">*</span></label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" autocomplete="new-password" minlength="8" required>
                    <p class="champ__aide"><?= e(t('inscription.mot_de_passe_aide')) ?></p>
                </div>

                <button type="submit" class="btn btn--large"><?= e(t('inscription.valider')) ?></button>
            </form>

            <p class="formulaire__pied">
                <?= e(t('inscription.deja_compte')) ?>
                <a href="connexion.php"><?= e(t('inscription.lien_connexion')) ?></a>
            </p>
        </div>

        <aside class="panneau panneau--aside">
            <p class="kicker">FindMyWay2Work</p>
            <h2><?= e(t('inscription.aside_titre')) ?></h2>
            <ol class="liste-numerotee">
                <li><strong><?= e(t('inscription.aside_1_titre')) ?></strong> <?= e(t('inscription.aside_1_texte')) ?></li>
                <li><strong><?= e(t('inscription.aside_2_titre')) ?></strong> <?= e(t('inscription.aside_2_texte')) ?></li>
                <li><strong><?= e(t('inscription.aside_3_titre')) ?></strong> <?= e(t('inscription.aside_3_texte')) ?></li>
            </ol>
            <p class="aside-note"><?= e(t('inscription.aside_note')) ?></p>
        </aside>
    </div>
</section>

<?php require __DIR__ . '/includes/pied.php'; ?>
