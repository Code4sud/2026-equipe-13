<?php
declare(strict_types=1);

/** Échappe une valeur pour l'affichage HTML. */
function e(?string $valeur): string
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Redirige et arrête le script. */
function redirect(string $url): never
{
    header('Location: ' . $url, true, 303);
    exit;
}

/** Jeton CSRF de la session (créé à la demande). */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Champ caché à insérer dans chaque formulaire POST. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Refuse la requête si le jeton CSRF est absent ou invalide. */
function csrf_check(): void
{
    $attendu = (string) ($_SESSION['csrf'] ?? '');
    $fourni = $_POST['csrf'] ?? '';
    if (!is_string($fourni)) {
        $fourni = '';
    }
    if ($attendu === '' || !hash_equals($attendu, $fourni)) {
        http_response_code(403);
        exit(t('erreur.csrf'));
    }
}

/** Ajoute un message flash affiché à la requête suivante. */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Récupère et vide les messages flash. */
function recuperer_flash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/** Utilisateur connecté, ou null. */
function utilisateur_actuel(PDO $pdo): ?array
{
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }
    $id = (int) ($_SESSION['utilisateur_id'] ?? 0);
    if ($id < 1) {
        return $cache = null;
    }
    $requete = $pdo->prepare('SELECT * FROM utilisateurs WHERE id = ?');
    $requete->execute([$id]);
    $utilisateur = $requete->fetch();
    if (!$utilisateur) {
        unset($_SESSION['utilisateur_id']);
        return $cache = null;
    }
    return $cache = $utilisateur;
}

/** Exige une session connectée et renvoie l'utilisateur. */
function exiger_connexion(PDO $pdo): array
{
    $utilisateur = utilisateur_actuel($pdo);
    if (!$utilisateur) {
        flash('erreur', t('erreur.connexion_requise'));
        $suite = $_SERVER['REQUEST_URI'] ?? 'index.php';
        redirect('connexion.php?next=' . urlencode($suite));
    }
    return $utilisateur;
}

/** URL de la page courante dans une autre langue. */
function url_langue(string $code): string
{
    $chemin = strtok($_SERVER['REQUEST_URI'] ?? 'index.php', '?');
    $parametres = $_GET;
    $parametres['lang'] = $code;
    return $chemin . '?' . http_build_query($parametres);
}

/** Avatar par défaut : initiales et couleur déterministe. */
function avatar_utilisateur(array $utilisateur): array
{
    $prenom = trim((string) ($utilisateur['prenom'] ?? ''));
    $nom = trim((string) ($utilisateur['nom'] ?? ''));

    $initiales = '';
    if ($prenom !== '') {
        $initiales .= mb_substr($prenom, 0, 1);
    }
    if ($nom !== '') {
        $initiales .= mb_substr($nom, 0, 1);
    }
    $initiales = mb_strtoupper($initiales);
    if ($initiales === '') {
        $initiales = '?';
    }

    $couleurs = ['#FF2E97', '#00E0FF', '#EDE6FF', '#C9B8FF'];
    $graine = abs(crc32(mb_strtolower($prenom . '|' . $nom)));

    return [
        'initiales' => $initiales,
        'couleur' => $couleurs[$graine % count($couleurs)],
    ];
}

/** Formate une note avec la virgule française ou le point anglais. */
function format_note(float $note, string $langue): string
{
    return number_format($note, 1, $langue === 'fr' ? ',' : '.', ' ');
}
