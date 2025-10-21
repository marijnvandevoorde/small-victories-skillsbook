<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Show renewal sent message
if (isset($_GET['renewal_sent'])) {
    $sent = intval($_GET['renewal_sent']);
    $total = intval($_GET['renewal_total']);
    echo '<div class="notice notice-success"><p>Vernieuwingsemails verzonden naar ' . $sent . ' van ' . $total . ' verlopen inzendingen.</p></div>';
}

// Show delete expired message
if (isset($_GET['deleted_expired'])) {
    $deleted = intval($_GET['deleted_expired']);
    echo '<div class="notice notice-success"><p>' . $deleted . ' verlopen inzendingen verwijderd.</p></div>';
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

            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sv-skillsbook&skillsbook_delete_expired=1'), 'skillsbook_delete_expired'); ?>"
               class="button button-secondary"
               style="margin-left: 10px;"
               onclick="return confirm('Weet je zeker dat je alle <?php echo $expired_count; ?> verlopen inzendingen wilt verwijderen? Dit kan niet ongedaan worden gemaakt.');">
                Verwijder verlopen inzendingen (<?php echo $expired_count; ?>)
            </a>
        <?php else: ?>
            <button class="button button-primary" disabled>Geen verlopen inzendingen</button>
        <?php endif; ?>
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
