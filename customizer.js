function openMediaWindow(widgetInstanceFormContainer) {
	var mediaUploader = wp.media({
		title: 'Select Media',
		button: {
			text: 'Select'
		},
		multiple: false
	});
	mediaUploader.on('select', function () {
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

(function (api, $) {
	if (!api) {
		return;
	}

	var previewerChannel = null;

	function notifyPreviewRefresh(sidebarId) {
		if (!previewerChannel || !previewerChannel.send) {
			return;
		}
		var payload = sidebarId ? { sidebarId: sidebarId } : {};
		previewerChannel.send('sass-refresh-widgets', payload);
	}

	function getSidebarSettingId(sidebarId) {
		return 'sidebars_widgets[' + sidebarId + ']';
	}

	function getSidebarSetting(sidebarId) {
		if (!sidebarId) {
			return null;
		}
		return api(getSidebarSettingId(sidebarId));
	}

	function setSidebarOrder(sidebarId, order) {
		if (!sidebarId) {
			return;
		}
		const setting = getSidebarSetting(sidebarId);
		if (!setting) {
			return;
		}
		const current = Array.isArray(setting.get()) ? setting.get().slice() : [];
		const unique = [];
		(order || []).forEach(function (id) {
			if (current.includes(id) && !unique.includes(id)) {
				unique.push(id);
			}
		});
		current.forEach(function (id) {
			if (!unique.includes(id)) {
				unique.push(id);
			}
		});

		if (unique.length && JSON.stringify(unique) !== JSON.stringify(current)) {
			setting.set(unique);
			notifyPreviewRefresh(sidebarId);
		}
	}

	function handleMoveWidgetRequest(payload) {
		if (!payload) {
			return;
		}

		const sidebarId = payload.sidebarId;
		const widgetId = payload.widgetId;
		const direction = payload.direction;
		if (!sidebarId || !widgetId || !direction) {
			return;
		}
		const setting = getSidebarSetting(sidebarId);
		if (!setting) {
			return;
		}
		const items = Array.isArray(setting.get()) ? setting.get().slice() : [];
		const index = items.indexOf(widgetId);
		if (index === -1) {
			return;
		}

		const offset = direction === 'up' ? -1 : 1;
		const targetIndex = index + offset;
		if (targetIndex < 0 || targetIndex >= items.length) {
			return;
		}

		const temp = items[index];
		items[index] = items[targetIndex];
		items[targetIndex] = temp;

		setting.set(items);
		setTimeout(function () {
			notifyPreviewRefresh(sidebarId);
		}, 100);
	}

	function handleRemoveWidgetRequest(payload) {
		if (!payload) {
			return;
		}
		const controlId = payload.controlId;
		const sidebarId = payload.sidebarId;
		const widgetId = payload.widgetId;
		if (!sidebarId || !widgetId) {
			return;
		}
		
		// Try to use the native WordPress remove button first
		if (controlId) {
			const control = api.control(controlId);
			if (control && control.container) {
				const removeButton = control.container.find('.widget-control-remove');
				if (removeButton.length) {
					removeButton.trigger('click');
					setTimeout(function () {
						notifyPreviewRefresh(sidebarId);
					}, 200);
					return;
				}
			}
		}

		// Fallback to manual removal from sidebar setting
		const setting = getSidebarSetting(sidebarId);
		if (!setting) {
			return;
		}
		const current = Array.isArray(setting.get()) ? setting.get().slice() : [];
		const updated = current.filter(function (id) {
			return id !== widgetId;
		});
		if (updated.length !== current.length) {
			setting.set(updated);
			setTimeout(function () {
				notifyPreviewRefresh(sidebarId);
			}, 200);
		}
	}

	api.bind('ready', function () {
		previewerChannel = api.previewer || null;

		api.previewer.bind('sass-editWidget', function (widgetIdentifier) {
			const control = api.control(widgetIdentifier);
			if (!control) {
				return;
			}

			const sectionId = control.section();
			const section = sectionId ? api.section(sectionId) : null;
			if (section) {
				section.expanded(true);
			}

			control.expanded(true);
			if (control.container && control.container[0]) {
				control.container[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
			}
		});

		api.previewer.bind('sass-move-widget', handleMoveWidgetRequest);
		api.previewer.bind('sass-remove-widget', handleRemoveWidgetRequest);
		api.previewer.bind('sass-reorder-sidebar', function (payload) {
			if (!payload) {
				return;
			}
			setSidebarOrder(payload.sidebarId, Array.isArray(payload.order) ? payload.order : []);
		});

		const widgetContainers = document.querySelectorAll(".customize-control.customize-control-widget_form");
		widgetContainers.forEach(function (widgetContainer) {
			widgetContainer.addEventListener('click', function (e) {
				if (e.target.matches(".button.select-media-button")) {
					// Reserved for future media button enhancements.
				}
			});
		});
	});
})(wp.customize, window.jQuery);
