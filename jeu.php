<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$utilisateur = exiger_connexion($pdo);
$utilisateurId = (int) $utilisateur['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) ($_POST['action'] ?? 'demarrer');
    $partieId = (int) ($_POST['partie'] ?? 0);

    if ($action === 'demarrer') {
        $metier = charger_metier($pdo, (int) ($_POST['metier'] ?? 0));
        if (!$metier) {
            flash('erreur', t('erreur.metier_introuvable'));
            redirect('metiers.php');
        }
        if (!interets_utilisateur($pdo, $utilisateurId)) {
            flash('erreur', t('erreur.profil_incomplet'));
            redirect('profil.php');
        }
        if (trim((string) ($config['deepseek']['cle_api'] ?? '')) === '') {
            flash('erreur', t('erreur.cle_api_absente'));
            redirect('metiers.php');
        }

        $notifications = generer_notifications_ia($config, $metier);
        if ($notifications === null) {
            flash('erreur', t('erreur.generation_notifications'));
            redirect('metiers.php');
        }

        $total = count($notifications);
        $indices = range(0, $total - 1);
        if ($total > 1) {
            $ordreAttendu = $indices;
            $essais = 0;
            do {
                shuffle($indices);
                $essais++;
            } while ($indices === $ordreAttendu && $essais < 20);

            if ($indices === $ordreAttendu) {
                $indices[] = array_shift($indices);
            }
        }

        try {
            $pdo->beginTransaction();

            // Une nouvelle partie remplace toute partie abandonnée de l'utilisateur.
            $suppression = $pdo->prepare('DELETE FROM parties WHERE utilisateur_id = ?');
            $suppression->execute([$utilisateurId]);

            $creation = $pdo->prepare('INSERT INTO parties (utilisateur_id, metier_id) VALUES (?, ?)');
            $creation->execute([$utilisateurId, (int) $metier['id']]);
            $nouvellePartie = (int) $pdo->lastInsertId();

            $insertion = $pdo->prepare(
                'INSERT INTO partie_notifications
                 (partie_id, source_fr, source_en, texte_fr, texte_en, position_attendue, ordre_affichage)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($indices as $affichage => $indexOriginal) {
                $notification = $notifications[$indexOriginal];
                $insertion->execute([
                    $nouvellePartie,
                    $notification['source_fr'],
                    $notification['source_en'],
                    $notification['texte_fr'],
                    $notification['texte_en'],
                    $indexOriginal + 1,
                    $affichage + 1,
                ]);
            }

            $pdo->commit();
            redirect('jeu.php?partie=' . $nouvellePartie);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('FindMyWay2Work : création de partie impossible — ' . $exception->getMessage());
            flash('erreur', t('erreur.generation_notifications'));
            redirect('metiers.php');
        }
    }

    $partie = $partieId > 0 ? charger_partie($pdo, $partieId, $utilisateurId) : null;
    if (!$partie) {
        flash('erreur', t('erreur.partie_introuvable'));
        redirect('metiers.php');
    }

    if ($action === 'abandonner') {
        $requete = $pdo->prepare('DELETE FROM parties WHERE id = ? AND utilisateur_id = ?');
        $requete->execute([$partieId, $utilisateurId]);
        flash('info', t('jeu.abandonnee'));
        redirect('metiers.php');
    }

    if ($action === 'classer') {
        if ($partie['note_reussite'] !== null) {
            flash('info', t('jeu.deja_classee'));
            redirect('jeu.php?partie=' . $partieId);
        }

        $notifications = notifications_partie($pdo, $partieId);
        $idsAttendus = array_map('intval', array_column($notifications, 'id'));

        $ordreBrut = trim((string) ($_POST['ordre'] ?? ''));
        $ordre = array_values(array_filter(
            array_map('intval', explode(',', $ordreBrut)),
            static fn (int $id): bool => $id > 0
        ));

        $idsFournis = $ordre;
        sort($idsFournis);
        $idsComparaison = $idsAttendus;
        sort($idsComparaison);

        if (count($ordre) !== count($idsAttendus)
            || count(array_unique($ordre)) !== count($ordre)
            || $idsFournis !== $idsComparaison) {
            flash('erreur', t('jeu.ordre_invalide'));
            redirect('jeu.php?partie=' . $partieId);
        }

        $requete = $pdo->prepare('SELECT id, position_attendue FROM partie_notifications WHERE partie_id = ?');
        $requete->execute([$partieId]);
        $positions = [];
        foreach ($requete->fetchAll() as $ligne) {
            $positions[(int) $ligne['id']] = (int) $ligne['position_attendue'];
        }

        $note = calculer_note_reussite($positions, $ordre);
        $miseAJour = $pdo->prepare('UPDATE parties SET note_reussite = ? WHERE id = ? AND utilisateur_id = ?');
        $miseAJour->execute([$note, $partieId, $utilisateurId]);

        redirect('jeu.php?partie=' . $partieId);
    }

    if ($action === 'noter') {
        if ($partie['note_reussite'] === null) {
            flash('erreur', t('jeu.pas_classee'));
            redirect('jeu.php?partie=' . $partieId);
        }

        $appreciation = filter_var($_POST['note_appreciation'] ?? '', FILTER_VALIDATE_FLOAT);
        if ($appreciation === false || $appreciation < 0 || $appreciation > 10) {
            flash('erreur', t('jeu.note_invalide'));
            redirect('jeu.php?partie=' . $partieId);
        }
        $appreciation = round((float) $appreciation, 1);

        try {
            $pdo->beginTransaction();

            $enregistrement = $pdo->prepare(
                'INSERT INTO resultats (utilisateur_id, metier_id, note_reussite, note_appreciation)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE note_reussite = ?, note_appreciation = ?'
            );
            $enregistrement->execute([
                $utilisateurId,
                (int) $partie['metier_id'],
                $partie['note_reussite'],
                $appreciation,
                $partie['note_reussite'],
                $appreciation,
            ]);

            $suppression = $pdo->prepare('DELETE FROM parties WHERE id = ? AND utilisateur_id = ?');
            $suppression->execute([$partieId, $utilisateurId]);

            $pdo->commit();
            flash('succes', t('jeu.succes'));
            redirect('resultats.php');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('FindMyWay2Work : enregistrement des notes impossible — ' . $exception->getMessage());
            flash('erreur', t('erreur.note'));
            redirect('jeu.php?partie=' . $partieId);
        }
    }

    redirect('metiers.php');
}

$partieId = (int) ($_GET['partie'] ?? 0);
if ($partieId < 1) {
    $requete = $pdo->prepare('SELECT id FROM parties WHERE utilisateur_id = ? ORDER BY id DESC LIMIT 1');
    $requete->execute([$utilisateurId]);
    $partieExistante = $requete->fetchColumn();
    if ($partieExistante) {
        redirect('jeu.php?partie=' . (int) $partieExistante);
    }
    flash('info', t('jeu.aucune_partie'));
    redirect('metiers.php');
}

$partie = charger_partie($pdo, $partieId, $utilisateurId);
if (!$partie) {
    flash('erreur', t('erreur.partie_introuvable'));
    redirect('metiers.php');
}

$classementValide = $partie['note_reussite'] !== null;
$notifications = $classementValide ? [] : notifications_partie($pdo, $partieId);

if (!$classementValide && count($notifications) < 10) {
    $suppression = $pdo->prepare('DELETE FROM parties WHERE id = ? AND utilisateur_id = ?');
    $suppression->execute([$partieId, $utilisateurId]);
    flash('erreur', t('jeu.introuvable'));
    redirect('metiers.php');
}

$ordreInitial = implode(',', array_map(static fn (array $n): int => (int) $n['id'], $notifications));

$titre_page = nom_metier($partie);
$page_active = 'jeu';
require __DIR__ . '/includes/entete.php';
?>

<section class="section zone-jeu">
    <div class="conteneur">
        <div class="jeu-grille">
            <div class="jeu-cote">
                <p class="kicker"><?= e(t('jeu.kicker')) ?></p>

                <div class="jeu-cote__fiche">
                    <p class="jeu-cote__corps"><?= e(corps_metier($partie)) ?></p>
                    <p class="jeu-cote__description"><?= e(description_metier($partie)) ?></p>
                </div>

                <ol class="etapes-jeu" aria-label="<?= e(t('jeu.kicker')) ?>">
                    <li class="<?= $classementValide ? 'est-fait' : 'est-actif' ?>">
                        <span class="etapes-jeu__num">1</span><?= e(t('jeu.etape_1')) ?>
                    </li>
                    <li class="<?= $classementValide ? 'est-actif' : '' ?>">
                        <span class="etapes-jeu__num">2</span><?= e(t('jeu.etape_2')) ?>
                    </li>
                </ol>

                <p class="jeu-cote__aide"><?= e(t('jeu.aide')) ?></p>
            </div>

            <div class="telephone">
                <div class="telephone__ecran">
                    <div class="telephone__statut">
                        <span class="telephone__heure">09:02</span>
                        <form method="post" action="jeu.php" data-confirmer="<?= e(t('jeu.confirmer_abandon')) ?>" class="telephone__abandon">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="abandonner">
                            <input type="hidden" name="partie" value="<?= $partieId ?>">
                            <button type="submit"><?= e(t('jeu.abandonner')) ?></button>
                        </form>
                        <span class="telephone__reseau" aria-hidden="true">▮▮▮</span>
                    </div>

                    <div class="telephone__entete">
                        <div>
                            <p class="telephone__corps"><?= e(corps_metier($partie)) ?></p>
                            <h1 class="telephone__titre"><?= e(nom_metier($partie)) ?></h1>
                        </div>
                        <?php if (!$classementValide): ?>
                            <span class="telephone__compteur"><?= e(t('jeu.compteur', ['n' => count($notifications)])) ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if (!$classementValide): ?>
                        <div class="telephone__consigne">
                            <p><?= e(t('jeu.consigne')) ?></p>
                            <p class="telephone__aide"><?= e(t('jeu.aide')) ?></p>
                        </div>

                        <form method="post" action="jeu.php" class="jeu-formulaire" data-classement data-partie="<?= $partieId ?>" data-modele-position="<?= e(t('jeu.position', ['position' => ':position', 'total' => ':total'])) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="classer">
                            <input type="hidden" name="partie" value="<?= $partieId ?>">
                            <input type="hidden" name="ordre" value="<?= e($ordreInitial) ?>" data-ordre>

                            <p class="sr-only" data-annonce aria-live="polite"></p>

                            <ol class="notifs" data-notifs aria-label="<?= e(t('jeu.consigne')) ?>">
                                <?php foreach ($notifications as $rang => $notification): ?>
                                    <li class="notif" data-id="<?= (int) $notification['id'] ?>">
                                        <button type="button" class="notif__poignee" data-poignee aria-label="<?= e(t('jeu.poignee')) ?>">
                                            <span aria-hidden="true">⠿</span>
                                        </button>
                                        <span class="notif__rang" data-rang><?= $rang + 1 ?></span>
                                        <div class="notif__contenu">
                                            <span class="notif__source"><?= e($langue === 'en' ? $notification['source_en'] : $notification['source_fr']) ?></span>
                                            <span class="notif__texte"><?= e($langue === 'en' ? $notification['texte_en'] : $notification['texte_fr']) ?></span>
                                        </div>
                                        <div class="notif__fleches">
                                            <button type="button" data-dir="haut" aria-label="<?= e(t('jeu.monter')) ?>">↑</button>
                                            <button type="button" data-dir="bas" aria-label="<?= e(t('jeu.descendre')) ?>">↓</button>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ol>

                            <div class="telephone__actions">
                                <button type="submit" class="btn btn--large btn--bloc"><?= e(t('jeu.valider')) ?></button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="resultat-partie">
                            <p class="kicker kicker--clair"><?= e(t('jeu.reussite_titre')) ?></p>
                            <p class="resultat-partie__note"><?= e(format_note((float) $partie['note_reussite'], $langue)) ?><span>/ 10</span></p>
                            <p class="resultat-partie__label"><?= e(t('jeu.reussite_label')) ?></p>
                            <p class="resultat-partie__texte"><?= e(t('jeu.reussite_texte')) ?></p>
                        </div>

                        <form method="post" action="jeu.php" class="jeu-formulaire jeu-formulaire--avis">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="noter">
                            <input type="hidden" name="partie" value="<?= $partieId ?>">

                            <h2 class="avis__titre"><?= e(t('jeu.appreciation_titre')) ?></h2>
                            <p class="telephone__aide"><?= e(t('jeu.appreciation_aide')) ?></p>

                            <div class="avis">
                                <output class="avis__valeur" for="note_appreciation" data-valeur="note_appreciation" data-suffixe="/ 10">
                                    <?= e(t('jeu.appreciation_valeur', ['note' => '5'])) ?>
                                </output>
                                <input type="range" id="note_appreciation" name="note_appreciation" min="0" max="10" step="1" value="5" data-curseur="note" required>
                                <div class="curseur__bornes" aria-hidden="true"><span>0</span><span>10</span></div>
                            </div>

                            <div class="telephone__actions">
                                <button type="submit" class="btn btn--large btn--bloc"><?= e(t('jeu.terminer')) ?></button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/pied.php'; ?>
