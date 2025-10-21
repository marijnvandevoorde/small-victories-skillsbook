<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Handle settings update
if (isset($_POST['skillsbook_update_settings'])) {
    check_admin_referer('skillsbook_settings');
    update_option('skillsbook_renewal_subject', sanitize_text_field($_POST['renewal_subject']));
    update_option('skillsbook_renewal_body', sanitize_textarea_field($_POST['renewal_body']));
    echo '<div class="notice notice-success"><p>Instellingen opgeslagen.</p></div>';
}

// Show renewal sent message
if (isset($_GET['renewal_sent'])) {
    $sent = intval($_GET['renewal_sent']);
    $total = intval($_GET['renewal_total']);
    echo '<div class="notice notice-success"><p>Vernieuwingsemails verzonden naar ' . $sent . ' van ' . $total . ' verlopen inzendingen.</p></div>';
}

// Get all entries, ordered by date_added DESC
$entries = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}skillsbook_entries ORDER BY date_added DESC"
);

// Count expired entries
$expired_count = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}skillsbook_entries
     WHERE valid_until < CURDATE() AND confirmed = 1"
);

// Get current settings
$renewal_subject = get_option('skillsbook_renewal_subject', 'Kun je volgend jaar terug helpen?');
$renewal_body = get_option('skillsbook_renewal_body',
    "Beste [name],\n\n" .
    "Een tijd terug gaf je je gegevens door via de website van de Ouderraad van GO! Flora. Het schooljaar is alweer ten einde, dus we willen even checken of we volgend jaar terug op je kunnen rekenen. Zoja, klik dan even op de onderstaande link, controleer je gegevens, en druk op de knop! Zoneen, dan hoef je niks te doen. We verwijderen je bij aanvang van het nieuwe jaar uit ons adresboek.\n\n" .
    "[link]\n\n" .
    "Groetjes,\nDe Ouderraad"
);
?>

<div class="wrap">
    <h1>Skillsbook Inzendingen</h1>

    <div class="skillsbook-admin-actions" style="margin-bottom: 20px;">
        <?php if ($expired_count > 0): ?>
            <a href="<?php echo wp_nonce_url(admin_url('admin.php?skillsbook_send_renewal=1'), 'skillsbook_send_renewal'); ?>"
               class="button button-primary"
               onclick="return confirm('Wil je vernieuwingsemails sturen naar <?php echo $expired_count; ?> verlopen inzendingen?');">
                Verstuur vernieuwingsemails (<?php echo $expired_count; ?>)
            </a>
        <?php else: ?>
            <button class="button button-primary" disabled>Geen verlopen inzendingen</button>
        <?php endif; ?>

        <button type="button" class="button" id="skillsbook-toggle-settings">Email instellingen</button>
    </div>

    <div id="skillsbook-email-settings" style="display: none; background: #fff; padding: 20px; margin-bottom: 20px; border: 1px solid #ccc;">
        <h2>Email instellingen voor vernieuwingen</h2>
        <p>Gebruik <code>[name]</code> voor de naam van de persoon en <code>[link]</code> voor de bewerkingslink.</p>

        <form method="post">
            <?php wp_nonce_field('skillsbook_settings'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="renewal_subject">Email onderwerp</label></th>
                    <td>
                        <input type="text" id="renewal_subject" name="renewal_subject" value="<?php echo esc_attr($renewal_subject); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="renewal_body">Email inhoud</label></th>
                    <td>
                        <textarea id="renewal_body" name="renewal_body" rows="10" class="large-text"><?php echo esc_textarea($renewal_body); ?></textarea>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="skillsbook_update_settings" class="button button-primary" value="Instellingen opslaan">
            </p>
        </form>
    </div>

    <?php if (empty($entries)): ?>
        <p>Nog geen inzendingen.</p>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Naam</th>
                    <th>Kinderen</th>
                    <th>Email</th>
                    <th>Telefoon</th>
                    <th>Labels</th>
                    <th>Bericht</th>
                    <th>Bevestigd</th>
                    <th>Geldig tot</th>
                    <th>Toegevoegd</th>
                    <th>Bijgewerkt</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $entry):
                    // Get labels for this entry
                    $entry_labels = $wpdb->get_results($wpdb->prepare(
                        "SELECT l.name FROM {$wpdb->prefix}skillsbook_labels l
                         INNER JOIN {$wpdb->prefix}skillsbook_submission_labels sl ON l.id = sl.label_id
                         WHERE sl.submission_id = %d
                         ORDER BY l.name ASC",
                        $entry->id
                    ));
                    $label_names = array_map(function($l) { return $l->name; }, $entry_labels);
                    $labels_text = implode(', ', $label_names);
                ?>
                    <tr data-entry-id="<?php echo $entry->id; ?>">
                        <td>
                            <span class="field-display" data-field="name"><?php echo esc_html($entry->name); ?></span>
                            <input type="text" class="field-edit" data-field="name" data-original="<?php echo esc_attr($entry->name); ?>" value="<?php echo esc_attr($entry->name); ?>" style="display:none;">
                        </td>
                        <td>
                            <span class="field-display" data-field="children"><?php echo esc_html(wp_trim_words($entry->children, 10)); ?></span>
                            <textarea class="field-edit" data-field="children" data-original="<?php echo esc_attr($entry->children); ?>" rows="2" style="display:none;"><?php echo esc_textarea($entry->children); ?></textarea>
                        </td>
                        <td>
                            <span class="field-display" data-field="email"><?php echo esc_html($entry->email); ?></span>
                            <input type="email" class="field-edit" data-field="email" data-original="<?php echo esc_attr($entry->email); ?>" value="<?php echo esc_attr($entry->email); ?>" style="display:none;">
                        </td>
                        <td>
                            <span class="field-display" data-field="phone"><?php echo esc_html($entry->phone); ?></span>
                            <input type="text" class="field-edit" data-field="phone" data-original="<?php echo esc_attr($entry->phone); ?>" value="<?php echo esc_attr($entry->phone); ?>" style="display:none;">
                        </td>
                        <td>
                            <span class="labels-display"><?php echo esc_html($labels_text ?: '-'); ?></span>
                            <div class="labels-edit" style="display:none;">
                                <input type="text" class="labels-input" value="<?php echo esc_attr($labels_text); ?>" placeholder="Label1, Label2, ..." style="width: 200px;">
                                <button type="button" class="button button-small save-labels-btn">Opslaan</button>
                            </div>
                        </td>
                        <td>
                            <div class="skillsbook-message-preview">
                                <?php echo esc_html(wp_trim_words($entry->message, 15)); ?>
                            </div>
                            <?php if (str_word_count($entry->message) > 15): ?>
                                <button type="button" class="button button-small skillsbook-view-full" data-message="<?php echo esc_attr($entry->message); ?>">
                                    Volledig
                                </button>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($entry->confirmed): ?>
                                <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                            <?php else: ?>
                                <span class="dashicons dashicons-minus" style="color: #999;"></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html(date('d/m/Y', strtotime($entry->valid_until))); ?></td>
                        <td><?php echo esc_html(date('d/m/Y H:i', strtotime($entry->date_added))); ?></td>
                        <td><?php echo esc_html(date('d/m/Y H:i', strtotime($entry->date_updated))); ?></td>
                        <td>
                            <button type="button" class="button button-small edit-entry-btn">Bewerken</button>
                            <button type="button" class="button button-small button-primary save-entry-btn" style="display:none;">Opslaan</button>
                            <button type="button" class="button button-small cancel-entry-btn" style="display:none;">Annuleren</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div id="skillsbook-message-modal" class="skillsbook-modal" style="display: none;">
    <div class="skillsbook-modal-content">
        <span class="skillsbook-modal-close">&times;</span>
        <h2>Volledig bericht</h2>
        <div class="skillsbook-modal-message"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle email settings
    $('#skillsbook-toggle-settings').on('click', function() {
        $('#skillsbook-email-settings').slideToggle();
    });

    // View full message modal
    $('.skillsbook-view-full').on('click', function() {
        var message = $(this).data('message');
        $('#skillsbook-message-modal .skillsbook-modal-message').text(message);
        $('#skillsbook-message-modal').show();
    });

    $('.skillsbook-modal-close').on('click', function() {
        $('#skillsbook-message-modal').hide();
    });

    $(window).on('click', function(e) {
        if ($(e.target).is('#skillsbook-message-modal')) {
            $('#skillsbook-message-modal').hide();
        }
    });
});
</script>
