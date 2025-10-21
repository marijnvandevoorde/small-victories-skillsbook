<?php
if (!defined('ABSPATH')) {
    exit;
}

// Handle settings save
if (isset($_POST['skillsbook_save_settings'])) {
    check_admin_referer('skillsbook_settings');
    update_option('skillsbook_form_page_id', intval($_POST['form_page_id']));
    echo '<div class="notice notice-success"><p>Instellingen opgeslagen.</p></div>';
}

// Get current settings
$form_page_id = get_option('skillsbook_form_page_id', 0);

// Get all pages
$pages = get_pages();
?>

<div class="wrap">
    <h1>Skillsbook Instellingen</h1>

    <form method="post">
        <?php wp_nonce_field('skillsbook_settings'); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="form_page_id">Formulier pagina</label>
                </th>
                <td>
                    <select name="form_page_id" id="form_page_id" class="regular-text">
                        <option value="0">-- Selecteer een pagina --</option>
                        <?php foreach ($pages as $page): ?>
                            <option value="<?php echo $page->ID; ?>" <?php selected($form_page_id, $page->ID); ?>>
                                <?php echo esc_html($page->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        Selecteer de pagina waar het formulier moet verschijnen (bijv. "ikkanda").
                        Voeg het shortcode <code>[skillsbook_form]</code> toe aan deze pagina.
                    </p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="skillsbook_save_settings" class="button button-primary" value="Instellingen opslaan">
        </p>
    </form>
</div>
