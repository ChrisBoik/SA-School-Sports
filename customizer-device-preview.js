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
	if (typeof wp === 'undefined' || !wp.customize) {
		return;
	}

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
		// Widget Options widgets (extendedwopts classes)
		document.querySelectorAll('.extendedwopts-hide, .extendedwopts-show').forEach(function (el) {
			var display = resolveDisplay(el, device);
			if (display !== null) {
				el.style.setProperty('display', display, 'important');
			}
		});

		// Our own SASS widgets (data-sass-device-* attributes)
		// Skip widgets also controlled by Widget Options to avoid conflict
		document.querySelectorAll('.custom-widget-container[data-sass-device-desktop]').forEach(function (el) {
			var wrapper = el.closest('.extendedwopts-hide, .extendedwopts-show');
			if (wrapper) return;

			var showOnDevice = el.getAttribute('data-sass-device-' + device);
			if (showOnDevice === '0') {
				el.style.setProperty('display', 'none', 'important');
			} else {
				el.style.removeProperty('display');
			}
		});
	}

	var deviceMap = {
		desktop: 'desktop',
		tablet: 'tablet',
		mobile: 'mobile',
	};

	wp.customize.bind('preview-ready', function () {
		// Listen for device changes sent from the controls pane
		wp.customize.preview.bind('previewedDevice', function (newDevice) {
			var mapped = deviceMap[newDevice] || 'desktop';
			applyDeviceVisibility(mapped);
		});

		// Also try to read from the parent frame directly
		try {
			var parent = window.parent;
			if (parent && parent.wp && parent.wp.customize) {
				var previewedDevice = parent.wp.customize.previewedDevice;
				if (previewedDevice && typeof previewedDevice.get === 'function') {
					var initial = previewedDevice.get();
					if (initial && deviceMap[initial]) {
						applyDeviceVisibility(deviceMap[initial]);
					}

					previewedDevice.bind(function (newDevice) {
						var mapped = deviceMap[newDevice] || 'desktop';
						applyDeviceVisibility(mapped);
					});
				}
			}
		} catch (e) {
			// Cross-origin — fall back to message-based channel only
		}
	});
})();
