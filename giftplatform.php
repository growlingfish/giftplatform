<?php
/**
 * Plugin Name:       GIFT platform plugin
 * Plugin URI:        https://github.com/growlingfish/giftplatform
 * Description:       WordPress admin and server for GIFT project digital gifting platform
 * Version:           0.0.0.5
 * Author:            Ben Bedwell
 * License:           GNU General Public License v3
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       giftplatform
 * GitHub Plugin URI: https://github.com/growlingfish/giftplatform
 * GitHub Branch:     master
 */
 
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
 
register_activation_hook( __FILE__, 'giftplatform_activate' );
function giftplatform_activate () {
	flush_rewrite_rules();
}

/*
*	Place in Wrap custom post type
*/

function giftplatform_enqueue_admin ($hook) {
    wp_enqueue_script( 'google_maps', 'https://maps.googleapis.com/maps/api/js?key=AIzaSyD9lN8Xdj31lA9w4DwpuZ1SwzQliwOI21I&libraries=drawing', array( 'jquery' ) );
}
add_action( 'admin_enqueue_scripts', 'giftplatform_enqueue_admin' );

function adding_custom_meta_boxes_wrap ( $post ) {
    add_meta_box( 
        'wrap_geo_meta_box',
        __( 'Place' ),
        'render_wrap_geo_meta_box',
        'wrap',
        'advanced',
        'high'
    );
}
add_action( 'add_meta_boxes_wrap', 'adding_custom_meta_boxes_wrap' );

function render_wrap_geo_meta_box ( $post ) { ?>
<div id="wrap_geo_meta_box_map" style="width: 100%; height: 400px;"></div>
<script>	
	jQuery(document).ready(function( $ ) {
		var displayMarker;
		
		// display blank map
		var map = new google.maps.Map(
			document.getElementById('wrap_geo_meta_box_map'), {
				center: {lat: 52.938597, lng: -1.195291},
				zoom: 15
			}
		);

		// show drawing manager
		var drawingManager = new google.maps.drawing.DrawingManager({
			drawingMode: google.maps.drawing.OverlayType.MARKER,
			drawingControl: true,
			drawingControlOptions: {
				position: google.maps.ControlPosition.TOP_CENTER,
				drawingModes: [
					google.maps.drawing.OverlayType.MARKER
				]
			},
			markerOptions: {
				clickable: false,
				editable: false
			}
		});
		drawingManager.setMap(map);

		if (jQuery('#acf-field-place').val()) {
			try {
				var point = new google.maps.Data.Point(
					JSON.parse(jQuery('#acf-field-place').val())
				);
				displayMarker = new google.maps.Marker({
				  	position: point.get(),
				  	map: map
				});
			} catch (e) { // JSON syntax error
				console.log('Bad JSON syntax in ACF field Place: ' + jQuery('#acf-field-place').val());
				jQuery('#acf-field-place').val('');
			}
		}

		google.maps.event.addListener(drawingManager, 'overlaycomplete', function(event) {
			if (typeof displayMarker !== "undefined") {
				displayMarker.setMap(null);
			}
							
			switch (event.type) {
				case google.maps.drawing.OverlayType.MARKER:
					displayMarker = event.overlay;
					jQuery('#acf-field-place').val(JSON.stringify(displayMarker.getPosition()));
					break;
			}
		});
	});
</script>
<?php	
}
 
/*
*	Simplify
*/

add_action('init', 'giftplatform_remove_categories');
function giftplatform_remove_categories () {
	register_taxonomy('category', array());
}

add_action('admin_menu', 'giftplatform_remove_admin_options');
function giftplatform_remove_admin_options () {
	//if (!current_user_can('manage_options')) {
		remove_menu_page( 'edit.php' );
		remove_menu_page( 'edit.php?post_type=page' );
		remove_menu_page( 'edit-comments.php' );
    //}
}

?>