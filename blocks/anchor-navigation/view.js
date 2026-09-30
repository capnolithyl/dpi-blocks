(() => {
	'use strict';

	const reducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)');

	const initialize = (scope = document) => {
		const roots = [];
		if (scope.matches?.('[data-dpi-anchor-navigation]')) roots.push(scope);
		roots.push(...(scope.querySelectorAll?.('[data-dpi-anchor-navigation]') || []));

		roots.forEach((root) => {
			if (root.dataset.dpiAnchorNavigationInitialized === 'true') return;
			root.dataset.dpiAnchorNavigationInitialized = 'true';

			root.querySelectorAll('[data-dpi-anchor-link]').forEach((link) => {
				link.addEventListener('click', (event) => {
					const hash = link.hash;
					if (!hash || hash.length < 2) return;
					const target = document.getElementById(decodeURIComponent(hash.slice(1)));
					if (!target) return;

					event.preventDefault();
					const offset = Number.parseFloat(getComputedStyle(root).getPropertyValue('--dpi-anchor-navigation-offset')) || 0;
					const top = target.getBoundingClientRect().top + window.scrollY - offset;
					window.scrollTo({ top, behavior: reducedMotion?.matches ? 'auto' : 'smooth' });
					history.pushState(null, '', hash);
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
		window.acf.addAction('render_block_preview/type=dpi/anchor-navigation', initialize);
	}
})();
