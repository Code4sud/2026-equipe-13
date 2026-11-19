<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/sections_legales.php';

$titre_page = t('confidentialite.titre');
$page_active = 'confidentialite';
require __DIR__ . '/includes/entete.php';
?>

<section class="section">
    <div class="conteneur conteneur--lecture">
        <p class="kicker"><?= e(t('confidentialite.kicker')) ?></p>
        <h1 class="titre-page"><?= e(t('confidentialite.titre')) ?></h1>
        <p class="chapeau"><?= e(t('confidentialite.maj')) ?></p>
        <?php afficher_sections_legales($LANG['confidentialite_sections']); ?>
        <p class="note-legale">
            <a href="cgu.php"><?= e(t('pied.cgu')) ?></a>
        </p>
    </div>
</section>

<?php require __DIR__ . '/includes/pied.php'; ?>
