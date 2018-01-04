<?php

/*
*	Place in Wrap custom post type
*/

function giftplatform_enqueue_admin ($hook) {
	include('cred.php');
    wp_enqueue_script( 'google_maps', 'https://maps.googleapis.com/maps/api/js?key='.GOOGLEMAPSAPI.'&libraries=drawing', array( 'jquery' ) );
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

?>