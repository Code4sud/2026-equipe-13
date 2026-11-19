<?php
/**
 * Configuration de FindMyWay2Work.
 *
 * 1. Copie ce fichier en config.php.
 * 2. Renseigne les identifiants MySQL et la clé API DeepSeek.
 * 3. Ne commit jamais config.php : il est ignoré par Git.
 */
return [
    'db' => [
        'hote'     => 'localhost',
        'nom'      => 'findmyway2work',
        'utilisateur' => 'root',
        'mot_de_passe' => '',
        'charset'  => 'utf8mb4',
    ],
    'deepseek' => [
        'cle_api'  => '',
        'modele'   => 'deepseek-chat',
        'url'      => 'https://api.deepseek.com/chat/completions',
    ],
    'application' => [
        'debug' => false,
    ],
];
