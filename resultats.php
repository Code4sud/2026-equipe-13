<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$utilisateur = exiger_connexion($pdo);
$resultats = classement_utilisateur($pdo, $utilisateur);
$total = count($resultats);
$top = array_slice($resultats, 0, 5);
$debloque = $total >= 5;
$restant = max(0, 5 - $total);
$progression = min(100, (int) round($total / 5 * 100));

$titre_page = t('resultats.titre');
$page_active = 'resultats';
require __DIR__ . '/includes/entete.php';
?>

<section class="section">
    <div class="conteneur">
        <div class="entete-page entete-page--resultats">
            <div>
                <p class="kicker"><?= e(t('resultats.kicker')) ?></p>
                <h1 class="titre-page"><?= e(t('resultats.titre')) ?></h1>
                <p class="chapeau"><?= e(t('resultats.texte')) ?></p>
            </div>

            <div class="progression">
                <p class="progression__texte">
                    <?= e($debloque
                        ? t('resultats.total_joues', ['n' => $total])
                        : t('resultats.progression', ['fait' => $total])) ?>
                </p>
                <div class="progression__barre" role="progressbar" aria-valuemin="0" aria-valuemax="5" aria-valuenow="<?= min(5, $total) ?>">
                    <span style="width: <?= $progression ?>%"></span>
                </div>
                <?php if (!$debloque): ?>
                    <p class="progression__aide"><?= e(t('resultats.encore', ['restant' => $restant])) ?></p>
                <?php else: ?>
                    <p class="progression__aide progression__aide--fort"><?= e(t('resultats.debloque')) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$resultats): ?>
            <div class="etat-vide">
                <p class="etat-vide__titre"><?= e(t('resultats.aucun')) ?></p>
                <a class="btn btn--large" href="metiers.php"><?= e(t('resultats.cta')) ?></a>
            </div>
        <?php elseif ($debloque): ?>
            <h2 class="titre-section"><?= e(t('resultats.top_titre')) ?></h2>
            <div class="podium">
                <?php foreach ($top as $rang => $resultat): ?>
                    <article class="carte-resultat<?= $rang === 0 ? ' carte-resultat--or' : '' ?>">
                        <div class="carte-resultat__rang" aria-hidden="true"><?= $rang + 1 ?></div>
                        <div class="carte-resultat__corps">
                            <p class="carte-resultat__kicker"><?= e(t('resultats.rang', ['rang' => $rang + 1])) ?> · <?= e(corps_metier($resultat)) ?></p>
                            <h3 class="carte-resultat__nom"><?= e(nom_metier($resultat)) ?></h3>
                            <dl class="carte-resultat__notes">
                                <div><dt><?= e(t('resultats.reussite')) ?></dt><dd><?= e(format_note((float) $resultat['note_reussite'], $langue)) ?>/10</dd></div>
                                <div><dt><?= e(t('resultats.appreciation')) ?></dt><dd><?= e(format_note((float) $resultat['note_appreciation'], $langue)) ?>/10</dd></div>
                            </dl>
                        </div>
                        <div class="carte-resultat__finale">
                            <span><?= e(t('resultats.note_finale')) ?></span>
                            <strong><?= e(format_note((float) $resultat['note_finale'], $langue)) ?></strong>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <p class="note-legale"><?= e(t('resultats.remarque')) ?></p>
        <?php else: ?>
            <h2 class="titre-section"><?= e(t('resultats.joues_titre')) ?></h2>
            <p class="chapeau"><?= e(t('resultats.joues_texte')) ?></p>

            <div class="liste-joues">
                <?php foreach ($resultats as $resultat): ?>
                    <article class="carte-resultat carte-resultat--simple">
                        <div class="carte-resultat__corps">
                            <p class="carte-resultat__kicker"><?= e(corps_metier($resultat)) ?></p>
                            <h3 class="carte-resultat__nom"><?= e(nom_metier($resultat)) ?></h3>
                        </div>
                        <dl class="carte-resultat__notes">
                            <div><dt><?= e(t('resultats.reussite')) ?></dt><dd><?= e(format_note((float) $resultat['note_reussite'], $langue)) ?>/10</dd></div>
                            <div><dt><?= e(t('resultats.appreciation')) ?></dt><dd><?= e(format_note((float) $resultat['note_appreciation'], $langue)) ?>/10</dd></div>
                        </dl>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="etat-vide etat-vide--compact">
                <p class="etat-vide__titre"><?= e(t('resultats.encore', ['restant' => $restant])) ?></p>
                <a class="btn" href="metiers.php"><?= e(t('resultats.cta')) ?></a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/pied.php'; ?>
