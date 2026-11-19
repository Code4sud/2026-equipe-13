/* FindMyWay2Work — interactions vanilla JS (aucune dépendance) */
(function () {
    'use strict';

    const $ = (selecteur, racine = document) => racine.querySelector(selecteur);
    const $$ = (selecteur, racine = document) => Array.from(racine.querySelectorAll(selecteur));

    /* Menu mobile ---------------------------------------------------------- */
    const boutonMenu = $('[data-menu-bouton]');
    const panneauMenu = $('[data-menu-panneau]');

    if (boutonMenu && panneauMenu) {
        const libelle = $('[data-menu-libelle]', boutonMenu);

        const fermer = () => {
            panneauMenu.classList.remove('est-ouvert');
            boutonMenu.classList.remove('est-ouvert');
            boutonMenu.setAttribute('aria-expanded', 'false');
            if (libelle) libelle.textContent = boutonMenu.dataset.labelOuvrir || '';
        };

        const basculer = () => {
            const ouvert = panneauMenu.classList.toggle('est-ouvert');
            boutonMenu.classList.toggle('est-ouvert', ouvert);
            boutonMenu.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
            if (libelle) {
                libelle.textContent = ouvert
                    ? (boutonMenu.dataset.labelFermer || '')
                    : (boutonMenu.dataset.labelOuvrir || '');
            }
        };

        boutonMenu.addEventListener('click', basculer);
        document.addEventListener('keydown', (evenement) => {
            if (evenement.key === 'Escape') fermer();
        });
        document.addEventListener('click', (evenement) => {
            if (!panneauMenu.classList.contains('est-ouvert')) return;
            if (panneauMenu.contains(evenement.target) || boutonMenu.contains(evenement.target)) return;
            fermer();
        });
        window.matchMedia('(min-width: 981px)').addEventListener('change', (evenement) => {
            if (evenement.matches) fermer();
        });
    }

    /* Curseurs : valeur + libellé actif ----------------------------------- */
    $$('input[type="range"][data-curseur]').forEach((curseur) => {
        const sortie = curseur.id ? document.querySelector('output[for="' + curseur.id + '"]') : null;
        const suffixe = sortie ? (sortie.dataset.suffixe || '') : '';
        const groupe = curseur.closest('[data-curseur-groupe]');

        const mettreAJour = () => {
            if (sortie) {
                sortie.textContent = (curseur.value + ' ' + suffixe).trim();
            }
            if (groupe) {
                const positions = $$('[data-position]', groupe);
                positions.forEach((position) => {
                    position.classList.toggle('est-actif', position.dataset.position === curseur.value);
                });
                const actif = positions.find((position) => position.dataset.position === curseur.value);
                const fort = actif ? actif.querySelector('strong') : null;
                curseur.setAttribute('aria-valuetext', fort ? fort.textContent.trim() : curseur.value);
            }
        };

        curseur.addEventListener('input', mettreAJour);
        mettreAJour();
    });

    /* Compteur de centres d'intérêt --------------------------------------- */
    const compteurInterets = $('[data-compteur-interets]');
    if (compteurInterets) {
        const cases = $$('[data-interet]');
        const modele = compteurInterets.dataset.modele || ':n';
        const mettreAJour = () => {
            const total = cases.filter((c) => c.checked).length;
            compteurInterets.textContent = modele.replace(':n', String(total));
        };
        cases.forEach((c) => c.addEventListener('change', mettreAJour));
        mettreAJour();
    }

    /* Filtre du catalogue ------------------------------------------------- */
    const grille = $('[data-grille-metiers]');
    if (grille) {
        const recherche = $('[data-filtre-recherche]');
        const puces = $$('[data-filtre-corps]');
        const compteur = $('[data-filtre-compteur]');
        const vide = $('[data-filtre-vide]');
        const cartes = $$('[data-metier]', grille);
        const modele = compteur ? (compteur.dataset.modele || '') : '';
        let corpsActif = '';

        const normaliser = (texte) => (texte || '')
            .toLocaleLowerCase('fr')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');

        const appliquer = () => {
            const requete = normaliser(recherche ? recherche.value : '').trim();
            let visibles = 0;

            cartes.forEach((carte) => {
                const bonCorps = corpsActif === '' || carte.dataset.corps === corpsActif;
                const texte = normaliser((carte.dataset.recherche || '') + ' ' + carte.textContent);
                const bonTexte = requete === '' || texte.includes(requete);
                const visible = bonCorps && bonTexte;
                carte.hidden = !visible;
                if (visible) visibles++;
            });

            if (compteur) {
                compteur.textContent = modele
                    .replace(':visible', String(visibles))
                    .replace(':total', String(cartes.length));
            }
            if (vide) vide.hidden = visibles !== 0;
        };

        if (recherche) recherche.addEventListener('input', appliquer);

        puces.forEach((puce) => {
            puce.addEventListener('click', () => {
                corpsActif = puce.dataset.filtreCorps || '';
                puces.forEach((autre) => {
                    const actif = autre === puce;
                    autre.classList.toggle('est-actif', actif);
                    autre.setAttribute('aria-pressed', actif ? 'true' : 'false');
                });
                appliquer();
            });
        });

        appliquer();
    }

    /* Classement des notifications ---------------------------------------- */
    $$('[data-classement]').forEach((formulaire) => {
        const liste = $('[data-notifs]', formulaire);
        const champOrdre = $('[data-ordre]', formulaire);
        const annonce = $('[data-annonce]', formulaire);
        if (!liste || !champOrdre) return;

        const modelePosition = formulaire.dataset.modelePosition || ':position / :total';
        const cleStockage = 'fmw2w_ordre_' + (formulaire.dataset.partie || '0');

        const identifiants = () => $$('.notif', liste).map((item) => item.dataset.id);

        const memoriser = () => {
            const ordre = identifiants();
            champOrdre.value = ordre.join(',');
            try {
                window.sessionStorage.setItem(cleStockage, JSON.stringify(ordre));
            } catch (erreur) {
                /* Stockage indisponible : le formulaire reste fonctionnel. */
            }
        };

        const mettreAJourRangs = () => {
            Array.from(liste.children).forEach((item, index) => {
                const rang = $('[data-rang]', item);
                if (rang) rang.textContent = String(index + 1);
                item.setAttribute('aria-posinset', String(index + 1));
                item.setAttribute('aria-setsize', String(liste.children.length));
            });
        };

        const annoncer = (item) => {
            if (!annonce) return;
            const position = Array.from(liste.children).indexOf(item) + 1;
            annonce.textContent = modelePosition
                .replace(':position', String(position))
                .replace(':total', String(liste.children.length));
        };

        /* Restaure l'ordre local après un changement de langue ou un rechargement. */
        let ordreMemorise = null;
        try {
            ordreMemorise = JSON.parse(window.sessionStorage.getItem(cleStockage) || 'null');
        } catch (erreur) {
            ordreMemorise = null;
        }
        if (Array.isArray(ordreMemorise)) {
            const actuels = identifiants();
            const valide = ordreMemorise.length === actuels.length
                && ordreMemorise.every((id) => actuels.includes(id));
            if (valide) {
                ordreMemorise.forEach((id) => {
                    const item = liste.querySelector('.notif[data-id="' + id + '"]');
                    if (item) liste.appendChild(item);
                });
            }
        }

        mettreAJourRangs();
        memoriser();

        formulaire.addEventListener('submit', memoriser);

        /* Flèches et clavier. */
        const deplacer = (item, direction) => {
            if (direction === 'haut' && item.previousElementSibling) {
                liste.insertBefore(item, item.previousElementSibling);
            } else if (direction === 'bas' && item.nextElementSibling) {
                liste.insertBefore(item.nextElementSibling, item);
            } else {
                return;
            }
            mettreAJourRangs();
            memoriser();
            annoncer(item);
        };

        liste.addEventListener('click', (evenement) => {
            const bouton = evenement.target.closest('[data-dir]');
            if (!bouton) return;
            const item = bouton.closest('.notif');
            if (!item) return;
            deplacer(item, bouton.dataset.dir);
            bouton.focus();
        });

        $$('[data-poignee]', liste).forEach((poignee) => {
            poignee.addEventListener('keydown', (evenement) => {
                if (evenement.key !== 'ArrowUp' && evenement.key !== 'ArrowDown') return;
                evenement.preventDefault();
                const item = poignee.closest('.notif');
                if (!item) return;
                deplacer(item, evenement.key === 'ArrowUp' ? 'haut' : 'bas');
            });
        });

        /* Glisser-déposer tactile et souris via Pointer Events. */
        $$('[data-poignee]', liste).forEach((poignee) => {
            let itemDeplace = null;
            let pointeur = null;

            const auDeplacement = (evenement) => {
                if (!itemDeplace || evenement.pointerId !== pointeur) return;
                evenement.preventDefault();

                const autres = $$('.notif', liste).filter((item) => item !== itemDeplace);
                let avant = null;
                for (const autre of autres) {
                    const rect = autre.getBoundingClientRect();
                    if (evenement.clientY < rect.top + rect.height / 2) {
                        avant = autre;
                        break;
                    }
                }
                if (avant) {
                    liste.insertBefore(itemDeplace, avant);
                } else {
                    liste.appendChild(itemDeplace);
                }

                const cadre = liste.getBoundingClientRect();
                if (evenement.clientY < cadre.top + 42) {
                    liste.scrollTop -= 14;
                } else if (evenement.clientY > cadre.bottom - 42) {
                    liste.scrollTop += 14;
                }

                mettreAJourRangs();
            };

            const terminer = () => {
                if (!itemDeplace) return;
                itemDeplace.classList.remove('est-drag');
                document.removeEventListener('pointermove', auDeplacement);
                document.removeEventListener('pointerup', terminer);
                document.removeEventListener('pointercancel', terminer);
                memoriser();
                annoncer(itemDeplace);
                itemDeplace = null;
                pointeur = null;
            };

            poignee.addEventListener('pointerdown', (evenement) => {
                if (evenement.button !== 0) return;
                const item = poignee.closest('.notif');
                if (!item) return;
                evenement.preventDefault();
                itemDeplace = item;
                pointeur = evenement.pointerId;
                item.classList.add('est-drag');
                try {
                    poignee.setPointerCapture(evenement.pointerId);
                } catch (erreur) {
                    /* Certains navigateurs ne capturent pas : les écouteurs globaux suffisent. */
                }
                document.addEventListener('pointermove', auDeplacement, { passive: false });
                document.addEventListener('pointerup', terminer);
                document.addEventListener('pointercancel', terminer);
            });
        });
    });

    /* Confirmation des actions destructrices ------------------------------- */
    $$('form[data-confirmer]').forEach((formulaire) => {
        formulaire.addEventListener('submit', (evenement) => {
            if (!window.confirm(formulaire.dataset.confirmer || '')) {
                evenement.preventDefault();
            }
        });
    });
})();
