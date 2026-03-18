/**
 * Customizer Device Preview - Widget Visibility Toggle
 *
 * Runs inside the customizer preview iframe.
 * Listens for device-mode changes (desktop/tablet/mobile) from the
 * customizer controls and force-shows/hides widgets whose visibility
 * is controlled by Widget Options' extendedwopts-* CSS classes.
 *
 * Widget Options adds these class patterns to widget wrappers:
 *   extendedwopts-hide  + extendedwopts-{device}  → hide on that device
 *   extendedwopts-show  + extendedwopts-{device}  → show only on that device
 *
 * The stock CSS uses orientation-based media queries that don't fire when
 * the customizer simply resizes the preview iframe, so we override inline.
 */
(function () {
	console.log('[SASS Device Preview] Script loaded in preview iframe');

	if (typeof wp === 'undefined' || !wp.customize) {
		console.warn('[SASS Device Preview] wp.customize not available — aborting');
		return;
	}

	console.log('[SASS Device Preview] wp.customize is available');

	var DEVICES = ['mobile', 'tablet', 'desktop'];

	/**
	 * Determine what display value a widget *should* have for a given device.
	 */
	function resolveDisplay(el, device) {
		var isHideMode = el.classList.contains('extendedwopts-hide');
		var isShowMode = el.classList.contains('extendedwopts-show');

		if (!isHideMode && !isShowMode) {
			return null;
		}

		var flaggedDevices = [];
		DEVICES.forEach(function (d) {
			if (el.classList.contains('extendedwopts-' + d)) {
				flaggedDevices.push(d);
			}
		});

		if (flaggedDevices.length === 0) {
			return null;
		}

		var deviceFlagged = flaggedDevices.indexOf(device) !== -1;

		if (isHideMode) {
			return deviceFlagged ? 'none' : '';
		}

		// isShowMode
		return deviceFlagged ? '' : 'none';
	}

	/**
	 * Apply visibility overrides for the given device.
	 */
	function applyDeviceVisibility(device) {
		var widgets = document.querySelectorAll(
			'.extendedwopts-hide, .extendedwopts-show'
		);

		console.log('[SASS Device Preview] Applying device: "' + device + '" — found ' + widgets.length + ' widget(s) with device classes');

		widgets.forEach(function (el) {
			var display = resolveDisplay(el, device);
			if (display !== null) {
				console.log('[SASS Device Preview]   Widget:', el.id || el.className, '→ display:', display || '(visible)');
				el.style.setProperty('display', display, 'important');
			}
		});
	}

	var deviceMap = {
		desktop: 'desktop',
		tablet: 'tablet',
		mobile: 'mobile',
	};

	wp.customize.bind('preview-ready', function () {
		console.log('[SASS Device Preview] preview-ready fired');

		// Listen for device changes sent from the controls pane
		wp.customize.preview.bind('previewedDevice', function (newDevice) {
			console.log('[SASS Device Preview] Received previewedDevice message: "' + newDevice + '"');
			var mapped = deviceMap[newDevice] || 'desktop';
			applyDeviceVisibility(mapped);
		});

		// Also try to read from the parent frame directly
		try {
			var parent = window.parent;
			if (parent && parent.wp && parent.wp.customize) {
				console.log('[SASS Device Preview] Parent wp.customize accessible');
				var previewedDevice = parent.wp.customize.previewedDevice;
				if (previewedDevice && typeof previewedDevice.get === 'function') {
					var initial = previewedDevice.get();
					console.log('[SASS Device Preview] Initial device from parent: "' + initial + '"');
					if (initial && deviceMap[initial]) {
						applyDeviceVisibility(deviceMap[initial]);
					}

					previewedDevice.bind(function (newDevice) {
						console.log('[SASS Device Preview] Parent previewedDevice changed: "' + newDevice + '"');
						var mapped = deviceMap[newDevice] || 'desktop';
						applyDeviceVisibility(mapped);
					});
				} else {
					console.warn('[SASS Device Preview] parent.wp.customize.previewedDevice not found or not a Value');
				}
			} else {
				console.warn('[SASS Device Preview] Cannot access parent wp.customize');
			}
		} catch (e) {
			console.warn('[SASS Device Preview] Cross-origin error accessing parent:', e.message);
		}
	});

	// Fallback: also listen on the 'active' event in case preview-ready has already fired
	wp.customize.bind('active', function () {
		console.log('[SASS Device Preview] "active" event fired (fallback)');
	});
})();
