(() => {
	'use strict';

	const blockList = document.querySelector('[data-dpi-block-list]');

	if (!blockList) {
		return;
	}

	const checkboxes = Array.from(
		blockList.querySelectorAll('input[type="checkbox"]')
	);

	document.querySelectorAll('[data-dpi-block-toggle]').forEach((button) => {
		button.addEventListener('click', () => {
			const shouldSelect = button.dataset.dpiBlockToggle === 'select';

			checkboxes.forEach((checkbox) => {
				checkbox.checked = shouldSelect;
				checkbox.dispatchEvent(new Event('change', { bubbles: true }));
			});
		});
	});
})();
