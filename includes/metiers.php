<?php
declare(strict_types=1);

/** Clés de la liste fixe des 15 centres d'intérêt. */
function interets_cles(): array
{
    return [
        'sport', 'nature', 'informatique', 'art', 'musique',
        'cuisine', 'mode', 'bricolage', 'sciences', 'voyages',
        'aider', 'commerce', 'sante', 'lecture', 'photo',
    ];
}

/** Centres d'intérêt enregistrés pour un utilisateur. */
function interets_utilisateur(PDO $pdo, int $utilisateurId): array
{
    $requete = $pdo->prepare('SELECT interet FROM utilisateur_interets WHERE utilisateur_id = ? ORDER BY interet');
    $requete->execute([$utilisateurId]);
    return array_column($requete->fetchAll(), 'interet');
}

/** Nombre de métiers en base (0 tant que l'IA n'a pas généré le catalogue). */
function compter_metiers(PDO $pdo): int
{
    return (int) $pdo->query('SELECT COUNT(*) FROM metiers')->fetchColumn();
}

/** Catalogue complet avec les centres d'intérêt de chaque métier. */
function charger_metiers(PDO $pdo): array
{
    $metiers = $pdo->query('SELECT * FROM metiers ORDER BY nom_fr')->fetchAll();

    $parMetier = [];
    foreach ($pdo->query('SELECT metier_id, interet FROM metier_interets') as $ligne) {
        $parMetier[(int) $ligne['metier_id']][] = $ligne['interet'];
    }

    foreach ($metiers as &$metier) {
        $metier['interets'] = $parMetier[(int) $metier['id']] ?? [];
    }
    unset($metier);

    return $metiers;
}

/** Un métier avec ses intérêts, ou null. */
function charger_metier(PDO $pdo, int $metierId): ?array
{
    $requete = $pdo->prepare('SELECT * FROM metiers WHERE id = ?');
    $requete->execute([$metierId]);
    $metier = $requete->fetch();
    if (!$metier) {
        return null;
    }

    $requete = $pdo->prepare('SELECT interet FROM metier_interets WHERE metier_id = ? ORDER BY interet');
    $requete->execute([$metierId]);
    $metier['interets'] = array_column($requete->fetchAll(), 'interet');

    return $metier;
}

/** Libellés d'un métier dans la langue active. */
function nom_metier(array $metier): string
{
    return langue_active() === 'en' ? $metier['nom_en'] : $metier['nom_fr'];
}

function corps_metier(array $metier): string
{
    return langue_active() === 'en' ? $metier['corps_en'] : $metier['corps_fr'];
}

function description_metier(array $metier): string
{
    return langue_active() === 'en' ? $metier['description_en'] : $metier['description_fr'];
}

/** Vrai si le métier partage au moins un intérêt avec l'utilisateur. */
function metier_recommande(array $metier, array $interetsUtilisateur): bool
{
    return (bool) array_intersect($metier['interets'], $interetsUtilisateur);
}

/**
 * Trie les métiers : recommandés d'abord (puis par nombre d'intérêts communs),
 * puis les autres, chacun par ordre alphabétique.
 */
function trier_metiers_pour_utilisateur(array $metiers, array $interetsUtilisateur): array
{
    $scores = [];
    foreach ($metiers as $metier) {
        $scores[(int) $metier['id']] = count(array_intersect($metier['interets'], $interetsUtilisateur));
    }

    usort($metiers, static function (array $a, array $b) use ($scores): int {
        $scoreA = $scores[(int) $a['id']];
        $scoreB = $scores[(int) $b['id']];
        if ($scoreA !== $scoreB) {
            return $scoreB <=> $scoreA;
        }
        return strcoll(nom_metier($a), nom_metier($b));
    });

    return $metiers;
}

/** Corps de métier distincts, pour le filtre. */
function corps_disponibles(array $metiers): array
{
    $corps = [];
    foreach ($metiers as $metier) {
        $corps[$metier['corps_fr']] = [
            'cle' => $metier['corps_fr'],
            'fr' => $metier['corps_fr'],
            'en' => $metier['corps_en'],
        ];
    }
    ksort($corps);
    return array_values($corps);
}

/** Notifications d'une partie, dans l'ordre d'affichage mélangé. */
function notifications_partie(PDO $pdo, int $partieId): array
{
    $requete = $pdo->prepare(
        'SELECT id, source_fr, source_en, texte_fr, texte_en, ordre_affichage
         FROM partie_notifications
         WHERE partie_id = ?
         ORDER BY ordre_affichage'
    );
    $requete->execute([$partieId]);
    return $requete->fetchAll();
}

/** Charge une partie active appartenant à l'utilisateur. */
function charger_partie(PDO $pdo, int $partieId, int $utilisateurId): ?array
{
    $requete = $pdo->prepare(
        'SELECT p.*, m.nom_fr, m.nom_en, m.corps_fr, m.corps_en, m.description_fr, m.description_en
         FROM parties p
         JOIN metiers m ON m.id = p.metier_id
         WHERE p.id = ? AND p.utilisateur_id = ?'
    );
    $requete->execute([$partieId, $utilisateurId]);
    $partie = $requete->fetch();
    return $partie ?: null;
}

/**
 * Note de réussite : D = somme des écarts de position,
 * Dmax = floor(n² / 2), R = 10 × (1 − D / Dmax), arrondie à 0,1.
 */
function calculer_note_reussite(array $positionsAttendues, array $ordreUtilisateur): float
{
    $n = count($positionsAttendues);
    if ($n === 0) {
        return 0.0;
    }

    $ecartTotal = 0;
    foreach ($ordreUtilisateur as $place => $notificationId) {
        $attendue = $positionsAttendues[$notificationId] ?? 0;
        $ecartTotal += abs(($place + 1) - $attendue);
    }

    $ecartMax = intdiv($n * $n, 2);
    if ($ecartMax === 0) {
        return 10.0;
    }

    $note = 10 * (1 - $ecartTotal / $ecartMax);
    return round(max(0.0, min(10.0, $note)), 1);
}

/**
 * Résultats de l'utilisateur, triés uniquement par la note qu'il a lui-même
 * donnée. La note de réussite reste interne et n'influence jamais le classement.
 */
function classement_utilisateur(PDO $pdo, array $utilisateur): array
{
    $requete = $pdo->prepare(
        'SELECT r.*, m.nom_fr, m.nom_en, m.corps_fr, m.corps_en,
                m.revenu, m.contact_social
         FROM resultats r
         JOIN metiers m ON m.id = r.metier_id
         WHERE r.utilisateur_id = ?'
    );
    $requete->execute([(int) $utilisateur['id']]);
    $resultats = $requete->fetchAll();

    usort($resultats, static function (array $a, array $b): int {
        $noteA = (float) $a['note_appreciation'];
        $noteB = (float) $b['note_appreciation'];
        if ($noteA !== $noteB) {
            return $noteB <=> $noteA;
        }
        return strcoll(nom_metier($a), nom_metier($b));
    });

    return $resultats;
}
