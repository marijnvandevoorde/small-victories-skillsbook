<?php
if (!defined('ABSPATH')) {
    exit;
}

// Handle settings save
if (isset($_POST['skillsbook_save_settings'])) {
    check_admin_referer('skillsbook_settings');

    // General settings
    update_option('skillsbook_form_page_id', intval($_POST['form_page_id']));

    // Email templates
    update_option('skillsbook_confirmation_email_subject', sanitize_text_field($_POST['confirmation_email_subject']));
    update_option('skillsbook_confirmation_email_body', sanitize_textarea_field($_POST['confirmation_email_body']));
    update_option('skillsbook_edit_email_subject', sanitize_text_field($_POST['edit_email_subject']));
    update_option('skillsbook_edit_email_body', sanitize_textarea_field($_POST['edit_email_body']));
    update_option('skillsbook_renewal_email_subject', sanitize_text_field($_POST['renewal_email_subject']));
    update_option('skillsbook_renewal_email_body', sanitize_textarea_field($_POST['renewal_email_body']));

    // Success messages
    update_option('skillsbook_msg_success_submit', sanitize_text_field($_POST['msg_success_submit']));
    update_option('skillsbook_msg_success_update', sanitize_text_field($_POST['msg_success_update']));
    update_option('skillsbook_msg_success_confirmed', sanitize_text_field($_POST['msg_success_confirmed']));
    update_option('skillsbook_msg_existing_user', sanitize_text_field($_POST['msg_existing_user']));

    echo '<div class="notice notice-success"><p>Instellingen opgeslagen.</p></div>';
}

// Get current settings
$form_page_id = get_option('skillsbook_form_page_id', 0);

// Email templates with defaults
$confirmation_email_subject = get_option('skillsbook_confirmation_email_subject', 'Bevestig je inschrijving');
$confirmation_email_body = get_option('skillsbook_confirmation_email_body',
    "Hallo [name],\n\n" .
    "Bedankt voor je inschrijving. Klik op de onderstaande link om je email adres te bevestigen:\n\n" .
    "[link]\n\n" .
    "Groetjes,\nSmall Victories"
);

$edit_email_subject = get_option('skillsbook_edit_email_subject', 'Bewerk je informatie');
$edit_email_body = get_option('skillsbook_edit_email_body',
    "Hallo,\n\n" .
    "Gebruik de onderstaande link om je informatie te bewerken:\n\n" .
    "[link]\n\n" .
    "Groetjes,\nSmall Victories"
);

$renewal_email_subject = get_option('skillsbook_renewal_email_subject', 'Kun je volgend jaar terug helpen?');
$renewal_email_body = get_option('skillsbook_renewal_email_body',
    "Beste [name],\n\n" .
    "Een tijd terug gaf je je gegevens door via de website van de Ouderraad van GO! Flora. Het schooljaar is alweer ten einde, dus we willen even checken of we volgend jaar terug op je kunnen rekenen. Zoja, klik dan even op de onderstaande link, controleer je gegevens, en druk op de knop! Zoneen, dan hoef je niks te doen. We verwijderen je bij aanvang van het nieuwe jaar uit ons adresboek.\n\n" .
    "[link]\n\n" .
    "Groetjes,\nDe Ouderraad"
);

// Success messages with defaults
$msg_success_submit = get_option('skillsbook_msg_success_submit',
    'Bedankt dat je de handen uit de mouwen wil steken. Je krijgt een email met een link om je info te bevestigen.'
);
$msg_success_update = get_option('skillsbook_msg_success_update',
    'Je informatie is bijgewerkt.'
);
$msg_success_confirmed = get_option('skillsbook_msg_success_confirmed',
    'Je email is bevestigd! Je krijgt een email met een link om je informatie later te bewerken.'
);
$msg_existing_user = get_option('skillsbook_msg_existing_user',
    'Je hebt je info al eerder doorgegeven. We hebben je een email gestuurd om deze te bewerken. Geen zorgen, alle info blijft behouden.'
);

// Get all pages
$pages = get_pages();
?>

<div class="wrap">
    <h1>Skillsbook Instellingen</h1>

    <form method="post">
        <?php wp_nonce_field('skillsbook_settings'); ?>

        <h2>Algemeen</h2>
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

        <h2>Email Templates</h2>
        <p class="description">Gebruik <code>[name]</code> voor de naam van de persoon en <code>[link]</code> voor de actie link.</p>

        <table class="form-table">
            <tr>
                <th scope="row"><label for="confirmation_email_subject">Bevestigingsemail - Onderwerp</label></th>
                <td>
                    <input type="text" id="confirmation_email_subject" name="confirmation_email_subject" value="<?php echo esc_attr($confirmation_email_subject); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="confirmation_email_body">Bevestigingsemail - Inhoud</label></th>
                <td>
                    <textarea id="confirmation_email_body" name="confirmation_email_body" rows="8" class="large-text"><?php echo esc_textarea($confirmation_email_body); ?></textarea>
                    <p class="description">Email die verstuurd wordt na het indienen van het formulier.</p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="edit_email_subject">Bewerkingsemail - Onderwerp</label></th>
                <td>
                    <input type="text" id="edit_email_subject" name="edit_email_subject" value="<?php echo esc_attr($edit_email_subject); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="edit_email_body">Bewerkingsemail - Inhoud</label></th>
                <td>
                    <textarea id="edit_email_body" name="edit_email_body" rows="8" class="large-text"><?php echo esc_textarea($edit_email_body); ?></textarea>
                    <p class="description">Email die verstuurd wordt na email bevestiging of aan bestaande gebruikers.</p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="renewal_email_subject">Vernieuwingsemail - Onderwerp</label></th>
                <td>
                    <input type="text" id="renewal_email_subject" name="renewal_email_subject" value="<?php echo esc_attr($renewal_email_subject); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="renewal_email_body">Vernieuwingsemail - Inhoud</label></th>
                <td>
                    <textarea id="renewal_email_body" name="renewal_email_body" rows="10" class="large-text"><?php echo esc_textarea($renewal_email_body); ?></textarea>
                    <p class="description">Email die verstuurd wordt naar verlopen inzendingen.</p>
                </td>
            </tr>
        </table>

        <h2>Succes Berichten</h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="msg_success_submit">Nieuwe inzending</label></th>
                <td>
                    <input type="text" id="msg_success_submit" name="msg_success_submit" value="<?php echo esc_attr($msg_success_submit); ?>" class="large-text">
                    <p class="description">Bericht na succesvolle nieuwe inzending.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="msg_success_update">Informatie bijgewerkt</label></th>
                <td>
                    <input type="text" id="msg_success_update" name="msg_success_update" value="<?php echo esc_attr($msg_success_update); ?>" class="large-text">
                    <p class="description">Bericht na succesvol bijwerken van informatie.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="msg_success_confirmed">Email bevestigd</label></th>
                <td>
                    <input type="text" id="msg_success_confirmed" name="msg_success_confirmed" value="<?php echo esc_attr($msg_success_confirmed); ?>" class="large-text">
                    <p class="description">Bericht op de pagina na email bevestiging.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="msg_existing_user">Bestaande gebruiker</label></th>
                <td>
                    <input type="text" id="msg_existing_user" name="msg_existing_user" value="<?php echo esc_attr($msg_existing_user); ?>" class="large-text">
                    <p class="description">Bericht wanneer iemand probeert in te schrijven met een bestaand email adres.</p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="skillsbook_save_settings" class="button button-primary" value="Instellingen opslaan">
        </p>
    </form>
</div>
