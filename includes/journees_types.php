<?php
declare(strict_types=1);

/**
 * Journées types extraites du PDF « Journées types par secteur »
 * (Centre des Possibles). Les journées sont indicatives et varient
 * selon l'employeur et le poste.
 *
 * Chaque entrée est associée à un métier du catalogue par mots-clés
 * normalisés, pour rester valable si l'IA reformule légèrement un nom.
 */
function journees_types(): array
{
    static $journees = null;
    if ($journees !== null) {
        return $journees;
    }

    return $journees = [
        'developpeur' => [
            'source_fr' => 'Développeur',
            'source_en' => 'Developer',
            'mots_cles' => ['developpeur', 'developer'],
            'phases' => [
                ['moment_fr' => 'Matin', 'moment_en' => 'Morning', 'texte_fr' => 'Point d’équipe, développement de fonctionnalités', 'texte_en' => 'Team stand-up, feature development'],
                ['moment_fr' => 'Midi', 'moment_en' => 'Midday', 'texte_fr' => 'Pause, revue de code', 'texte_en' => 'Break, code review'],
                ['moment_fr' => 'Après-midi', 'moment_en' => 'Afternoon', 'texte_fr' => 'Tests, correction de bugs', 'texte_en' => 'Testing, bug fixing'],
                ['moment_fr' => 'Fin de journée', 'moment_en' => 'End of day', 'texte_fr' => 'Mise en production ou sauvegarde, documentation', 'texte_en' => 'Release or backup, documentation'],
            ],
            'taches_fr' => ['Développement : code, fonctionnalités', 'Tests : qualité, bugs', 'Collaboration : équipe, revue de code', 'Documentation : suivi, déploiement'],
            'taches_en' => ['Development: code and features', 'Testing: quality and bugs', 'Collaboration: team and code review', 'Documentation: tracking and deployment'],
        ],

        'infirmier' => [
            'source_fr' => 'Infirmier(ère)',
            'source_en' => 'Nurse',
            'mots_cles' => ['infirmier', 'infirmiere', 'nurse'],
            'phases' => [
                ['moment_fr' => 'Matin', 'moment_en' => 'Morning', 'texte_fr' => 'Transmissions, prise des constantes, soins du matin', 'texte_en' => 'Handover, vital signs, morning care'],
                ['moment_fr' => 'Midi', 'moment_en' => 'Midday', 'texte_fr' => 'Repas des patients, traitements, pause', 'texte_en' => 'Patient meals, treatments, break'],
                ['moment_fr' => 'Après-midi', 'moment_en' => 'Afternoon', 'texte_fr' => 'Soins, dossiers, échanges avec les familles', 'texte_en' => 'Care, records, conversations with families'],
                ['moment_fr' => 'Fin de journée', 'moment_en' => 'End of day', 'texte_fr' => 'Transmissions à l’équipe suivante', 'texte_en' => 'Handover to the next team'],
            ],
            'taches_fr' => ['Soins : injections, pansements', 'Surveillance : état des patients', 'Coordination : médecins, équipe', 'Administratif : dossiers de soins'],
            'taches_en' => ['Care: injections and dressings', 'Monitoring: patient condition', 'Coordination: doctors and team', 'Administration: care records'],
        ],

        'educateur' => [
            'source_fr' => 'Éducateur / assistant social',
            'source_en' => 'Educator / social worker',
            'mots_cles' => ['educateur', 'educator', 'assistant social', 'social worker'],
            'phases' => [
                ['moment_fr' => 'Matin', 'moment_en' => 'Morning', 'texte_fr' => 'Accueil du public, entretiens individuels', 'texte_en' => 'Welcoming people, one-to-one interviews'],
                ['moment_fr' => 'Midi', 'moment_en' => 'Midday', 'texte_fr' => 'Pause, point rapide avec l’équipe', 'texte_en' => 'Break, quick team check-in'],
                ['moment_fr' => 'Après-midi', 'moment_en' => 'Afternoon', 'texte_fr' => 'Visites, démarches, réunions de synthèse', 'texte_en' => 'Visits, procedures, case meetings'],
                ['moment_fr' => 'Fin de journée', 'moment_en' => 'End of day', 'texte_fr' => 'Rédaction des rapports et suivis', 'texte_en' => 'Writing reports and follow-ups'],
            ],
            'taches_fr' => ['Accueil : écoute, évaluation de la situation', 'Accompagnement : démarches, orientation', 'Partenaires : administrations, associations', 'Administratif : rapports, dossiers'],
            'taches_en' => ['Welcome: listening, assessing the situation', 'Support: procedures and guidance', 'Partners: public services and associations', 'Administration: reports and files'],
        ],

        'mecanicien' => [
            'source_fr' => 'Mécanicien',
            'source_en' => 'Car mechanic',
            'mots_cles' => ['mecanicien', 'mechanic'],
            'phases' => [
                ['moment_fr' => 'Matin', 'moment_en' => 'Morning', 'texte_fr' => 'Prise des véhicules, diagnostic', 'texte_en' => 'Receiving vehicles, diagnosis'],
                ['moment_fr' => 'Midi', 'moment_en' => 'Midday', 'texte_fr' => 'Pause, commande de pièces', 'texte_en' => 'Break, ordering parts'],
                ['moment_fr' => 'Après-midi', 'moment_en' => 'Afternoon', 'texte_fr' => 'Réparations, entretien, contrôles', 'texte_en' => 'Repairs, servicing, checks'],
                ['moment_fr' => 'Fin de journée', 'moment_en' => 'End of day', 'texte_fr' => 'Essais, remise des véhicules, facturation', 'texte_en' => 'Test drives, vehicle handover, invoicing'],
            ],
            'taches_fr' => ['Diagnostic : pannes, contrôles', 'Réparation : pièces, entretien', 'Relation client : devis, explications', 'Administratif : ordres de réparation'],
            'taches_en' => ['Diagnosis: breakdowns and checks', 'Repair: parts and servicing', 'Customer relations: quotes and explanations', 'Administration: repair orders'],
        ],

        'communication' => [
            'source_fr' => 'Chargé de communication',
            'source_en' => 'Communications officer',
            'mots_cles' => ['charge de communication', 'communication officer', 'communications officer'],
            'phases' => [
                ['moment_fr' => 'Matin', 'moment_en' => 'Morning', 'texte_fr' => 'Mails, planning éditorial', 'texte_en' => 'Emails, editorial planning'],
                ['moment_fr' => 'Midi', 'moment_en' => 'Midday', 'texte_fr' => 'Pause, veille sur les réseaux', 'texte_en' => 'Break, social media watch'],
                ['moment_fr' => 'Après-midi', 'moment_en' => 'Afternoon', 'texte_fr' => 'Création de contenus, réunions', 'texte_en' => 'Content creation, meetings'],
                ['moment_fr' => 'Fin de journée', 'moment_en' => 'End of day', 'texte_fr' => 'Statistiques, publication, préparation du lendemain', 'texte_en' => 'Analytics, publishing, preparing tomorrow'],
            ],
            'taches_fr' => ['Création : textes, visuels', 'Diffusion : réseaux, newsletters', 'Veille : tendances, concurrence', 'Analyse : résultats, statistiques'],
            'taches_en' => ['Creation: copy and visuals', 'Distribution: social media and newsletters', 'Monitoring: trends and competitors', 'Analysis: results and statistics'],
        ],

        'vendeur' => [
            'source_fr' => 'Conseiller de vente',
            'source_en' => 'Sales adviser',
            'mots_cles' => ['vendeur', 'vendeuse', 'conseiller de vente', 'sales assistant', 'retail sales'],
            'phases' => [
                ['moment_fr' => 'Matin', 'moment_en' => 'Morning', 'texte_fr' => 'Ouverture, mise en rayon, objectifs du jour', 'texte_en' => 'Opening, shelf display, daily targets'],
                ['moment_fr' => 'Midi', 'moment_en' => 'Midday', 'texte_fr' => 'Pause, réassort', 'texte_en' => 'Break, restocking'],
                ['moment_fr' => 'Après-midi', 'moment_en' => 'Afternoon', 'texte_fr' => 'Accueil et conseil des clients, ventes', 'texte_en' => 'Welcoming, advising customers, sales'],
                ['moment_fr' => 'Fin de journée', 'moment_en' => 'End of day', 'texte_fr' => 'Encaissement, inventaire, clôture', 'texte_en' => 'Checkout, inventory, closing'],
            ],
            'taches_fr' => ['Accueil : conseil, besoins', 'Vente : argumentation, encaissement', 'Magasin : rayons, stocks', 'Suivi : objectifs, clôture'],
            'taches_en' => ['Welcome: advice and needs', 'Sales: pitching and checkout', 'Store: shelves and stock', 'Follow-up: targets and closing'],
        ],

        'data_analyst' => [
            'source_fr' => 'Analyste de données / cybersécurité',
            'source_en' => 'Data analyst / cybersecurity',
            'mots_cles' => ['data analyst', 'analyste de donnees', 'analyste donnees'],
            'phases' => [
                ['moment_fr' => 'Matin', 'moment_en' => 'Morning', 'texte_fr' => 'Veille, vérification des alertes ou des données', 'texte_en' => 'Monitoring, checking alerts or data'],
                ['moment_fr' => 'Midi', 'moment_en' => 'Midday', 'texte_fr' => 'Pause, point avec l’équipe', 'texte_en' => 'Break, team check-in'],
                ['moment_fr' => 'Après-midi', 'moment_en' => 'Afternoon', 'texte_fr' => 'Analyse, audit, traitement des incidents', 'texte_en' => 'Analysis, audit, incident handling'],
                ['moment_fr' => 'Fin de journée', 'moment_en' => 'End of day', 'texte_fr' => 'Rapport, mise à jour des procédures', 'texte_en' => 'Report, updating procedures'],
            ],
            'taches_fr' => ['Veille : menaces, tendances', 'Analyse : données, journaux', 'Protection : correctifs, accès', 'Rapport : synthèse, recommandations'],
            'taches_en' => ['Monitoring: threats and trends', 'Analysis: data and logs', 'Protection: patches and access', 'Reporting: summary and recommendations'],
        ],

        'guide' => [
            'source_fr' => 'Guide touristique',
            'source_en' => 'Tour guide',
            'mots_cles' => ['guide touristique', 'tour guide'],
            'phases' => [
                ['moment_fr' => 'Matin', 'moment_en' => 'Morning', 'texte_fr' => 'Accueil du groupe, visite commentée', 'texte_en' => 'Welcoming the group, guided tour'],
                ['moment_fr' => 'Midi', 'moment_en' => 'Midday', 'texte_fr' => 'Pause déjeuner, vérification des réservations', 'texte_en' => 'Lunch break, checking bookings'],
                ['moment_fr' => 'Après-midi', 'moment_en' => 'Afternoon', 'texte_fr' => 'Seconde visite ou activité', 'texte_en' => 'Second tour or activity'],
                ['moment_fr' => 'Fin de journée', 'moment_en' => 'End of day', 'texte_fr' => 'Conseils au groupe, compte rendu, préparation du lendemain', 'texte_en' => 'Advice to the group, report, preparing tomorrow'],
            ],
            'taches_fr' => ['Préparation : météo, matériel', 'Animation : visites, anecdotes', 'Organisation : pauses, transports', 'Administratif : comptes rendus, agences'],
            'taches_en' => ['Preparation: weather and equipment', 'Animation: tours and stories', 'Organisation: breaks and transport', 'Administration: reports and agencies'],
        ],
    ];
}

/** Minuscule sans accents, pour comparer les noms de métiers. */
function normaliser_journee(string $texte): string
{
    $texte = mb_strtolower($texte);
    return str_replace(
        ['é', 'è', 'ê', 'ë', 'à', 'â', 'ä', 'î', 'ï', 'ô', 'ö', 'ù', 'û', 'ü', 'ç', 'œ'],
        ['e', 'e', 'e', 'e', 'a', 'a', 'a', 'i', 'i', 'o', 'o', 'u', 'u', 'u', 'c', 'oe'],
        $texte
    );
}

/** Journée type correspondant à un métier, ou null. */
function journee_type_pour_metier(array $metier): ?array
{
    $noms = normaliser_journee((string) $metier['nom_fr'] . ' ' . (string) $metier['nom_en']);
    foreach (journees_types() as $journee) {
        foreach ($journee['mots_cles'] as $motCle) {
            if (str_contains($noms, normaliser_journee($motCle))) {
                return $journee;
            }
        }
    }
    return null;
}

/** Version traduite de la journée pour l'affichage. */
function journee_type_traduite(array $journee): array
{
    $en = langue_active() === 'en';
    $phases = [];
    foreach ($journee['phases'] as $phase) {
        $phases[] = [
            'moment' => $en ? $phase['moment_en'] : $phase['moment_fr'],
            'texte' => $en ? $phase['texte_en'] : $phase['texte_fr'],
        ];
    }

    return [
        'source' => $en ? $journee['source_en'] : $journee['source_fr'],
        'phases' => $phases,
        'taches' => $en ? $journee['taches_en'] : $journee['taches_fr'],
    ];
}
