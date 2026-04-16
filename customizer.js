function openMediaWindow(widgetInstanceFormContainer) {
	var mediaUploader = wp.media({
		title: 'Select Media',
		button: {
			text: 'Select'
		},
		multiple: false
	});
	mediaUploader.on('select', function () {
		var attachment = mediaUploader.state().get('selection').first().toJSON();
		var mediaUriInput = widgetInstanceFormContainer.querySelector('input.media-uri-input');
		mediaUriInput.value = attachment.url;
		jQuery(mediaUriInput).trigger('change');

		var mediaAspectRatio = widgetInstanceFormContainer.querySelector('input.media_aspect_ratio');
		mediaAspectRatio.value = attachment.width / attachment.height <= 0 ? 1 : attachment.width / attachment.height;
		jQuery(mediaAspectRatio).trigger('change');

		// Update the thumbnail preview in the widget form
		var thumbnailContainer = widgetInstanceFormContainer.querySelector('.sass-widget-thumbnail');
		if (attachment.url) {
			var isVideo = /\.(webm|mp4)$/i.test(attachment.url);
			var mediaEl;
			if (isVideo) {
				mediaEl = document.createElement('video');
				mediaEl.muted = true;
				mediaEl.loop = true;
				mediaEl.autoplay = true;
				mediaEl.playsInline = true;
				mediaEl.src = attachment.url;
			} else {
				mediaEl = document.createElement('img');
				mediaEl.src = attachment.url;
				mediaEl.alt = '';
			}

			if (!thumbnailContainer) {
				thumbnailContainer = document.createElement('div');
				thumbnailContainer.className = 'sass-widget-thumbnail';
				widgetInstanceFormContainer.insertBefore(thumbnailContainer, widgetInstanceFormContainer.firstChild);
			}
			// Replace contents safely
			while (thumbnailContainer.firstChild) {
				thumbnailContainer.removeChild(thumbnailContainer.firstChild);
			}
			thumbnailContainer.appendChild(mediaEl);

			// Handle landscape-wide class for very wide aspect ratios
			var ratio = attachment.width / attachment.height;
			if (ratio > 3) {
				thumbnailContainer.classList.add('landscape-wide');
			} else {
				thumbnailContainer.classList.remove('landscape-wide');
			}
		}
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

		// Compute live status from form field values (not the static PHP data-status attribute)
		function computeWidgetStatus(form) {
			if (!form) return '';
			var now = new Date().toISOString().slice(0, 16); // YYYY-MM-DDTHH:mm

			var showTo = (form.querySelector('input[id*="show_to"]') || {}).value || '';
			if (showTo && now > showTo) return 'Expired';

			var showFrom = (form.querySelector('input[id*="show_from"]') || {}).value || '';
			if (showFrom && now < showFrom) return 'Scheduled';

			var deskCb = form.querySelector('input[type="checkbox"][name*="device_desktop"]');
			var tabCb  = form.querySelector('input[type="checkbox"][name*="device_tablet"]');
			var mobCb  = form.querySelector('input[type="checkbox"][name*="device_mobile"]');
			var allOff = deskCb && tabCb && mobCb && !deskCb.checked && !tabCb.checked && !mobCb.checked;
			if (allOff) return 'Hidden';

			return '';
		}

		// Guard against MutationObserver re-entrancy — rewriting the title is
		// itself a DOM mutation, so without this the observer would loop.
		var _rewriting = false;

		// Rewrite widget titles and add/update status badges
		function rewriteSassWidgetTitles() {
			if (_rewriting) return;
			_rewriting = true;

			document.querySelectorAll('.widget-top .widget-title h3').forEach(function (h3) {
				var nameNode = h3.childNodes[0];
				if (!nameNode || nameNode.nodeType !== 3) return;
				var rawName = nameNode.textContent.trim();
				if (rawName.indexOf('Custom Banner Widget') === -1 && rawName.indexOf('SASS Widget') === -1 && rawName.indexOf('SASS') === -1) return;

				var widgetControl = h3.closest('.widget');

				// Always clear the in-widget-title span — the PHP filter already
				// bakes the instance title into the widget name, so the span
				// duplicates it and causes ghost text overlap.
				var titleSpan = h3.querySelector('.in-widget-title');
				if (titleSpan) titleSpan.textContent = '';

				// Build the clean title from form fields (source of truth).
				// Widget forms are lazy-loaded in the customizer — if the form
				// hasn't been expanded yet, titleInput will be null. In that
				// case, try to read the title from the customize API settings
				// (which have the saved data even before the form is rendered).
				var titleInput = widgetControl ? widgetControl.querySelector('input[id*="-title"]') : null;
				var posInput = widgetControl ? widgetControl.querySelector('input[id*="position_description"]') : null;

				var title = '';
				var position = '';

				if (titleInput) {
					// Form is loaded — read from DOM (live values)
					title = titleInput.value.trim();
					position = posInput ? posInput.value.trim() : '';
				} else {
					// Form not yet loaded — read from customize API settings.
					// The widget's <li> wrapper has id="customize-control-widget_custom_media_widget-N"
					// which maps to setting "widget_custom_media_widget[N]".
					var controlLi = widgetControl ? widgetControl.closest('[id^="customize-control-widget_custom_media_widget"]') : null;
					if (controlLi) {
						var settingId = controlLi.id
							.replace('customize-control-', '')
							.replace(/-(\d+)$/, '[$1]');
						var setting = api(settingId);
						if (setting) {
							var val = setting.get();
							if (val && typeof val === 'object') {
								title = (val.title || '').trim();
								position = (val.position_description || '').trim();
							}
						}
					}
					// If we still couldn't resolve a title, leave the PHP name as-is
					if (!title && !position) return;
				}

				var parts = [];
				if (title) parts.push(title);
				if (position) parts.push(position);
				var cleanName = (parts.length ? parts.join(' \u2013 ') : '(untitled)') + ' \u2014 SASS';

				// Only touch the text node if it actually needs changing
				if (nameNode.textContent.trim() !== cleanName) {
					nameNode.textContent = cleanName;
				}

				// Update status badge and CSS class from live form values
				var form = widgetControl ? widgetControl.querySelector('.custom-media-widget-form') : null;
				var status = computeWidgetStatus(form);
				var existingBadge = h3.querySelector('.sass-widget-status-badge');

				// Sync CSS class on the form — :has() rules use this for
				// the collapsed title bar tint and section header tags
				if (form) {
					form.classList.remove('sass-status-expired', 'sass-status-scheduled', 'sass-status-hidden');
					if (status) {
						form.classList.add('sass-status-' + status.toLowerCase());
					}
					form.setAttribute('data-status', status);
				}

				if (status) {
					if (existingBadge) {
						existingBadge.className = 'sass-widget-status-badge status-' + status.toLowerCase();
						existingBadge.textContent = status;
					} else {
						var badge = document.createElement('span');
						badge.className = 'sass-widget-status-badge status-' + status.toLowerCase();
						badge.textContent = status;
						h3.appendChild(badge);
					}
				} else {
					if (existingBadge) existingBadge.remove();
				}
			});

			_rewriting = false;
		}

		// Ensure all forms have correct status CSS class (drives :has() indicators)
		function syncAllFormStatusClasses() {
			document.querySelectorAll('.custom-media-widget-form').forEach(function (form) {
				var status = computeWidgetStatus(form);
				form.classList.remove('sass-status-expired', 'sass-status-scheduled', 'sass-status-hidden');
				if (status) {
					form.classList.add('sass-status-' + status.toLowerCase());
				}
				form.setAttribute('data-status', status);
			});
		}

		// Re-run status computation when date or device fields change
		document.addEventListener('change', function (e) {
			var target = e.target;
			if (!target) return;
			var isDateField = target.type === 'date' && (target.name && (target.name.indexOf('show_from') !== -1 || target.name.indexOf('show_to') !== -1));
			var isDeviceField = target.type === 'checkbox' && target.name && target.name.indexOf('device_') !== -1;
			if (isDateField || isDeviceField) {
				syncAllFormStatusClasses();
				rewriteSassWidgetTitles();
			}
		});

		// Debounced MutationObserver callback — coalesces rapid DOM changes
		var _titleTimer = null;
		function debouncedTitleRewrite() {
			if (_titleTimer) return;
			_titleTimer = setTimeout(function () {
				_titleTimer = null;
				syncAllFormStatusClasses();
				rewriteSassWidgetTitles();
			}, 80);
		}

		// Run on load and observe for dynamically added/expanded widgets
		syncAllFormStatusClasses();
		rewriteSassWidgetTitles();
		var titleObserver = new MutationObserver(debouncedTitleRewrite);
		titleObserver.observe(document.getElementById('customize-controls') || document.body, {
			childList: true,
			subtree: true
		});

		// Rewrite titles live as the user types in the title/position fields
		document.addEventListener('input', function (e) {
			var target = e.target;
			if (!target || !target.closest) return;
			if (!target.closest('.custom-media-widget-form')) return;
			var isTitle = target.id && target.id.indexOf('-title') !== -1;
			var isPosition = target.id && target.id.indexOf('position_description') !== -1;
			if (isTitle || isPosition) {
				rewriteSassWidgetTitles();
			}
		});

		// Delegated click handler for media button — works for dynamically added widgets too
		document.addEventListener('click', function (e) {
			var btn = e.target.closest('.select-media-button');
			if (!btn) return;
			var form = btn.closest('.custom-media-widget-form');
			if (form) {
				e.preventDefault();
				openMediaWindow(form);
			}
		});

		// Taxonomy search filter
		document.addEventListener('input', function (e) {
			if (!e.target.classList.contains('sass-tax-search')) return;
			var query = e.target.value.toLowerCase().trim();
			var body = e.target.closest('.sass-section-body');
			if (!body) return;
			var list = body.querySelector('.sass-tax-list');
			if (!list) return;
			list.querySelectorAll('.sass-tax-item').forEach(function (item) {
				var searchText = item.getAttribute('data-search') || '';
				if (!query || searchText.indexOf(query) !== -1) {
					item.classList.remove('sass-hidden');
				} else {
					item.classList.add('sass-hidden');
				}
			});
		});

		// Category badge remove — uncheck the corresponding checkbox and remove badge
		document.addEventListener('click', function (e) {
			var removeBtn = e.target.closest('.sass-tax-badge-remove');
			if (!removeBtn) return;
			var badge = removeBtn.closest('.sass-tax-badge');
			if (!badge) return;
			var termId = badge.getAttribute('data-term-id');
			var section = badge.closest('.sass-section-body');
			if (section && termId) {
				var checkbox = section.querySelector('.sass-tax-item[data-term-id="' + termId + '"] input[type="checkbox"]');
				if (checkbox) {
					checkbox.checked = false;
					checkbox.dispatchEvent(new Event('change', { bubbles: true }));
				}
			}
			badge.remove();
		});

		// Sync category badges when checkboxes are toggled
		document.addEventListener('change', function (e) {
			var checkbox = e.target;
			if (!checkbox || checkbox.type !== 'checkbox') return;
			var item = checkbox.closest('.sass-tax-item');
			if (!item) return;
			var section = item.closest('.sass-section-body');
			if (!section) return;
			var termId = item.getAttribute('data-term-id');
			if (!termId) return;

			var badgeContainer = section.querySelector('.sass-tax-selected-badges');

			if (checkbox.checked) {
				// Create badge container if it doesn't exist yet
				if (!badgeContainer) {
					badgeContainer = document.createElement('div');
					badgeContainer.className = 'sass-tax-selected-badges';
					var modeSelect = section.querySelector('.sass-tax-mode');
					if (modeSelect) {
						modeSelect.insertAdjacentElement('afterend', badgeContainer);
					} else {
						section.prepend(badgeContainer);
					}
				}
				// Don't add if badge already exists
				if (badgeContainer.querySelector('.sass-tax-badge[data-term-id="' + termId + '"]')) return;

				// Build label from the item's text (ancestors + name)
				var ancestors = item.querySelector('.sass-tax-ancestors');
				var label = (ancestors ? ancestors.textContent.replace(/›/g, '>').trim() + ' ' : '') + item.textContent.replace(ancestors ? ancestors.textContent : '', '').trim();

				var badge = document.createElement('span');
				badge.className = 'sass-tax-badge';
				badge.setAttribute('data-term-id', termId);
				badge.textContent = label.trim() + ' ';
				var removeBtn = document.createElement('button');
				removeBtn.type = 'button';
				removeBtn.className = 'sass-tax-badge-remove';
				removeBtn.setAttribute('aria-label', 'Remove');
				removeBtn.textContent = '\u00d7';
				badge.appendChild(removeBtn);
				badgeContainer.appendChild(badge);
			} else {
				// Remove badge when unchecked
				if (badgeContainer) {
					var badge = badgeContainer.querySelector('.sass-tax-badge[data-term-id="' + termId + '"]');
					if (badge) badge.remove();
				}
			}

			// Update the summary count badge
			var details = section.closest('.sass-section-details');
			if (details) {
				var summary = details.querySelector('summary');
				var count = section.querySelectorAll('.sass-tax-item input[type="checkbox"]:checked').length;
				var countBadge = summary ? summary.querySelector('.sass-summary-count') : null;
				if (count > 0) {
					if (!countBadge) {
						countBadge = document.createElement('span');
						countBadge.className = 'sass-summary-count';
						summary.appendChild(countBadge);
					}
					countBadge.textContent = count;
				} else if (countBadge) {
					countBadge.remove();
				}
			}
		});

		// Move to sidebar handler
		document.addEventListener('click', function (e) {
			var btn = e.target.closest('.sass-move-btn');
			if (!btn) return;
			var fieldset = btn.closest('.sass-move-sidebar');
			if (!fieldset) return;
			var select = fieldset.querySelector('.sass-move-target');
			var targetSidebarId = select ? select.value : '';
			if (!targetSidebarId) {
				alert('Please select a target sidebar.');
				return;
			}
			var form = btn.closest('.custom-media-widget-form');
			if (!form) return;
			var widgetId = form.getAttribute('data-widget-id');
			if (!widgetId) return;

			// Find the current sidebar this widget belongs to
			var currentSidebarId = null;
			api.each(function (setting) {
				if (typeof setting.id !== 'string') return;
				if (setting.id.indexOf('sidebars_widgets[') !== 0) return;
				var items = setting.get();
				if (Array.isArray(items) && items.indexOf(widgetId) !== -1) {
					currentSidebarId = setting.id.replace('sidebars_widgets[', '').replace(']', '');
				}
			});

			if (!currentSidebarId) {
				alert('Could not determine current sidebar.');
				return;
			}
			if (currentSidebarId === targetSidebarId) {
				alert('Widget is already in that sidebar.');
				return;
			}

			// Remove from source sidebar
			var sourceSetting = api('sidebars_widgets[' + currentSidebarId + ']');
			if (sourceSetting) {
				var sourceItems = sourceSetting.get().slice();
				var idx = sourceItems.indexOf(widgetId);
				if (idx !== -1) {
					sourceItems.splice(idx, 1);
					sourceSetting.set(sourceItems);
				}
			}

			// Add to target sidebar
			var targetSetting = api('sidebars_widgets[' + targetSidebarId + ']');
			if (targetSetting) {
				var targetItems = targetSetting.get().slice();
				targetItems.push(widgetId);
				targetSetting.set(targetItems);
			}

			select.value = '';
			// Notify user
			var sidebarName = select.querySelector('option[value="' + targetSidebarId + '"]');
			alert('Moved to ' + (sidebarName ? sidebarName.textContent : targetSidebarId));
		});
	});
})(wp.customize, window.jQuery);
