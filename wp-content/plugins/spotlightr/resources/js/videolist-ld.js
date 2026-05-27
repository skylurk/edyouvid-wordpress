jQuery(document).ready(function ($) {

    $(document).on('click', '#spotlight_video_select_button', function (){
        prepareModal();

        let modalContent = $(document).find('#spotlightr-modal-body');
        let groups = '';
        if (spotlightrInfo.is_user_logged_in) {
        $.ajax({
            url: "admin-ajax.php",
            method: 'POST',
            data: {
                action: 'spotlightr_ajax_get_groups',
            },
            success: function (response) {
                if (response === "empty") {
                    groups = '<span>There are no Videos on this project</span>'

                } else {
                    let data = JSON.parse(response);
                    groups = '' +
                        Object.keys(data).map(function (key) {
                            let groupValue = data[key]
                            return '<div>' +
                                '                <div class="spotlightr-group-row-ld" data-id="' + key + '">' +
                                '                    <span class="spotlightr-group-title"><i' +
                                '                            class="dashicons dashicons-open-folder"></i>' + groupValue.name + '</span>' +
                                '                    <span class="spotlightr-group-video-count">' + groupValue.numberOfVideos + ' videos</span>' +
                                '                </div>' +
                                '                <table class="spotlightr-video-row spotlightr-video-row-ld" style="display: none">' +
                                '                </table>' +
                                '            </div>'
                        }).join('');
                }
                modalContent.html(groups);
            }
        });
        } else {
            modalContent.html('<div><p>You need to authorize to<a href="' + spotlightrInfo.plugin_url + '" target="_blank"> Spotlightr plugin</a></p></div>');
        }

    });


    $(document).on('click', '.spotlightr-group-row-ld', function () {
        let row = $(this);
        let groupId = row.data('id');
        if (row.hasClass('selected')) {
            row.removeClass('selected').siblings('table').html('').hide();
        } else {
            row.siblings('table').html('Loading').show()
            let videolist = '';
            $.ajax({
                url: "admin-ajax.php",
                method: 'POST',
                data: {
                    action: 'spotlightr_ajax_get_videos',
                    group: groupId,
                },
                success: function (response) {
                    if (response === "empty") {
                        videolist = '<tr><td colspan="4"><span>There are no Videos on this project</span></td></tr>'
                        row.addClass('selected').siblings('table').html(videolist).show();
                    } else {
                        let data = JSON.parse(response);
                        videolist = '' +
                            data.map(function (item) {
                                return ' <tr class="spotlightr-video-row spotlightr-video-row-ld" data-id="' + item.id + '">' +
                                    '               <td class="spotlightr-videolist-thumbnail"><img src="' + item.thumbnail + '" height="40"></td>' +
                                    '               <td class="spotlightr-videolist-title"><span>' + item.name + '</span></td>' +
                                    '               <td class="spotlightr-videolist-actions">' +
                                    '                   <span class="spotlightr-video-publish spotlightr-video-publish-ld" data-id="' + item.id + '" data-subdomain="' + spotlightrInfo.subdomain + '"' +
                                    '                   data-resolution="' + item.customResolution + '" data-encode-id="' + btoa(item.id) + '"' +
                                    '                   data-toggle="modal" data-target="#spotlightr_video_modal"><i class="dashicons dashicons-admin-links"></i></span>' +
                                    '               </td>' +
                                    '           </tr>'
                            }).join('');
                        row.addClass('selected').siblings('table').html(videolist).show();
                    }
                }
            });

        }
    });

    let publishOptionsExample = '<tr class="spotlightr-publishing-row-ld"><td class="spotlightr-publishing-wraper-ld" colspan="3"><div class="spotlightr-ld-modal-longcode-wraper" style="display: none">' +
        '                        <textarea class="spotlightr-ld-modal-longcode"><script src="https://subdomain_name.cdn.spotlightr.com/assets/spotlightr.js"></script><iframe ' +
        'allow="autoplay" class="video-player-container spotlightr" data-playerid="encode_id" allowtransparency="true" style="max-width:100%" ' +
        'name="videoPlayerframe" allowfullscreen="true" src="https://subdomain_name.cdn.spotlightr.com/watch/encode_id?start_time&end_time&aspect&resolution" ' +
        'watch-type="" url-params="start_time&end_time&aspect&resolution" frameborder="0" scrolling="no"></iframe></textarea>' +
        '                    </div>' +
        '                       <h5>You can choose additional options</h5>' +
        '                    <div>' +
        '                        <label for="spotlightr_ld_aspect_checkbox">Custom aspect ratio</label>' +
        '                        <input type="checkbox" id="spotlightr_ld_aspect_checkbox" class="checkbox">' +
        '                        <div class="spotlightr-ld-aspect-select-wrapper" style="display: none">' +
        '                            <h6>Select aspect ratio</h6>' +
        '                            <label for="spotlightr_ld_aspect_select">Aspect size Presets</label>' +
        '                            <select id="spotlightr_ld_aspect_select">' +
        Object.keys(spotlightrInfo.custom_aspects).map(function (key) {
            let value_custom_aspects = spotlightrInfo.custom_aspects[key]
            return '<option' +
                ' value="' + value_custom_aspects.value + '">' + value_custom_aspects.label + '(' + value_custom_aspects.ratio +') </option>'
        }).join('')+
        '                            </select>' +
        '                        </div>' +
        '                    </div>' +
        '                    <hr/>' +
        '                    <div>' +
        '                        <label for="spotlightr_ld_time_checkbox">Custom start/end time</label>' +
        '                        <input type="checkbox" id="spotlightr_ld_time_checkbox" class="checkbox">' +
        '                        <div class="spotlightr-ld-time-select-wrapper" style="display: none">' +
        '                            <h6>Start time</h6>' +
        '                            <label for="spotlightr_ld_start_hour">Hours</label>' +
        '                            <input id="spotlightr_ld_start_hour" class="spotlightr-time-input spotlightr-ld-time-input" type="number" min="0" value="0">' +
        '                            <label for="spotlightr_ld_start_minute">Minutes</label>' +
        '                            <input id="spotlightr_ld_start_minute" class="spotlightr-time-input spotlightr-ld-time-input" type="number" min="0" value="0">' +
        '                            <label for="spotlightr_ld_start_second">Seconds</label>' +
        '                            <input id="spotlightr_ld_start_second" class="spotlightr-time-input spotlightr-ld-time-input" type="number" min="0" value="0">' +
        '                            <h6>End time</h6>' +
        '                            <label for="spotlightr_ld_end_hour">Hours</label>' +
        '                            <input id="spotlightr_ld_end_hour" class="spotlightr-time-input spotlightr-ld-time-input" type="number" min="0" value="0">' +
        '                            <label for="spotlightr_ld_end_minute">Minutes</label>' +
        '                            <input id="spotlightr_ld_end_minute" class="spotlightr-time-input spotlightr-ld-time-input" type="number" min="0" value="0">' +
        '                            <label for="spotlightr_ld_end_second">Seconds</label>' +
        '                            <input id="spotlightr_ld_end_second" class="spotlightr-time-input spotlightr-ld-time-input" type="number" min="0" value="0">' +
        '                        </div>' +
        '                    </div>' +
        '                    <div class="spotlightr-ld-resolution-wraper" style="display: none">' +
        '                        <hr/>' +
        '                        <label for="spotlightr_ld_resolution_checkbox">Custom resolution</label>' +
        '                        <input type="checkbox" id="spotlightr_ld_resolution_checkbox" class="checkbox">' +
        '                        <div class="spotlightr-ld-resolution-select-wrapper" style="display: none">' +
        '                            <h6>Select resolution</h6>' +
        '                            <label for="spotlightr_ld_resolution_select">Resolution</label>' +
        '                            <select id="spotlightr_ld_resolution_select">' +
        '                            </select>' +
        '                        </div>' +
        '                    </div>' +
        '                    <hr/>' +
        '                    <button id="spotlightr_ld_copy_longcode" class="spotlightr-copy spotlightr-button">&nbsp; Publish Video &nbsp;</button><hr/> </td></tr>';

    let aspectCheckbox = $(document).find('#spotlightr_ld_aspect_checkbox');
    let aspectSelectValue = '';
    let aspectSelectWrapper = $(document).find('.spotlightr-ld-aspect-select-wrapper');
    let encodeId;
    let endHour;
    let endMinute;
    let endSecond;
    let endTime = 0;
    let longcode;
    let longcodeExample;
    let resolutionArray;
    let resolutionData;
    let resolutionCheckbox = $(document).find('#spotlightr_ld_resolution_checkbox');
    let resolutionSelect = $(document).find('#spotlightr_ld_resolution_select');
    let resolutionSelectValue = '';
    let resolutionWrapper = $(document).find('.spotlightr-ld-resolution-wraper');
    let startHour;
    let startMinute;
    let startSecond;
    let startTime = 0;
    let subdomain;
    let timeCheckbox = $(document).find('#spotlightr_ld_time_checkbox');

    $(document).on('click', '#spotlightr_ld_copy_longcode', function () {
        longcodeExample = $(document).find('.spotlightr-ld-modal-longcode').text();
        longcode = longcodeExample.replaceAll('encode_id', encodeId).replaceAll('subdomain_name', subdomain).replaceAll('aspect', aspectSelectValue === '' ? 'aspect=' : 'aspect=' + aspectSelectValue).replaceAll('start_time', startTime === 0 ? 's=' : 's=' + startTime).replaceAll('end_time', endTime === 0 ? 'e=' : 'e=' + endTime).replaceAll('resolution', resolutionSelectValue === '' ? 'resolution=' : 'resolution=' + resolutionSelectValue)
        let fieldLesson = $(document).find('#learndash-lesson-display-content-settings_lesson_video_url')
        let fieldTopic = $(document).find('#learndash-topic-display-content-settings_lesson_video_url')
        fieldLesson.val(longcode);
        fieldTopic.val(longcode);
        $(this).closest('#spotlightr_ldModal').first().modal('hide');
    })

    $(document).on('click', '.spotlightr-video-publish-ld', function () {
        if ($(this).hasClass('.spotlightr-video-publish-ld-selected')) {
            $(this).removeClass('.spotlightr-video-publish-ld-selected')
            $(document).find('tr.spotlightr-publishing-row-ld').remove();
        } else {
            $(this).addClass('.spotlightr-video-publish-ld-selected')

            encodeId = $(this).data('encodeId');
            resolutionData = $(this).data('resolution');
            subdomain = $(this).data('subdomain');

            $(document).find('tr.spotlightr-publishing-row-ld').remove();

            $(publishOptionsExample).insertAfter(this.closest('tr.spotlightr-video-row-ld'))

            aspectCheckbox = $(document).find('#spotlightr_ld_aspect_checkbox');
            aspectSelectWrapper = $(document).find('.spotlightr-ld-aspect-select-wrapper');
            resolutionWrapper = $(document).find('.spotlightr-ld-resolution-wraper');
            resolutionCheckbox = $(document).find('#spotlightr_ld_resolution_checkbox');
            timeCheckbox = $(document).find('#spotlightr_ld_time_checkbox');
            resolutionSelect = $(document).find('#spotlightr_ld_resolution_select');

            aspectSelectValue = '';
            endTime = 0;
            resolutionSelectValue = '';
            startTime = 0;
            resolutionWrapper.hide();
            $(aspectCheckbox).prop('checked', false);
            $(resolutionCheckbox).prop('checked', false);
            $(timeCheckbox).prop('checked', false);
            $(resolutionSelect).find('option').remove();

            if (resolutionData !== 'empty') {
                if (resolutionData.toString().indexOf(',') > 0) {
                    resolutionArray = resolutionData.toString().split(',');
                } else {
                    resolutionArray = resolutionData.toString().split();
                }
                resolutionWrapper.show();
                $.each(resolutionArray, function (key, value) {
                    $(resolutionSelect)
                        .append($("<option></option>")
                            .attr("value", value)
                            .text(value));
                });
            }
        }
    })

    $(document).on('click', '#spotlightr_ld_aspect_checkbox', function () {
        if ($(this).is(':checked')) {
            aspectSelectWrapper.show();
            aspectSelectValue = $(document).find('#spotlightr_ld_aspect_select').val();
        } else {
            aspectSelectWrapper.hide();
            aspectSelectValue = '';
        }
    });

    $(document).on('change', '#spotlightr_ld_aspect_select', function () {
        aspectSelectValue = $(this).val();
    });

    $(document).on('click', '#spotlightr_ld_time_checkbox', function () {
        if ($(this).is(':checked')) {
            $(document).find('.spotlightr-ld-time-select-wrapper').show();
            countTime();
        } else {
            $(document).find('.spotlightr-ld-time-select-wrapper').hide();
            startTime = 0;
            endTime = 0;
            resetTime();
        }
    });

    $(document).on('change', '.spotlightr-ld-time-input', function () {
        countTime();
    });

    function resetTime() {
        $(document).find('.spotlightr-ld-time-input').val(0);
        countTime();
    }

    function countTime() {
        startHour = $(document).find('#spotlightr_ld_start_hour').val();
        startMinute = $(document).find('#spotlightr_ld_start_minute').val();
        startSecond = $(document).find('#spotlightr_ld_start_second').val();
        endHour = $(document).find('#spotlightr_ld_end_hour').val();
        endMinute = $(document).find('#spotlightr_ld_end_minute').val();
        endSecond = $(document).find('#spotlightr_ld_end_second').val();

        startTime = startHour * 3600 + startMinute * 60 + startSecond * 1;
        endTime = endHour * 3600 + endMinute * 60 + endSecond * 1;
    }

    $(document).on('click', '#spotlightr_ld_resolution_checkbox', function () {
        if ($(this).is(':checked')) {
            $(document).find('.spotlightr-ld-resolution-select-wrapper').show();
            resolutionSelectValue = $(document).find('#spotlightr_ld_resolution_select').val();
        } else {
            $(document).find('.spotlightr-ld-resolution-select-wrapper').hide();
            resolutionSelectValue = '';
        }
    });

    $(document).on('change', '#spotlightr_ld_resolution_select', function () {
        resolutionSelectValue = $(this).val();
    });
})

function prepareModal() {

    var ldModal = getLdModal();

    // Init the modal if it hasn't been already.
    if (!ldModal) { ldModal = initLdModal(); }

    var html =
        '<div class="modal-header">' +
        '<h5 class="modal-title" id="spotlightr_ldModalLabel">Select Video</h5>' +
        '<button type="button" class="close" data-dismiss="modal" aria-label="Close">' +
        '<span aria-hidden="true">&times;</span>' +
        '</button>' +
        '</div>' +
        '<div class="modal-body" id="spotlightr-modal-body">' +
        'Please Wait...' +
        '</div>' +
        '<div class="modal-footer">' +
        '<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>' +
        '</div>';

    setLdModalContent(html);

    // Show the modal.
    jQuery(ldModal).modal('show');

}

function getLdModal() {
    return document.getElementById('spotlightr_ldModal');
}

function setLdModalContent(html) {
    getLdModal().querySelector('.spotlightr-modal-content').innerHTML = html;
}

function initLdModal() {
    var modal = document.createElement('div');
    modal.classList.add('modal', 'fade', 'spotlightr-modal-fade');
    modal.setAttribute('id', 'spotlightr_ldModal');
    modal.setAttribute('tabindex', '-1');
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-labelledby', 'spotlightr_ldModalLabel');
    modal.setAttribute('aria-hidden', 'true');
    modal.innerHTML =
        '<div class="modal-dialog spotlightr-modal-dialog" role="document">' +
        '<div class="modal-content spotlightr-modal-content" style="margin-top: 150px"></div>' +
        '</div>';
    document.body.appendChild(modal);
    return modal;
}
