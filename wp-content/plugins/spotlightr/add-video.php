<div>
    <div id="spotlightr_upload_type" style="display: none">
        <h4>Upload from computer</h4>
        <input type="text" id="spotlightr_file_name" placeholder="Video name" value=""/>
        <select id="spotlightr_file_group">
            <option value="0">Video Project</option>
            <?php
            foreach ($this->group_list as $group_id => $group_value) { ?>
                print '<option value="<?php echo esc_attr($group_id) ?>"><?php echo esc_html($group_value['name']) ?></option>';
            <?php } ?>
        </select>
        <input id="spotlightr_video_upload_input" type="file" style="display: inline"/><br><br>
        <button class="spotlightr-upload-file-submit spotlightr-upload-submit button spotlightr-button">Upload Video</button>
        <img
            src='<?php echo esc_url($this->plugin_url . '/resources/img/loading.gif')?>' id='spotlightr_upload_file_wait'
            class="spotlightr-wait" style='display:none;'>
        <div id="spotlightr_file_msg" style="color: red; margin-top:10px;" class="spotlightr-msg"></div>
        <br/>
        <div id="uploadProgressDivFile" style="display:none">
            <progress id="uploadProgressFile" value="" max="100"></progress>
            <span id="uploadPercentageFile"></span>
        </div>
        <hr>
        <h4>Upload from Media library or custom link</h4>
        <input class="" type="text" id="spotlightr_ml_file_name" placeholder="Video name" value=""/>

        <select id="spotlightr_ml_group">
            <option value="0">Video Project</option>
            <?php
            foreach ($this->group_list as $group_id => $group_value) { ?>
                print '<option value="<?php echo esc_attr($group_id) ?>"><?php echo esc_html($group_value['name']) ?></option>';
            <?php } ?>
        </select>
        <input class="" type="text" id="spotlightr_ml_file_url" placeholder="Video url" value=""/>
        <button type="button" class="button spotlightr-doc-upload-dialog">Select from Media
        </button>
        <br>
        <br>
        <button class="spotlightr-upload-url-submit spotlightr-upload-submit button spotlightr-button">Add Video</button>
        <img
            src='<?php echo esc_url( $this->plugin_url .'/resources/img/loading.gif')?>' id='spotlightr_upload_url_wait'
            class="spotlightr-wait" style='display:none;'>
        <div id="spotlightr_url_msg" style="color: red; margin-top:10px;" class="spotlightr-msg"></div>
        <br/>
        <div id="spotlightr_uploadProgressDivUrl" style="display:none">
            <progress id="spotlightr_uploadProgressUrl" value="" max="100"></progress>
            <span id="spotlightr_uploadPercentageUrl"></span>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function ($) {
        let uploadType = $('#spotlightr_upload_type');



        $(document).on('click', '.spotlightr-doc-upload-dialog', function (e) {
            e.preventDefault();

            var customUploader = wp.media({
                title: 'Select Media',
                multiple: false,
                library: {
                    type: 'video',
                }
            })
                .on('select', function () {
                    var attachment = customUploader.state().get('selection').first().toJSON();
                    $('#spotlightr_ml_file_url').val(attachment.url);
                })
                .open();
        });

        $(document).on('click', '#spotlightr_upload_wraper', function (e) {
            if (uploadType.is(":visible")) {
                uploadType.hide();
                $('#spotlightr_upload_wraper').removeClass('spotlightr-selected-button');
            } else {
                uploadType.show();
                $('#spotlightr_upload_wraper').addClass('spotlightr-selected-button');
                $('#spotlightr_add_group_container').hide();
                $('#spotlightr_add_group_wraper').removeClass('spotlightr-selected-button');
            }
        });

        $(document).on('click', '.spotlightr-upload-submit', function () {
            let sourceType;
            let name;
            let files;
            let group;
            let videoUrl;
            let errorMsg;
            let apiUrl = 'https://api.spotlightr.com/api/createVideo';
            let uploadProgressDiv;
            let uploadProgress;
            let uploadPercentage;

            if ($(this).hasClass('spotlightr-upload-file-submit')) {
                sourceType = 'file';
                $("#spotlightr_upload_file_wait").show();
                name = $('#spotlightr_file_name').val();
                group = $('#spotlightr_file_group').val();
                files = $('#spotlightr_video_upload_input').prop('files');
                errorMsg = $('#spotlightr_file_msg');
                uploadProgressDiv = $('#uploadProgressDivFile');
                uploadProgress = $('#uploadProgressFile');
                uploadPercentage = $('#uploadPercentageFile');
            } else {
                sourceType = 'url';
                $("#spotlightr_upload_url_wait").show();
                name = $('#spotlightr_ml_file_name').val();
                group = $('#spotlightr_ml_group').val();
                videoUrl = $('#spotlightr_ml_file_url').val();
                errorMsg = $('#spotlightr_url_msg');
                uploadProgressDiv = $('#spotlightr_uploadProgressDivUrl');
                uploadProgress = $('#spotlightr_uploadProgressUrl');
                uploadPercentage = $('#spotlightr_uploadPercentageUrl');
            }

            const formData = new FormData();
            formData.append('vooKey', '<?php echo esc_html(get_option('spotlightr_api_key'))?>');
            formData.append('name', name);
            formData.append('videoGroup', group);
            formData.append('create', 1);
            formData.append('hls', 1);
            if (files && files.length > 0) {
                formData.append('file', files[0]);
            }
            if (videoUrl) {
                formData.append('URL', videoUrl);
            }

            if (name && ((files && files.length > 0) || videoUrl)) {
                uploadProgressDiv.show();
                let config = {
                    onUploadProgress: function (progressEvent) {
                        let percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);

                        uploadProgress.val(percentCompleted)
                        uploadPercentage.html(percentCompleted + "%")

                    }
                };

                axios.post(apiUrl, formData, config)
                    .then(function (res) {
                        uploadProgressDiv.hide()
                        uploadPercentage.html('')
                        uploadProgress.val('');
                        $("#spotlightr_upload_file_wait").hide();
                        $("#spotlightr_upload_url_wait").hide();
                        if (res.statusText === 'OK') {
                            location.reload();
                        } else {
                            errorMsg.html(res.statusText);
                        }
                    })
                    .catch(function (err) {
                        uploadProgressDiv.hide()
                        $("#spotlightr_upload_file_wait").hide();
                        $("#spotlightr_upload_url_wait").hide();
                        errorMsg.html(err.message);
                    });
            } else {
                $("#spotlightr_upload_file_wait").hide();
                $("#spotlightr_upload_url_wait").hide();
                errorMsg.html('Fill all fields');
            }
        });
    });

</script>
