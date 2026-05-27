<div class="spotlightr-page">
<h3>Quizzes</h3>
<br>
<div>
    <table id="spotlightr_quiz_table">
        <thead>
        <tr>
            <th class="spotlightr-quiz-thumbnail"></th>
            <th>Title</th>
            <th class="spotlightr-quiz-actions">Actions</th>
        </tr>
        </thead>
        <?php
        if ($this->quizzes) {
            foreach ($this->quizzes as $quiz) { ?>
                <tr>
                    <td>
                        <img src="<?php echo esc_url('https://thumbnails.spotlightr.com/video/image?id='. esc_attr($quiz['data']['video']['id'])) ?>" height="40">
                    </td>
                    <td>
                        <span class="spotlightr-quiz-title"><?php echo esc_html($quiz['name']) ?></span>
                    </td>
                    <td class="spotlightr-quiz-actions">
                        <span><a title="Edit on Spotlighr" href="<?php echo esc_url('https://projects.spotlightr.com/quiz/' . base64_encode(esc_attr($quiz['id']))) ?>"
                                 target="_blank"><i class="dashicons dashicons-edit"></i></a></span>
                        <span class="spotlightr-quiz-publish" data-encode-id="<?php echo esc_attr(base64_encode($quiz['id'])) ?>" data-toggle="modal" data-target="#spotlightr_quiz_modal"><i
                                class="dashicons dashicons-admin-links"></i></span>
                    </td>
                </tr>

            <?php }
        } else { ?>
            <tr>
                <td colspan="4"><h3>You don't have quizzes</h3></td>
            </tr>
        <?php } ?>
    </table>
    <div class="modal fade spotlightr-modal-fade" id="spotlightr_quiz_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog spotlightr-modal-dialog" role="document">
            <div class="modal-content spotlightr-modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myModalLabel">Publish Options</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body spotlightr-modal-body spotlightr-quiz-modal-body">
                    <div class="spotlightr-modal-shortcode-wraper">
                        <h5>Shortcode</h5>
                        <span class="spotlightr-quiz-modal-shortcode">[spotlightr-q subdomain="<?php echo esc_attr(get_option('spotlightr_subdomain')) ?>" id="" a=""]</span>
                        <div class="spotlightr-copy-wrapper">
                          <i id="spotlightr_copy_shortcode" class="dashicons dashicons-admin-page copy"></i>
                        </div>
                        <span id="spotlightr_msg_copied" style="display: none; color: green">Copied</span>
                    </div>
                    <hr/>
                    <div>
                        <label for="spotlightr_quiz_aspect_checkbox">Custom aspect ratio</label>
                        <input type="checkbox" id="spotlightr_quiz_aspect_checkbox" class="checkbox">
                        <div class="spotlightr-quiz-aspect-select-wrapper" style="display: none">
                            <label for="spotlightr_quiz_aspect_select">Aspect size Presets</label>
                            <select id="spotlightr_quiz_aspect_select" name="quiz_aspect_select">
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
