<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_name('fmw2w_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

$fichierConfig = __DIR__ . '/../config.php';
if (!is_file($fichierConfig)) {
    http_response_code(500);
    exit('Configuration manquante : copie config.example.php vers config.php, puis renseigne la base de données et la clé API.');
}

$config = require $fichierConfig;

require_once __DIR__ . '/fonctions.php';
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/metiers.php';
require_once __DIR__ . '/carte_metier.php';
require_once __DIR__ . '/ia.php';

$langue = initialiser_langue();
$LANG = charger_langue($langue);

try {
    $pdo = db_connect($config);
} catch (Throwable $exception) {
    error_log('FindMyWay2Work : connexion MySQL impossible — ' . $exception->getMessage());
    http_response_code(500);
    $detail = !empty($config['application']['debug']) ? '<pre>' . e($exception->getMessage()) . '</pre>' : '';
    exit('<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8"><title>Base de données indisponible</title>'
        . '<link rel="stylesheet" href="assets/style.css"></head><body><main class="conteneur section">'
        . '<p class="kicker">Erreur 500</p><h1>La base de données est indisponible.</h1>'
        . '<p>Vérifie le fichier <code>config.php</code> et l’import de <code>database.sql</code>.</p>' . $detail
        . '</main></body></html>');
}

// Génération unique du catalogue à la première consultation du site.
if (empty($_SESSION['metiers_verifies'])) {
    $_SESSION['metiers_verifies'] = true;
    try {
        if (compter_metiers($pdo) === 0) {
            generer_metiers($pdo, $config);
        }
    } catch (Throwable $exception) {
        error_log('FindMyWay2Work : génération des métiers impossible — ' . $exception->getMessage());
    }
}
