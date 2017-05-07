(function() {
    $(document).ready(function() {
        $('body').on('click', '.folksonomy-tagging-add', function(e) {
            e.preventDefault();
            var button = $(this);
            var form = button.parent('form');
            var field = form.find('[name="o-module-folksonomy:tag-new"]');
            var text = $.trim(field.val().replace(/\s+/g,' '));
            if (text.length == 0) {
                field.val('');
                return;
            }
            if (!form.find('[name="legal_agreement"]').prop('checked')) {
                var msg = 'You must check the legal agreement.';
                var isOmeka = typeof Omeka !== 'undefined';
                alert(isOmeka ? Omeka.jsTranslate(msg) : msg)
                return;
            }
            var url = button.attr('data-url');
            $.post({
                url: url,
                data: form.serialize(),
                timeout: 30000,
                beforeSend: function() {
                    button.addClass('transmit');
                    button.find('span').removeClass('fa-tag');
                }
            })
            .done(function (data) {
                var isOmeka = typeof Omeka !== 'undefined';
                var msg = 'Data were added to the resource.';
                if (isOmeka) msg = Omeka.jsTranslate(msg);
                if (data.moderation) {
                    var msgTmp = 'They will be displayed when approved.';
                    msg += ' ' + (isOmeka ? Omeka.jsTranslate(msgTmp) : msgTmp);
                } else {
                    var msgTmp = 'Reload page to see new tags.';
                    msg += ' ' + (isOmeka ? Omeka.jsTranslate(msgTmp) : msgTmp);
                }
                alert(msg);
                form.find('input[type=text]').val(' ');
            })
            .fail(function(jqXHR, textStatus) {
                var isOmeka = typeof Omeka !== 'undefined';
                if (textStatus == 'timeout') {
                    var msg = 'Request too long to process.';
                    alert(isOmeka ? Omeka.jsTranslate(msg) : msg)
                } else {
                    var msg = jqXHR.hasOwnProperty('responseJSON')
                        && typeof jqXHR.responseJSON.error !== 'undefined'
                        ? jqXHR.responseJSON.error
                        : (isOmeka ? Omeka.jsTranslate('Something went wrong') : 'Something went wrong');
                    alert(msg);
                }
            })
            .always(function () {
                button.removeClass('transmit');
                button.find('span').addClass('fa-tag');
            });
        });
    });
})();
