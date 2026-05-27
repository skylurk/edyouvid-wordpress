jQuery(document).ready(function ($) {
    let galleryId;
    let shortcode = $('.spotlightr-gallery-modal-shortcode').html();

    $('.spotlightr-gallery-publish').on('click', function () {
        galleryId = $(this).data('encodeId')
        $('.spotlightr-gallery-modal-shortcode').html(shortcode.replace('id=""', 'id="' + galleryId + '"'));
        if (!navigator.clipboard || !window.isSecureContext) {
            $('#spotlightr_copy_shortcode').hide();
        }
    });

    $('#spotlightr_copy_shortcode').on('click', function (event) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText($('.spotlightr-gallery-modal-shortcode').html()).then(() => {
                $('#spotlightr_msg_copied').show();
                setTimeout(
                    function () {
                        $('#spotlightr_msg_copied').hide();
                    },
                    2000
                )
            })
        }
    });
});
