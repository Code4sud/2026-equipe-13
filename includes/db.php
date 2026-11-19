<?php
declare(strict_types=1);

/** Connexion PDO à la base MySQL/MariaDB. */
function db_connect(array $config): PDO
{
    $db = $config['db'];
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $db['hote'],
        $db['nom'],
        $db['charset'] ?? 'utf8mb4'
    );

    return new PDO($dsn, $db['utilisateur'], $db['mot_de_passe'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
