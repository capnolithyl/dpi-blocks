/* global document, window */
/** Initialize the plugin-owned site-search surfaces. */
(() => {
	'use strict';

	const initialize = () => {
		const surface = document.querySelector('[data-dpi-search-surface]');
		const triggers = Array.from(
			document.querySelectorAll('[data-dpi-search-open]')
		);

		if (!surface || !triggers.length || surface.dataset.dpiInitialized) {
			return;
		}

		surface.dataset.dpiInitialized = 'true';
		const closeButton = surface.querySelector('[data-dpi-search-close]');
		const input = surface.querySelector('[data-dpi-search-input]');
		const type = surface.dataset.dpiSearchType;
		const supportsDialog =
			'dialog' === type &&
			'function' === typeof surface.showModal &&
			'function' === typeof surface.close;
		const supportsPopover =
			'popover' === type && 'function' === typeof surface.showPopover;
		let activeTrigger = null;

		const setOpenState = (open) => {
			triggers.forEach((trigger) => {
				trigger.setAttribute(
					'aria-expanded',
					open && trigger === activeTrigger ? 'true' : 'false'
				);
			});
			document.documentElement.classList.toggle(
				'dpi-search-open',
				open && 'dialog' === type
			);
		};

		const nativePopoverIsOpen = () => {
			if (!supportsPopover) {
				return false;
			}

			try {
				return surface.matches(':popover-open');
			} catch {
				return false;
			}
		};

		const isOpen = () => {
			if ('dialog' === type) {
				return supportsDialog
					? Boolean(surface.open)
					: Boolean(surface.dataset.dpiSearchOpen);
			}

			return (
				nativePopoverIsOpen() || Boolean(surface.dataset.dpiSearchOpen)
			);
		};

		const positionPopover = () => {
			if ('popover' !== type || !activeTrigger || !isOpen()) {
				return;
			}

			const gap = 8;
			const triggerRect = activeTrigger.getBoundingClientRect();
			const surfaceRect = surface.getBoundingClientRect();
			const width =
				surfaceRect.width || Math.min(window.innerWidth - 32, 448);
			const left = Math.max(
				gap,
				Math.min(
					triggerRect.right - width,
					window.innerWidth - width - gap
				)
			);
			const top = triggerRect.bottom + gap;

			surface.style.setProperty('--dpi-search-left', `${left}px`);
			surface.style.setProperty('--dpi-search-top', `${top}px`);
		};

		if (
			('popover' === type && !supportsPopover) ||
			('dialog' === type && !supportsDialog)
		) {
			surface.hidden = true;
		}

		const open = (trigger) => {
			activeTrigger = trigger;

			if (supportsDialog) {
				surface.showModal();
			} else if (supportsPopover) {
				surface.showPopover();
			} else {
				surface.dataset.dpiSearchOpen = 'true';
				surface.hidden = false;
			}

			setOpenState(true);
			window.requestAnimationFrame(() => {
				positionPopover();
				input?.focus();
			});
		};

		const close = () => {
			if (supportsDialog) {
				surface.close();
			} else if (supportsPopover && nativePopoverIsOpen()) {
				surface.hidePopover();
			} else {
				delete surface.dataset.dpiSearchOpen;
				surface.hidden = true;
				setOpenState(false);
				activeTrigger?.focus();
				activeTrigger = null;
			}
		};

		triggers.forEach((trigger) => {
			trigger.addEventListener('click', () => {
				if (isOpen()) {
					close();
				} else {
					open(trigger);
				}
			});
		});

		closeButton?.addEventListener('click', close);

		if ('dialog' === type) {
			surface.addEventListener('click', (event) => {
				if (event.target === surface) {
					close();
				}
			});
			surface.addEventListener('close', () => {
				setOpenState(false);
				activeTrigger?.focus();
				activeTrigger = null;
			});
		} else if (supportsPopover) {
			surface.addEventListener('toggle', (event) => {
				const openNow = 'open' === event.newState;
				setOpenState(openNow);
				if (!openNow) {
					activeTrigger?.focus();
					activeTrigger = null;
				}
			});
		}

		document.addEventListener('keydown', (event) => {
			if ('Escape' === event.key && isOpen()) {
				close();
				return;
			}

			if (
				'Tab' === event.key &&
				'dialog' === type &&
				!supportsDialog &&
				isOpen()
			) {
				const focusable = Array.from(
					surface.querySelectorAll(
						'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
					)
				).filter((element) => !element.hidden);

				if (!focusable.length) {
					event.preventDefault();
					return;
				}

				const first = focusable[0];
				const last = focusable[focusable.length - 1];
				if (event.shiftKey && document.activeElement === first) {
					event.preventDefault();
					last.focus();
				} else if (!event.shiftKey && document.activeElement === last) {
					event.preventDefault();
					first.focus();
				}
			}
		});

		document.addEventListener('click', (event) => {
			if (
				'popover' === type &&
				!supportsPopover &&
				isOpen() &&
				!surface.contains(event.target) &&
				!triggers.some((trigger) => trigger.contains(event.target))
			) {
				close();
			}
		});

		window.addEventListener('resize', positionPopover);
		window.addEventListener('scroll', positionPopover, { passive: true });
	};

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', initialize);
	} else {
		initialize();
	}
})();
