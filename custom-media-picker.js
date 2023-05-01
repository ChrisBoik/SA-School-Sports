document.addEventListener('DOMContentLoaded', function() {
    var widgetContainers = document.querySelectorAll('.custom-widget-container');
    widgetContainers.forEach(function(widgetContainer) {		
        var editWidgetButton = widgetContainer.querySelector('.edit-widget-button');
		var widgetInstanceIdentifier = widgetContainer.querySelector("[name='sass_widget_id']").value;
		console.log(editWidgetButton);
        editWidgetButton.addEventListener('click', function(e) {
            e.preventDefault();
			wp.customize.myCustomizerPreview.preview.send( 'sass-editWidget', widgetInstanceIdentifier );
        });		
	});
});

( function ( wp, $ ) {
	"use strict";

	// Bail if the customizer isn't initialized
	if ( ! wp || ! wp.customize ) {
		return;
	}

	var api = wp.customize, OldPreview;

	// Custom Customizer Preview class (attached to the Customize API)
	api.myCustomizerPreview = {
		// Init
		init: function () {
			var self = this; // Store a reference to "this"

			// When the previewer is active, the "active" event has been triggered (on load)
			this.preview.bind( 'active', function() {
				console.log("BIND SENT");
				// Send "my-custom-event" data over to the Customizer
				self.preview.send( 'my-custom-event', window.myCustomData );
			} );
		},
		updateWidget: function (data) {
			var self = this; // Store a reference to "this"
			this.preview.bind( 'active', function() {
				// Send "my-custom-event" data over to the Customizer
				self.preview.send( 'my-custom-event-updateWidget', {fakeData: data} );
				
// 				wp.customize.myCustomizerPreview.preview.send( 'sass-updateWidget', {widgetIdentifier: } );
			});
		}
	};

	/**
	 * Capture the instance of the Preview since it is private (this has changed in WordPress 4.0)
	 *
	 * @see https://github.com/WordPress/WordPress/blob/5cab03ab29e6172a8473eb601203c9d3d8802f17/wp-admin/js/customize-controls.js#L1013
	 */
	OldPreview = api.Preview;
	api.Preview = OldPreview.extend( {
		initialize: function( params, options ) {
			// Store a reference to the Preview
			api.myCustomizerPreview.preview = this;

			// Call the old Preview's initialize function
			OldPreview.prototype.initialize.call( this, params, options );
		}
	} );

	// Document ready
	$( function () {
		// Initialize our Preview
		api.myCustomizerPreview.init();
	} );
} )( window.wp, jQuery );

