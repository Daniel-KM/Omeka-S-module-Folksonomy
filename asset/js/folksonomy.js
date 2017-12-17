$(document).ready(function() {
    function taggingValidate(form) {
        var button = form.find('button');
        var field = form.find('[name="o-module-folksonomy:tag-new"]');
        var text = $.trim(field.val().replace(/\s+/g,' '));
        if (text.length == 0) {
            field.val('');
            return;
        }
        var isOmeka = typeof Omeka !== 'undefined' && typeof Omeka.jsTranslate !== 'undefined';
        var legalAgreement = form.find('[name="legal_agreement"]');
        if (legalAgreement.length > 0 && !legalAgreement.prop('checked')) {
            var msg = 'You should accept the legal agreement.';
            alert(isOmeka ? Omeka.jsTranslate(msg) : msg)
            return;
        }
        var url = form.attr('action');
        $.post({
            url: url,
            data: form.serialize(),
            timeout: 30000,
            beforeSend: function() {
                button.removeClass('fa-tag').addClass('o-icon-transmit');
            }
        })
        .done(function (data) {
            if (!data.content) {
                msg = 'Something went wrong';
                if (isOmeka) msg = Omeka.jsTranslate(msg);
                alert(msg);
            } else {
                var msg = 'Data were added to the resource.';
                if (isOmeka) msg = Omeka.jsTranslate(msg);
                if (data.content.moderation) {
                    var msgTmp = 'They will be displayed definively when approved.';
                    msg += ' ' + (isOmeka ? Omeka.jsTranslate(msgTmp) : msgTmp);
                    alert(msg);
                    form.find('input[type=text]').val('');
                } else {
                    location.reload(true);
                }
            }
        })
        .fail(function(jqXHR, textStatus) {
            if (textStatus == 'timeout') {
                var msg = 'Request too long to process.';
                alert(isOmeka ? Omeka.jsTranslate(msg) : msg)
            } else if (jqXHR.status == 404) {
                var msg = 'The resource or the tag doesn’t exist.';
                alert(isOmeka ? Omeka.jsTranslate(msg) : msg);
            } else {
                var msg = jqXHR.hasOwnProperty('responseJSON')
                    && typeof jqXHR.responseJSON.error !== 'undefined'
                    ? jqXHR.responseJSON.error
                    : (isOmeka ? Omeka.jsTranslate('Something went wrong') : 'Something went wrong');
                alert(msg);
            }
        })
        .always(function () {
            button.removeClass('o-icon-transmit').addClass('fa-tag');
        });
    }

    $('.tagging-form').submit(function(e) {
        e.preventDefault();
        taggingValidate($(this));
    });

    $('.tagging-form button').on('click', function(e) {
        e.preventDefault();
        taggingValidate($(this).closest('form'));
    });
});
