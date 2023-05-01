    // Listen for message from previewed page
console.log("Customizer.js loaded");	

function openMediaWindow(widgetInstanceFormContainer){
	var mediaUploader = wp.media({
		title: 'Select Media',
		button: {
			text: 'Select'
		},
		multiple: false
	});
	mediaUploader.on('select', function() {
		console.log("select")
		var attachment = mediaUploader.state().get('selection').first().toJSON();
		console.log(attachment);
		var mediaUriInput = widgetInstanceFormContainer.querySelector('input.media-uri-input');
		mediaUriInput.value = attachment.url;
		mediaUriInput.dispatchEvent(new Event('change', { bubbles: true }));

		var mediaAspectRatio = widgetInstanceFormContainer.querySelector('input.media_aspect_ratio');
		mediaAspectRatio.value = attachment.width / attachment.height <= 0 ? 1 : attachment.width / attachment.height;
		mediaAspectRatio.dispatchEvent(new Event('change', { bubbles: true }));
	});
	mediaUploader.open();
}

wp.customize.bind( 'ready', function () {
	console.log("customizer ready")
	wp.customize.previewer.bind( 'my-custom-event', function( data ) {
		console.log( '"my-custom-event" has been received from the Previewer. Data:', data );
	});
	
	wp.customize.previewer.bind( 'sass-editWidget', function( widgetIdentifier ) {
		let control = wp.customize.control(widgetIdentifier);
		wp.customize.section(control.section()).expanded(true);
		control.expanded(true);
		control.container[0].scrollIntoView();
	});
	
	console.log("widgetContainers");
	var widgetContainers = document.querySelectorAll(".customize-control.customize-control-widget_form");
	widgetContainers.forEach(function(widgetContainer) {
		widgetContainer.addEventListener('click', function(e) {
			if(e.target.matches(".button.select-media-button")){
// 				var mediaUploader = wp.media({
// 					title: 'Select Media',
// 					button: {
// 						text: 'Select'
// 					},
// 					multiple: false
// 				});
// 				mediaUploader.on('select', function() {
// 					var attachment = mediaUploader.state().get('selection').first().toJSON();
// 					console.log(attachment);
// 					var mediaUriInput = widgetContainer.querySelector('input.media-uri-input');
// 					mediaUriInput.value = attachment.url;
					
// 					var mediaAspectRatio = widgetContainer.querySelector('input.media_aspect_ratio');
// 					mediaAspectRatio.value = attachment.width / attachment.height <= 0 ? 1 : attachment.height;

// 					// 				// set the value to the customize control
// 					// 				var widgetId = widgetContainer.getAttribute('data-widget-id');

// 					// 				mediaUriInput.addEventListener( 'change', function() {
// 					// 					console.log("input changed");
// 					// 					// Get the new value
// 					// 					var newVal = this.value;
// 					// 					// Send a message to the Customizer with the new value
// 					// 					parent.postMessage({ type: 'update-customizer-setting', value: newVal }, '*');
// 					// 				});

// 				});
// 				mediaUploader.open();
			}
		});		
	});
});
