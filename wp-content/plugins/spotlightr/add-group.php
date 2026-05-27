<div>
    <div id="spotlightr_add_group_container" style="display: none">
        <h4>Enter new Project name</h4>
            <input type="text" id="spotlightr_new_group_name" placeholder="Project name" value=""/>
            <button type="button" id="spotlightr_add_group_submit" class="button spotlightr-button">Add Project</button>
            <img
                src='<?php echo esc_url($this->plugin_url . '/resources/img/loading.gif')?>' id='spotlightr_add_group_wait'
                class="spotlightr-wait" style='display:none;'>
            <div id="spotlightr_group_msg" style="color: red; margin-top:10px;" class="spotlightr-msg"></div>
    </div>
</div>
<script>
    jQuery(document).ready(function ($) {
        let addGroup = $('#spotlightr_add_group_container');

        $(document).on('click', '#spotlightr_add_group_wraper', function (e) {
            if (addGroup.is(":visible")) {
                addGroup.hide();
                $('#spotlightr_add_group_wraper').removeClass('spotlightr-selected-button');
            } else {
                addGroup.show();
                $('#spotlightr_add_group_wraper').addClass('spotlightr-selected-button');
                $('#spotlightr_upload_type').hide();
                $('#spotlightr_upload_wraper').removeClass('spotlightr-selected-button');
            }
        });

        $(document).on('click', '#spotlightr_add_group_submit', function (e) {
            $("#spotlightr_add_group_wait").show();
            let newGroupName = $('#spotlightr_new_group_name').val();
            if (newGroupName == '') {
                $("#spotlightr_add_group_wait").hide();
                $('#spotlightr_group_msg').html('Name field is required')
            } else {
                $.ajax({
                    url: "<?php echo esc_url(get_site_url())?>"+"/wp-admin/admin-ajax.php",
                    method: 'POST',
                    data: {
                        action: 'spotlightr_ajax_add_group',
                        name: newGroupName,
                    },
                    success: function (response) {
                        if (response == 'success') {
                            $('#spotlightr_group_msg').html('Group created');
                            $("#spotlightr_add_group_wait").hide();
                            location.reload();
                        } else {
                            $('#spotlightr_group_msg').html(response);
                            $("#spotlightr_add_group_wait").hide();
                        }
                    }
                });
            }
        });
    });
</script>
