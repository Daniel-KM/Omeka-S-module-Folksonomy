$(document).ready(function() {

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

});
