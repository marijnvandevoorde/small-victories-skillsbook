jQuery(document).ready(function($) {
    // Make entry rows editable
    $(document).on('click', '.edit-entry-btn', function() {
        var $row = $(this).closest('tr');
        $row.find('.field-display').hide();
        $row.find('.field-edit').show();
        $row.find('.edit-entry-btn').hide();
        $row.find('.save-entry-btn').show();
        $row.find('.cancel-entry-btn').show();
        $row.find('.labels-edit').show();
        $row.find('.labels-display').hide();
    });

    // Cancel edit
    $(document).on('click', '.cancel-entry-btn', function() {
        var $row = $(this).closest('tr');
        $row.find('.field-edit').each(function() {
            var originalValue = $(this).data('original');
            $(this).val(originalValue);
        });
        $row.find('.field-display').show();
        $row.find('.field-edit').hide();
        $row.find('.edit-entry-btn').show();
        $row.find('.save-entry-btn').hide();
        $row.find('.cancel-entry-btn').hide();
        $row.find('.labels-edit').hide();
        $row.find('.labels-display').show();
    });

    // Save entry
    $(document).on('click', '.save-entry-btn', function() {
        var $row = $(this).closest('tr');
        var entryId = $row.data('entry-id');
        var updates = [];

        // Collect all changed fields
        $row.find('.field-edit').each(function() {
            var $field = $(this);
            var fieldName = $field.data('field');
            var newValue = $field.val();

            updates.push({
                field: fieldName,
                value: newValue
            });
        });

        // Save each field
        var savePromises = [];
        updates.forEach(function(update) {
            var promise = $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'skillsbook_update_entry',
                    nonce: skillsbookAjax.nonce,
                    entry_id: entryId,
                    field: update.field,
                    value: update.value
                }
            });
            savePromises.push(promise);
        });

        // Wait for all saves to complete
        $.when.apply($, savePromises).done(function() {
            // Update displays
            $row.find('.field-edit').each(function() {
                var $edit = $(this);
                var newValue = $edit.val();
                var $display = $row.find('.field-display[data-field="' + $edit.data('field') + '"]');
                $display.text(newValue);
                $edit.data('original', newValue);
            });

            $row.find('.field-display').show();
            $row.find('.field-edit').hide();
            $row.find('.edit-entry-btn').show();
            $row.find('.save-entry-btn').hide();
            $row.find('.cancel-entry-btn').hide();
            $row.find('.labels-edit').hide();
            $row.find('.labels-display').show();
        }).fail(function() {
            alert('Er ging iets mis bij het opslaan.');
        });
    });

    // Label autocomplete
    var availableLabels = [];

    function loadLabels() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'skillsbook_get_labels',
                nonce: skillsbookAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    availableLabels = response.data.map(function(label) {
                        return label.name;
                    });
                }
            }
        });
    }

    loadLabels();

    // Initialize autocomplete on labels input
    $(document).on('focus', '.labels-input', function() {
        if (!$(this).data('ui-autocomplete')) {
            $(this).autocomplete({
                source: availableLabels,
                minLength: 0
            }).focus(function() {
                $(this).autocomplete('search', '');
            });
        }
    });

    // Save labels
    $(document).on('click', '.save-labels-btn', function() {
        var $btn = $(this);
        var $row = $btn.closest('tr');
        var entryId = $row.data('entry-id');
        var labelsText = $row.find('.labels-input').val();
        var labels = labelsText.split(',').map(function(l) {
            return l.trim();
        }).filter(function(l) {
            return l.length > 0;
        });

        // Validate that all labels exist
        var invalidLabels = labels.filter(function(label) {
            return availableLabels.indexOf(label) === -1;
        });

        if (invalidLabels.length > 0) {
            alert('Ongeldige labels: ' + invalidLabels.join(', ') + '\nGebruik alleen bestaande labels.');
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'skillsbook_update_entry_labels',
                nonce: skillsbookAjax.nonce,
                entry_id: entryId,
                labels: labels
            },
            success: function(response) {
                if (response.success) {
                    // Update display
                    $row.find('.labels-display').text(labelsText || '-');
                    $row.find('.labels-edit').hide();
                    $row.find('.labels-display').show();
                } else {
                    alert(response.data.message || 'Er ging iets mis.');
                }
            },
            error: function() {
                alert('Er ging iets mis bij het opslaan van labels.');
            }
        });
    });
});
