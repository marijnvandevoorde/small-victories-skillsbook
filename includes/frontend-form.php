<?php
if (!defined('ABSPATH')) {
    exit;
}

$plugin = SmallVictoriesSkillsbook::get_instance();
$is_edit_mode = false;
$existing_entry = null;
$uuid = '';

// Check if this is an edit link
if (isset($_GET['skillsbook_edit']) && isset($_GET['uuid'])) {
    $uuid = sanitize_text_field($_GET['uuid']);
    $existing_entry = $plugin->get_entry_by_uuid($uuid);

    if ($existing_entry && $existing_entry->confirmed) {
        $is_edit_mode = true;
    }
}

// Check if confirmation was successful
$show_confirmation_message = isset($_GET['skillsbook_confirmed']);
?>

<div class="skillsbook-form-wrapper">
    <?php if ($show_confirmation_message): ?>
        <div class="skillsbook-message skillsbook-success">
            Je email is bevestigd! Je krijgt een email met een link om je informatie later te bewerken.
        </div>
    <?php endif; ?>

    <div class="skillsbook-message" style="display: none;"></div>

    <?php if ($is_edit_mode): ?>
        <div class="skillsbook-existing-message">
            <strong>Je huidige bericht:</strong>
            <div class="skillsbook-existing-content"><?php echo esc_html($existing_entry->message); ?></div>
        </div>
    <?php endif; ?>

    <form class="skillsbook-form" data-mode="<?php echo $is_edit_mode ? 'edit' : 'new'; ?>" data-uuid="<?php echo esc_attr($uuid); ?>">
        <div class="skillsbook-field">
            <label for="skillsbook-name">Naam *</label>
            <input
                type="text"
                id="skillsbook-name"
                name="name"
                required
                value="<?php echo $is_edit_mode ? esc_attr($existing_entry->name) : ''; ?>"
            >
        </div>

        <div class="skillsbook-field">
            <label for="skillsbook-children">Naam & klas van kinderen die op GO! Flora zitten *</label>
            <textarea
                id="skillsbook-children"
                name="children"
                rows="3"
                required
            ><?php echo $is_edit_mode ? esc_textarea($existing_entry->children) : ''; ?></textarea>
        </div>

        <div class="skillsbook-field">
            <label for="skillsbook-email">Email *</label>
            <input
                type="email"
                id="skillsbook-email"
                name="email"
                required
                value="<?php echo $is_edit_mode ? esc_attr($existing_entry->email) : ''; ?>"
            >
        </div>

        <div class="skillsbook-field">
            <label for="skillsbook-phone">Telefoonnummer</label>
            <input
                type="tel"
                id="skillsbook-phone"
                name="phone"
                value="<?php echo $is_edit_mode ? esc_attr($existing_entry->phone) : ''; ?>"
            >
        </div>


        <div class="skillsbook-field">
            <label for="skillsbook-message">Bericht *</label>
            <textarea
                id="skillsbook-message"
                name="message"
                rows="5"
                required
            ><?php echo $is_edit_mode ? esc_textarea($existing_entry->message) : ''; ?></textarea>
        </div>

        <button type="submit" class="skillsbook-submit">
            <?php echo $is_edit_mode ? 'Bijwerken' : 'Versturen'; ?>
        </button>
    </form>
</div>
