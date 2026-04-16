(function (wp, $) {
	"use strict";

	const sortableParents = new Map();

	function getPreviewChannel() {
		if (wp && wp.customize) {
			if (wp.customize.myCustomizerPreview && wp.customize.myCustomizerPreview.preview) {
				return wp.customize.myCustomizerPreview.preview;
			}
			if (wp.customize.preview) {
				return wp.customize.preview;
			}
		}
		return null;
	}

	function getWrapperElement(container) {
		if (!container) {
			return null;
		}
		
		const widgetId = container.dataset.widgetInstanceId;
		let wrapper = null;
		
		// First try to find by widget ID
		if (widgetId) {
			wrapper = document.getElementById(widgetId);
		}
		
		// If not found, try to find the closest widget wrapper
		if (!wrapper && container.closest) {
			wrapper = container.closest('.widget');
		}
		
		// If still not found, look for a widget wrapper that contains this container
		if (!wrapper) {
			const widgets = document.querySelectorAll('.widget');
			for (let i = 0; i < widgets.length; i++) {
				if (widgets[i].contains(container)) {
					wrapper = widgets[i];
					break;
				}
			}
		}
		
		// Store the widget ID if we found one and it wasn't already set
		if (wrapper && !container.dataset.widgetInstanceId && wrapper.id) {
			container.dataset.widgetInstanceId = wrapper.id;
		}
		
		return wrapper;
	}

	function getAdjacentWidget(wrapper, direction) {
		if (!wrapper) return null;
		
		let node = wrapper[direction];
		while (node) {
			if (node.nodeType === 1 && node.classList.contains('widget')) {
				// Check if this widget is actually visible (not hidden by widget options plugin)
				const computedStyle = window.getComputedStyle(node);
				if (computedStyle.display !== 'none' && computedStyle.visibility !== 'hidden') {
					return node;
				}
			}
			node = node[direction];
		}
		return null;
	}

	function refreshSidebarMoveButtons(sidebarId) {
		if (!sidebarId) {
			return;
		}
		
		// Use setTimeout to ensure DOM is ready
		setTimeout(function() {
			document.querySelectorAll('.custom-widget-container[data-sidebar-id="' + sidebarId + '"]').forEach(function (container) {
				const wrapper = getWrapperElement(container);
				if (!wrapper) {
					return;
				}

				const moveUp = container.querySelector('.move-widget-up-button');
				const moveDown = container.querySelector('.move-widget-down-button');

				const hasPrevious = !!getAdjacentWidget(wrapper, 'previousElementSibling');
				const hasNext = !!getAdjacentWidget(wrapper, 'nextElementSibling');

				if (moveUp) {
					moveUp.disabled = !hasPrevious;
					moveUp.setAttribute('aria-disabled', !hasPrevious);
				}
				if (moveDown) {
					moveDown.disabled = !hasNext;
					moveDown.setAttribute('aria-disabled', !hasNext);
				}
			});
		}, 50);
	}

	function refreshAllMoveButtons() {
		const seen = new Set();
		document.querySelectorAll('.custom-widget-container[data-sidebar-id]').forEach(function (container) {
			const sidebarId = container.dataset.sidebarId;
			if (sidebarId && !seen.has(sidebarId)) {
				refreshSidebarMoveButtons(sidebarId);
				seen.add(sidebarId);
			}
		});
	}

	function ensureSortable(sidebarId, wrapper) {
		if (!sidebarId || !wrapper || !wrapper.parentElement || !$ || !$.fn || !$.fn.sortable) {
			return;
		}
		const parent = wrapper.parentElement;
		const existing = sortableParents.get(sidebarId);
		if (existing && existing !== parent) {
			try {
				$(existing).sortable('destroy');
			} catch(e) {
				// Ignore destroy errors
			}
			sortableParents.delete(sidebarId);
		}
		if (sortableParents.has(sidebarId)) {
			return;
		}

		const $parent = $(parent);

		// Prevent native browser link-drag on <a> tags inside widgets so jQuery UI
		// sortable can handle the drag without the browser starting a link-drag ghost.
		parent.querySelectorAll('.sass-media-widget').forEach(function (link) {
			link.addEventListener('dragstart', function (e) { e.preventDefault(); });
		});

		$parent.sortable({
			items: '> .widget:visible',
			handle: '.drag-widget-handle',
			cancel: '',  // Override default which blocks <button> elements from triggering drag
			axis: 'y',
			helper: 'clone',
			placeholder: 'sass-widget-placeholder',
			forcePlaceholderSize: true,
			tolerance: 'pointer',
			start: function (event, ui) {
				// The clone follows the cursor (styled as "lifted").
				// The original stays in place as a ghost.
				ui.helper.addClass('sass-widget-dragging');
				ui.item.addClass('sass-widget-ghost');
				// Match placeholder to widget size (placeholder sits where item will drop)
				ui.placeholder.height(ui.item.outerHeight());
				ui.placeholder.width(ui.item.outerWidth());
			},
			stop: function (event, ui) {
				ui.item.removeClass('sass-widget-ghost');
				refreshSidebarMoveButtons(sidebarId);
			},
			update: function () {
				const order = $parent.children('.widget:visible').map(function () {
					return this.id || null;
				}).get().filter(Boolean);

				const channel = getPreviewChannel();
				if (channel && order.length) {
					channel.send('sass-reorder-sidebar', {
						sidebarId: sidebarId,
						order: order
					});
				}
				// Refresh buttons after a short delay to allow DOM to settle
				setTimeout(function() {
					refreshSidebarMoveButtons(sidebarId);
				}, 100);
			}
		});
		sortableParents.set(sidebarId, parent);
	}

	function setupContainer(container) {
		if (!container || container.dataset.sassWidgetInit === '1') {
			return;
		}
		container.dataset.sassWidgetInit = '1';

		const controlInput = container.querySelector('[name="sass_widget_id"]');
		const controlId = controlInput ? controlInput.value : null;
		const sidebarId = container.dataset.sidebarId || '';
		const widgetId = container.dataset.widgetInstanceId || '';

		const wrapper = getWrapperElement(container);
		if (wrapper && widgetId && !wrapper.id) {
			wrapper.id = widgetId;
		}
		// If we still don't have a widget ID but have a wrapper with an ID, use it
		if (wrapper && wrapper.id && !container.dataset.widgetInstanceId) {
			container.dataset.widgetInstanceId = wrapper.id;
		}
		if (wrapper) {
			ensureSortable(sidebarId, wrapper);
		}

		const editButton = container.querySelector('.edit-widget-button');
		if (editButton && controlId) {
			editButton.addEventListener('click', function (e) {
				const channel = getPreviewChannel();
				if (!channel) {
					return;
				}
				e.preventDefault();
				e.stopPropagation();
				channel.send('sass-editWidget', controlId);
			});
		}

		if (controlId && sidebarId && widgetId) {
			// Shared handler for Up/Down — computes visible order and sends
			// sass-reorder-sidebar (same path as drag-n-drop) so hidden widgets
			// are skipped and the merge logic in customizer.js handles the rest.
			function handleMove(direction) {
				var wrapper = getWrapperElement(container);
				if (!wrapper || !wrapper.parentElement) {
					return;
				}

				// Build visible order from DOM
				var parent = wrapper.parentElement;
				var visibleIds = [];
				var children = parent.children;
				for (var i = 0; i < children.length; i++) {
					var child = children[i];
					if (child.nodeType === 1 && child.classList.contains('widget')) {
						var cs = window.getComputedStyle(child);
						if (cs.display !== 'none' && cs.visibility !== 'hidden' && child.id) {
							visibleIds.push(child.id);
						}
					}
				}

				var currentIndex = visibleIds.indexOf(wrapper.id);
				if (currentIndex === -1) {
					return;
				}

				var targetIndex = direction === 'up' ? currentIndex - 1 : currentIndex + 1;
				if (targetIndex < 0 || targetIndex >= visibleIds.length) {
					return;
				}

				// Swap in visible order
				var temp = visibleIds[currentIndex];
				visibleIds[currentIndex] = visibleIds[targetIndex];
				visibleIds[targetIndex] = temp;

				// Also swap in DOM so the preview updates immediately
				var targetEl = document.getElementById(visibleIds[currentIndex]);
				if (targetEl) {
					if (direction === 'up') {
						parent.insertBefore(wrapper, targetEl);
					} else {
						parent.insertBefore(targetEl, wrapper);
					}
				}

				var channel = getPreviewChannel();
				if (channel && visibleIds.length) {
					channel.send('sass-reorder-sidebar', {
						sidebarId: sidebarId,
						order: visibleIds
					});
				}

				setTimeout(function () {
					refreshSidebarMoveButtons(sidebarId);
				}, 150);
			}

			var moveUp = container.querySelector('.move-widget-up-button');
			if (moveUp) {
				moveUp.addEventListener('click', function (e) {
					e.preventDefault();
					e.stopPropagation();
					handleMove('up');
				});
			}

			var moveDown = container.querySelector('.move-widget-down-button');
			if (moveDown) {
				moveDown.addEventListener('click', function (e) {
					e.preventDefault();
					e.stopPropagation();
					handleMove('down');
				});
			}

			const removeBtn = container.querySelector('.remove-widget-button');
			if (removeBtn) {
				removeBtn.addEventListener('click', function (e) {
					e.preventDefault();
					e.stopPropagation();
					if (!window.confirm('Remove this widget?')) {
						return;
					}
					const channel = getPreviewChannel();
					if (!channel) {
						return;
					}
					channel.send('sass-remove-widget', {
						controlId: controlId,
						sidebarId: sidebarId,
						widgetId: widgetId
					});
				});
			}
		}
	}

	function initializeAllContainers() {
		document.querySelectorAll('.custom-widget-container').forEach(setupContainer);
		// Delay button refresh to allow all widgets to be processed
		setTimeout(function() {
			refreshAllMoveButtons();
		}, 100);
	}

	document.addEventListener('DOMContentLoaded', function () {
		initializeAllContainers();

		if (window.MutationObserver) {
			const observer = new MutationObserver(function (mutations) {
				let shouldRefresh = false;
				mutations.forEach(function (mutation) {
					mutation.addedNodes.forEach(function (node) {
						if (node.nodeType !== 1) {
							return;
						}
						if (node.classList && node.classList.contains('custom-widget-container')) {
							setupContainer(node);
							shouldRefresh = true;
						} else if (node.querySelectorAll) {
							node.querySelectorAll('.custom-widget-container').forEach(function (child) {
								setupContainer(child);
								shouldRefresh = true;
							});
						}
					});
				});
				if (shouldRefresh) {
					setTimeout(function() {
						refreshAllMoveButtons();
					}, 100);
				}
			});

			observer.observe(document.body, { childList: true, subtree: true });
		}
	});

	if (!wp || !wp.customize) {
		return;
	}

	var api = wp.customize, OldPreview;

	api.myCustomizerPreview = api.myCustomizerPreview || {
		init: function () {
			var self = this;
			this.preview.bind('active', function () {
				if (window.myCustomData) {
					self.preview.send('my-custom-event', window.myCustomData);
				}
			});
		},
		updateWidget: function (data) {
			var self = this;
			this.preview.bind('active', function () {
				self.preview.send('my-custom-event-updateWidget', { fakeData: data });
			});
		}
	};

	OldPreview = api.Preview;
	api.Preview = OldPreview.extend({
		initialize: function (params, options) {
			api.myCustomizerPreview.preview = this;
			OldPreview.prototype.initialize.call(this, params, options);
		}
	});

	api.bind('preview-ready', function () {
		api.preview.bind('sass-refresh-widgets', function (payload) {
			// Allow DOM to settle before reinitializing
			setTimeout(function() {
				initializeAllContainers();
				if (payload && payload.sidebarId) {
					setTimeout(function() {
						refreshSidebarMoveButtons(payload.sidebarId);
					}, 150);
				} else {
					setTimeout(function() {
						refreshAllMoveButtons();
					}, 150);
				}
			}, 50);
		});
	});

	$(function () {
		api.myCustomizerPreview.init();
		
		// Additional initialization after jQuery is ready
		setTimeout(function() {
			initializeAllContainers();
		}, 250);
	});
})(window.wp, window.jQuery);
