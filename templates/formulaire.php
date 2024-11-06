<?php
if (!defined('ABSPATH')) {
    exit;
}

// Récupération des créneaux horaires
$creneaux = $this->obtenir_creneaux();
?>

<div class="journal-classe-conteneur">
    <form id="formulaire-journal" class="journal-classe-form" method="post">
        <?php wp_nonce_field('journal_classe_nonce', 'journal_nonce'); ?>
        
<!-- Date du cours avec un placeholder et type dynamique -->
<div class="journal-classe-groupe">
    <label for="date"><?php _e('Date du cours :', 'journal-classe'); ?></label>
    <input type="text" 
           id="date" 
           name="date" 
           placeholder="Choisissez la date" 
           required 
           class="journal-classe-input" 
           onfocus="(this.type='date')">
</div>

        <!-- Heure du cours -->
        <div class="journal-classe-groupe">
            <label for="heure_cours"><?php _e('Heure de cours :', 'journal-classe'); ?></label>
            <select id="heure_cours" name="heure_cours" required class="journal-classe-select">
                <option value=""><?php _e('Sélectionnez une heure', 'journal-classe'); ?></option>
                <?php foreach ($creneaux as $value => $label): ?>
                    <option value="<?php echo esc_attr($value); ?>">
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Classe -->
        <div class="journal-classe-groupe">
            <label for="niveau"><?php _e('Classe :', 'journal-classe'); ?></label>
            <div class="journal-classe-classe-conteneur">
                <select id="niveau" name="niveau" required class="journal-classe-select">
                    <option value=""><?php _e('Niveau', 'journal-classe'); ?></option>
                    <?php for ($i = 1; $i <= 7; $i++): ?>
                        <option value="<?php echo esc_attr($i . 'e'); ?>">
                            <?php echo esc_html($i . 'e'); ?>
                        </option>
                    <?php endfor; ?>
                </select>
                
                <select id="lettre" name="lettre" required class="journal-classe-select">
                    <option value=""><?php _e('Lettre', 'journal-classe'); ?></option>
                    <?php foreach (range('A', 'H') as $lettre): ?>
                        <option value="<?php echo esc_attr($lettre); ?>">
                            <?php echo esc_html($lettre); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" id="classe" name="classe">
            </div>
        </div>

        <!-- Cours -->
        <div class="journal-classe-groupe">
            <label for="cours"><?php _e('Cours :', 'journal-classe'); ?></label>
            <input type="text" 
                   id="cours" 
                   name="cours" 
                   value="Français" 
                   readonly 
                   class="journal-classe-input-readonly">
        </div>

        <!-- Étapes -->
        <div class="journal-classe-groupe">
            <label for="etapes"><?php _e('Étapes de la séquence :', 'journal-classe'); ?></label>
            <textarea id="etapes" 
                      name="etapes" 
                      required 
                      rows="5" 
                      class="journal-classe-textarea"
                      placeholder="<?php _e('Décrivez les étapes de votre séquence...', 'journal-classe'); ?>"></textarea>
        </div>

        <!-- Compétences -->
        <div class="journal-classe-groupe">
            <label><?php _e('Compétences travaillées :', 'journal-classe'); ?></label>
            <div class="journal-classe-competences">
                <?php
                $competences = [
                    'lire' => __('Lire', 'journal-classe'),
                    'ecrire' => __('Écrire', 'journal-classe'),
                    'ecouter' => __('Écouter', 'journal-classe'),
                    'parler' => __('Parler', 'journal-classe')
                ];

                foreach ($competences as $value => $label):
                ?>
                    <div class="journal-classe-checkbox">
                        <input type="checkbox" 
                               id="comp_<?php echo esc_attr($value); ?>" 
                               name="competences[]" 
                               value="<?php echo esc_attr($label); ?>"
                               class="journal-classe-checkbox-input">
                        <label for="comp_<?php echo esc_attr($value); ?>" 
                               class="journal-classe-checkbox-label">
                            <?php echo esc_html($label); ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Bouton de soumission -->
        <div class="journal-classe-groupe">
            <button type="submit" class="journal-classe-bouton">
                <?php _e('Enregistrer', 'journal-classe'); ?>
            </button>
        </div>
    </form>

    <!-- Messages de feedback -->
    <div id="journal-classe-message" class="journal-classe-message" style="display: none;"></div>
</div>