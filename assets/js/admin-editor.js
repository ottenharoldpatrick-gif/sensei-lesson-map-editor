jQuery(document).ready(function($) {
    $('#slme-save').on('click', function() {
        $.post(slmeAjax.ajaxurl, {
            action: 'slme_save',
            nonce: slmeAjax.nonce,
            layout: $('#slme-layout').val()
        }, function(response) {
            alert(response.data.message);
        });
    });

    $('#slme-reset').on('click', function() {
        $.post(slmeAjax.ajaxurl, {
            action: 'slme_reset',
            nonce: slmeAjax.nonce
        }, function(response) {
            alert(response.data.message);
        });
    });
});
