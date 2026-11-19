<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$utilisateur = utilisateur_actuel($pdo);
$metiers = charger_metiers($pdo);
$interets = $utilisateur ? interets_utilisateur($pdo, (int) $utilisateur['id']) : [];

if ($metiers) {
    $metiers = trier_metiers_pour_utilisateur($metiers, $interets);
}
$vedettes = array_slice($metiers, 0, 2);

$titre_page = t('nav.accueil');
$page_active = 'accueil';
require __DIR__ . '/includes/entete.php';
?>

<section class="hero">
    <div class="conteneur hero__grille">
        <div class="hero__texte">
            <p class="kicker kicker--clair"><?= e(t('accueil.kicker')) ?></p>
            <h1 class="hero__titre">
                <span><?= e(t('accueil.hero_1')) ?></span>
                <em><?= e(t('accueil.hero_2')) ?></em>
            </h1>
            <p class="hero__sous"><?= e(t('accueil.hero_texte')) ?></p>
            <div class="hero__actions">
                <?php if ($utilisateur): ?>
                    <a class="btn" href="metiers.php"><?= e(t('accueil.cta_secondaire')) ?></a>
                    <a class="btn btn--cyan" href="resultats.php"><?= e(t('nav.resultats')) ?></a>
                <?php else: ?>
                    <a class="btn" href="inscription.php"><?= e(t('accueil.cta_principal')) ?></a>
                    <a class="btn btn--cyan" href="metiers.php"><?= e(t('accueil.cta_secondaire')) ?></a>
                <?php endif; ?>
            </div>
            <ul class="preuves">
                <li><strong><?= e(t('accueil.preuve_1')) ?></strong><span><?= e(t('accueil.preuve_1_detail')) ?></span></li>
                <li><strong><?= e(t('accueil.preuve_2')) ?></strong><span><?= e(t('accueil.preuve_2_detail')) ?></span></li>
                <li><strong><?= e(t('accueil.preuve_3')) ?></strong><span><?= e(t('accueil.preuve_3_detail')) ?></span></li>
            </ul>
        </div>

        <div class="hero__cote">
            <p class="autocollant"><?= e(t('accueil.sticker')) ?></p>
            <?php if ($vedettes): ?>
                <div class="hero__cartes">
                    <?php foreach ($vedettes as $rang => $metier): ?>
                        <?php afficher_carte_metier($metier, [
                            'index' => str_pad((string) ($rang + 1), 2, '0', STR_PAD_LEFT),
                            'compact' => true,
                            'action' => false,
                            'recommande' => $utilisateur && metier_recommande($metier, $interets),
                        ]); ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="carte-vide">
                    <p class="carte-vide__titre"><?= e(t('accueil.catalogue_vide_titre')) ?></p>
                    <p><?= e(t('accueil.catalogue_vide_texte')) ?></p>
                    <a class="btn btn--petit" href="metiers.php"><?= e(t('nav.metiers')) ?></a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section section--lilas">
    <div class="conteneur">
        <p class="kicker"><?= e(t('accueil.comment_kicker')) ?></p>
        <h2 class="titre-section"><?= e(t('accueil.comment_titre')) ?></h2>
        <div class="grille-etapes">
            <article class="etape">
                <span class="etape__num">01</span>
                <h3><?= e(t('accueil.etape_1_titre')) ?></h3>
                <p><?= e(t('accueil.etape_1_texte')) ?></p>
            </article>
            <article class="etape">
                <span class="etape__num etape__num--magenta">02</span>
                <h3><?= e(t('accueil.etape_2_titre')) ?></h3>
                <p><?= e(t('accueil.etape_2_texte')) ?></p>
            </article>
            <article class="etape">
                <span class="etape__num etape__num--cyan">03</span>
                <h3><?= e(t('accueil.etape_3_titre')) ?></h3>
                <p><?= e(t('accueil.etape_3_texte')) ?></p>
            </article>
        </div>
    </div>
</section>

<section class="section">
    <div class="conteneur">
        <div class="entete-section">
            <div>
                <p class="kicker"><?= e(t('accueil.metiers_kicker')) ?></p>
                <h2 class="titre-section"><?= e(t('accueil.metiers_titre')) ?></h2>
                <p class="chapeau"><?= e(t('accueil.metiers_texte')) ?></p>
            </div>
            <a class="btn btn--fantome" href="metiers.php"><?= e(t('accueil.cta_secondaire')) ?> →</a>
        </div>

        <?php if ($metiers): ?>
            <div class="grille-metiers">
                <?php foreach (array_slice($metiers, 0, 3) as $rang => $metier): ?>
                    <?php afficher_carte_metier($metier, [
                        'index' => str_pad((string) ($rang + 1), 2, '0', STR_PAD_LEFT),
                        'connecte' => (bool) $utilisateur,
                        'recommande' => $utilisateur && metier_recommande($metier, $interets),
                    ]); ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="texte-centre"><?= e(t('metiers.generation_erreur')) ?></p>
        <?php endif; ?>
    </div>
</section>

<section class="bande-ia">
    <div class="conteneur bande-ia__grille">
        <div>
            <p class="kicker"><?= e(t('accueil.ia_kicker')) ?></p>
            <h2><?= e(t('accueil.ia_titre')) ?></h2>
        </div>
        <div class="bande-ia__points">
            <div class="bande-ia__point">
                <h3><?= e(t('accueil.ia_1_titre')) ?></h3>
                <p><?= e(t('accueil.ia_1_texte')) ?></p>
            </div>
            <div class="bande-ia__point">
                <h3><?= e(t('accueil.ia_2_titre')) ?></h3>
                <p><?= e(t('accueil.ia_2_texte')) ?></p>
            </div>
            <div class="bande-ia__point">
                <h3><?= e(t('accueil.ia_3_titre')) ?></h3>
                <p><?= e(t('accueil.ia_3_texte')) ?></p>
            </div>
        </div>
    </div>
</section>

<section class="bande-cta">
    <div class="conteneur bande-cta__grille">
        <div>
            <h2><?= e($utilisateur ? t('accueil.bande_titre_connecte') : t('accueil.bande_titre')) ?></h2>
            <p><?= e($utilisateur ? t('accueil.bande_texte_connecte') : t('accueil.bande_texte')) ?></p>
        </div>
        <?php if ($utilisateur): ?>
            <a class="btn btn--abysse" href="metiers.php"><?= e(t('resultats.cta')) ?></a>
        <?php else: ?>
            <a class="btn btn--abysse" href="inscription.php"><?= e(t('accueil.bande_cta')) ?></a>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/pied.php'; ?>
