<?php
declare(strict_types=1);

/**
 * Appelle l'API DeepSeek en mode JSON et renvoie le tableau décodé.
 * Aucune donnée utilisateur ne doit jamais être passée dans $messages.
 */
function appel_deepseek(array $config, array $messages, int $maxTokens, float $temperature): ?array
{
    $cle = trim((string) ($config['deepseek']['cle_api'] ?? ''));
    if ($cle === '' || !function_exists('curl_init')) {
        error_log('FindMyWay2Work : clé DeepSeek absente ou extension cURL indisponible.');
        return null;
    }

    @set_time_limit(240);

    $charge = [
        'model' => $config['deepseek']['modele'] ?? 'deepseek-chat',
        'messages' => $messages,
        'temperature' => $temperature,
        'max_tokens' => $maxTokens,
        'response_format' => ['type' => 'json_object'],
        'stream' => false,
    ];

    $curl = curl_init((string) ($config['deepseek']['url'] ?? 'https://api.deepseek.com/chat/completions'));
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $cle,
        ],
        CURLOPT_POSTFIELDS => json_encode($charge, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_TIMEOUT => 90,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $reponse = curl_exec($curl);
    $code = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $erreur = curl_error($curl);

    if ($reponse === false || $code !== 200) {
        error_log('FindMyWay2Work : erreur DeepSeek (HTTP ' . $code . ') ' . $erreur);
        return null;
    }

    $donnees = json_decode((string) $reponse, true);
    $contenu = trim((string) ($donnees['choices'][0]['message']['content'] ?? ''));

    if ($contenu === '') {
        error_log('FindMyWay2Work : réponse DeepSeek vide.');
        return null;
    }

    if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/is', $contenu, $correspondance)) {
        $contenu = $correspondance[1];
    }

    $json = json_decode($contenu, true);
    if (!is_array($json)) {
        $debut = strpos($contenu, '{');
        $fin = strrpos($contenu, '}');
        if ($debut !== false && $fin !== false && $fin > $debut) {
            $json = json_decode(substr($contenu, $debut, $fin - $debut + 1), true);
        }
    }

    if (!is_array($json)) {
        error_log('FindMyWay2Work : JSON DeepSeek invalide.');
        return null;
    }

    return $json;
}

/** Valide strictement la réponse de génération des 15 métiers. */
function valider_metiers_ia(?array $donnees): ?array
{
    $liste = $donnees['metiers'] ?? null;
    if (!is_array($liste) || count($liste) !== 15) {
        return null;
    }

    $cles = interets_cles();
    $noms = [];
    $corps = [];
    $revenus = [];
    $contacts = [];
    $propre = [];

    foreach ($liste as $metier) {
        if (!is_array($metier)) {
            return null;
        }

        foreach (['nom_fr', 'nom_en', 'corps_fr', 'corps_en', 'description_fr', 'description_en'] as $champ) {
            if (!isset($metier[$champ]) || !is_string($metier[$champ]) || trim($metier[$champ]) === '') {
                return null;
            }
        }

        $nomFr = trim($metier['nom_fr']);
        $nomEn = trim($metier['nom_en']);
        $corpsFr = trim($metier['corps_fr']);
        $corpsEn = trim($metier['corps_en']);
        $descriptionFr = trim($metier['description_fr']);
        $descriptionEn = trim($metier['description_en']);

        if (mb_strlen($nomFr) > 120 || mb_strlen($nomEn) > 120
            || mb_strlen($corpsFr) > 120 || mb_strlen($corpsEn) > 120) {
            return null;
        }

        $interets = $metier['interets'] ?? null;
        if (!is_array($interets)) {
            return null;
        }
        $interets = array_values(array_unique(array_filter($interets, 'is_string')));
        if (count($interets) < 1 || count($interets) > 3) {
            return null;
        }
        foreach ($interets as $interet) {
            if (!in_array($interet, $cles, true)) {
                return null;
            }
        }

        $revenu = strtolower(trim((string) ($metier['revenu'] ?? '')));
        $contact = strtolower(trim((string) ($metier['contact_social'] ?? '')));
        $revenu = str_replace(['é', 'è', 'ê'], 'e', $revenu);
        $contact = str_replace(['é', 'è', 'ê'], 'e', $contact);
        if (!in_array($revenu, ['faible', 'moyen', 'eleve'], true)
            || !in_array($contact, ['faible', 'moyen', 'eleve'], true)) {
            return null;
        }

        $cleNom = mb_strtolower($nomFr);
        if (isset($noms[$cleNom])) {
            return null;
        }
        $noms[$cleNom] = true;
        $corps[$corpsFr] = true;
        $revenus[$revenu] = true;
        $contacts[$contact] = true;

        $propre[] = [
            'nom_fr' => $nomFr,
            'nom_en' => $nomEn,
            'corps_fr' => $corpsFr,
            'corps_en' => $corpsEn,
            'description_fr' => $descriptionFr,
            'description_en' => $descriptionEn,
            'interets' => $interets,
            'revenu' => $revenu,
            'contact_social' => $contact,
        ];
    }

    if (count($corps) < 4
        || count($revenus) < 3
        || count($contacts) < 3) {
        return null;
    }

    return $propre;
}

/** Génère et enregistre les 15 métiers si la table est vide. */
function generer_metiers(PDO $pdo, array $config): bool
{
    $verrou = (int) $pdo->query("SELECT GET_LOCK('fmw2w_metiers', 30)")->fetchColumn();
    if ($verrou !== 1) {
        return false;
    }

    try {
        if (compter_metiers($pdo) > 0) {
            return true;
        }

        $libelles = [];
        foreach (interets_cles() as $cle) {
            $libelles[] = '- ' . $cle . ' : ' . libelle_interet($cle);
        }

        $prompt = "Génère exactement 15 métiers variés et réalistes pour le marché du travail en France, destinés à des jeunes de 15 à 25 ans.\n\n"
            . "Contraintes :\n"
            . "- exactement 15 métiers, tous différents ;\n"
            . "- plusieurs corps de métier différents (au moins 6 serait idéal), avec un libellé français et un libellé anglais cohérents pour un même corps ;\n"
            . "- chaque métier possède entre 1 et 3 centres d'intérêt choisis EXCLUSIVEMENT parmi les clés ci-dessous ;\n"
            . "- les trois niveaux de revenu (faible, moyen, eleve) et les trois niveaux de contact social (faible, moyen, eleve) doivent chacun être représentés ;\n"
            . "- descriptions courtes et concrètes (1 à 2 phrases, 220 caractères maximum), fidèles au quotidien et aux salaires réels en France ;\n"
            . "- aucun métier inventé, aucune aberration, aucun doublon.\n\n"
            . "Centres d'intérêt autorisés (utilise la clé exacte) :\n" . implode("\n", $libelles) . "\n\n"
            . "Signification des champs cachés :\n"
            . "- revenu : estimation du niveau de salaire du métier (faible, moyen ou eleve) ;\n"
            . "- contact_social : fréquence des contacts humains dans le métier (faible, moyen ou eleve).\n\n"
            . "Schéma JSON exact :\n"
            . '{"metiers":[{"nom_fr":"...","nom_en":"...","corps_fr":"...","corps_en":"...","description_fr":"...","description_en":"...","interets":["cle1","cle2"],"revenu":"faible","contact_social":"moyen"}]}' . "\n\n"
            . "Réponds uniquement avec cet objet JSON, sans texte autour.";

        $messages = [
            ['role' => 'system', 'content' => 'Tu es un concepteur de contenu pour un jeu d’orientation professionnelle en France. Tu réponds uniquement avec un objet JSON valide, sans texte autour.'],
            ['role' => 'user', 'content' => $prompt],
        ];

        for ($essai = 0; $essai < 3; $essai++) {
            $reponse = appel_deepseek($config, $messages, 6000, 0.8);
            $metiers = valider_metiers_ia($reponse);
            if ($metiers !== null && enregistrer_metiers($pdo, $metiers)) {
                return true;
            }
        }

        return false;
    } finally {
        $pdo->query("SELECT RELEASE_LOCK('fmw2w_metiers')");
    }
}

/** Insère le catalogue validé dans une transaction. */
function enregistrer_metiers(PDO $pdo, array $metiers): bool
{
    try {
        $pdo->beginTransaction();

        $insertionMetier = $pdo->prepare(
            'INSERT INTO metiers (nom_fr, nom_en, corps_fr, corps_en, description_fr, description_en, revenu, contact_social)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insertionInteret = $pdo->prepare('INSERT INTO metier_interets (metier_id, interet) VALUES (?, ?)');

        foreach ($metiers as $metier) {
            $insertionMetier->execute([
                $metier['nom_fr'],
                $metier['nom_en'],
                $metier['corps_fr'],
                $metier['corps_en'],
                $metier['description_fr'],
                $metier['description_en'],
                $metier['revenu'],
                $metier['contact_social'],
            ]);
            $metierId = (int) $pdo->lastInsertId();
            foreach ($metier['interets'] as $interet) {
                $insertionInteret->execute([$metierId, $interet]);
            }
        }

        $pdo->commit();
        return true;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('FindMyWay2Work : insertion des métiers impossible — ' . $exception->getMessage());
        return false;
    }
}

/** Valide la réponse de génération des notifications d'une partie. */
function valider_notifications_ia(?array $donnees): ?array
{
    $liste = $donnees['notifications'] ?? null;
    if (!is_array($liste)) {
        return null;
    }

    $total = count($liste);
    if ($total < 10 || $total > 15) {
        return null;
    }

    $propres = [];
    $dejaVu = [];
    foreach ($liste as $notification) {
        if (!is_array($notification)) {
            return null;
        }
        foreach (['source_fr', 'source_en', 'texte_fr', 'texte_en'] as $champ) {
            if (!isset($notification[$champ]) || !is_string($notification[$champ]) || trim($notification[$champ]) === '') {
                return null;
            }
        }

        $sourceFr = trim($notification['source_fr']);
        $sourceEn = trim($notification['source_en']);
        $texteFr = trim($notification['texte_fr']);
        $texteEn = trim($notification['texte_en']);

        if (mb_strlen($sourceFr) > 140 || mb_strlen($sourceEn) > 140) {
            return null;
        }
        if (mb_strlen($texteFr) < 30 || mb_strlen($texteEn) < 30
            || mb_strlen($texteFr) > 360 || mb_strlen($texteEn) > 360) {
            return null;
        }

        $cle = mb_strtolower($texteFr);
        if (isset($dejaVu[$cle])) {
            return null;
        }
        $dejaVu[$cle] = true;

        $propres[] = [
            'source_fr' => $sourceFr,
            'source_en' => $sourceEn,
            'texte_fr' => $texteFr,
            'texte_en' => $texteEn,
        ];
    }

    return $propres;
}

/**
 * Génère 10 à 15 notifications bilingues pour un métier, dans l'ordre de priorité attendu.
 * Le prompt ne contient aucune donnée utilisateur.
 */
function generer_notifications_ia(array $config, array $metier): ?array
{
    $prompt = "Contexte : dans un jeu d'orientation, le joueur découvre un métier en vivant une journée type. Au lancement de la partie, il reçoit entre 10 et 15 notifications qui tombent en même temps. Sa mission est de les classer de la plus prioritaire à la moins prioritaire.\n\n"
        . "Objectif du jeu : classer par priorité et importance. La priorité combine l'urgence (sécurité, délai, tâche bloquante) et l'importance (impact sur les personnes, les clients, la qualité ou le coût). Chaque notification doit donc apporter assez de contexte pour que le joueur puisse la comparer aux autres et justifier sa place.\n\n"
        . "Métier : " . $metier['nom_fr'] . ' (' . $metier['nom_en'] . ")\n"
        . "Corps de métier : " . $metier['corps_fr'] . "\n"
        . 'Description : ' . $metier['description_fr'] . "\n\n"
        . "Règles de génération :\n"
        . "- génère entre 10 et 15 notifications, toutes différentes et propres à ce métier ;\n"
        . "- chaque notification est un mini-scénario concret et autonome de 1 à 2 phrases (30 à 360 caractères) : on doit comprendre qui est concerné, ce qui se passe, depuis quand ou dans quel délai, et ce qui est en jeu ;\n"
        . "- une notification ne doit jamais être une simple étiquette ni une phrase vague du type « Le client attend » : ajoute toujours le contexte qui permet de juger la priorité ;\n"
        . "- varie les sources et les situations : sécurité, client mécontent, délai serré, collègue en difficulté, machine bloquée, erreur de qualité, matériel manquant, demande du manager, etc. ;\n"
        . "- crée une vraie gradation : la 1re notification est clairement la plus prioritaire, la dernière clairement la moins prioritaire. L'ordre attendu doit être justifiable sans ambiguïté ;\n"
        . "- chaque notification possède une source courte (qui ou quoi l'envoie : « Réception », « Atelier », « Client », « Manager », « Machine »…) ;\n"
        . "- écris chaque notification en français et en anglais, avec exactement le même sens et le même niveau de détail dans les deux langues, et au même rang dans l'ordre.\n\n"
        . "Classe les notifications dans l'ordre de priorité attendu : la première est la plus prioritaire.\n\n"
        . "Schéma JSON exact :\n"
        . '{"notifications":[{"source_fr":"...","source_en":"...","texte_fr":"...","texte_en":"..."}]}' . "\n\n"
        . "Réponds uniquement avec cet objet JSON, sans texte autour.";

    $messages = [
        ['role' => 'system', 'content' => 'Tu es un concepteur de mises en situation professionnelles réalistes pour un jeu d’orientation. Chaque notification est un mini-scénario de 1 à 2 phrases, avec assez de contexte pour être classé par priorité et importance. Tu réponds uniquement avec un objet JSON valide, sans texte autour.'],
        ['role' => 'user', 'content' => $prompt],
    ];

    for ($essai = 0; $essai < 3; $essai++) {
        $reponse = appel_deepseek($config, $messages, 6000, 0.9);
        $notifications = valider_notifications_ia($reponse);
        if ($notifications !== null) {
            return $notifications;
        }
    }

    return null;
}
