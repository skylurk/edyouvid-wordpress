jQuery(document).ready(function ($) {

    document.addEventListener("bind_spotlightr_playlist", function(event) {
        let playlistEncodeId;
        let playlistLongcode;
        let playlistLongcodeExample = '<script src="https://subdomain_name.cdn.spotlightr.com/assets/spotlightr.js"></script><iframe ' +
            'allow="autoplay" class="video-player-container spotlightr" data-playerid="encode_id" allowtransparency="true" style="max-width:100%" ' +
            'name="videoPlayerframe" allowfullscreen="true" src="https://subdomain_name.cdn.spotlightr.com/watch/playlist/encode_id" ' +
            'watch-type="" url-params="aspect" frameborder="0" scrolling="no"></iframe>';
        let playlistSubdomain;

        jQuery(document).off('click').on('click', '#'+uid_p+'_spotlightr_block_publish',function () {
            jQuery('#'+uid_p+'_block_modal').modal('show');
            playlistEncodeId = jQuery(this).data('encodeId');
            playlistSubdomain = jQuery(this).data('subdomain');
            playlistLongcode = playlistLongcodeExample.replaceAll('encode_id', playlistEncodeId).replaceAll('subdomain_name', playlistSubdomain);
            jQuery(document).find('#'+uid_p+'_block_modal_longcode').text(playlistLongcode);
        });
    });
});
