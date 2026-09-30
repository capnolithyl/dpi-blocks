(() => {
	'use strict';

	const initialize = (scope = document) => {
		const roots = [];
		if (scope.matches?.('[data-dpi-accordion]')) roots.push(scope);
		roots.push(...(scope.querySelectorAll?.('[data-dpi-accordion]') || []));

		roots.forEach((root) => {
			if (root.dataset.dpiAccordionInitialized === 'true') return;

			const items = Array.from(root.querySelectorAll('[data-dpi-accordion-item]'));
			if (!items.length) return;
			root.dataset.dpiAccordionInitialized = 'true';
			const allowMultiple = root.dataset.dpiAllowMultiple === 'true';

			const setOpen = (item, open) => {
				const trigger = item.querySelector('[data-dpi-accordion-trigger]');
				const panel = item.querySelector('[data-dpi-accordion-panel]');
				if (!trigger || !panel) return;
				trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
				panel.hidden = !open;
			};

			items.forEach((item) => setOpen(item, item.dataset.dpiInitialOpen === 'true'));

			items.forEach((item) => {
				const trigger = item.querySelector('[data-dpi-accordion-trigger]');
				if (!trigger) return;
				trigger.addEventListener('click', () => {
					const opening = trigger.getAttribute('aria-expanded') !== 'true';
					if (opening && !allowMultiple) {
						items.forEach((other) => {
							if (other !== item) setOpen(other, false);
						});
					}
					setOpen(item, opening);
				});
			});
		});
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => initialize(document));
	} else {
		initialize(document);
	}

	if (window.acf) {
		window.acf.addAction('render_block_preview/type=dpi/accordion', initialize);
	}
})();
