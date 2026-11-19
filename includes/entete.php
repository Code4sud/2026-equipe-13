<?php
/** Entête commun. Variables attendues : $titre_page, $page_active, $page_classe (optionnelle). */
$titre_page = $titre_page ?? 'FindMyWay2Work';
$page_active = $page_active ?? '';
$page_classe = $page_classe ?? '';
$utilisateurEntete = utilisateur_actuel($pdo);
$avatarEntete = $utilisateurEntete ? avatar_utilisateur($utilisateurEntete) : null;
$lienActif = static fn (string $cle): string => $page_active === $cle ? ' aria-current="page"' : '';
?>
<!DOCTYPE html>
<html lang="<?= e($langue) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titre_page) ?> — FindMyWay2Work</title>
    <meta name="description" content="<?= e(t('meta.description')) ?>">
    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Archivo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="page-<?= e($page_active !== '' ? $page_active : 'accueil') ?><?= $page_classe !== '' ? ' ' . e($page_classe) : '' ?>">
<a class="lien-evitement" href="#contenu"><?= e(t('nav.aller_contenu')) ?></a>

<header class="entete">
    <div class="entete__barre">
        <a class="logo" href="index.php" aria-label="FindMyWay2Work — <?= e(t('nav.accueil')) ?>">
            <span>FindMyWay</span><span class="logo__deux">2</span><span>Work</span>
        </a>

        <button class="menu-bouton" type="button" aria-expanded="false" aria-controls="navigation-principale" data-menu-bouton data-label-ouvrir="<?= e(t('nav.menu_ouvrir')) ?>" data-label-fermer="<?= e(t('nav.menu_fermer')) ?>">
            <span class="menu-bouton__icone" aria-hidden="true"></span>
            <span class="sr-only" data-menu-libelle><?= e(t('nav.menu_ouvrir')) ?></span>
        </button>

        <div class="entete__panneau" id="navigation-principale" data-menu-panneau>
            <nav class="nav" aria-label="<?= e(t('nav.principale')) ?>">
                <a href="index.php"<?= $lienActif('accueil') ?>><?= e(t('nav.accueil')) ?></a>
                <a href="metiers.php"<?= $lienActif('metiers') ?>><?= e(t('nav.decouvrir_metiers')) ?></a>
                <?php if ($utilisateurEntete): ?>
                    <a href="resultats.php"<?= $lienActif('resultats') ?>><?= e(t('nav.resultats')) ?></a>
                    <a href="profil.php"<?= $lienActif('profil') ?>><?= e(t('nav.profil')) ?></a>
                <?php endif; ?>
            </nav>

            <div class="entete__compte">
                <?php if ($utilisateurEntete): ?>
                    <span class="entete__identite">
                        <?php if ($avatarEntete): ?>
                            <span class="avatar" style="background: <?= e($avatarEntete['couleur']) ?>" aria-hidden="true"><?= e($avatarEntete['initiales']) ?></span>
                        <?php endif; ?>
                        <span class="entete__utilisateur" title="<?= e(t('nav.compte', ['nom' => $utilisateurEntete['prenom'] . ' ' . $utilisateurEntete['nom']])) ?>">
                            <?= e($utilisateurEntete['prenom']) ?>
                        </span>
                    </span>
                    <form method="post" action="deconnexion.php" class="entete__formulaire">
                        <?= csrf_field() ?>
                        <button type="submit" class="lien-bouton"><?= e(t('nav.deconnexion')) ?></button>
                    </form>
                <?php else: ?>
                    <a class="entete__connexion" href="connexion.php"><?= e(t('nav.connexion')) ?></a>
                    <a class="btn btn--petit" href="inscription.php"><?= e(t('nav.inscription')) ?></a>
                <?php endif; ?>
            </div>
        </div>

        <div class="langues" aria-label="<?= e(t('nav.langue')) ?>">
            <a href="<?= e(url_langue('fr')) ?>" hreflang="fr" lang="fr" class="langues__lien<?= $langue === 'fr' ? ' est-actif' : '' ?>"<?= $langue === 'fr' ? ' aria-current="true"' : '' ?>>FR</a>
            <span class="langues__sep" aria-hidden="true">/</span>
            <a href="<?= e(url_langue('en')) ?>" hreflang="en" lang="en" class="langues__lien<?= $langue === 'en' ? ' est-actif' : '' ?>"<?= $langue === 'en' ? ' aria-current="true"' : '' ?>>EN</a>
        </div>
    </div>
</header>

<main id="contenu">
<?php $messagesFlash = recuperer_flash(); ?>
<?php if ($messagesFlash): ?>
    <div class="conteneur flash-zone" aria-live="polite">
        <?php foreach ($messagesFlash as $message): ?>
            <p class="flash flash--<?= e($message['type']) ?>"><?= e($message['message']) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
