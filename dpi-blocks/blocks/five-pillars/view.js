(() => {
	'use strict';

	const initialize = (scope = document) => {
		const roots = [];
		if (scope.matches?.('[data-dpi-five-pillars]')) roots.push(scope);
		roots.push(...(scope.querySelectorAll?.('[data-dpi-five-pillars]') || []));

		roots.forEach((root) => {
			if (root.dataset.dpiFivePillarsInitialized === 'true') return;

			const triggers = Array.from(root.querySelectorAll('[data-dpi-pillar-trigger]'));
			const panels = Array.from(root.querySelectorAll('[data-dpi-pillar-panel]'));
			const media = Array.from(root.querySelectorAll('[data-dpi-pillar-media]'));
			if (!triggers.length) return;
			root.dataset.dpiFivePillarsInitialized = 'true';

			const activate = (index, moveFocus = false) => {
				triggers.forEach((trigger, current) => {
					const active = current === index;
					trigger.setAttribute('aria-expanded', active ? 'true' : 'false');
					if (active && moveFocus) trigger.focus();
				});

				panels.forEach((panel) => {
					panel.hidden = Number(panel.dataset.dpiPillarIndex) !== index;
				});

				media.forEach((item) => {
					const active = Number(item.dataset.dpiPillarIndex) === index;
					item.hidden = !active;
					item.toggleAttribute('data-dpi-pillar-active', active);
				});
			};

			triggers.forEach((trigger, index) => {
				trigger.addEventListener('click', () => activate(index));
				trigger.addEventListener('keydown', (event) => {
					const destinations = {
						ArrowDown: (index + 1) % triggers.length,
						ArrowRight: (index + 1) % triggers.length,
						ArrowUp: (index - 1 + triggers.length) % triggers.length,
						ArrowLeft: (index - 1 + triggers.length) % triggers.length,
						Home: 0,
						End: triggers.length - 1,
					};
					if (Object.prototype.hasOwnProperty.call(destinations, event.key)) {
						event.preventDefault();
						activate(destinations[event.key], true);
					}
				});
			});

			activate(0);
		});
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => initialize(document));
	} else {
		initialize(document);
	}

	if (window.acf) {
		window.acf.addAction('render_block_preview/type=dpi/five-pillars', initialize);
	}
})();
