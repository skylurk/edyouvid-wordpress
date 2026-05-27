<div class="spotlightr-page">
<h3>Videos</h3>
<br>
<div>
    <button id="spotlightr_upload_wraper" class="button spotlightr-button">Add new video</button>
    <button id="spotlightr_add_group_wraper" class="button spotlightr-button">Add new project</button>

</div>
<?php include('add-video.php'); ?>
<?php include('add-group.php'); ?>

<br>

<div>
    <?php
        foreach ($this->group_list as $group_id => $group_value) { ?>
            <div>
                <div class="spotlightr-group-row" data-id="<?php echo esc_attr($group_id) ?>">
                    <span class="spotlightr-group-title"><i
                            class="dashicons dashicons-open-folder"></i><?php echo esc_html($group_value['name']) ?></span>
                    <span class="spotlightr-group-video-count"><?php echo esc_html($group_value['numberOfVideos']) ?> videos</span>
                </div>
                <table class="spotlightr-video-row" style="display: none">
                </table>
            </div>
        <?php } ?>
    <div class="modal fade spotlightr-modal-fade" id="spotlightr_video_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog spotlightr-modal-dialog" role="document">
            <div class="modal-content spotlightr-modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myModalLabel">Publish Options</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body spotlightr-modal-body spotlightr-video-modal-body">
                    <div class="spotlightr-modal-shortcode-wraper">
                        <h5>Shortcode</h5>
                        <span class="spotlightr-video-modal-shortcode">[spotlightr-v subdomain="<?php echo esc_attr(get_option('spotlightr_subdomain')) ?>" id="" s="" e="" r="" a=""]</span>
                        <div class="spotlightr-copy-wrapper">
                          <i id="spotlightr_copy_shortcode" class="dashicons dashicons-admin-page copy"></i>
                        </div>
                        <span id="spotlightr_msg_copied" style="display: none; color: green">Copied</span>
                    </div>
                    <hr/>
                    <div>
                        <label for="spotlightr_video_aspect_checkbox">Custom aspect ratio</label>
                        <input type="checkbox" id="spotlightr_video_aspect_checkbox" class="checkbox">
                        <div class="spotlightr-video-aspect-select-wrapper" style="display: none">
                            <label for="spotlightr_video_aspect_select">Aspect size Presets</label>
                            <select id="spotlightr_video_aspect_select">
                                <?php foreach ($this->custom_aspects as $aspect) { ?>
                                    <option
                                        value="<?php echo esc_attr($aspect['value']) ?>" <?php echo esc_attr($aspect['value']) == 1.78 ? 'selected="selected"' : '' ?>><?php echo esc_html($aspect['label']) . '(' . esc_html($aspect['ratio']) . ')' ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <hr/>
                    <div>
                        <label for="spotlightr_video_time_checkbox">Custom start/end time</label>
                        <input type="checkbox" id="spotlightr_video_time_checkbox" class="checkbox">
                        <div class="spotlightr-video-time-select-wrapper" style="display: none">
                            <h6>Start time</h6>
                            <label for="spotlightr_start_hour">Hours</label>
                            <input id="spotlightr_start_hour" class="spotlightr-time-input" type="number" min="0" value="0">
                            <label for="spotlightr_start_minute">Minutes</label>
                            <input id="spotlightr_start_minute" class="spotlightr-time-input" type="number" min="0" value="0">
                            <label for="spotlightr_start_second">Seconds</label>
                            <input id="spotlightr_start_second" class="spotlightr-time-input" type="number" min="0" value="0">
                            <h6>End time</h6>
                            <label for="spotlightr_end_hour">Hours</label>
                            <input id="spotlightr_end_hour" class="spotlightr-time-input" type="number" min="0" value="0">
                            <label for="spotlightr_end_minute">Minutes</label>
                            <input id="spotlightr_end_minute" class="spotlightr-time-input" type="number" min="0" value="0">
                            <label for="spotlightr_end_second">Seconds</label>
                            <input id="spotlightr_end_second" class="spotlightr-time-input" type="number" min="0" value="0">
                        </div>
                    </div>

                    <div class="spotlightr-video-resolution-wraper" style="display: none">
                        <hr/>
                        <label for="spotlightr_video_resolution_checkbox">Custom resolution</label>
                        <input type="checkbox" id="spotlightr_video_resolution_checkbox" class="checkbox">
                        <div class="spotlightr-video-resolution-select-wrapper" style="display: none">
                            <label for="spotlightr_video_resolution_select">Resolution</label>
                            <select id="spotlightr_video_resolution_select">
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
