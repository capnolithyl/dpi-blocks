/* global document, MutationObserver, window */
(function (window, document, $) {
	'use strict';

	const config = window.DPIBlocksConfig || {};
	const reducedMotion = window.matchMedia
		? window.matchMedia('(prefers-reduced-motion: reduce)')
		: { matches: false };
	const feedObservers = new WeakMap();
	const dialogTriggers = new WeakMap();

	/** Return DOM roots from a document, element, or ACF jQuery scope. */
	const getRoots = (scope) => {
		if (!scope) {
			return [document];
		}

		if (scope.jquery) {
			return scope.toArray();
		}

		return [scope];
	};

	/** Find matching elements, including a matching scope root. */
	const findAll = (scope, selector) => {
		const matches = [];

		getRoots(scope).forEach((root) => {
			if (root.matches && root.matches(selector)) {
				matches.push(root);
			}

			if (root.querySelectorAll) {
				matches.push(...root.querySelectorAll(selector));
			}
		});

		return Array.from(new Set(matches));
	};

	/** Parse one renderer-provided Slick options object. */
	const getSlickOptions = (host) => {
		try {
			const options = JSON.parse(host.dataset.dpiSlick || '{}');

			if (reducedMotion.matches) {
				options.autoplay = false;
			}

			options.rows = 0;
			return options;
		} catch {
			return null;
		}
	};

	/** Build a theme-neutral Slick arrow using plugin-owned accessible labels. */
	const getArrow = (direction) => {
		const isPrevious = direction === 'previous';
		const label = isPrevious
			? config.previousLabel || 'Previous'
			: config.nextLabel || 'Next';
		const icon = isPrevious ? config.previousIcon : config.nextIcon;
		const button = $('<button type="button" />')
			.addClass(isPrevious ? 'slick-prev' : 'slick-next')
			.attr('aria-label', label);

		if (icon) {
			button.html(icon);
		} else {
			button.text(label);
		}

		return button;
	};

	/** Locate the actual carousel track for a normal block or shortcode feed. */
	const getTrack = (host) => {
		if (!host.hasAttribute('data-dpi-social-feed')) {
			return host;
		}

		const selector = host.dataset.dpiFeedSelector;

		if (selector) {
			try {
				const track = host.querySelector(selector);

				if (track) {
					return track;
				}
			} catch {
				// Fall through to the markup-level integration hook.
			}
		}

		return host.querySelector('[data-dpi-feed-track]');
	};

	/** Initialize one carousel when both its markup and Slick are ready. */
	const initializeSlider = (host) => {
		if (!$ || !$.fn || typeof $.fn.slick !== 'function') {
			return false;
		}

		if (host.closest('[hidden]')) {
			return false;
		}

		const track = getTrack(host);

		if (!track) {
			return false;
		}

		const $track = $(track);

		if ($track.hasClass('slick-initialized')) {
			$track.slick('setPosition');
			host.dataset.dpiSlickInitialized = 'true';
			return true;
		}

		if (track.children.length < 2) {
			return false;
		}

		const options = getSlickOptions(host);

		if (!options) {
			return false;
		}

		if (options.arrows) {
			options.prevArrow = getArrow('previous');
			options.nextArrow = getArrow('next');
		}

		$track.slick(options);
		host.dataset.dpiSlickInitialized = 'true';
		return true;
	};

	/** Wait for asynchronously rendered shortcode feeds without assuming a vendor. */
	const observeFeed = (host) => {
		if (
			feedObservers.has(host) ||
			typeof window.MutationObserver === 'undefined'
		) {
			return;
		}

		const observer = new MutationObserver(() => {
			if (!host.isConnected || initializeSlider(host)) {
				observer.disconnect();
				feedObservers.delete(host);
			}
		});

		observer.observe(host, { childList: true, subtree: true });
		feedObservers.set(host, observer);
	};

	/** Initialize every ready renderer-provided carousel in a scope. */
	const initializeSliders = (scope) => {
		findAll(scope, '[data-dpi-slick]').forEach((host) => {
			if (
				!initializeSlider(host) &&
				host.hasAttribute('data-dpi-social-feed')
			) {
				observeFeed(host);
			}
		});
	};

	/** Connect one category tablist with its panels and deferred carousels. */
	const initializeTabs = (tablist) => {
		if (tablist.dataset.dpiTabsInitialized === 'true') {
			return;
		}

		const block = tablist.closest('.dpi-community-slider');
		const tabs = Array.from(tablist.querySelectorAll('[data-dpi-tab]'));
		const panels = block
			? Array.from(block.querySelectorAll('[data-dpi-tab-panel]'))
			: [];

		if (!tabs.length || !panels.length) {
			return;
		}

		tablist.dataset.dpiTabsInitialized = 'true';

		const activate = (activeIndex, moveFocus) => {
			tabs.forEach((tab, index) => {
				const active = index === activeIndex;
				tab.setAttribute('aria-selected', active ? 'true' : 'false');
				tab.tabIndex = active ? 0 : -1;

				if (active && moveFocus) {
					tab.focus();
				}
			});

			panels.forEach((panel) => {
				const active =
					panel.id ===
					tabs[activeIndex].getAttribute('aria-controls');
				panel.hidden = !active;

				if (active) {
					initializeSliders(panel);
				}
			});
		};

		tabs.forEach((tab, index) => {
			tab.addEventListener('click', () => activate(index, false));
			tab.addEventListener('keydown', (event) => {
				const destinations = {
					ArrowRight: (index + 1) % tabs.length,
					ArrowLeft: (index - 1 + tabs.length) % tabs.length,
					Home: 0,
					End: tabs.length - 1,
				};

				if (
					Object.prototype.hasOwnProperty.call(
						destinations,
						event.key
					)
				) {
					event.preventDefault();
					activate(destinations[event.key], true);
				}
			});
		});

		const selected = Math.max(
			0,
			tabs.findIndex(
				(tab) => tab.getAttribute('aria-selected') === 'true'
			)
		);
		activate(selected, false);
	};

	/** Apply the visitor's reduced-motion preference to autoplaying media. */
	const updateMotion = (scope) => {
		findAll(scope || document, '.dpi-hero video[autoplay]').forEach(
			(video) => {
				if (reducedMotion.matches) {
					video.pause();
				} else {
					const play = video.play();
					if (play && typeof play.catch === 'function') {
						play.catch(() => {});
					}
				}
			}
		);

		findAll(scope || document, '[data-dpi-slick]').forEach((host) => {
			const track = getTrack(host);

			if (!track || !$ || !$(track).hasClass('slick-initialized')) {
				return;
			}

			if (reducedMotion.matches) {
				$(track).slick('slickPause');
				return;
			}

			const options = getSlickOptions(host);
			if (options && options.autoplay) {
				$(track).slick('slickPlay');
			}
		});
	};

	/** Initialize block interactions in a document or new editor preview. */
	const initialize = (scope) => {
		findAll(scope || document, '[data-dpi-tabs]').forEach(initializeTabs);
		initializeSliders(scope || document);
		updateMotion(scope || document);
	};

	const publicApi =
		window.DPIBlocks && typeof window.DPIBlocks === 'object'
			? window.DPIBlocks
			: {};

	publicApi.initialize = initialize;
	window.DPIBlocks = publicApi;

	/** Progressive enhancement for native staff biography dialogs. */
	document.addEventListener('click', (event) => {
		const opener = event.target.closest
			? event.target.closest('[data-dpi-dialog-open]')
			: null;

		if (opener) {
			if (
				event.defaultPrevented ||
				event.button > 0 ||
				event.altKey ||
				event.ctrlKey ||
				event.metaKey ||
				event.shiftKey
			) {
				return;
			}

			const dialog = document.getElementById(
				opener.getAttribute('aria-controls')
			);

			if (dialog && typeof dialog.showModal === 'function') {
				event.preventDefault();
				dialogTriggers.set(dialog, opener);
				dialog.showModal();
				document.documentElement.classList.add(
					'dpi-staff-card-dialog-open'
				);
				dialog.querySelector('[data-dpi-dialog-close]')?.focus();
			}
			return;
		}

		const closer = event.target.closest
			? event.target.closest('[data-dpi-dialog-close]')
			: null;

		if (closer) {
			closer.closest('dialog')?.close();
			return;
		}

		if (event.target.matches && event.target.matches('[data-dpi-dialog]')) {
			event.target.close();
		}
	});

	document.addEventListener(
		'close',
		(event) => {
			if (
				!event.target.matches ||
				!event.target.matches('[data-dpi-dialog]')
			) {
				return;
			}

			const trigger = dialogTriggers.get(event.target);
			dialogTriggers.delete(event.target);
			document.documentElement.classList.remove(
				'dpi-staff-card-dialog-open'
			);

			if (trigger && trigger.isConnected) {
				trigger.focus();
			}
		},
		true
	);

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () =>
			initialize(document)
		);
	} else {
		initialize(document);
	}

	if (typeof reducedMotion.addEventListener === 'function') {
		reducedMotion.addEventListener('change', () => updateMotion(document));
	} else if (typeof reducedMotion.addListener === 'function') {
		reducedMotion.addListener(() => updateMotion(document));
	}

	if (window.acf) {
		window.acf.addAction('ready', initialize);
		window.acf.addAction('append', initialize);
		[
			'dpi/community-slider',
			'dpi/hero',
			'dpi/social-media',
			'dpi/staff-card',
		].forEach((type) => {
			window.acf.addAction(
				`render_block_preview/type=${type}`,
				initialize
			);
		});
	}
})(window, document, window.jQuery);
