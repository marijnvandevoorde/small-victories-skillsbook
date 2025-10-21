jQuery(document).ready(function($) {
    $('.skillsbook-form').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $submitBtn = $form.find('.skillsbook-submit');
        var $messageDiv = $form.closest('.skillsbook-form-wrapper').find('.skillsbook-message');
        var isEditMode = $form.data('mode') === 'edit';
        var uuid = $form.data('uuid');

        // Disable submit button
        $submitBtn.prop('disabled', true);

        // Collect form data
        var formData = {
            action: isEditMode ? 'skillsbook_update' : 'skillsbook_submit',
            nonce: skillsbookAjax.nonce,
            name: $form.find('[name="name"]').val(),
            children: $form.find('[name="children"]').val(),
            email: $form.find('[name="email"]').val(),
            phone: $form.find('[name="phone"]').val(),
            message: $form.find('[name="message"]').val()
        };

        if (isEditMode) {
            formData.uuid = uuid;
        }

        // If not in edit mode, check localStorage for existing data
        if (!isEditMode) {
            // Store form data in localStorage in case we need it later
            localStorage.setItem('skillsbook_temp_name', formData.name);
            localStorage.setItem('skillsbook_temp_children', formData.children);
            localStorage.setItem('skillsbook_temp_email', formData.email);
            localStorage.setItem('skillsbook_temp_phone', formData.phone);
            localStorage.setItem('skillsbook_temp_message', formData.message);
        }

        // Submit via AJAX
        $.ajax({
            url: skillsbookAjax.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                $submitBtn.prop('disabled', false);

                if (response.success) {
                    $messageDiv
                        .removeClass('skillsbook-error')
                        .addClass('skillsbook-success')
                        .html(response.data.message)
                        .show();

                    // Clear form if it's a new submission and not an existing user
                    if (!isEditMode && !response.data.existing) {
                        $form[0].reset();
                        // Clear localStorage
                        localStorage.removeItem('skillsbook_temp_name');
                        localStorage.removeItem('skillsbook_temp_children');
                        localStorage.removeItem('skillsbook_temp_email');
                        localStorage.removeItem('skillsbook_temp_phone');
                        localStorage.removeItem('skillsbook_temp_message');
                    }

                    // Scroll to message
                    $('html, body').animate({
                        scrollTop: $messageDiv.offset().top - 100
                    }, 500);
                } else {
                    $messageDiv
                        .removeClass('skillsbook-success')
                        .addClass('skillsbook-error')
                        .html(response.data.message)
                        .show();

                    // Scroll to message
                    $('html, body').animate({
                        scrollTop: $messageDiv.offset().top - 100
                    }, 500);
                }
            },
            error: function() {
                $submitBtn.prop('disabled', false);
                $messageDiv
                    .removeClass('skillsbook-success')
                    .addClass('skillsbook-error')
                    .html('Er ging iets mis. Probeer het later opnieuw.')
                    .show();
            }
        });
    });

    // Check if we're on edit page and there's data in localStorage
    if ($('.skillsbook-form').data('mode') === 'edit') {
        var tempName = localStorage.getItem('skillsbook_temp_name');
        var tempChildren = localStorage.getItem('skillsbook_temp_children');
        var tempEmail = localStorage.getItem('skillsbook_temp_email');
        var tempPhone = localStorage.getItem('skillsbook_temp_phone');
        var tempMessage = localStorage.getItem('skillsbook_temp_message');

        // If we have temp data, fill in the form
        if (tempName || tempChildren || tempEmail || tempPhone || tempMessage) {
            if (tempName) $('.skillsbook-form [name="name"]').val(tempName);
            if (tempChildren) $('.skillsbook-form [name="children"]').val(tempChildren);
            if (tempEmail) $('.skillsbook-form [name="email"]').val(tempEmail);
            if (tempPhone) $('.skillsbook-form [name="phone"]').val(tempPhone);
            if (tempMessage) $('.skillsbook-form [name="message"]').val(tempMessage);
        }
    }
});
