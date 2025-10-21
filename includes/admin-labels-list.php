<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get all labels
$labels = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}skillsbook_labels ORDER BY name ASC"
);
?>

<div class="wrap">
    <h1>Skillsbook Labels</h1>

    <div class="skillsbook-label-add" style="margin-bottom: 20px; background: #fff; padding: 15px; border: 1px solid #ccc;">
        <h2>Nieuw label toevoegen</h2>
        <div style="display: flex; gap: 10px; align-items: center;">
            <input type="text" id="new-label-name" class="regular-text" placeholder="Label naam">
            <button type="button" class="button button-primary" id="add-label-btn">Toevoegen</button>
        </div>
    </div>

    <?php if (empty($labels)): ?>
        <p>Nog geen labels.</p>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 60%;">Naam</th>
                    <th>Aangemaakt</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody id="labels-tbody">
                <?php foreach ($labels as $label): ?>
                    <tr data-label-id="<?php echo $label->id; ?>">
                        <td>
                            <span class="label-name-display"><?php echo esc_html($label->name); ?></span>
                            <input type="text" class="label-name-edit regular-text" value="<?php echo esc_attr($label->name); ?>" style="display: none;">
                        </td>
                        <td><?php echo esc_html(date('d/m/Y H:i', strtotime($label->created_at))); ?></td>
                        <td>
                            <button type="button" class="button button-small edit-label-btn">Bewerken</button>
                            <button type="button" class="button button-small button-primary save-label-btn" style="display: none;">Opslaan</button>
                            <button type="button" class="button button-small cancel-label-btn" style="display: none;">Annuleren</button>
                            <button type="button" class="button button-small button-link-delete delete-label-btn">Verwijderen</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Add new label
    $('#add-label-btn').on('click', function() {
        var name = $('#new-label-name').val().trim();
        if (!name) {
            alert('Vul een label naam in.');
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'skillsbook_add_label',
                nonce: '<?php echo wp_create_nonce('skillsbook_admin'); ?>',
                name: name
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || 'Er ging iets mis.');
                }
            }
        });
    });

    // Edit label
    $(document).on('click', '.edit-label-btn', function() {
        var $row = $(this).closest('tr');
        $row.find('.label-name-display').hide();
        $row.find('.label-name-edit').show();
        $row.find('.edit-label-btn').hide();
        $row.find('.delete-label-btn').hide();
        $row.find('.save-label-btn').show();
        $row.find('.cancel-label-btn').show();
    });

    // Cancel edit
    $(document).on('click', '.cancel-label-btn', function() {
        var $row = $(this).closest('tr');
        var originalName = $row.find('.label-name-display').text();
        $row.find('.label-name-edit').val(originalName);
        $row.find('.label-name-display').show();
        $row.find('.label-name-edit').hide();
        $row.find('.edit-label-btn').show();
        $row.find('.delete-label-btn').show();
        $row.find('.save-label-btn').hide();
        $row.find('.cancel-label-btn').hide();
    });

    // Save label
    $(document).on('click', '.save-label-btn', function() {
        var $row = $(this).closest('tr');
        var labelId = $row.data('label-id');
        var newName = $row.find('.label-name-edit').val().trim();

        if (!newName) {
            alert('Vul een label naam in.');
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'skillsbook_update_label',
                nonce: '<?php echo wp_create_nonce('skillsbook_admin'); ?>',
                label_id: labelId,
                name: newName
            },
            success: function(response) {
                if (response.success) {
                    $row.find('.label-name-display').text(newName);
                    $row.find('.label-name-display').show();
                    $row.find('.label-name-edit').hide();
                    $row.find('.edit-label-btn').show();
                    $row.find('.delete-label-btn').show();
                    $row.find('.save-label-btn').hide();
                    $row.find('.cancel-label-btn').hide();
                } else {
                    alert(response.data.message || 'Er ging iets mis.');
                }
            }
        });
    });

    // Delete label
    $(document).on('click', '.delete-label-btn', function() {
        if (!confirm('Weet je zeker dat je dit label wilt verwijderen?')) {
            return;
        }

        var $row = $(this).closest('tr');
        var labelId = $row.data('label-id');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'skillsbook_delete_label',
                nonce: '<?php echo wp_create_nonce('skillsbook_admin'); ?>',
                label_id: labelId
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(function() {
                        $(this).remove();
                    });
                } else {
                    alert(response.data.message || 'Er ging iets mis.');
                }
            }
        });
    });
});
</script>
