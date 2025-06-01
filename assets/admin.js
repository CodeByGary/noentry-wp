jQuery(document).ready(function ($) {
    const { __ } = wp.i18n;

    let isDirty = false;

    $('#noentry-wp-accordion').accordion({
        collapsible: false,
        heightStyle: "content"
    });

    // Track changes to mark page as dirty
    $(document).on('change', '.noentry-wp-rule-row select, .noentry-wp-rule-row input', function () {
        isDirty = true;
    });

    $('.noentry-wp-add-row').click(function () {
        const userId = $(this).data('user');
        const container = $('.noentry-wp-rules[data-user="' + userId + '"]');
        const index = container.children('.noentry-wp-rule-row').length;

        // Check for empty fields
        let hasEmpty = false;
        container.find('.noentry-wp-rule-row').each(function () {
            const type = $(this).find('select').val();
            const value = $(this).find('input').val().trim();

            if (!type || !value) {
                hasEmpty = true;
            }
        });

        if (hasEmpty) {
            alert(__('Please fill in all existing rules before adding a new one.', 'noentry-wp'));
            return;
        }

        const html = `
            <div class="noentry-wp-rule-row">
                <select name="noentry-wp_user_rules[${userId}][${index}][type]">
                    <option value="contains">${__('Contains', 'noentry-wp')}</option>
                    <option value="equals">${__('Equals', 'noentry-wp')}</option>
                    <option value="starts_with">${__('Starts With', 'noentry-wp')}</option>
                    <option value="regex">${__('Regex', 'noentry-wp')}</option>
                </select>
                <input type="text" name="noentry-wp_user_rules[${userId}][${index}][value]" value="" />
                <button type="button" class="button noentry-wp-remove-row">−</button>
            </div>
        `;
        container.append(html);
        isDirty = true;
    });

    $(document).on('click', '.noentry-wp-remove-row', function () {
        if (confirm(__('Are you sure you want to delete this rule?', 'noentry-wp'))) {
            $(this).closest('.noentry-wp-rule-row').remove();
            isDirty = true;
        }
    });

    // Reset dirty flag on save
    $('form').on('submit', function () {
        isDirty = false;
    });

    // Warn before leaving if there are unsaved changes
    window.addEventListener('beforeunload', function (e) {
        if (isDirty) {
            const message = __('You have unsaved changes. Are you sure you want to leave this page?', 'noentry-wp');
            e.preventDefault();
            e.returnValue = message; // For most browsers
            return message;
        }
    });
});
