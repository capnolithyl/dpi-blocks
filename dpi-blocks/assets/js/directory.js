/* global document, ResizeObserver, window */
/** Directory overflow navigation and optional staff biography dialogs. */
(() => {
	'use strict';

	const initializeNavigation = (directory) => {
		const navigation = directory.querySelector('[data-dpi-group-nav]');
		const track = navigation?.querySelector('[data-dpi-group-slider]');
		const previous = navigation?.querySelector('[data-dpi-group-prev]');
		const next = navigation?.querySelector('[data-dpi-group-next]');
		const links = Array.from(
			navigation?.querySelectorAll('[data-dpi-group-link]') || []
		);

		if (!track || !previous || !next || !links.length) {
			return;
		}

		const jquery = window.jQuery;
		if (!jquery || 'function' !== typeof jquery.fn.slick) {
			return;
		}

		const $track = jquery(track);
		let lastWidth = -1;
		const reducedMotion = window.matchMedia(
			'(prefers-reduced-motion: reduce)'
		).matches;
		const naturalGap =
			Number.parseFloat(window.getComputedStyle(track).columnGap) || 0;
		const naturalContentWidth = () =>
			links.reduce(
				(total, link) => total + link.getBoundingClientRect().width,
				0
			) +
			naturalGap * Math.max(0, links.length - 1);
		const shouldOverflow = () => {
			return naturalContentWidth() > navigation.clientWidth + 1;
		};
		const updateControls = (currentSlide = 0) => {
			previous.disabled = currentSlide <= 0;
			next.disabled = currentSlide >= links.length - 1;
		};

		$track.on('init reInit afterChange', (_event, slick, currentSlide) => {
			updateControls(
				Number.isInteger(currentSlide)
					? currentSlide
					: slick.currentSlide
			);
		});
		previous.addEventListener('click', () => {
			if ($track.hasClass('slick-initialized')) {
				$track.slick('slickPrev');
			}
		});
		next.addEventListener('click', () => {
			if ($track.hasClass('slick-initialized')) {
				$track.slick('slickNext');
			}
		});

		const initializeSlider = () => {
			if ($track.hasClass('slick-initialized')) {
				return;
			}
			previous.hidden = false;
			next.hidden = false;
			$track.slick({
				accessibility: true,
				adaptiveHeight: false,
				arrows: false,
				draggable: true,
				infinite: false,
				rows: 0,
				slidesToScroll: 1,
				speed: reducedMotion ? 0 : 350,
				swipeToSlide: true,
				variableWidth: true,
				waitForAnimate: false,
			});
		};

		const destroySlider = () => {
			if ($track.hasClass('slick-initialized')) {
				$track.slick('unslick');
			}
			previous.hidden = true;
			next.hidden = true;
		};

		const update = () => {
			const currentWidth = Math.round(
				navigation.getBoundingClientRect().width
			);
			if (currentWidth === lastWidth) {
				return;
			}
			lastWidth = currentWidth;
			if (shouldOverflow()) {
				initializeSlider();
			} else {
				destroySlider();
			}
		};

		links.forEach((link) => {
			link.addEventListener('click', () => {
				links.forEach((candidate) => {
					if (candidate === link) {
						candidate.setAttribute('aria-current', 'location');
					} else {
						candidate.removeAttribute('aria-current');
					}
				});
			});
		});
		if ('ResizeObserver' in window) {
			new ResizeObserver(update).observe(navigation);
		} else {
			window.addEventListener('resize', update);
		}
		document.fonts?.ready?.then(() => {
			lastWidth = -1;
			update();
		});
		update();
	};

	const initializeDialogs = (directory) => {
		const dialogs = Array.from(
			directory.querySelectorAll('[data-dpi-staff-dialog]')
		);
		if (!dialogs.length) {
			return;
		}

		let activeTrigger = null;
		const setLocked = (locked) =>
			document.documentElement.classList.toggle(
				'dpi-staff-dialog-open',
				locked
			);

		directory.addEventListener('click', (event) => {
			const trigger = event.target.closest?.(
				'[data-dpi-staff-modal-open]'
			);
			if (
				!trigger ||
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
				trigger.getAttribute('aria-controls')
			);
			if (!dialog || 'function' !== typeof dialog.showModal) {
				return;
			}

			event.preventDefault();
			activeTrigger = trigger;
			dialog.showModal();
			setLocked(true);
			window.requestAnimationFrame(() =>
				dialog.querySelector('[data-dpi-staff-modal-close]')?.focus()
			);
		});

		dialogs.forEach((dialog) => {
			dialog
				.querySelector('[data-dpi-staff-modal-close]')
				?.addEventListener('click', () => dialog.close());
			dialog.addEventListener('click', (event) => {
				if (event.target === dialog) {
					dialog.close();
				}
			});
			dialog.addEventListener('close', () => {
				setLocked(false);
				activeTrigger?.focus();
				activeTrigger = null;
			});
		});
	};

	const initialize = () => {
		document
			.querySelectorAll('[data-dpi-directory]')
			.forEach((directory) => {
				initializeNavigation(directory);
				initializeDialogs(directory);
			});
	};

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', initialize);
	} else {
		initialize();
	}
})();
