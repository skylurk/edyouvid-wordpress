<div class="spotlightr-page">
<h3>Gallery</h3>
<br>
<div>
    <table id="spotlightr_gallery_table">
        <thead>
        <tr>
            <th class="spotlightr-gallery-thumbnail"></th>
            <th>Title</th>
            <th># of videos</th>
            <th class="spotlightr-gallery-actions">Actions</th>
        </tr>
        </thead>
        <?php
        if ($this->galleries) {
            foreach ($this->galleries as $gallery) {?>
                <tr>
                    <td>
                        <img src="<?php echo esc_url('https://thumbnails.spotlightr.com/video/image?id=' . esc_attr($gallery['content'][0]['videos'][0]['id'])) ?>" height="40">
                    </td>
                    <td>
                        <span class="spotlightr-gallery-title"><?php echo esc_html($gallery['name']) ?></span>
                    </td>
                    <td>
                        <span class="spotlightr-gallery-video-count"><?php echo esc_html($gallery['numberOfVideos']) ?></span>
                    </td>
                    <td class="spotlightr-gallery-actions">
                        <span><a title="Edit on Spotlightr" href="<?php echo esc_url('https://projects.spotlightr.com/gallery/' . base64_encode(esc_attr($gallery['id']))) ?>"
                                 target="_blank"><i class="dashicons dashicons-edit"></i></a></span>
                        <span class="spotlightr-gallery-publish" data-encode-id="<?php echo esc_attr(base64_encode($gallery['id'])) ?>" data-toggle="modal"
                              data-target="#spotlightr_gallery_modal"><i class="dashicons dashicons-admin-links"></i></span>
                    </td>
                </tr>

            <?php }
        } else { ?>
            <tr>
                <td colspan="4"><h3>You don't have galleries</h3></td>
            </tr>
        <?php } ?>
    </table>
    <div class="modal fade spotlightr-modal-fade" id="spotlightr_gallery_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog spotlightr-modal-dialog" role="document">
            <div class="modal-content spotlightr-modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myModalLabel">Publish Options</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body spotlightr-modal-body spotlightr-gallery-modal-body">
                    <div class="spotlightr-modal-shortcode-wraper">
                        <h5>Shortcode</h5>
                        <span class="spotlightr-gallery-modal-shortcode">[spotlightr-g subdomain="<?php echo esc_html(get_option('spotlightr_subdomain')) ?>" id=""]</span>
                        <div class="spotlightr-copy-wrapper">
                          <i id="spotlightr_copy_shortcode" class="dashicons dashicons-admin-page copy"></i>
                        </div>
                        <span id="spotlightr_msg_copied" style="display: none; color: green">Copied</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
