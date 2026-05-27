/* eslint-disable no-undef, no-restricted-globals, no-alert, func-names */
function deleteSncFromTable(itemId, mode) {
  if (confirm('Do you really want to delete this?')) {
    const data = {
      action: 'SnC_Media_Delete',
      item_id: itemId,
      mode,
      security: jQuery('form.snc-media_enbed_form input[name="security"]').val(),
    };

    jQuery.post(ajaxurl, data, () => {
      if (mode === 'media library' || mode === 'vc') {
        jQuery(`#snc-content_library_wrap table tr[data-item_id="${itemId}"]`).remove();
      } else {
        location.reload();
      }
    });
  }
}

jQuery(document).ready(($) => {
  // <-- Lightbox Options
  $('.insert_type input[type="radio"]').click(function () {
    const key = $(this).attr('data-item_id');

    $(`form[data-item_id="${key}"] .options`).stop().slideUp();
    $(`form[data-item_id="${key}"] .options[data-item_option="${$(this).val()}"]`).stop().slideDown();
  });

  $('.lightbox_title input[type="radio"]').click(function () {
    const key = $(this).attr('data-item_id');
    const val = $(this).val();

    $(`input.text_with_title[data-item_id="${key}"]`).hide();

    if (val === 'With Title') {
      $(`input.text_with_title[data-item_id="${key}"]`).show().focus();
    }
  });

  $('.lightbox_button input[type="radio"]').click(function () {
    const key = $(this).attr('data-item_id');
    const val = $(this).val();

    $(`input.lightbox_button_text[data-item_id="${key}"]`).hide();
    $(`div.lightbox_button_text[data-item_id="${key}"]`).hide();
    $(`section.lightbox_button_custom[data-item_id="${key}"]`).hide();
    $(`input.lightbox_button_url[data-item_id="${key}"]`).hide();

    if (val === 'text' || val === 'small' || val === 'medium' || val === 'large') {
      $(`input.lightbox_button_text[data-item_id="${key}"]`).show();
      $(`div.lightbox_button_text[data-item_id="${key}"]`).show();
    }

    if (val === 'url') {
      $(`input.lightbox_button_url[data-item_id="${key}"]`).show();
    }

    if (val === 'image') {
      $(`.lightbox_button_custom[data-item_id="${key}"]`).show();
    }
  });

  $('input[name="global_lightbox"]').change(function () {
	const $parent = $(this).closest('.lightbox_size_options');
	const $widthSetting = $parent.find('.lightbox_width_settings');
	const $heightSetting = $parent.find('.lightbox_height_settings');
	if ( $(this).is(':checked') ) {
		$widthSetting.hide();
		$heightSetting.hide();
	} else {
		$widthSetting.show();
		$heightSetting.show();
	}
  });



  // <-- New Window Options
  $('.new_window_option input[type="radio"]').click(function () {
    const key = $(this).attr('data-item_id');
    const val = $(this).val();

    $(`.new_window_option[data-item_id="${key}"] input[type="text"]`).hide();
    $(`div._blank_button_text[data-item_id="${key}"]`).hide();
    $(`.new_window_option[data-item_id="${key}"] .snc_custom_image_upload`).hide();

    if (val === 'text' || val === 'small' || val === 'medium' || val === 'large') {
      $(`.new_window_option[data-item_id="${key}"] input._blank_text`).show();
      $(`div._blank_button_text[data-item_id="${key}"]`).show();
    }

    if (val === 'image') {
      $(`.new_window_option[data-item_id="${key}"] .snc_custom_image_upload`).show();
    }

    if (val === 'url') {
      $(`.new_window_option[data-item_id="${key}"] input._blank_url`).show();
    }
  });
  // New Window Options -->

  // <-- Same Window Options
  $('.same_window_option input[type="radio"]').click(function () {
    const key = $(this).attr('data-item_id');
    const val = $(this).val();

    $(`.same_window_option[data-item_id="${key}"] input[type="text"]`).hide();
    $(`div._self_button_text[data-item_id="${key}"]`).hide();
    $(`.same_window_option[data-item_id="${key}"] .snc_custom_image_upload`).hide();

    if (val === 'text' || val === 'small' || val === 'medium' || val === 'large') {
      $(`.same_window_option[data-item_id="${key}"] input._self_text`).show();
      $(`div._self_button_text[data-item_id="${key}"]`).show();
    }

    if (val === 'image') {
      $(`.same_window_option[data-item_id="${key}"] .snc_custom_image_upload`).show();
    }

    if (val === 'url') {
      $(`.same_window_option[data-item_id="${key}"] input._self_url`).show();
    }
  });
  // Same Window Options -->

  // <-- Delete
  $('form.snc-media_enbed_form .delete-media, #snc-content_library_wrap table span a.delete').click(function (e) {
    e.preventDefault();

    const mode = ($('#snc-content_library_wrap').length) ? 'media library' : 'upload form';
    const itemId = $(this).attr('data-item_id');

    deleteSncFromTable(itemId, mode);
  });
  // Delete -->


  // <-- Custom Image Upload.
  $('.snc_custom_image_upload button').click(function (e) {
	e.preventDefault();
	const $parent = $(this).closest('.snc_custom_image_upload');
	const $fileInput = $parent.find('input[type="file"]');
	$fileInput.click();
  });

  $('.snc_custom_image_upload input[type="file"]').on("change", function(){
	const $parent = $(this).closest('.snc_custom_image_upload');
	const $desc = $parent.find('button .button-description');
	var fn = $(this).val();
	$desc.text( fn.match(/[^\\/]*$/)[0] );
  });
// Custom Image Upload. -->

});
