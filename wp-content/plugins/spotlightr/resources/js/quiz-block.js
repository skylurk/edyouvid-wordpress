jQuery(document).ready(function ($) {

    document.addEventListener("bind_spotlightr_quiz", function(event) {
        let quizEncodeId;
        let quizLongcode;
        let quizLongcodeExample = '<script src="https://subdomain_name.cdn.spotlightr.com/assets/spotlightr.js"></script><iframe ' +
            'allow="autoplay" class="video-player-container spotlightr" data-playerid="encode_id" allowtransparency="true" style="max-width:100%" ' +
            'name="videoPlayerframe" allowfullscreen="true" src="https://subdomain_name.cdn.spotlightr.com/watch/quiz/encode_id?aspect" ' +
            'watch-type="" url-params="aspect" frameborder="0" scrolling="no"></iframe>';
        let quizSubdomain;

        jQuery(document).off('click').on('click', '#'+uid_q+'_spotlightr_block_publish', function () {
            jQuery('#'+uid_q+'_block_modal').modal('show');
            quizEncodeId = $(this).data('encodeId');
            quizSubdomain = $(this).data('subdomain');
            quizLongcode = quizLongcodeExample.replaceAll('encode_id', quizEncodeId).replaceAll('subdomain_name', quizSubdomain);
            jQuery(document).find('#'+uid_q+'_block_modal_longcode').text(quizLongcode);
        });
    });
});
