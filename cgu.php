<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/sections_legales.php';

$titre_page = t('cgu.titre');
$page_active = 'cgu';
require __DIR__ . '/includes/entete.php';
?>

<section class="section">
    <div class="conteneur conteneur--lecture">
        <p class="kicker"><?= e(t('cgu.kicker')) ?></p>
        <h1 class="titre-page"><?= e(t('cgu.titre')) ?></h1>
        <p class="chapeau"><?= e(t('cgu.maj')) ?></p>
        <?php afficher_sections_legales($LANG['cgu_sections']); ?>
        <p class="note-legale">
            <a href="confidentialite.php"><?= e(t('pied.confidentialite')) ?></a>
        </p>
    </div>
</section>

<?php require __DIR__ . '/includes/pied.php'; ?>
