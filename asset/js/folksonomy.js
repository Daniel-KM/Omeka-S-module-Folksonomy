(function() {
    $(document).ready(function() {
        $('body').on('click', '.folksonomy-tagging-add', function(e) {
            e.preventDefault();
            var button = $(this);
            var form = button.parent('form');
            var url = button.attr('data-url');
            $.ajax({
                url: url,
                dataType: 'json',
                data: form.serialize(),
                timeout: 30000,
                beforeSend: function() {
                    button.addClass('transmit');
                    button.find('span').removeClass('fa-tag');
                }
            })
            .done(function (data) {
                var msg = 'Data were added to the resource.' + ' ';
                if (data.moderation) {
                    msg += 'They will be displayed when approved.';
                } else {
                    msg += 'Reload page to see new tags.';
                }
                alert(msg);
                form.find('input[type=text]').val(' ');
            })
            .fail(function (data, errorString, error) {
                if (errorString == 'timeout') {
                    alert('Request too long to process.');
                } else {
                    alert(data.responseJSON.error);
                }
            })
            .always(function (data, status) {
                button.removeClass('transmit');
                button.find('span').addClass('fa-tag');
            });
        });
    });
})();
