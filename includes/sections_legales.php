<?php
declare(strict_types=1);

/** Affiche une suite de sections légales traduites. */
function afficher_sections_legales(array $sections): void
{
    echo '<div class="contenu-legal">';
    foreach ($sections as $section) {
        echo '<section class="contenu-legal__section">';
        echo '<h2>' . e($section['titre']) . '</h2>';
        foreach ($section['paragraphes'] as $paragraphe) {
            echo '<p>' . e($paragraphe) . '</p>';
        }
        echo '</section>';
    }
    echo '</div>';
}
