<?php
declare(strict_types=1);

const LANGUES_DISPONIBLES = ['fr', 'en'];

/**
 * Détermine la langue de la requête, mémorise un changement éventuel
 * puis recharge la page sans le paramètre lang.
 */
function initialiser_langue(): string
{
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['lang']) && in_array($_GET['lang'], LANGUES_DISPONIBLES, true)) {
        $code = $_GET['lang'];
        $_SESSION['langue'] = $code;
        setcookie('langue', $code, [
            'expires' => time() + 31536000,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);

        $chemin = strtok($_SERVER['REQUEST_URI'] ?? 'index.php', '?');
        $parametres = $_GET;
        unset($parametres['lang']);
        redirect($chemin . ($parametres ? '?' . http_build_query($parametres) : ''));
    }

    if (!empty($_SESSION['langue']) && in_array($_SESSION['langue'], LANGUES_DISPONIBLES, true)) {
        return $_SESSION['langue'];
    }

    if (!empty($_COOKIE['langue']) && in_array($_COOKIE['langue'], LANGUES_DISPONIBLES, true)) {
        $_SESSION['langue'] = $_COOKIE['langue'];
        return $_COOKIE['langue'];
    }

    return 'fr';
}

/** Charge le fichier de langue et le rend accessible aux helpers. */
function charger_langue(string $code): array
{
    $fichier = __DIR__ . '/../lang/' . $code . '.php';
    $langue = is_file($fichier) ? require $fichier : require __DIR__ . '/../lang/fr.php';
    $GLOBALS['LANG'] = $langue;
    $GLOBALS['langue'] = $code;
    return $langue;
}

/** Traduit une clé, avec remplacements :nom. */
function t(string $cle, array $remplacements = []): string
{
    $texte = $GLOBALS['LANG'][$cle] ?? $cle;
    if (!is_string($texte)) {
        return $cle;
    }
    foreach ($remplacements as $nom => $valeur) {
        $texte = str_replace(':' . $nom, (string) $valeur, $texte);
    }
    return $texte;
}

/** Code de la langue active. */
function langue_active(): string
{
    return $GLOBALS['langue'] ?? 'fr';
}

/** Libellé traduit d'un centre d'intérêt. */
function libelle_interet(string $cle): string
{
    return $GLOBALS['LANG']['interets'][$cle] ?? $cle;
}
