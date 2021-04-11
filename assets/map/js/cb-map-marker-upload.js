var cb_map_marker_upload = {
  translation: {},

  //based on: https://jeroensormani.com/how-to-include-the-wordpress-media-selector-in-your-plugin/
  init: function($, data) {
    // uploading files
  	var file_frame;
  	var wp_media_post_id = wp.media.model.settings.post.id; // Store the old id
  	var set_to_post_id = data.$custom_marker_media_id.val();

    data.$remove_image_button.on('click', function(event) {
      event.preventDefault();

      data.$custom_marker_media_id.val('');
      data.$image_preview.attr('src', '');

      data.$marker_icon_width.val(0);
      data.$marker_icon_height.val(0);
      data.$marker_icon_anchor_x.val(0);
      data.$marker_icon_anchor_y.val(0);

      data.$image_preview_settings.hide();
      data.$image_preview_measurements.text('');
      data.$marker_icon_size.hide();
      data.$marker_icon_anchor.hide();

    });

  	data.$select_image_button.on('click', function(event) {

  		event.preventDefault();
  		// if the media frame already exists, reopen it.
  		if ( file_frame ) {
  			// Set the post ID to what we want
  			file_frame.uploader.uploader.param( 'post_id', set_to_post_id );
  			// Open frame
  			file_frame.open();
  			return;

  		} else {
  			// set the wp.media post id so the uploader grabs the ID we want when initialised
  			wp.media.model.settings.post.id = set_to_post_id;
  		}

  		// create the media frame
  		file_frame = wp.media.frames.file_frame = wp.media({
  			title: cb_map_marker_upload.translation.SELECT_IMAGE,
  			button: {
  				text: cb_map_marker_upload.translation.SAVE,
  			},
  			multiple: false
  		});

  		// image select callback
  		file_frame.on( 'select', function() {
  			var attachment = file_frame.state().get('selection').first().toJSON();

  			// Do something with attachment.id and/or attachment.url here
  			data.$image_preview.attr( 'src', attachment.url ).css( 'width', 'auto' );
  			data.$custom_marker_media_id.val( attachment.id );
  			// restore the main post ID
  			wp.media.model.settings.post.id = wp_media_post_id;

        data.$marker_icon_width.val(0);
        data.$marker_icon_height.val(0);
        data.$marker_icon_anchor_x.val(0);
        data.$marker_icon_anchor_y.val(0);
  		});

  		// finally, open the modal
  		file_frame.open();
  	});

  	// restore the main ID when the add media button is pressed
  	$( 'a.add_media' ).on( 'click', function() {
  		wp.media.model.settings.post.id = wp_media_post_id;
  	});

    data.$image_preview.on('load', function() {

      data.$image_preview_settings.show();
      data.$image_preview_measurements.text(cb_map_marker_upload.translation.MARKER_IMAGE_MEASUREMENTS + ': ' + data.$image_preview.width() + ' x ' + data.$image_preview.height() + ' px');
      data.$marker_icon_size.show();
      data.$marker_icon_anchor.show();

      if(data.$marker_icon_width.val() == 0) {
        data.$marker_icon_width.val(data.$image_preview.width());
      }

      if(data.$marker_icon_height.val() == 0) {
        data.$marker_icon_height.val(data.$image_preview.height());
      }

      if(data.$marker_icon_anchor_x.val() == 0) {
        data.$marker_icon_anchor_x.val(data.$image_preview.width() / 2);
      }

      if(data.$marker_icon_anchor_y.val() == 0) {
        data.$marker_icon_anchor_y.val(data.$image_preview.height());
      }

    });

    //if parent details got opened, trigger load for cached images
    var $parent_details = data.$image_preview.closest('details');
    $parent_details.on('toggle', function() {
      var src = data.$image_preview.attr('src');
      if($parent_details.prop('open') == true && src.length > 0) {
        setTimeout(function() {
          data.$image_preview.load();
        }, 0);
      }
    });
  }
}

jQuery(document).ready(function($) {

  var marker_data = {
    $select_image_button: $('#select-marker-image-button'),
    $remove_image_button: $('#remove-marker-image-button'),
    $custom_marker_media_id: $('input[name="cb_map_options[custom_marker_media_id]"'),
    $image_preview: $('#marker-image-preview'),
    $marker_icon_width: $('input[name="cb_map_options[marker_icon_width]"'),
    $marker_icon_height: $('input[name="cb_map_options[marker_icon_height]"'),
    $marker_icon_anchor_x: $('input[name="cb_map_options[marker_icon_anchor_x]"'),
    $marker_icon_anchor_y: $('input[name="cb_map_options[marker_icon_anchor_y]"'),
    $image_preview_settings: $('#marker-image-preview-settings'),
    $image_preview_measurements: $('#marker-image-preview-measurements'),
    $marker_icon_size: $('#marker-icon-size'),
    $marker_icon_anchor: $('#marker-icon-anchor')
  };

	cb_map_marker_upload.init($, marker_data);

  var marker_cluster_data = {
    $select_image_button: $('#select-marker-cluster-image-button'),
    $remove_image_button: $('#remove-marker-cluster-image-button'),
    $custom_marker_media_id: $('input[name="cb_map_options[custom_marker_cluster_media_id]"'),
    $image_preview: $('#marker-cluster-image-preview'),
    $marker_icon_width: $('input[name="cb_map_options[marker_cluster_icon_width]"'),
    $marker_icon_height: $('input[name="cb_map_options[marker_cluster_icon_height]"'),
    $marker_icon_anchor_x: $('input[name="cb_map_options[marker_cluster_icon_anchor_x]"'),
    $marker_icon_anchor_y: $('input[name="cb_map_options[marker_cluster_icon_anchor_y]"'),
    $image_preview_settings: $('#marker-cluster-image-preview-settings'),
    $image_preview_measurements: $('#marker-cluster-image-preview-measurements'),
    $marker_icon_size: $('#marker-cluster-icon-size'),
    $marker_icon_anchor: $('#marker-cluster-icon-anchor')
  };

  cb_map_marker_upload.init($, marker_cluster_data);

  var marker_item_draft_data = {
    $select_image_button: $('#select-marker-item-draft-image-button'),
    $remove_image_button: $('#remove-marker-item-draft-image-button'),
    $custom_marker_media_id: $('input[name="cb_map_options[marker_item_draft_media_id]"'),
    $image_preview: $('#marker-item-draft-image-preview'),
    $marker_icon_width: $('input[name="cb_map_options[marker_item_draft_icon_width]"'),
    $marker_icon_height: $('input[name="cb_map_options[marker_item_draft_icon_height]"'),
    $marker_icon_anchor_x: $('input[name="cb_map_options[marker_item_draft_icon_anchor_x]"'),
    $marker_icon_anchor_y: $('input[name="cb_map_options[marker_item_draft_icon_anchor_y]"'),
    $image_preview_settings: $('#marker-item-draft-image-preview-settings'),
    $image_preview_measurements: $('#marker-item-draft-image-preview-measurements'),
    $marker_icon_size: $('#marker-item-draft-icon-size'),
    $marker_icon_anchor: $('#marker-item-draft-icon-anchor')
  };

  cb_map_marker_upload.init($, marker_item_draft_data);
});
