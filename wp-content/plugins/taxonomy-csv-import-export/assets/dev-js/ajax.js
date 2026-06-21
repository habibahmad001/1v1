jQuery(function ($) {

    // Tab switcher
    $('.taxocsie-tab').on('click', function () {
        var panelId = $(this).data('panel');
        $('.taxocsie-tab').removeClass('is-active');
        $(this).addClass('is-active');
        $('#taxocsie-export-panel, #taxocsie-import-panel').hide();
        $('#' + panelId).show();
        $('#export-preview-container').hide().empty();
    });

    // Create term — toggle row open/closed
    $(document).on('click', '.taxocsie-taxonomy-row__header', function () {
        var $row  = $(this).closest('.taxocsie-taxonomy-row');
        var $body = $row.find('.taxocsie-taxonomy-row__body');
        var isOpen = $body.is(':visible');

        // Close all other rows
        $('.taxocsie-taxonomy-row__body').slideUp(160);
        $('.taxocsie-taxonomy-row__header').removeClass('is-open');

        if (!isOpen) {
            $body.slideDown(160);
            $(this).addClass('is-open');
            $row.find('.taxocsie-row-title').focus();
        }
    });

    // Create term — button click
    $(document).on('click', '.taxocsie-row-create-btn', function () {
        doCreateTerm($(this).closest('.taxocsie-taxonomy-row'));
    });

    // Create term — Enter key in title field
    $(document).on('keydown', '.taxocsie-row-title', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            doCreateTerm($(this).closest('.taxocsie-taxonomy-row'));
        }
    });

    function doCreateTerm($row) {
        var taxonomy    = $row.data('taxonomy');
        var $title      = $row.find('.taxocsie-row-title');
        var $desc       = $row.find('.taxocsie-row-desc');
        var $btn        = $row.find('.taxocsie-row-create-btn');
        var $feedback   = $row.find('.taxocsie-row-feedback');
        var name        = $.trim($title.val());
        var description = $.trim($desc.val());

        if (!name) {
            showRowFeedback($feedback, 'Term title is required.', 'error');
            $title.focus();
            return;
        }

        $btn.prop('disabled', true);
        $feedback.hide().empty();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action:      'taxocsie_ajax_create_term',
                taxonomy:    taxonomy,
                name:        name,
                description: description,
                nonce:       taxocsieData.ajaxCreateTermNonce,
            },
            success: function (res) {
                if (!res.success) {
                    showRowFeedback($feedback, res.data, 'error');
                    return;
                }
                $title.val('');
                $desc.val('');
                showRowFeedback($feedback, '&#10003; <strong>' + escHtml(res.data.name) + '</strong> created (slug: <code>' + escHtml(res.data.slug) + '</code>).', 'success');
            },
            error: function () {
                showRowFeedback($feedback, 'Request failed. Please try again.', 'error');
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    }

    function showRowFeedback($el, message, type) {
        $el.removeClass('taxocsie-row-feedback--success taxocsie-row-feedback--error')
           .addClass('taxocsie-row-feedback--' + type)
           .html(message)
           .show();
    }

    // Export preview
    $('#export-preview-btn').on('click', function () {
        var taxonomy = $('#export-taxonomy').val();

        if (!taxonomy) {
            alert('Please select a taxonomy first.');
            return;
        }

        var $btn = $(this);
        var $container = $('#export-preview-container');

        $btn.prop('disabled', true).text('Loading...');
        $container.hide().empty();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'taxocsie_preview_export',
                taxonomy: taxonomy,
                nonce: taxocsieData.previewExportNonce,
            },
            success: function (res) {
                if (!res.success) {
                    $container.html('<p class="taxocsie-export__preview-error">' + escHtml(res.data) + '</p>').show();
                    return;
                }

                var rows = res.data;

                if (rows.length === 0) {
                    $container.html('<p class="taxocsie-export__preview-empty">No terms found for this taxonomy.</p>').show();
                    return;
                }

                var html = '<p class="taxocsie-export__preview-count">Preview — <strong>' + rows.length + '</strong> term(s) will be exported.</p>';
                html += '<div class="taxocsie-export__preview-tablewrap">';
                html += '<table class="widefat striped taxocsie-export__preview-table">';
                html += '<thead><tr><th>#</th><th>Name</th><th>Slug</th><th>Description</th><th>Parent Slug</th></tr></thead>';
                html += '<tbody>';

                $.each(rows, function (i, row) {
                    html += '<tr>';
                    html += '<td>' + (i + 1) + '</td>';
                    html += '<td>' + escHtml(row.name) + '</td>';
                    html += '<td>' + escHtml(row.slug) + '</td>';
                    html += '<td>' + (row.description ? escHtml(row.description) : '<span class="taxocsie-muted">—</span>') + '</td>';
                    html += '<td>' + (row.parent_slug ? escHtml(row.parent_slug) : '<span class="taxocsie-muted">—</span>') + '</td>';
                    html += '</tr>';
                });

                html += '</tbody></table></div>';

                $container.html(html).show();
            },
            error: function () {
                $container.html('<p class="taxocsie-export__preview-error">Request failed. Please try again.</p>').show();
            },
            complete: function () {
                $btn.prop('disabled', false).text('Preview');
            }
        });
    });

    // Clear preview when taxonomy selection changes
    $('#export-taxonomy').on('change', function () {
        $('#export-preview-container').hide().empty();
    });

    // ── Register Taxonomy ──────────────────────────────────────────

    // Auto-generate slug from label
    $('#reg-label').on('input', function () {
        var slug = $(this).val()
            .toLowerCase()
            .replace(/[^a-z0-9\s_-]/g, '')
            .trim()
            .replace(/[\s-]+/g, '_')
            .substring(0, 32);
        $('#reg-slug').val(slug);
    });

    // Highlight selected type card
    $(document).on('change', 'input[name="reg-type"]', function () {
        $('.taxocsie-type-option').removeClass('taxocsie-type-option--active');
        $(this).closest('.taxocsie-type-option').addClass('taxocsie-type-option--active');
    });

    // Submit new taxonomy
    $('#taxocsie-reg-submit').on('click', function () {
        var label       = $.trim($('#reg-label').val());
        var singular    = $.trim($('#reg-singular').val());
        var slug        = $.trim($('#reg-slug').val());
        var description = $.trim($('#reg-description').val());
        var hierarchical = $('input[name="reg-type"]:checked').val();
        var postTypes   = [];
        $('.reg-post-type:checked').each(function () { postTypes.push($(this).val()); });

        var $btn      = $(this);
        var $feedback = $('#taxocsie-reg-feedback');

        if (!label) { showRegFeedback($feedback, 'Label is required.', 'error'); return; }
        if (!slug)  { showRegFeedback($feedback, 'Slug is required.', 'error'); return; }
        if (!postTypes.length) { showRegFeedback($feedback, 'Please select at least one post type.', 'error'); return; }

        $btn.prop('disabled', true);
        $feedback.hide().empty();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action:         'taxocsie_save_taxonomy',
                label:          label,
                singular_label: singular,
                slug:           slug,
                description:    description,
                hierarchical:   hierarchical,
                post_types:     postTypes,
                nonce:          taxocsieData.saveTaxonomyNonce,
            },
            success: function (res) {
                if (!res.success) {
                    showRegFeedback($feedback, res.data, 'error');
                    return;
                }
                var d = res.data;
                var typeLabel = d.hierarchical ? 'Categories' : 'Tags';
                var row = '<tr data-slug="' + escHtml(d.slug) + '">' +
                    '<td><strong>' + escHtml(d.label) + '</strong></td>' +
                    '<td><code>' + escHtml(d.slug) + '</code></td>' +
                    '<td>' + escHtml(typeLabel) + '</td>' +
                    '<td>' + escHtml(d.post_types.join(', ')) + '</td>' +
                    '<td><button type="button" class="button taxocsie-delete-taxonomy" data-slug="' + escHtml(d.slug) + '">Delete</button></td>' +
                    '</tr>';
                $('#taxocsie-reg-table tbody').append(row);
                $('#taxocsie-reg-panel').show();
                $('#reg-label, #reg-singular, #reg-slug, #reg-description').val('');
                $('.reg-post-type').prop('checked', false);
                showRegFeedback($feedback, '&#10003; Taxonomy <strong>' + escHtml(d.label) + '</strong> registered. Refresh the page to use it.', 'success');
            },
            error: function () {
                showRegFeedback($feedback, 'Request failed. Please try again.', 'error');
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    });

    // Delete taxonomy
    $(document).on('click', '.taxocsie-delete-taxonomy', function () {
        var slug = $(this).data('slug');
        if (!window.confirm('Delete taxonomy "' + slug + '"? This cannot be undone.')) {
            return;
        }
        var $btn = $(this);
        $btn.prop('disabled', true);
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'taxocsie_delete_taxonomy',
                slug:   slug,
                nonce:  taxocsieData.deleteTaxonomyNonce,
            },
            success: function (res) {
                if (!res.success) { alert(res.data); $btn.prop('disabled', false); return; }
                var $row = $('#taxocsie-reg-table tbody tr[data-slug="' + slug + '"]');
                $row.fadeOut(250, function () {
                    $row.remove();
                    if (!$('#taxocsie-reg-table tbody tr').length) {
                        $('#taxocsie-reg-panel').hide();
                    }
                });
            },
            error: function () { alert('Request failed.'); $btn.prop('disabled', false); }
        });
    });

    function showRegFeedback($el, message, type) {
        $el.removeClass('taxocsie-reg-feedback--success taxocsie-reg-feedback--error')
           .addClass('taxocsie-reg-feedback--' + type)
           .html(message)
           .show();
    }

    // ── Helpers ────────────────────────────────────────────────────

function escHtml(str) {
        return $('<div>').text(String(str)).html();
    }

});
