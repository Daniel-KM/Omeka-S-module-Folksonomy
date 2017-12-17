$(document).ready(function() {

/* Tagging a resource. */

// Add the selected tag to the edit panel.
$('#tag-selector .selector-child').click(function(event) {
    event.preventDefault();

    $('#resource-tags').removeClass('empty');
    var tagName = $(this).data('child-search');

    if ($('#resource-tags').find("input[value='" + tagName + "']").length) {
        return;
    }

    var row = $($('#tag-template').data('template'));
    row.children('td.tag-name').text(tagName);
    row.find('td > input').val(tagName);
    $('#resource-tags > tbody:last').append(row);
});

// Remove a tag from the edit panel.
$('#resource-tags').on('click', '.o-icon-delete', function(event) {
    event.preventDefault();

    var removeLink = $(this);
    var tagRow = $(this).closest('tr');
    var tagInput = removeLink.closest('td').find('input');
    tagInput.prop('disabled', true);

    // Undo remove tag link.
    var undoRemoveLink = $('<a>', {
        href: '#',
        class: 'fa fa-undo',
        title: Omeka.jsTranslate('Undo remove tag'),
        click: function(event) {
            event.preventDefault();
            tagRow.toggleClass('delete');
            tagInput.prop('disabled', false);
            removeLink.show();
            $(this).remove();
        },
    });

    tagRow.toggleClass('delete');
    undoRemoveLink.insertAfter(removeLink);
    removeLink.hide();
});

/* Update taggings. */

// Toggle the status of a tagging.
$('#content').on('click', 'a.status-toggle', function(e) {
    e.preventDefault();

    var button = $(this);
    var url = button.data('status-toggle-url');
    var status = button.data('status');
    $.ajax({
        url: url,
        beforeSend: function() {
            button.removeClass('o-icon-' + status).addClass('o-icon-transmit');
        }
    })
    .done(function(data) {
        if (!data.content) {
            alert(Omeka.jsTranslate('Something went wrong'));
        } else {
            status = data.content.status;
            button.data('status', status);
            var row = button.closest('tr')
            row.find('.status-label').text(data.content.statusLabel);
            var isPublicOrNot = row.find('.is-public-or-not');
            if (status === 'proposed' || status === 'rejected') {
                isPublicOrNot.show();
                isPublicOrNot.addClass('o-icon-private');
            } else {
                isPublicOrNot.hide();
                isPublicOrNot.removeClass('o-icon-private');
            }
        }
    })
    .fail(function(jqXHR, textStatus) {
        if (jqXHR.status == 404) {
            alert(Omeka.jsTranslate('The resource or the tag doesn’t exist.'));
        } else {
            alert(Omeka.jsTranslate('Something went wrong'));
        }
    })
    .always(function () {
        button.removeClass('o-icon-transmit').addClass('o-icon-' + status);
    });
});

// Approve or reject a list of taggings.
$('#content').on('click', 'a.status-batch', function(e) {
    e.preventDefault();

    var selected = $('.batch-edit td input[name="resource_ids[]"][type="checkbox"]:checked');
    if (selected.length == 0) {
        return;
    }
    var checked = selected.map(function() { return $(this).val(); }).get();
    var button = $(this);
    var url = button.data('status-batch-url');
    $.post({
        url: url,
        data: {resource_ids: checked},
        beforeSend: function() {
            selected.closest('tr').find('.status-toggle').each(function() {
                $(this).removeClass('o-icon-' + $(this).data('status')).addClass('o-icon-transmit');
            });
            $('.select-all').prop('checked', false);
        }
    })
    .done(function(data) {
        if (!data.content) {
            alert(Omeka.jsTranslate('Something went wrong'));
        } else {
            status = data.content.status;
            var statusLabel = Omeka.jsTranslate(data.content.statusLabel);
            selected.closest('tr').each(function() {
                var row = $(this);
                row.find('input[type="checkbox"]').prop('checked', false);
                row.find('.status-toggle').data('status', status);
                row.find('.status-toggle').removeClass('o-icon-transmit').addClass('o-icon-' + status);
                row.find('.status-label').text(statusLabel);
                var isPublicOrNot = row.find('.is-public-or-not');
                if (status === 'approved') {
                    isPublicOrNot.hide();
                    isPublicOrNot.removeClass('o-icon-private');
                } else {
                    isPublicOrNot.show();
                    isPublicOrNot.addClass('o-icon-private');
                }
            });
        }
    })
    .fail(function(jqXHR, textStatus) {
        selected.closest('tr').find('.status-toggle').each(function() {
            $(this).removeClass('o-icon-transmit').addClass('o-icon-' + $(this).data('status'));
        });
        if (jqXHR.status == 404) {
            alert(Omeka.jsTranslate('The resource or the tag doesn’t exist.'));
        } else {
            alert(Omeka.jsTranslate('Something went wrong'));
        }
    })
});

/* Update tags. */

// Update the name of a tag.
$('.folksonomy .o-icon-edit.contenteditable')
    .on('click', function(e) {
        e.preventDefault();
        var field = $(this).closest('td').find('.tag-name');
        field.focus();
    });

// Update the name of a tag.
$('.folksonomy .tag-name[contenteditable=true]')
    .focus(function() {
        var field = $(this);
        field.data('original-text', field.text());
    })
    .blur(function(e) {
        var field = $(this);
        var oldText = field.data('original-text');
        var newText = $.trim(field.text().replace(/\s+/g,' '));
        $.removeData(field, 'original-text');
        if (newText.length > 0 && newText !== oldText) {
            var url = field.data('update-url');
            $.post({
                url: url,
                data: {text: newText},
                beforeSend: function() {
                    field.text(newText);
                    field.addClass('o-icon-transmit');
                }
            })
            .done(function(data) {
                var row = field.closest('tr');
                field.text(data.content.text);
                field.data('update-url', data.content.urls.update);
                row.find('[name="resource_ids[]"]').val(data.content.escaped);
                row.find('.o-icon-delete').data('sidebar-content-url', data.content.urls.delete_confirm);
                row.find('.tag-browse-item-sets').prop('href', data.content.urls.item_sets);
                row.find('.tag-browse-items').prop('href', data.content.urls.items);
                row.find('.tag-browse-media').prop('href', data.content.urls.media);
            })
            .fail(function(jqXHR, textStatus) {
                var msg = jqXHR.hasOwnProperty('responseJSON')
                    && typeof jqXHR.responseJSON.error !== 'undefined'
                    ? jqXHR.responseJSON.error
                    : Omeka.jsTranslate('Something went wrong');
                alert(msg);
                field.text(oldText);
            })
            .always(function () {
                field.removeClass('o-icon-transmit');
                field.parent().focus();
            });
        } else {
            field.text(newText);
        }
    })
    .keydown(function(e) {
        if (e.keyCode === 13) {
            e.preventDefault();
        }
    })
    .keyup(function(e) {
        if (e.keyCode === 13) {
            $(this).blur();
        } else if (e.keyCode === 27) {
            var field = $(this);
            var oldText = field.data('original-text');
            $.removeData(field, 'original-text');
            field.text(oldText);
            field.parent().focus();
        }
    });

});
