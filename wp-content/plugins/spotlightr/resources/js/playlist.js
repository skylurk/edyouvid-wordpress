jQuery(document).ready(function ($) {
    let aspectCheckbox = $('#spotlightr_playlist_aspect_checkbox');
    let aspectSelect = $('#spotlightr_playlist_aspect_select');
    let aspectSelectValue = '';
    let aspectSelectWrapper = $('.spotlightr-playlist-aspect-select-wrapper');
    let playlistId;
    let shortcode;
    let shortcodeExample = $('.spotlightr-playlist-modal-shortcode').html();

    $('.spotlightr-playlist-publish').on('click', function () {
        playlistId = $(this).data('encodeId')
        shortcode = shortcodeExample.replace('id=""', 'id="' + playlistId + '"');
        $('.spotlightr-playlist-modal-shortcode').html(shortcode.replace('a=""', ''));
        if (!navigator.clipboard || !window.isSecureContext) {
            $('#spotlightr_copy_shortcode').hide();
        }
    });

    $(aspectCheckbox).on('click', function () {
        if ($(aspectCheckbox).is(':checked')) {
            aspectSelectWrapper.show();
            aspectSelectValue = aspectSelect.val();
            updateShortCode();
        } else {
            aspectSelectWrapper.hide();
            aspectSelectValue = '';
            updateShortCode();
        }
    });

    $(aspectSelect).on('change', function () {
        aspectSelectValue = aspectSelect.val();
        updateShortCode();
    });

    function updateShortCode() {
        $('.spotlightr-playlist-modal-shortcode').html(shortcode.replace('a=""', aspectSelectValue === '' ? '' : 'a="' + aspectSelectValue + '"'));
    }

    $('#spotlightr_playlist_modal').on('hidden.bs.modal', function () {
        $(aspectCheckbox).prop('checked', false);
        aspectSelectWrapper.hide();
    })

    $('#spotlightr_copy_shortcode').on('click', function (event) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText($('.spotlightr-playlist-modal-shortcode').html()).then(() => {
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
