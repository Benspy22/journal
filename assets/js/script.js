/**
 * Journal de Classe - Scripts
 */
jQuery(document).ready(function($) {
    // Variables globales
    const formulaire = $('#formulaire-journal');
    const message = $('#journal-classe-message');
    const submitButton = formulaire.find('button[type="submit"]');

    // Debug: Afficher l'URL AJAX
    console.log('Config AJAX:', {
        url: journalClasseConfig.ajaxurl,
        noncePresent: !!journalClasseConfig.nonce
    });

    /**
     * Initialisation du formulaire
     */
    function initialiserFormulaire() {
        // Date du jour par défaut
        $('#date').val(new Date().toISOString().split('T')[0]);
        
        // Cours en lecture seule
        $('#cours').val('Français').prop('readonly', true);
        
        // Mise à jour de la classe lors de la sélection
        $('#niveau, #lettre').on('change', mettreAJourClasse);
        
        // Validation en temps réel
        $('input[required], select[required], textarea[required]').on('input change', function() {
            validerChamp($(this));
        });
        
        // Gestion de la soumission
        formulaire.on('submit', soumettreFormulaire);
        
        console.log('Formulaire initialisé');
    }

    /**
     * Mise à jour du champ classe
     */
    function mettreAJourClasse() {
        const niveau = $('#niveau').val();
        const lettre = $('#lettre').val();
        if (niveau && lettre) {
            $('#classe').val(niveau + lettre);
            console.log('Classe mise à jour:', niveau + lettre);
        } else {
            $('#classe').val('');
        }
    }

    /**
     * Validation d'un champ
     */
    function validerChamp(champ) {
        const estValide = champ.val().trim() !== '';
        champ.toggleClass('erreur', !estValide);
        return estValide;
    }

    /**
     * Validation du formulaire complet
     */
    function validerFormulaire() {
        let estValide = true;
        
        // Validation des champs requis
        formulaire.find('[required]').each(function() {
            if (!validerChamp($(this))) {
                estValide = false;
            }
        });
        
        // Validation des compétences
        const competencesValides = $('.journal-classe-checkbox-input:checked').length > 0;
        if (!competencesValides) {
            $('.journal-classe-competences').addClass('erreur');
            estValide = false;
        } else {
            $('.journal-classe-competences').removeClass('erreur');
        }

        return estValide;
    }

    /**
     * Soumission du formulaire
     */
    function soumettreFormulaire(e) {
        e.preventDefault();
        console.log('Tentative de soumission du formulaire');

        if (!validerFormulaire()) {
            afficherMessage('error', 'Veuillez remplir tous les champs requis.');
            return;
        }

        // Préparation des données
        const formData = new FormData(formulaire[0]);
        formData.append('action', 'sauvegarder_journal');
        formData.append('nonce', journalClasseConfig.nonce);

        // Debug des données
        for (let pair of formData.entries()) {
            console.log('Donnée envoyée:', pair[0], pair[1]);
        }

        // Désactivation du formulaire
        toggleChargement(true);

        // Envoi de la requête
        $.ajax({
            url: journalClasseConfig.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(reponse) {
                console.log('Réponse du serveur:', reponse);
                
                if (reponse.success) {
                    gererSucces(reponse);
                } else {
                    gererErreur(reponse);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX:', {
                    status: status,
                    error: error,
                    xhr: xhr
                });
                afficherMessage('error', 'Erreur de connexion au serveur. Veuillez réessayer.');
            },
            complete: function() {
                toggleChargement(false);
            }
        });
    }

    /**
     * Gestion du succès de l'envoi
     */
    function gererSucces(reponse) {
        afficherMessage('success', reponse.data.message);
        reinitialiserFormulaire();
    }

    /**
     * Gestion des erreurs
     */
    function gererErreur(reponse) {
        const messageErreur = reponse.data ? reponse.data.message : 'Une erreur est survenue';
        afficherMessage('error', messageErreur);
    }

    /**
     * Affichage des messages
     */
    function afficherMessage(type, contenu) {
        message
            .removeClass('success error')
            .addClass(type)
            .html(contenu)
            .fadeIn()
            .delay(5000)
            .fadeOut();
    }

    /**
     * Toggle l'état de chargement
     */
    function toggleChargement(actif) {
        submitButton.prop('disabled', actif);
        formulaire.toggleClass('journal-classe-loading', actif);
    }

    /**
     * Réinitialisation du formulaire
     */
    function reinitialiserFormulaire() {
        formulaire[0].reset();
        $('#cours').val('Français');
        $('#date').val(new Date().toISOString().split('T')[0]);
        $('.journal-classe-competences').removeClass('erreur');
        $('[required]').removeClass('erreur');
    }

    // Initialisation au chargement
    initialiserFormulaire();

    // Gestion du clic pour sélectionner/désélectionner toutes les cases
    $('#select-all').on('click', function() {
        $('.select-entry').prop('checked', $(this).prop('checked'));
    });

    // Gestion du clic sur le bouton "Supprimer les entrées sélectionnées"
    $('#supprimer-selection').on('click', function() {
        // Récupérer tous les IDs des entrées sélectionnées
        const selectedIds = $('.select-entry:checked').map(function() {
            return $(this).data('id');
        }).get();

        if (selectedIds.length === 0) {
            alert('Veuillez sélectionner au moins une entrée.');
            return;
        }

        if (confirm("Êtes-vous sûr de vouloir supprimer les entrées sélectionnées ?")) {
            $.ajax({
                url: journalClasseConfig.ajaxurl,
                type: 'POST',
                data: {
                    action: 'supprimer_journal_entrees_multiple',
                    ids: selectedIds,
                    nonce: journalClasseConfig.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload(); // Recharge la page pour mettre à jour la liste
                    } else {
                        alert(response.data.message || 'Erreur lors de la suppression.');
                    }
                },
                error: function() {
                    alert('Erreur de connexion au serveur.');
                }
            });
        }
    });

    // Fonction de filtrage pour le champ de recherche et la sélection de classe
    function filtrerEntrees() {
        var recherche = $('#journal-classe-recherche').val().toLowerCase();
        var classeSelectionnee = $('#journal-classe-filtre-classe').val();
        
        $('.journal-classe-ligne').each(function() {
            var ligne = $(this);
            var texte = ligne.text().toLowerCase();
            var classe = ligne.data('classe');
            
            // Vérifier si le texte et la classe correspondent aux filtres
            var afficher = texte.includes(recherche) && 
                           (!classeSelectionnee || classe === classeSelectionnee);
            ligne.toggle(afficher);
        });
    }

    // Appliquer le filtrage à chaque modification des filtres
    $('#journal-classe-recherche').on('keyup', filtrerEntrees);
    $('#journal-classe-filtre-classe').on('change', filtrerEntrees);
});