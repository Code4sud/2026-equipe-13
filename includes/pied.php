<?php
/** Pied de page commun. */
$utilisateurPied = utilisateur_actuel($pdo);
?>
</main>

<footer class="pied">
    <div class="conteneur pied__grille">
        <div class="pied__marque">
            <a class="logo logo--pied" href="index.php">
                <span>FindMyWay</span><span class="logo__deux">2</span><span>Work</span>
            </a>
            <p class="pied__baseline"><?= e(t('pied.baseline')) ?></p>
            <p class="pied__mention"><?= e(t('pied.mention')) ?></p>
        </div>

        <nav class="pied__colonne" aria-label="<?= e(t('pied.navigation')) ?>">
            <h2 class="pied__titre"><?= e(t('pied.navigation')) ?></h2>
            <a href="index.php"><?= e(t('nav.accueil')) ?></a>
            <a href="metiers.php"><?= e(t('nav.metiers')) ?></a>
            <?php if ($utilisateurPied): ?>
                <a href="resultats.php"><?= e(t('nav.resultats')) ?></a>
                <a href="profil.php"><?= e(t('nav.profil')) ?></a>
            <?php else: ?>
                <a href="connexion.php"><?= e(t('nav.connexion')) ?></a>
                <a href="inscription.php"><?= e(t('nav.inscription')) ?></a>
            <?php endif; ?>
        </nav>

        <nav class="pied__colonne" aria-label="<?= e(t('pied.legal')) ?>">
            <h2 class="pied__titre"><?= e(t('pied.legal')) ?></h2>
            <a href="cgu.php"><?= e(t('pied.cgu')) ?></a>
            <a href="confidentialite.php"><?= e(t('pied.confidentialite')) ?></a>
            <?php if ($utilisateurPied): ?>
                <a href="profil.php#supprimer"><?= e(t('pied.supprimer')) ?></a>
            <?php endif; ?>
        </nav>
    </div>

    <div class="conteneur pied__bas">
        <p><?= e(t('pied.droits', ['annee' => date('Y')])) ?></p>
        <p>Hackathon Azuréen #3 · Code4Sud</p>
    </div>
</footer>

<script src="assets/app.js" defer></script>
</body>
</html>
