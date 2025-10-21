<?php
/**
 * Plugin Name: Small Victories Skillsbook
 * Plugin URI: https://small-victories.co
 * Description: Allow visitors to submit and manage their contact information with email confirmation
 * Version: 1.2.0
 * Author: Small Victories
 * License: GPL v2 or later
 * Text Domain: small-victories-skillsbook
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SKILLSBOOK_VERSION', '1.2.0');
define('SKILLSBOOK_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SKILLSBOOK_PLUGIN_URL', plugin_dir_url(__FILE__));

class SmallVictoriesSkillsbook {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('wp_ajax_skillsbook_submit', array($this, 'handle_submission'));
        add_action('wp_ajax_nopriv_skillsbook_submit', array($this, 'handle_submission'));
        add_action('wp_ajax_skillsbook_update', array($this, 'handle_update'));
        add_action('wp_ajax_nopriv_skillsbook_update', array($this, 'handle_update'));
        add_action('wp_ajax_skillsbook_send_renewal', array($this, 'handle_send_renewal_emails'));
        add_action('wp_ajax_skillsbook_add_label', array($this, 'ajax_add_label'));
        add_action('wp_ajax_skillsbook_update_label', array($this, 'ajax_update_label'));
        add_action('wp_ajax_skillsbook_delete_label', array($this, 'ajax_delete_label'));
        add_action('wp_ajax_skillsbook_update_entry', array($this, 'ajax_update_entry'));
        add_action('wp_ajax_skillsbook_update_entry_labels', array($this, 'ajax_update_entry_labels'));
        add_action('wp_ajax_skillsbook_get_labels', array($this, 'ajax_get_labels'));
        add_action('admin_init', array($this, 'handle_send_renewal_button'));
        add_action('template_redirect', array($this, 'handle_confirm_email'));
        add_action('template_redirect', array($this, 'handle_edit_page'));
        add_shortcode('skillsbook_form', array($this, 'render_shortcode'));
    }

    public function load_textdomain() {
        load_plugin_textdomain('small-victories-skillsbook', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        $this->create_tables();
    }

    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = array();

        // Entries table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}skillsbook_entries (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            uuid varchar(36) NOT NULL,
            name varchar(255) NOT NULL,
            children text NOT NULL,
            email varchar(255) NOT NULL,
            phone varchar(50) DEFAULT NULL,
            message text NOT NULL,
            valid_until date NOT NULL,
            confirmed tinyint(1) DEFAULT 0,
            date_added datetime DEFAULT CURRENT_TIMESTAMP,
            date_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uuid (uuid),
            KEY email (email),
            KEY confirmed (confirmed)
        ) $charset_collate;";

        // Labels table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}skillsbook_labels (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY name (name)
        ) $charset_collate;";

        // Submission labels junction table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}skillsbook_submission_labels (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            submission_id bigint(20) NOT NULL,
            label_id bigint(20) NOT NULL,
            PRIMARY KEY (id),
            KEY submission_id (submission_id),
            KEY label_id (label_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        foreach ($sql as $query) {
            dbDelta($query);
        }
    }

    public function add_admin_menu() {
        add_menu_page(
            'Small Victories Skillsbook',
            'SV Skillsbook',
            'manage_options',
            'sv-skillsbook',
            array($this, 'render_entries_list'),
            'dashicons-groups',
            30
        );

        add_submenu_page(
            'sv-skillsbook',
            'Labels',
            'Labels',
            'manage_options',
            'sv-skillsbook-labels',
            array($this, 'render_labels_list')
        );

        add_submenu_page(
            'sv-skillsbook',
            'Instellingen',
            'Instellingen',
            'manage_options',
            'sv-skillsbook-settings',
            array($this, 'render_settings_page')
        );
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'sv-skillsbook') === false) {
            return;
        }
        wp_enqueue_style('skillsbook-admin', SKILLSBOOK_PLUGIN_URL . 'assets/admin.css', array(), SKILLSBOOK_VERSION);
        wp_enqueue_script('jquery-ui-autocomplete');
        wp_enqueue_style('wp-jquery-ui-dialog');
        wp_enqueue_script('skillsbook-admin', SKILLSBOOK_PLUGIN_URL . 'assets/admin.js', array('jquery', 'jquery-ui-autocomplete'), SKILLSBOOK_VERSION, true);
        wp_localize_script('skillsbook-admin', 'skillsbookAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('skillsbook_admin')
        ));
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_style('skillsbook-frontend', SKILLSBOOK_PLUGIN_URL . 'assets/frontend.css', array(), SKILLSBOOK_VERSION);
        wp_enqueue_script('skillsbook-frontend', SKILLSBOOK_PLUGIN_URL . 'assets/frontend.js', array('jquery'), SKILLSBOOK_VERSION, true);
        wp_localize_script('skillsbook-frontend', 'skillsbookAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('skillsbook_nonce')
        ));
    }

    public function render_entries_list() {
        require_once SKILLSBOOK_PLUGIN_DIR . 'includes/admin-entries-list.php';
    }

    public function render_labels_list() {
        require_once SKILLSBOOK_PLUGIN_DIR . 'includes/admin-labels-list.php';
    }

    public function render_settings_page() {
        require_once SKILLSBOOK_PLUGIN_DIR . 'includes/admin-settings.php';
    }

    public function render_shortcode($atts) {
        ob_start();
        require SKILLSBOOK_PLUGIN_DIR . 'includes/frontend-form.php';
        return ob_get_clean();
    }

    private function calculate_valid_until() {
        $current_date = new DateTime();
        $current_year = (int) $current_date->format('Y');
        $july_15_this_year = new DateTime($current_year . '-07-15');

        if ($current_date > $july_15_this_year) {
            // If we're past July 15 this year, use next year
            return ($current_year + 1) . '-07-15';
        } else {
            // If we haven't reached July 15 this year, use this year
            return $current_year . '-07-15';
        }
    }

    public function handle_submission() {
        check_ajax_referer('skillsbook_nonce', 'nonce');

        global $wpdb;

        $name = sanitize_text_field($_POST['name']);
        $children = sanitize_textarea_field($_POST['children']);
        $email = sanitize_email($_POST['email']);
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $message = sanitize_textarea_field($_POST['message']);

        if (empty($name) || empty($children) || empty($email) || empty($message)) {
            wp_send_json_error(array('message' => 'Vul alle verplichte velden in.'));
            return;
        }

        // Check if email already exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT uuid FROM {$wpdb->prefix}skillsbook_entries WHERE email = %s",
            $email
        ));

        if ($existing) {
            // Send edit link to existing user
            $this->send_edit_email($existing->uuid, $email);
            $msg = get_option('skillsbook_msg_existing_user',
                'Je hebt je info al eerder doorgegeven. We hebben je een email gestuurd om deze te bewerken. Geen zorgen, alle info blijft behouden.'
            );
            wp_send_json_success(array(
                'message' => $msg,
                'existing' => true
            ));
            return;
        }

        // Create new entry
        $uuid = wp_generate_uuid4();
        $valid_until = $this->calculate_valid_until();

        $result = $wpdb->insert(
            $wpdb->prefix . 'skillsbook_entries',
            array(
                'uuid' => $uuid,
                'name' => $name,
                'children' => $children,
                'email' => $email,
                'phone' => $phone,
                'message' => $message,
                'valid_until' => $valid_until,
                'confirmed' => 0,
                'date_added' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
        );

        if ($result === false) {
            wp_send_json_error(array('message' => 'Er ging iets mis. Probeer het later opnieuw.'));
            return;
        }

        // Send confirmation email
        $this->send_confirmation_email($uuid, $email, $name);

        $msg = get_option('skillsbook_msg_success_submit',
            'Bedankt dat je de handen uit de mouwen wil steken. Je krijgt een email met een link om je info te bevestigen.'
        );
        wp_send_json_success(array(
            'message' => $msg
        ));
    }

    public function handle_update() {
        check_ajax_referer('skillsbook_nonce', 'nonce');

        global $wpdb;

        $uuid = sanitize_text_field($_POST['uuid']);
        $name = sanitize_text_field($_POST['name']);
        $children = sanitize_textarea_field($_POST['children']);
        $email = sanitize_email($_POST['email']);
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $message = sanitize_textarea_field($_POST['message']);

        if (empty($uuid) || empty($name) || empty($children) || empty($email) || empty($message)) {
            wp_send_json_error(array('message' => 'Vul alle verplichte velden in.'));
            return;
        }

        // Verify entry exists and is confirmed
        $entry = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}skillsbook_entries WHERE uuid = %s AND confirmed = 1",
            $uuid
        ));

        if (!$entry) {
            wp_send_json_error(array('message' => 'Ongeldige link.'));
            return;
        }

        // Recalculate valid_until date (in case it's expired)
        $valid_until = $this->calculate_valid_until();

        // Update entry
        $result = $wpdb->update(
            $wpdb->prefix . 'skillsbook_entries',
            array(
                'name' => $name,
                'children' => $children,
                'email' => $email,
                'phone' => $phone,
                'message' => $message,
                'valid_until' => $valid_until,
                'date_updated' => current_time('mysql')
            ),
            array('uuid' => $uuid),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s'),
            array('%s')
        );

        if ($result === false) {
            wp_send_json_error(array('message' => 'Er ging iets mis. Probeer het later opnieuw.'));
            return;
        }

        $msg = get_option('skillsbook_msg_success_update',
            'Je informatie is bijgewerkt.'
        );
        wp_send_json_success(array(
            'message' => $msg
        ));
    }

    public function handle_confirm_email() {
        if (!isset($_GET['skillsbook_confirm']) || !isset($_GET['uuid'])) {
            return;
        }

        global $wpdb;
        $uuid = sanitize_text_field($_GET['uuid']);

        // Check if already confirmed
        $entry = $wpdb->get_row($wpdb->prepare(
            "SELECT id, email, name, confirmed FROM {$wpdb->prefix}skillsbook_entries WHERE uuid = %s",
            $uuid
        ));

        if (!$entry) {
            wp_die('Ongeldige bevestigingslink.');
        }

        // Recalculate valid_until date (in case it's expired)
        $valid_until = $this->calculate_valid_until();

        // Only confirm if not already confirmed (prevents re-sending email on refresh)
        if (!$entry->confirmed) {
            // Confirm the entry and update valid_until
            $wpdb->update(
                $wpdb->prefix . 'skillsbook_entries',
                array(
                    'confirmed' => 1,
                    'valid_until' => $valid_until
                ),
                array('uuid' => $uuid),
                array('%d', '%s'),
                array('%s')
            );

            // Send edit link email
            $this->send_edit_email($uuid, $entry->email);
        } else {
            // Already confirmed, just update valid_until if needed
            $wpdb->update(
                $wpdb->prefix . 'skillsbook_entries',
                array('valid_until' => $valid_until),
                array('uuid' => $uuid),
                array('%s'),
                array('%s')
            );
        }

        // Redirect to form page with confirmation message (remove confirm params)
        $form_page_id = get_option('skillsbook_form_page_id', 0);
        if ($form_page_id) {
            $page_url = get_permalink($form_page_id);
            if (!$page_url) {
                wp_die('Kan de pagina niet vinden. Pagina ID: ' . $form_page_id . '. Controleer de instellingen.');
            }
            // Redirect without the skillsbook_confirm parameter to avoid loops
            $redirect_url = add_query_arg('skillsbook_confirmed', '1', $page_url);
            wp_redirect($redirect_url);
            exit;
        } else {
            wp_die('Formulier pagina is niet geconfigureerd in de instellingen.');
        }
    }

    public function handle_edit_page() {
        // Do nothing - the form will handle edit mode directly via GET parameters
        return;
    }

    private function send_confirmation_email($uuid, $email, $name) {
        $form_page_id = get_option('skillsbook_form_page_id', 0);
        $base_url = $form_page_id ? get_permalink($form_page_id) : home_url();

        $confirm_url = add_query_arg(
            array(
                'skillsbook_confirm' => '1',
                'uuid' => $uuid
            ),
            $base_url
        );

        // Get email template from options
        $subject = get_option('skillsbook_confirmation_email_subject', 'Bevestig je inschrijving');
        $body = get_option('skillsbook_confirmation_email_body',
            "Hallo [name],\n\n" .
            "Bedankt voor je inschrijving. Klik op de onderstaande link om je email adres te bevestigen:\n\n" .
            "[link]\n\n" .
            "Groetjes,\nSmall Victories"
        );

        // Replace placeholders
        $message = str_replace('[name]', $name, $body);
        $message = str_replace('[link]', $confirm_url, $message);

        wp_mail($email, $subject, $message);
    }

    private function send_edit_email($uuid, $email) {
        $form_page_id = get_option('skillsbook_form_page_id', 0);
        $base_url = $form_page_id ? get_permalink($form_page_id) : home_url();

        $edit_url = add_query_arg(
            array(
                'skillsbook_edit' => '1',
                'uuid' => $uuid
            ),
            $base_url
        );

        // Get email template from options
        $subject = get_option('skillsbook_edit_email_subject', 'Bewerk je informatie');
        $body = get_option('skillsbook_edit_email_body',
            "Hallo,\n\n" .
            "Gebruik de onderstaande link om je informatie te bewerken:\n\n" .
            "[link]\n\n" .
            "Groetjes,\nSmall Victories"
        );

        // Replace placeholders
        $message = str_replace('[link]', $edit_url, $body);

        wp_mail($email, $subject, $message);
    }

    public function handle_send_renewal_button() {
        if (!isset($_GET['skillsbook_send_renewal'])) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('skillsbook_send_renewal');

        $result = $this->send_renewal_emails();

        wp_redirect(add_query_arg(
            array(
                'page' => 'sv-skillsbook',
                'renewal_sent' => $result['sent'],
                'renewal_total' => $result['total']
            ),
            admin_url('admin.php')
        ));
        exit;
    }

    public function handle_send_renewal_emails() {
        check_ajax_referer('skillsbook_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }

        $result = $this->send_renewal_emails();

        wp_send_json_success(array(
            'message' => sprintf('Vernieuwingsemails verzonden naar %d van %d verlopen inzendingen.', $result['sent'], $result['total'])
        ));
    }

    public function send_renewal_emails() {
        global $wpdb;

        // Get email content from options
        $subject = get_option('skillsbook_renewal_email_subject', 'Kun je volgend jaar terug helpen?');
        $body = get_option('skillsbook_renewal_email_body',
            "Beste [name],\n\n" .
            "Een tijd terug gaf je je gegevens door via de website van de Ouderraad van GO! Flora. Het schooljaar is alweer ten einde, dus we willen even checken of we volgend jaar terug op je kunnen rekenen. Zoja, klik dan even op de onderstaande link, controleer je gegevens, en druk op de knop! Zoneen, dan hoef je niks te doen. We verwijderen je bij aanvang van het nieuwe jaar uit ons adresboek.\n\n" .
            "[link]\n\n" .
            "Groetjes,\nDe Ouderraad"
        );

        // Get all entries where valid_until is in the past and confirmed
        $entries = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}skillsbook_entries
             WHERE valid_until < CURDATE()
             AND confirmed = 1"
        );

        $total = count($entries);
        $sent = 0;

        $form_page_id = get_option('skillsbook_form_page_id', 0);
        $base_url = $form_page_id ? get_permalink($form_page_id) : home_url();

        foreach ($entries as $entry) {
            $edit_url = add_query_arg(
                array(
                    'skillsbook_edit' => '1',
                    'uuid' => $entry->uuid
                ),
                $base_url
            );

            // Replace placeholders
            $personalized_subject = str_replace('[name]', $entry->name, $subject);
            $personalized_body = str_replace('[name]', $entry->name, $body);
            $personalized_body = str_replace('[link]', $edit_url, $personalized_body);

            if (wp_mail($entry->email, $personalized_subject, $personalized_body)) {
                $sent++;
            }
        }

        return array(
            'total' => $total,
            'sent' => $sent
        );
    }

    // Label AJAX handlers
    public function ajax_add_label() {
        check_ajax_referer('skillsbook_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }

        global $wpdb;
        $name = sanitize_text_field($_POST['name']);

        if (empty($name)) {
            wp_send_json_error(array('message' => 'Label naam is verplicht.'));
            return;
        }

        $result = $wpdb->insert(
            $wpdb->prefix . 'skillsbook_labels',
            array('name' => $name),
            array('%s')
        );

        if ($result === false) {
            wp_send_json_error(array('message' => 'Er ging iets mis.'));
            return;
        }

        wp_send_json_success();
    }

    public function ajax_update_label() {
        check_ajax_referer('skillsbook_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }

        global $wpdb;
        $label_id = intval($_POST['label_id']);
        $name = sanitize_text_field($_POST['name']);

        if (empty($name)) {
            wp_send_json_error(array('message' => 'Label naam is verplicht.'));
            return;
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'skillsbook_labels',
            array('name' => $name),
            array('id' => $label_id),
            array('%s'),
            array('%d')
        );

        if ($result === false) {
            wp_send_json_error(array('message' => 'Er ging iets mis.'));
            return;
        }

        wp_send_json_success();
    }

    public function ajax_delete_label() {
        check_ajax_referer('skillsbook_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }

        global $wpdb;
        $label_id = intval($_POST['label_id']);

        // Delete from junction table first
        $wpdb->delete(
            $wpdb->prefix . 'skillsbook_submission_labels',
            array('label_id' => $label_id),
            array('%d')
        );

        // Delete the label
        $result = $wpdb->delete(
            $wpdb->prefix . 'skillsbook_labels',
            array('id' => $label_id),
            array('%d')
        );

        if ($result === false) {
            wp_send_json_error(array('message' => 'Er ging iets mis.'));
            return;
        }

        wp_send_json_success();
    }

    public function ajax_get_labels() {
        check_ajax_referer('skillsbook_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }

        global $wpdb;
        $labels = $wpdb->get_results(
            "SELECT id, name FROM {$wpdb->prefix}skillsbook_labels ORDER BY name ASC"
        );

        wp_send_json_success($labels);
    }

    public function ajax_update_entry() {
        check_ajax_referer('skillsbook_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }

        global $wpdb;
        $entry_id = intval($_POST['entry_id']);
        $field = sanitize_text_field($_POST['field']);
        $value = $_POST['value'];

        // Sanitize based on field type
        if ($field === 'children' || $field === 'message') {
            $value = sanitize_textarea_field($value);
        } elseif ($field === 'email') {
            $value = sanitize_email($value);
        } else {
            $value = sanitize_text_field($value);
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'skillsbook_entries',
            array($field => $value, 'date_updated' => current_time('mysql')),
            array('id' => $entry_id),
            array('%s', '%s'),
            array('%d')
        );

        if ($result === false) {
            wp_send_json_error(array('message' => 'Er ging iets mis.'));
            return;
        }

        wp_send_json_success();
    }

    public function ajax_update_entry_labels() {
        check_ajax_referer('skillsbook_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }

        global $wpdb;
        $entry_id = intval($_POST['entry_id']);
        $label_names = isset($_POST['labels']) ? array_map('sanitize_text_field', $_POST['labels']) : array();

        // Delete existing labels for this entry
        $wpdb->delete(
            $wpdb->prefix . 'skillsbook_submission_labels',
            array('submission_id' => $entry_id),
            array('%d')
        );

        // Add new labels
        foreach ($label_names as $label_name) {
            if (empty($label_name)) continue;

            // Get or create label
            $label = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}skillsbook_labels WHERE name = %s",
                $label_name
            ));

            if (!$label) {
                // Create new label
                $wpdb->insert(
                    $wpdb->prefix . 'skillsbook_labels',
                    array('name' => $label_name),
                    array('%s')
                );
                $label_id = $wpdb->insert_id;
            } else {
                $label_id = $label->id;
            }

            // Link label to submission
            $wpdb->insert(
                $wpdb->prefix . 'skillsbook_submission_labels',
                array(
                    'submission_id' => $entry_id,
                    'label_id' => $label_id
                ),
                array('%d', '%d')
            );
        }

        wp_send_json_success();
    }

    public function get_entry_by_uuid($uuid) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}skillsbook_entries WHERE uuid = %s",
            $uuid
        ));
    }
}

// Initialize plugin
SmallVictoriesSkillsbook::get_instance();
