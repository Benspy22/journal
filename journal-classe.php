<?php
/**
 * Plugin Name: Journal de Classe Intelligent
 * Description: Plugin de gestion du journal de classe pour les enseignants de français
 * Version: 2.0
 * Author: Votre Nom
 * Text Domain: journal-classe
 * Domain Path: /languages
 */

// Protection contre l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class JournalClasse {
    private static $instance = null;
    private $table_name;
    private $version = '2.0';

    /**
     * Constructeur privé (pattern Singleton)
     */
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'journal_classe';
        $this->initialiser_hooks();
    }

    /**
     * Obtenir l'instance unique
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }


public function supprimer_journal_entree() {
    // Vérifier le nonce pour la sécurité
    if (!check_ajax_referer('journal_classe_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => __('Nonce invalide.', 'journal-classe')]);
        return;
    }

    // Vérifier que l'ID de l'entrée est bien passé
    if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
        wp_send_json_error(['message' => __('ID d\'entrée invalide.', 'journal-classe')]);
        return;
    }

    global $wpdb;
    $id = intval($_POST['id']);

    // Supprimer l'entrée de la base de données
    $result = $wpdb->delete($this->table_name, ['id' => $id]);

    if ($result !== false) {
        wp_send_json_success(['message' => __('Entrée supprimée avec succès.', 'journal-classe')]);
    } else {
        wp_send_json_error(['message' => __('Erreur lors de la suppression de l\'entrée.', 'journal-classe')]);
    }
}

public function supprimer_journal_entrees_multiple() {
    // Vérification du nonce pour la sécurité
    if (!check_ajax_referer('journal_classe_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => __('Nonce invalide.', 'journal-classe')]);
        return;
    }

    // Vérification que les IDs sont bien passés et qu'il s'agit d'un tableau
    if (empty($_POST['ids']) || !is_array($_POST['ids'])) {
        wp_send_json_error(['message' => __('IDs d\'entrées invalides.', 'journal-classe')]);
        return;
    }

    global $wpdb;
    $ids = array_map('intval', $_POST['ids']);
    $placeholders = implode(',', array_fill(0, count($ids), '%d'));
    $query = "DELETE FROM {$this->table_name} WHERE id IN ($placeholders)";
    
    $result = $wpdb->query($wpdb->prepare($query, $ids));

    if ($result !== false) {
        wp_send_json_success(['message' => __('Les entrées sélectionnées ont été supprimées avec succès.', 'journal-classe')]);
    } else {
        wp_send_json_error(['message' => __('Erreur lors de la suppression des entrées.', 'journal-classe')]);
    }
}

  /**
 * Initialisation des hooks WordPress
 */
private function initialiser_hooks(): void {
    // Hooks d'activation et désactivation
    register_activation_hook(__FILE__, [$this, 'activer']);
    register_deactivation_hook(__FILE__, [$this, 'desactiver']);
    
    // Hooks d'initialisation
    add_action('init', [$this, 'charger_traductions']);
    add_action('wp_enqueue_scripts', [$this, 'charger_assets']);
    
    // Shortcodes
    add_shortcode('journal_classe', [$this, 'afficher_formulaire']);
    add_shortcode('journal_historique', [$this, 'afficher_historique']);
    
    // Actions AJAX
    add_action('wp_ajax_sauvegarder_journal', [$this, 'traiter_ajax_sauvegarde']);
    add_action('wp_ajax_nopriv_sauvegarder_journal', [$this, 'traiter_ajax_sauvegarde']);
    add_action('wp_ajax_supprimer_journal_entree', [$this, 'supprimer_journal_entree']); // AJOUT POUR LA SUPPRESSION
    add_action('wp_ajax_supprimer_journal_entrees_multiple', [$this, 'supprimer_journal_entrees_multiple']);
}

    /**
     * Activation du plugin
     */
    public function activer(): void {
        error_log('Activation du plugin Journal de Classe');
        $this->creer_table();
        
        // Vérification post-création
        global $wpdb;
        $structure = $wpdb->get_results("DESCRIBE {$this->table_name}");
        error_log('Structure de la table après activation : ' . print_r($structure, true));
        
        flush_rewrite_rules();
    }

    /**
     * Désactivation du plugin
     */
    public function desactiver(): void {
        error_log('Désactivation du plugin Journal de Classe');
        flush_rewrite_rules();
    }

    /**
     * Création de la table en base de données
     */
    private function creer_table(): void {
        global $wpdb;
        error_log('Début de la création de la table');
        
        // Supprimons la table existante pour être sûr
        $wpdb->query("DROP TABLE IF EXISTS {$this->table_name}");
        
        // Création de la table avec une requête SQL directe
        $sql = "CREATE TABLE {$this->table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            date_cours DATE NOT NULL,
            heure_cours TIME NOT NULL,
            classe VARCHAR(10) NOT NULL,
            cours VARCHAR(100) NOT NULL,
            etapes TEXT NOT NULL,
            competences TEXT NOT NULL,
            utilisateur_id BIGINT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) " . $wpdb->get_charset_collate() . ";";

        error_log('SQL de création : ' . $sql);

        // Exécution directe de la requête
        $resultat = $wpdb->query($sql);
        error_log('Résultat de la création : ' . ($resultat !== false ? 'succès' : 'échec'));

        if ($resultat !== false) {
            // Ajout des index séparément
            $wpdb->query("ALTER TABLE {$this->table_name} ADD INDEX idx_date (date_cours)");
            $wpdb->query("ALTER TABLE {$this->table_name} ADD INDEX idx_classe (classe)");
            $wpdb->query("ALTER TABLE {$this->table_name} ADD INDEX idx_utilisateur (utilisateur_id)");
        }

        // Vérification de la structure
        if ($wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'")) {
            $structure = $wpdb->get_results("SHOW COLUMNS FROM {$this->table_name}");
            error_log('Structure finale de la table : ' . print_r($structure, true));
        } else {
            error_log('ERREUR : La table n\'a pas été créée');
        }
    }
    
    /**
     * Vérification de l'existence de la table
     */
    private function verifier_table(): bool {
        global $wpdb;
        
        $table_existe = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'");
        
        if (!$table_existe) {
            error_log('Table manquante, tentative de création');
            $this->creer_table();
            
            // Vérifions à nouveau
            $table_existe = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'");
            if (!$table_existe) {
                error_log('Échec de la création de la table');
                return false;
            }
        }
        
        return true;
    }

    /**
     * Chargement des traductions
     */
    public function charger_traductions(): void {
        load_plugin_textdomain(
            'journal-classe',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

   /**
 * Chargement des assets (CSS/JS)
 */
public function charger_assets(): void {
    // CSS
    wp_enqueue_style(
        'journal-classe-style',
        plugins_url('assets/css/style.css', __FILE__),
        [],
        $this->version
    );

    // JavaScript
    wp_enqueue_script(
        'journal-classe-script',
        plugins_url('assets/js/script.js', __FILE__),
        ['jquery'],
        $this->version,
        true
    );

    // Transmettre les données AJAX et les messages au script JS
    wp_localize_script('journal-classe-script', 'journalClasseConfig', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('journal_classe_nonce'),
        'messages' => [
            'succes_sauvegarde' => __('Entrée sauvegardée avec succès!', 'journal-classe'),
            'erreur_sauvegarde' => __('Erreur lors de la sauvegarde.', 'journal-classe'),
            'succes_suppression' => __('Entrée supprimée avec succès.', 'journal-classe'),
            'erreur_suppression' => __('Erreur lors de la suppression de l\'entrée.', 'journal-classe')
        ]
    ]);
}

    /**
     * Affichage du formulaire
     */
    public function afficher_formulaire(): string {
        if (!is_user_logged_in()) {
            return sprintf(
                '<div class="journal-classe-erreur">%s</div>',
                __('Vous devez être connecté pour accéder au journal de classe.', 'journal-classe')
            );
        }

        error_log('Affichage du formulaire');
        ob_start();
        require plugin_dir_path(__FILE__) . 'templates/formulaire.php';
        return ob_get_clean();
    }

    /**
     * Affichage de l'historique
     */
    public function afficher_historique(): string {
        if (!is_user_logged_in()) {
            return sprintf(
                '<div class="journal-classe-erreur">%s</div>',
                __('Vous devez être connecté pour accéder à l\'historique.', 'journal-classe')
            );
        }

        ob_start();
        $entrees = $this->obtenir_historique();
        require plugin_dir_path(__FILE__) . 'templates/historique.php';
        return ob_get_clean();
    }

    /**
     * Obtenir les créneaux horaires
     */
    private function obtenir_creneaux(): array {
        return [
            '08:10' => '08h10 - 09h00',
            '09:00' => '09h00 - 09h50',
            '09:50' => '09h50 - 10h40',
            '10:55' => '10h55 - 11h45',
            '11:45' => '11h45 - 12h35',
            '13:25' => '13h25 - 14h15',
            '14:15' => '14h15 - 15h05',
            '15:05' => '15h05 - 15h55'
        ];
    }

    /**
     * Traitement de la requête AJAX
     */
    public function traiter_ajax_sauvegarde(): void {
        try {
            error_log('Début du traitement AJAX');
            
            // Vérifions la table avant tout
            if (!$this->verifier_table()) {
                wp_send_json_error([
                    'message' => __('Erreur de configuration de la base de données', 'journal-classe')
                ]);
                return;
            }

            // Vérification du nonce
            check_ajax_referer('journal_classe_nonce', 'nonce');

            // Log des données reçues
            error_log('Données POST reçues : ' . print_r($_POST, true));

            $donnees = $this->valider_donnees($_POST);
            if (is_wp_error($donnees)) {
                error_log('Erreur de validation : ' . $donnees->get_error_message());
                wp_send_json_error([
                    'message' => $donnees->get_error_message()
                ]);
                return;
            }

            $resultat = $this->sauvegarder_entree($donnees);
            if (is_wp_error($resultat)) {
                error_log('Erreur de sauvegarde : ' . $resultat->get_error_message());
                wp_send_json_error([
                    'message' => $resultat->get_error_message()
                ]);
                return;
            }

            wp_send_json_success([
                'message' => __('Entrée sauvegardée avec succès!', 'journal-classe')
            ]);

        } catch (\Exception $e) {
            error_log('Exception dans traiter_ajax_sauvegarde: ' . $e->getMessage());
            wp_send_json_error([
                'message' => __('Une erreur est survenue', 'journal-classe')
            ]);
        }
    }
   /**
     * Validation des données
     */
    private function valider_donnees(array $donnees): array|\WP_Error {
        $validees = [];
        
        // Validation de la date
        $date = sanitize_text_field($donnees['date'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return new \WP_Error('date_invalide', __('Format de date invalide', 'journal-classe'));
        }
        $validees['date_cours'] = $date;  // Changé de 'date' à 'date_cours'

        // Validation de l'heure
        $heure = sanitize_text_field($donnees['heure_cours'] ?? '');
        $creneaux = $this->obtenir_creneaux();
        if (!array_key_exists($heure, $creneaux)) {
            return new \WP_Error('heure_invalide', __('Créneau horaire invalide', 'journal-classe'));
        }
        $validees['heure_cours'] = $heure;

        // Validation de la classe
        $classe = sanitize_text_field($donnees['classe'] ?? '');
        if (!preg_match('/^[1-7]e[A-H]$/', $classe)) {
            return new \WP_Error('classe_invalide', __('Format de classe invalide', 'journal-classe'));
        }
        $validees['classe'] = $classe;

        // Validation des étapes
        $etapes = sanitize_textarea_field($donnees['etapes'] ?? '');
        if (empty($etapes)) {
            return new \WP_Error('etapes_vides', __('Les étapes sont requises', 'journal-classe'));
        }
        $validees['etapes'] = $etapes;

        // Validation des compétences
        $competences = isset($donnees['competences']) ? array_map('sanitize_text_field', $donnees['competences']) : [];
        if (empty($competences)) {
            return new \WP_Error('competences_vides', __('Au moins une compétence est requise', 'journal-classe'));
        }
        $validees['competences'] = serialize($competences);

        // Ajout de l'utilisateur et du cours
        $validees['utilisateur_id'] = get_current_user_id();
        $validees['cours'] = 'Français';

        return $validees;
    }

    /**
     * Sauvegarde d'une entrée
     */
    private function sauvegarder_entree(array $donnees): bool|\WP_Error {
        global $wpdb;
        
        error_log('Tentative de sauvegarde avec les données : ' . print_r($donnees, true));

        try {
            // Préparation de la requête SQL manuellement
            $query = $wpdb->prepare(
                "INSERT INTO {$this->table_name} 
                (date_cours, heure_cours, classe, cours, etapes, competences, utilisateur_id) 
                VALUES (%s, %s, %s, %s, %s, %s, %d)",
                $donnees['date_cours'],
                $donnees['heure_cours'],
                $donnees['classe'],
                $donnees['cours'],
                $donnees['etapes'],
                $donnees['competences'],
                $donnees['utilisateur_id']
            );

            error_log('Requête préparée : ' . $query);
            $resultat = $wpdb->query($query);

            if ($resultat === false) {
                error_log('Erreur MySQL : ' . $wpdb->last_error . ' - Dernière requête : ' . $wpdb->last_query);
                return new \WP_Error(
                    'erreur_db',
                    __('Erreur lors de l\'enregistrement : ', 'journal-classe') . $wpdb->last_error
                );
            }

            error_log('Sauvegarde réussie avec ID : ' . $wpdb->insert_id);
            error_log('Requête SQL exécutée : ' . $wpdb->last_query);
            return true;

        } catch (\Exception $e) {
            error_log('Exception lors de la sauvegarde : ' . $e->getMessage());
            return new \WP_Error('exception', $e->getMessage());
        }
    }

    /**
     * Récupération de l'historique
     */
    private function obtenir_historique(): array {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name}
                WHERE utilisateur_id = %d
                ORDER BY date_cours DESC, heure_cours DESC
                LIMIT 100",
                get_current_user_id()
            ),
            ARRAY_A
        );
    }
}

// Initialisation du plugin
function initialiser_journal_classe() {
    return JournalClasse::get_instance();
}

add_action('plugins_loaded', 'initialiser_journal_classe');