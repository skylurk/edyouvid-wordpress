jQuery(document).ready(function ($) {

    document.addEventListener("bind_spotlightr", function(event) {

        let aspectSelectValue = '';
        let encodeId;
        let endHour;
        let endMinute;
        let endSecond;
        let endTime = 0;
        let longcode;
        let longcodeExample = jQuery(document).find('.'+uid+'-block-modal-longcode').text();
        let resolutionArray;
        let resolutionData;
        let resolutionSelectValue = '';
        let startHour;
        let startMinute;
        let startSecond;
        let startTime = 0;
        let subdomain;

        jQuery(document).on('click', '.spotlightr-block-publish', function () {
			const newuid = jQuery('.modal')[0]
			jQuery(newuid).modal('show')
            encodeId = jQuery(this).data('encodeId');
            resolutionData = jQuery(this).data('resolution');
            subdomain = jQuery(this).data('subdomain');
			longcodeExample = jQuery(document).find('.'+uid+'-block-modal-longcode').text();
            longcode = longcodeExample.replaceAll('encode_id', encodeId).replaceAll('subdomain_name', subdomain);
            jQuery('.'+uid+'-block-modal-longcode').text(longcode.replaceAll('aspect', 'aspect=').replaceAll('start_time', 's=').replaceAll('end_time', 'e=').replaceAll('resolution', 'resolution='));

            if (resolutionData !== 'empty' && resolutionData) {
                if (resolutionData.toString().indexOf(',') > 0) {
                    resolutionArray = resolutionData.toString().split(',');
                } else {
                    resolutionArray = resolutionData.toString().split();
                }

                jQuery(document).find('#'+uid+' .block-resolution-wraper').show();
                jQuery.each(resolutionArray, function (key, value) {
                    jQuery(document).find('#'+uid+'_block_resolution_select')
                        .append(jQuery("<option></option>")
                            .attr("value", value)
                            .text(value));
                });
            }
        });

        // custom aspect
        jQuery(document).on('click', '#'+uid+'_block_aspect_checkbox', function () {
            if (jQuery(this).is(':checked')) {
                jQuery(document).find('#'+uid+' .spotlightr-block-aspect-select-wrapper').show();
                aspectSelectValue = jQuery(document).find('#'+uid+'_block_aspect_select').val();
                updateLongCode();
            } else {
                jQuery(document).find('#'+uid+' .spotlightr-block-aspect-select-wrapper').hide();
                aspectSelectValue = '';
                updateLongCode();
            }
        });

        jQuery(document).on('change', '#'+uid+'_block_aspect_select', function () {
            aspectSelectValue = jQuery(document).find('#'+uid+'_block_aspect_select').val();
            updateLongCode();
        });

        // start/end time
        jQuery(document).on('click', '#'+uid+'_block_time_checkbox', function () {
            if (jQuery(this).is(':checked')) {
                jQuery(document).find('#'+uid+' .block-time-select-wrapper').show();
                countTime();
                updateLongCode();
            } else {
                jQuery(document).find('#'+uid+' .block-time-select-wrapper').hide();
                startTime = 0;
                endTime = 0;
                resetTime();
                updateLongCode();
            }
        });

        jQuery(document).on('change', '#'+uid+' .spotlightr-time-input', function () {
            countTime();
            updateLongCode();
        });

        function resetTime() {
            jQuery(document).find('#'+uid+' .spotlightr-time-input').val(0);
            countTime();
        }

        function countTime() {

            startHour = jQuery(document).find('#'+uid+'_start_hour').val();
            startMinute = jQuery(document).find('#'+uid+'_start_minute').val();
            startSecond = jQuery(document).find('#'+uid+'_start_second').val();
            endHour = jQuery(document).find('#'+uid+'_end_hour').val();
            endMinute = jQuery(document).find('#'+uid+'_end_minute').val();
            endSecond = jQuery(document).find('#'+uid+'_end_second').val();

            startTime = startHour * 3600 + startMinute * 60 + startSecond * 1;
            endTime = endHour * 3600 + endMinute * 60 + endSecond * 1;
        }

        // custom resolution
        jQuery(document).on('click', '#'+uid+'_block_resolution_checkbox', function () {
            if (jQuery(this).is(':checked')) {
                jQuery(document).find('#'+uid+' .block-resolution-select-wrapper').show();
                resolutionSelectValue = jQuery(document).find('#'+uid+'_block_resolution_select').val();
                updateLongCode();
            } else {
                jQuery(document).find('#'+uid+' .block-resolution-select-wrapper').hide();
                resolutionSelectValue = '';
                updateLongCode();
            }
        });

        jQuery(document).on('change', '#'+uid+'_block_resolution_select', function () {
            resolutionSelectValue = jQuery(document).find('#'+uid+'_block_resolution_select').val();
            updateLongCode();
        });

        function updateLongCode() {
            jQuery(document).find('.'+uid+'-block-modal-longcode').text(longcode.replaceAll('aspect', aspectSelectValue === '' ? 'aspect=' : 'aspect=' + aspectSelectValue).replaceAll('start_time', startTime === 0 ? 's=' : 's=' + startTime).replaceAll('end_time', endTime === 0 ? 'e=' : 'e=' + endTime).replaceAll('resolution', resolutionSelectValue === '' ? 'resolution=' : 'resolution=' + resolutionSelectValue));
        }

        jQuery(document).on('hidden.bs.modal', '#'+uid+'_block_modal', function () {
            jQuery(document).find('#'+uid+'_block_aspect_checkbox').prop('checked', false);
            jQuery(document).find('#'+uid+'_block_time_checkbox').prop('checked', false);
            jQuery(document).find('#'+uid+'_block_resolution_checkbox').prop('checked', false);
            resetTime();
            aspectSelectValue = '';
            resolutionSelectValue = '';
            jQuery(document).find('#'+uid+'_block_resolution_select').find('option').remove();
            jQuery(document).find('#'+uid+' .block-resolution-wraper').hide();
            jQuery(document).find('#'+uid+' .block-resolution-select-wrapper').hide();
            jQuery(document).find('#'+uid+' .spotlightr-block-aspect-select-wrapper').hide();
            jQuery(document).find('#'+uid+' .block-time-select-wrapper').hide();
        })
    });
});
