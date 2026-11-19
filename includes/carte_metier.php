<?php
declare(strict_types=1);

/**
 * Affiche une carte métier réutilisée sur l'accueil et la page catalogue.
 * Options : index (string), compact (bool), action (bool), recommande (bool),
 * connecte (bool), deja_joue (bool), journee (bool).
 */
function afficher_carte_metier(array $metier, array $options = []): void
{
    $index = (string) ($options['index'] ?? '');
    $compact = !empty($options['compact']);
    $action = $options['action'] ?? true;
    $recommande = !empty($options['recommande']);
    $connecte = !empty($options['connecte']);
    $dejaJoue = !empty($options['deja_joue']);

    $journee = null;
    if (!empty($options['journee'])) {
        $journeeBrute = journee_type_pour_metier($metier);
        if ($journeeBrute !== null) {
            $journee = journee_type_traduite($journeeBrute);
        }
    }

    $recherche = implode(' ', [
        nom_metier($metier),
        corps_metier($metier),
        description_metier($metier),
        implode(' ', array_map('libelle_interet', $metier['interets'])),
    ]);
    ?>
    <article class="carte-metier<?= $compact ? ' carte-metier--compact' : '' ?>"
             data-metier
             data-corps="<?= e($metier['corps_fr']) ?>"
             data-recherche="<?= e($recherche) ?>">
        <div class="carte-metier__haut">
            <?php if ($index !== ''): ?>
                <span class="carte-metier__num" aria-hidden="true"><?= e($index) ?></span>
            <?php endif; ?>
            <div class="carte-metier__titres">
                <p class="carte-metier__corps"><?= e(corps_metier($metier)) ?></p>
                <h3 class="carte-metier__nom"><?= e(nom_metier($metier)) ?></h3>
            </div>
        </div>

        <?php if ($recommande): ?>
            <p class="badge badge--recommande">★ <?= e(t('metiers.recommande')) ?></p>
        <?php endif; ?>

        <?php if (!$compact): ?>
            <p class="carte-metier__description"><?= e(description_metier($metier)) ?></p>
        <?php endif; ?>

        <ul class="etiquettes" aria-label="<?= e(t('metiers.interets_label')) ?>">
            <?php foreach ($metier['interets'] as $interet): ?>
                <li><?= e(libelle_interet($interet)) ?></li>
            <?php endforeach; ?>
        </ul>

        <?php if ($journee !== null): ?>
            <details class="journee">
                <summary>
                    <span class="journee__resume"><?= e(t('metiers.journee_titre')) ?></span>
                    <span class="journee__source"><?= e($journee['source']) ?></span>
                </summary>
                <div class="journee__corps">
                    <ol class="journee__phases">
                        <?php foreach ($journee['phases'] as $phase): ?>
                            <li class="journee__phase">
                                <span class="journee__moment"><?= e($phase['moment']) ?></span>
                                <p><?= e($phase['texte']) ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                    <div class="journee__taches">
                        <p class="journee__sous-titre"><?= e(t('metiers.journee_taches')) ?></p>
                        <ul>
                            <?php foreach ($journee['taches'] as $tache): ?>
                                <li><?= e($tache) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <p class="journee__note"><?= e(t('metiers.journee_note')) ?></p>
                </div>
            </details>
        <?php endif; ?>

        <div class="carte-metier__bas">
            <?php if (!$action): ?>
                <a class="carte-metier__lien" href="metiers.php"><?= e(t('nav.metiers')) ?> →</a>
            <?php elseif ($connecte): ?>
                <form method="post" action="jeu.php" class="carte-metier__formulaire">
                    <?= csrf_field() ?>
                    <input type="hidden" name="metier" value="<?= (int) $metier['id'] ?>">
                    <button type="submit" class="btn btn--petit">
                        <?= e($dejaJoue ? t('metiers.rejouer') : t('metiers.jouer')) ?>
                    </button>
                </form>
                <?php if ($dejaJoue): ?>
                    <span class="pastille"><?= e(t('metiers.joue')) ?></span>
                <?php endif; ?>
            <?php else: ?>
                <a class="btn btn--petit" href="connexion.php?next=metiers.php"><?= e(t('metiers.jouer')) ?></a>
            <?php endif; ?>
        </div>
    </article>
    <?php
}
