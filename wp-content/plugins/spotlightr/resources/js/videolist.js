jQuery(document).ready(function ($) {

    $('.spotlightr-group-row').on('click', function (e) {
        e.preventDefault();
		e.stopPropagation();
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
                                return ' <tr>' +
                                    '               <td class="spotlightr-videolist-thumbnail"><img src="' + item.thumbnail + '" height="40"></td>' +
                                    '               <td class="spotlightr-videolist-title"><span>' + item.name + '</span></td>' +
                                    '               <td class="spotlightr-videolist-plays">' +
                                    '               </td>' +
                                    '               <td class="spotlightr-videolist-actions">' +
                                    '                   <span><a title="Edit on Spotlightr" href="https://projects.spotlightr.com/video/' + btoa(item.id) + '"' +
                                    '                   target="_blank"><i class="dashicons dashicons-edit"></i></a></span>' +
                                    '                   <span class="spotlightr-video-publish" data-id="' + item.id + '"' +
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

    let aspectCheckbox = $('#spotlightr_video_aspect_checkbox');
    let aspectSelect = $('#spotlightr_video_aspect_select');
    let aspectSelectValue = '';
    let aspectSelectWrapper = $('.spotlightr-video-aspect-select-wrapper');
    let encodeId;
    let endHour;
    let endMinute;
    let endSecond;
    let endTime = 0;
    let resolutionArray;
    let resolutionCheckbox = $('#spotlightr_video_resolution_checkbox');
    let resolutionData;
    let resolutionSelect = $('#spotlightr_video_resolution_select');
    let resolutionSelectValue = '';
    let resolutionSelectWrapper = $('.spotlightr-video-resolution-select-wrapper');
    let resolutionWrapper = $('.spotlightr-video-resolution-wraper');
    let shortcode;
    let shortcodeExample = $('.spotlightr-video-modal-shortcode').html();
    let startHour;
    let startMinute;
    let startSecond;
    let startTime = 0;
    let timeCheckbox = $('#spotlightr_video_time_checkbox');
    let timeWrapper = $('.spotlightr-video-time-select-wrapper');

    $(document).on('click', '.spotlightr-video-publish', function () {
        if (!navigator.clipboard || !window.isSecureContext) {
            $('#spotlightr_copy_shortcode').hide();
        }
        encodeId = $(this).data('encodeId');
        resolutionData = $(this).data('resolution');
        shortcode = shortcodeExample.replace('id=""', 'id="' + encodeId + '"');
        $('.spotlightr-video-modal-shortcode').html(shortcode.replace('a=""', '').replace('s=""', '').replace('e=""', '').replace('r=""', ''));

        if (resolutionData !== 'empty' && resolutionData) {
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
    });

    // custom aspect
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

    // start/end time
    $(timeCheckbox).on('click', function () {
        if ($(timeCheckbox).is(':checked')) {
            timeWrapper.show();
            countTime();
            updateShortCode();
        } else {
            timeWrapper.hide();
            startTime = 0;
            endTime = 0;
            resetTime();
            updateShortCode();
        }
    });

    $('.spotlightr-time-input').on('change', function () {
        countTime();
        updateShortCode();
    });

    function resetTime() {
        $('.spotlightr-time-input').val(0);
        countTime();
    }

    function countTime() {
        startHour = $('#spotlightr_start_hour').val();
        startMinute = $('#spotlightr_start_minute').val();
        startSecond = $('#spotlightr_start_second').val();
        endHour = $('#spotlightr_end_hour').val();
        endMinute = $('#spotlightr_end_minute').val();
        endSecond = $('#spotlightr_end_second').val();

        startTime = startHour * 3600 + startMinute * 60 + startSecond * 1;
        endTime = endHour * 3600 + endMinute * 60 + endSecond * 1;
    }

    // custom resolution
    $(resolutionCheckbox).on('click', function () {
        if ($(resolutionCheckbox).is(':checked')) {
            resolutionSelectWrapper.show();
            resolutionSelectValue = resolutionSelect.val();
            updateShortCode();
        } else {
            resolutionSelectWrapper.hide();
            resolutionSelectValue = '';
            updateShortCode();
        }
    });

    $(resolutionSelect).on('change', function () {
        resolutionSelectValue = resolutionSelect.val();
        updateShortCode();
    });

    function updateShortCode() {
        $('.spotlightr-video-modal-shortcode').html(shortcode.replace(' a=""', aspectSelectValue === '' ? '' : ' a="' + aspectSelectValue + '"').replace(' s=""', startTime === 0 ? '' : ' s="' + startTime + '"').replace(' e=""', endTime === 0 ? '' : ' e="' + endTime + '"').replace(' r=""', resolutionSelectValue === '' ? '' : ' r="' + resolutionSelectValue + '"'));
    }

    $('#spotlightr_video_modal').on('hidden.bs.modal', function () {
        $(aspectCheckbox).prop('checked', false);
        $(timeCheckbox).prop('checked', false);
        $(resolutionCheckbox).prop('checked', false);
        resetTime();
        aspectSelectValue = '';
        resolutionSelectValue = '';
        $(resolutionSelect).find('option').remove();
        resolutionWrapper.hide();
        resolutionSelectWrapper.hide();
        aspectSelectWrapper.hide();
        timeWrapper.hide();
    })

    $('#spotlightr_copy_shortcode').on('click', function (event) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText($('.spotlightr-video-modal-shortcode').html()).then(() => {
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
