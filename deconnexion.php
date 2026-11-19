<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

csrf_check();
unset($_SESSION['utilisateur_id'], $_SESSION['csrf']);
session_regenerate_id(true);
flash('succes', t('flash.deconnexion'));
redirect('index.php');
