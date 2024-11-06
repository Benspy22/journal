<?php
/**
 * Template de l'historique du journal de classe
 * 
 * À placer dans : journal-classe/templates/historique.php
 */

// Protection contre l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Vérifier si nous avons des entrées
if (empty($entrees)) {
    echo '<div class="journal-classe-notice">';
    _e('Aucune entrée trouvée dans le journal de classe.', 'journal-classe');
    echo '</div>';
    return;
}
?>

<div class="journal-classe-historique">
    <h2 class="journal-classe-titre">
        <?php _e('Historique du Journal de Classe', 'journal-classe'); ?>
    </h2>

    <!-- Filtres de recherche -->
    <div class="journal-classe-filtres">
        <select id="journal-classe-filtre-classe" class="journal-classe-select">
            <option value=""><?php _e('Toutes les classes', 'journal-classe'); ?></option>
            <?php
            $classes = array_unique(array_column($entrees, 'classe'));
            sort($classes);
            foreach ($classes as $classe) {
                printf(
                    '<option value="%s">%s</option>',
                    esc_attr($classe),
                    esc_html($classe)
                );
            }
            ?>
        </select>
    </div>

    <!-- Bouton pour supprimer les entrées sélectionnées -->
    <button id="supprimer-selection" class="journal-classe-supprimer-selection">
        <?php _e('Supprimer les entrées sélectionnées', 'journal-classe'); ?>
    </button>

    <!-- Tableau des entrées -->
    <div class="journal-classe-tableau-conteneur">
        <table class="journal-classe-tableau">
            <thead>
                <tr>
                    <th><input type="checkbox" id="select-all"></th>
                    <th><?php _e('Date', 'journal-classe'); ?></th>
                    <th><?php _e('Heure', 'journal-classe'); ?></th>
                    <th><?php _e('Classe', 'journal-classe'); ?></th>
                    <th><?php _e('Étapes', 'journal-classe'); ?></th>
                    <th><?php _e('Compétences', 'journal-classe'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entrees as $entree): ?>
                    <tr class="journal-classe-ligne" data-id="<?php echo esc_attr($entree['id']); ?>" data-classe="<?php echo esc_attr($entree['classe']); ?>">
                        <td><input type="checkbox" class="select-entry" data-id="<?php echo esc_attr($entree['id']); ?>"></td>
                        <td class="journal-classe-date">
                            <?php echo esc_html(date_i18n('d/m/Y', strtotime($entree['date_cours']))); ?>
                        </td>
                        <td class="journal-classe-heure">
                            <?php echo esc_html(date_i18n('H:i', strtotime($entree['heure_cours']))); ?>
                        </td>
                        <td class="journal-classe-classe">
                            <?php echo esc_html($entree['classe']); ?>
                        </td>
                        <td class="journal-classe-etapes">
                            <?php echo nl2br(esc_html($entree['etapes'])); ?>
                        </td>
                        <td class="journal-classe-competences">
                            <?php
                            $competences = unserialize($entree['competences']);
                            if (is_array($competences)) {
                                echo esc_html(implode(', ', $competences));
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Script de filtrage et de suppression -->
    <script>
    jQuery(document).ready(function($) {
        // Fonction de filtrage
        function filtrerEntrees() {
            var classeSelectionnee = $('#journal-classe-filtre-classe').val();
            
            $('.journal-classe-ligne').each(function() {
                var ligne = $(this);
                var classe = ligne.data('classe');
                
                // Affiche toutes les lignes si aucune classe spécifique n'est sélectionnée (filtre par défaut)
                var afficher = !classeSelectionnee || classe === classeSelectionnee;
                
                // Affiche ou masque la ligne en fonction des critères
                ligne.toggle(afficher);
            });
        }

        // Écouteur d'événement pour le filtre de classe
        $('#journal-classe-filtre-classe').on('change', filtrerEntrees);

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
    });
    </script>
</div>