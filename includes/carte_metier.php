<?php
declare(strict_types=1);

/**
 * Affiche une carte métier réutilisée sur l'accueil et la page catalogue.
 * Options : index (string), compact (bool), action (bool), recommande (bool),
 * connecte (bool), deja_joue (bool).
 */
function afficher_carte_metier(array $metier, array $options = []): void
{
    $index = (string) ($options['index'] ?? '');
    $compact = !empty($options['compact']);
    $action = $options['action'] ?? true;
    $recommande = !empty($options['recommande']);
    $connecte = !empty($options['connecte']);
    $dejaJoue = !empty($options['deja_joue']);

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
