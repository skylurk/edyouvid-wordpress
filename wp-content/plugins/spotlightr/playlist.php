<div class="spotlightr-page">
<h3>Playlists</h3>
<br>
<div>
    <table id="spotlightr_playlist_table">
        <thead>
        <tr>
            <th class="spotlightr-playlist-thumbnail"></th>
            <th>Title</th>
            <th># of videos</th>
            <th class="spotlightr-playlist-actions">Actions</th>
        </tr>
        </thead>
        <?php
        if ($this->playlists) {
            foreach ($this->playlists as $playlist) { ?>
                <tr>
                    <td>
                        <img src="<?php echo esc_url('https://thumbnails.spotlightr.com/video/image?id=' . esc_attr($playlist['data']['videos'][0]['id'])) ?>" height="40">
                    </td>
                    <td>
                        <span class="spotlightr-playlist-title"><?php echo esc_html($playlist['name']) ?></span>
                    </td>
                    <td>
                        <span class="spotlightr-playlist-video-count"><?php echo is_array($playlist['data']['videos']) ? count($playlist['data']['videos']) : '0' ?></span>
                    </td>
                    <td class="spotlightr-videolist-actions">
                        <span><a title="Edit on Spotlightr" href="<?php echo esc_url('https://projects.spotlightr.com/playlist/' . base64_encode(esc_attr($playlist['id']))) ?>"
                                 target="_blank"><i class="dashicons dashicons-edit"></i></a></span>
                        <span class="spotlightr-playlist-publish" data-encode-id="<?php echo esc_attr(base64_encode($playlist['id'])) ?>" data-toggle="modal" data-target="#spotlightr_playlist_modal"><i
                                class="dashicons dashicons-admin-links"></i></span>
                    </td>
                </tr>

            <?php }
        } else { ?>
            <tr>
                <td colspan="4"><h3>You don't have playlists</h3></td>
            </tr>
        <?php } ?>
    </table>
    <div class="modal fade spotlightr-modal-fade" id="spotlightr_playlist_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog spotlightr-modal-dialog" role="document">
            <div class="modal-content spotlightr-modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myModalLabel">Publish Options</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body spotlightr-modal-body spotlightr-playlist-modal-body">
                    <div class="spotlightr-modal-shortcode-wraper">
                        <h5>Shortcode</h5>
                        <span class="spotlightr-playlist-modal-shortcode">[spotlightr-p subdomain="<?php echo esc_attr(get_option('spotlightr_subdomain')) ?>" id="" a=""]</span>
                        <div class="spotlightr-copy-wrapper">
                          <i id="spotlightr_copy_shortcode" class="dashicons dashicons-admin-page copy"></i>
                        </div>
                        <span id="spotlightr_msg_copied" style="display: none; color: green">Copied</span>
                    </div>
                    <hr/>
                    <div>
                        <label for="spotlightr_playlist_aspect_checkbox">Custom aspect ratio</label>
                        <input type="checkbox" id="spotlightr_playlist_aspect_checkbox" class="checkbox">
                        <div class="spotlightr-playlist-aspect-select-wrapper" style="display: none">
                            <label for="spotlightr_playlist_aspect_select">Aspect size Presets</label>
                            <select id="spotlightr_playlist_aspect_select">
                                <?php foreach ($this->custom_aspects as $aspect) { ?>
                                    <option
                                        value="<?php echo esc_attr($aspect['value']) ?>" <?php echo esc_attr($aspect['value']) == 1.78 ? 'selected="selected"' : '' ?>><?php echo esc_html($aspect['label']) . '(' . esc_html($aspect['ratio']) . ')' ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
