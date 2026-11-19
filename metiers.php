<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'regenerer') {
        $succes = generer_metiers($pdo, $config);
        flash($succes ? 'succes' : 'erreur', $succes ? t('metiers.generation_succes') : t('erreur.generation_metiers'));
        redirect('metiers.php');
    }
}

$utilisateur = utilisateur_actuel($pdo);
$interets = $utilisateur ? interets_utilisateur($pdo, (int) $utilisateur['id']) : [];
$metiers = charger_metiers($pdo);
$generationRatee = !$metiers;

$dejaJoues = [];
if ($utilisateur) {
    $requete = $pdo->prepare('SELECT metier_id FROM resultats WHERE utilisateur_id = ?');
    $requete->execute([(int) $utilisateur['id']]);
    $dejaJoues = array_map('intval', array_column($requete->fetchAll(), 'metier_id'));
}

if ($metiers) {
    $metiers = trier_metiers_pour_utilisateur($metiers, $interets);
}
$corps = corps_disponibles($metiers);

$titre_page = t('metiers.titre');
$page_active = 'metiers';
require __DIR__ . '/includes/entete.php';
?>

<section class="section section--haut">
    <div class="conteneur">
        <div class="entete-page">
            <div>
                <p class="kicker"><?= e(t('metiers.kicker')) ?></p>
                <h1 class="titre-page"><?= e(t('metiers.titre')) ?></h1>
                <p class="chapeau"><?= e(t('metiers.texte')) ?></p>
            </div>
        </div>

        <?php if (!$utilisateur): ?>
            <div class="banniere">
                <div>
                    <strong><?= e(t('metiers.non_connecte_titre')) ?></strong>
                    <p><?= e(t('metiers.non_connecte_texte')) ?></p>
                </div>
                <div class="banniere__actions">
                    <a class="btn btn--petit" href="connexion.php?next=metiers.php"><?= e(t('nav.connexion')) ?></a>
                    <a class="btn btn--petit btn--cyan" href="inscription.php"><?= e(t('nav.inscription')) ?></a>
                </div>
            </div>
        <?php elseif (!$interets): ?>
            <div class="banniere banniere--attention">
                <div>
                    <strong><?= e(t('metiers.profil_incomplet_titre')) ?></strong>
                    <p><?= e(t('metiers.profil_incomplet_texte')) ?></p>
                </div>
                <div class="banniere__actions">
                    <a class="btn btn--petit" href="profil.php"><?= e(t('nav.profil')) ?></a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($generationRatee): ?>
            <div class="alerte alerte--erreur">
                <p><?= e(t('metiers.generation_erreur')) ?></p>
                <form method="post" action="metiers.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="regenerer">
                    <button type="submit" class="btn btn--petit"><?= e(t('metiers.regenerer')) ?></button>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($metiers): ?>
            <div class="filtres">
                <div class="filtres__recherche">
                    <label class="sr-only" for="recherche-metier"><?= e(t('metiers.recherche')) ?></label>
                    <input type="search" id="recherche-metier" placeholder="<?= e(t('metiers.recherche')) ?>" autocomplete="off" data-filtre-recherche>
                </div>

                <div class="filtres__corps" role="group" aria-label="<?= e(t('metiers.filtre')) ?>">
                    <button type="button" class="puce est-actif" data-filtre-corps="" aria-pressed="true"><?= e(t('metiers.tous')) ?></button>
                    <?php foreach ($corps as $unCorps): ?>
                        <button type="button" class="puce" data-filtre-corps="<?= e($unCorps['cle']) ?>" aria-pressed="false">
                            <?= e($langue === 'en' ? $unCorps['en'] : $unCorps['fr']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <p class="filtres__compteur" data-filtre-compteur data-modele="<?= e(t('metiers.compteur', ['visible' => ':visible', 'total' => ':total'])) ?>" aria-live="polite">
                    <?= e(t('metiers.compteur', ['visible' => count($metiers), 'total' => count($metiers)])) ?>
                </p>
            </div>

            <div class="grille-metiers" data-grille-metiers>
                <?php foreach ($metiers as $rang => $metier): ?>
                    <?php afficher_carte_metier($metier, [
                        'index' => str_pad((string) ($rang + 1), 2, '0', STR_PAD_LEFT),
                        'connecte' => (bool) $utilisateur,
                        'recommande' => $utilisateur && metier_recommande($metier, $interets),
                        'deja_joue' => in_array((int) $metier['id'], $dejaJoues, true),
                    ]); ?>
                <?php endforeach; ?>
            </div>

            <p class="vide" data-filtre-vide hidden><?= e(t('metiers.aucun')) ?></p>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/pied.php'; ?>
