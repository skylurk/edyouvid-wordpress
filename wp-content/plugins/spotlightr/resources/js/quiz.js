jQuery(document).ready(function ($) {
    let aspectCheckbox = $('#spotlightr_quiz_aspect_checkbox');
    let aspectSelect = $('#spotlightr_quiz_aspect_select');
    let aspectSelectValue = '';
    let aspectSelectWrapper = $('.spotlightr-quiz-aspect-select-wrapper');
    let shortcode;
    let shortcodeExample = $('.spotlightr-quiz-modal-shortcode').html();
    let quizId;

    $('.spotlightr-quiz-publish').on('click', function () {
        quizId = $(this).data('encodeId');
        shortcode = shortcodeExample.replace('id=""', 'id="' + quizId + '"');
        $('.spotlightr-quiz-modal-shortcode').html(shortcode.replace('a=""', ''));
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
        $('.spotlightr-quiz-modal-shortcode').html(shortcode.replace('a=""', aspectSelectValue === '' ? '' : 'a="' + aspectSelectValue + '"'));
    }

    $('#spotlightr_quiz_modal').on('hidden.bs.modal', function () {
        $(aspectCheckbox).prop('checked', false);
        aspectSelectWrapper.hide();
    })

    $('#spotlightr_copy_shortcode').on('click', function (event) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText($('.spotlightr-quiz-modal-shortcode').html()).then(() => {
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
