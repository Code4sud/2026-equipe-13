<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$utilisateur = exiger_connexion($pdo);
$avatar = avatar_utilisateur($utilisateur);
$interetsActuels = interets_utilisateur($pdo, (int) $utilisateur['id']);

$sociabilites = ['timide', 'normal', 'avenant'];
$attentes = ['financier', 'entre_deux', 'passion'];

$erreurs = [];
$valeurs = [
    'interets' => $interetsActuels,
    'sociabilite' => (string) array_search($utilisateur['sociabilite'], $sociabilites, true),
    'attente' => (string) array_search($utilisateur['attente'], $attentes, true),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) ($_POST['action'] ?? 'profil');

    if ($action === 'supprimer') {
        $motDePasse = (string) ($_POST['mot_de_passe'] ?? '');
        if ($motDePasse === '' || !password_verify($motDePasse, $utilisateur['mot_de_passe'])) {
            flash('erreur', t('erreur.suppression_mot_de_passe'));
            redirect('profil.php#supprimer');
        }

        try {
            $requete = $pdo->prepare('DELETE FROM utilisateurs WHERE id = ?');
            $requete->execute([(int) $utilisateur['id']]);
            unset($_SESSION['utilisateur_id'], $_SESSION['csrf']);
            session_regenerate_id(true);
            flash('succes', t('profil.supprimer_succes'));
            redirect('index.php');
        } catch (Throwable $exception) {
            error_log('FindMyWay2Work : suppression du compte impossible — ' . $exception->getMessage());
            flash('erreur', t('erreur.suppression'));
            redirect('profil.php#supprimer');
        }
    }

    $interetsPost = $_POST['interets'] ?? [];
    if (!is_array($interetsPost)) {
        $interetsPost = [];
    }
    $interetsPost = array_values(array_filter($interetsPost, 'is_string'));
    $interetsPost = array_values(array_unique(array_intersect($interetsPost, interets_cles())));

    $socioIndex = filter_var($_POST['sociabilite'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 2]]);
    $attenteIndex = filter_var($_POST['attente'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 2]]);

    $valeurs['interets'] = $interetsPost;
    $valeurs['sociabilite'] = $socioIndex === false ? '1' : (string) $socioIndex;
    $valeurs['attente'] = $attenteIndex === false ? '1' : (string) $attenteIndex;

    if (!$interetsPost) {
        $erreurs[] = t('erreur.interets');
    }
    if ($socioIndex === false || $attenteIndex === false) {
        $erreurs[] = t('erreur.champs');
    }

    if (!$erreurs) {
        try {
            $pdo->beginTransaction();

            $miseAJour = $pdo->prepare('UPDATE utilisateurs SET sociabilite = ?, attente = ? WHERE id = ?');
            $miseAJour->execute([
                $sociabilites[$socioIndex],
                $attentes[$attenteIndex],
                (int) $utilisateur['id'],
            ]);

            $suppression = $pdo->prepare('DELETE FROM utilisateur_interets WHERE utilisateur_id = ?');
            $suppression->execute([(int) $utilisateur['id']]);
            $insertion = $pdo->prepare('INSERT INTO utilisateur_interets (utilisateur_id, interet) VALUES (?, ?)');
            foreach ($interetsPost as $interet) {
                $insertion->execute([(int) $utilisateur['id'], $interet]);
            }

            $pdo->commit();
            flash('succes', t('profil.succes'));
            redirect('profil.php');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('FindMyWay2Work : mise à jour du profil impossible — ' . $exception->getMessage());
            $erreurs[] = t('erreur.champs');
        }
    }
}

$titre_page = t('profil.titre');
$page_active = 'profil';
require __DIR__ . '/includes/entete.php';
?>

<section class="section">
    <div class="conteneur">
        <div class="entete-page">
            <div>
                <p class="kicker"><?= e(t('profil.kicker')) ?></p>
                <h1 class="titre-page"><?= e(t('profil.titre')) ?></h1>
                <p class="chapeau"><?= e(t('profil.texte')) ?></p>
            </div>
        </div>

        <?php if ($erreurs): ?>
            <div class="alerte alerte--erreur" role="alert">
                <?php foreach (array_unique($erreurs) as $erreur): ?>
                    <p><?= e($erreur) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="profil-grille">
            <form method="post" action="profil.php" class="formulaire formulaire--profil">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="profil">

                <fieldset class="panneau panneau--fieldset">
                    <legend>
                        <span class="kicker">01</span>
                        <span class="titre-bloc"><?= e(t('profil.interets_titre')) ?></span>
                    </legend>
                    <p class="champ__aide"><?= e(t('profil.interets_aide')) ?></p>
                    <p class="compteur" data-compteur-interets data-modele="<?= e(t('profil.interets_compteur', ['n' => ':n'])) ?>" aria-live="polite"><?= e(t('profil.interets_compteur', ['n' => count($valeurs['interets'])])) ?></p>

                    <div class="choix-grille">
                        <?php foreach (interets_cles() as $cle): ?>
                            <label class="choix">
                                <input type="checkbox" name="interets[]" value="<?= e($cle) ?>" data-interet<?= in_array($cle, $valeurs['interets'], true) ? ' checked' : '' ?>>
                                <span><?= e(libelle_interet($cle)) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <fieldset class="panneau panneau--fieldset">
                    <legend>
                        <span class="kicker">02</span>
                        <span class="titre-bloc"><?= e(t('profil.sociabilite_titre')) ?></span>
                    </legend>
                    <p class="champ__aide"><?= e(t('profil.sociabilite_aide')) ?></p>

                    <div class="curseur" data-curseur-groupe="sociabilite">
                        <input type="range" id="sociabilite" name="sociabilite" min="0" max="2" step="1"
                               value="<?= e($valeurs['sociabilite']) ?>" data-curseur="sociabilite"
                               aria-describedby="sociabilite-aide">
                        <div class="curseur__legende" id="sociabilite-aide">
                            <span data-position="0"><strong><?= e(t('profil.sociabilite_timide')) ?></strong><small><?= e(t('profil.sociabilite_timide_detail')) ?></small></span>
                            <span data-position="1"><strong><?= e(t('profil.sociabilite_normal')) ?></strong><small><?= e(t('profil.sociabilite_normal_detail')) ?></small></span>
                            <span data-position="2"><strong><?= e(t('profil.sociabilite_avenant')) ?></strong><small><?= e(t('profil.sociabilite_avenant_detail')) ?></small></span>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="panneau panneau--fieldset">
                    <legend>
                        <span class="kicker">03</span>
                        <span class="titre-bloc"><?= e(t('profil.attente_titre')) ?></span>
                    </legend>
                    <p class="champ__aide"><?= e(t('profil.attente_aide')) ?></p>

                    <div class="curseur" data-curseur-groupe="attente">
                        <input type="range" id="attente" name="attente" min="0" max="2" step="1"
                               value="<?= e($valeurs['attente']) ?>" data-curseur="attente"
                               aria-describedby="attente-aide">
                        <div class="curseur__legende" id="attente-aide">
                            <span data-position="0"><strong><?= e(t('profil.attente_financier')) ?></strong><small><?= e(t('profil.attente_financier_detail')) ?></small></span>
                            <span data-position="1"><strong><?= e(t('profil.attente_entre_deux')) ?></strong><small><?= e(t('profil.attente_entre_deux_detail')) ?></small></span>
                            <span data-position="2"><strong><?= e(t('profil.attente_passion')) ?></strong><small><?= e(t('profil.attente_passion_detail')) ?></small></span>
                        </div>
                    </div>
                </fieldset>

                <div class="formulaire__actions">
                    <button type="submit" class="btn btn--large"><?= e(t('profil.enregistrer')) ?></button>
                    <a class="btn btn--fantome" href="resultats.php"><?= e(t('nav.resultats')) ?> →</a>
                </div>
            </form>

            <aside class="profil-cote">
                <div class="panneau">
                    <p class="kicker"><?= e(t('profil.compte_titre')) ?></p>
                    <p class="champ__aide"><?= e(t('profil.compte_texte')) ?></p>
                    <div class="compte-profil">
                        <span class="avatar avatar--grand" style="background: <?= e($avatar['couleur']) ?>" aria-hidden="true"><?= e($avatar['initiales']) ?></span>
                        <div>
                            <p class="compte-profil__nom"><?= e($utilisateur['prenom'] . ' ' . $utilisateur['nom']) ?></p>
                            <p class="compte-profil__email"><?= e($utilisateur['email']) ?></p>
                        </div>
                    </div>
                    <dl class="fiche">
                        <div><dt><?= e(t('profil.compte_age')) ?></dt><dd><?= (int) $utilisateur['age'] ?> <?= e(t('inscription.ans')) ?></dd></div>
                    </dl>
                    <a class="btn btn--petit btn--cyan" href="metiers.php"><?= e(t('resultats.cta')) ?></a>
                </div>

                <div class="panneau panneau--danger" id="supprimer">
                    <p class="kicker kicker--magenta"><?= e(t('profil.supprimer_kicker')) ?></p>
                    <h2 class="titre-bloc titre-bloc--petit"><?= e(t('profil.supprimer_titre')) ?></h2>
                    <p><?= e(t('profil.supprimer_texte')) ?></p>
                    <form method="post" action="profil.php" class="formulaire" data-confirmer="<?= e(t('profil.supprimer_confirmation')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="supprimer">
                        <div class="champ">
                            <label for="mot_de_passe_suppression"><?= e(t('profil.supprimer_mot_de_passe')) ?></label>
                            <input type="password" id="mot_de_passe_suppression" name="mot_de_passe" autocomplete="current-password" required>
                        </div>
                        <button type="submit" class="btn btn--danger"><?= e(t('profil.supprimer_bouton')) ?></button>
                    </form>
                </div>
            </aside>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/pied.php'; ?>
