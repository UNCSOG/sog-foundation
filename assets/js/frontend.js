document.addEventListener('DOMContentLoaded', function () {
	document.documentElement.classList.add('sog-rebrand__js');

	var mobileClass = 'sog-rebrand--is-mobile';

	function setupResponsiveComponent(component) {
		var breakpoint = parseInt(component.dataset.sogRebrandMobileBreakpoint || '', 10);

		if (!breakpoint) {
			return;
		}

		var mediaQuery = window.matchMedia('(max-width: ' + breakpoint + 'px)');

		function syncComponentState(event) {
			component.classList.toggle(mobileClass, event.matches);
		}

		syncComponentState(mediaQuery);

		if (typeof mediaQuery.addEventListener === 'function') {
			mediaQuery.addEventListener('change', syncComponentState);
			return;
		}

		mediaQuery.addListener(syncComponentState);
	}

	function setupHeader(header) {
		var toggle = header.querySelector('.sog-rebrand__menu-toggle');
		var mobileNav = header.querySelector('.sog-rebrand__mobile-nav');

		function closeMobileNav() {
			if (!toggle || !mobileNav) {
				return;
			}

			toggle.setAttribute('aria-expanded', 'false');
			mobileNav.hidden = true;
		}

		setupResponsiveComponent(header);

		if (!toggle || !mobileNav) {
			return;
		}

		toggle.addEventListener('click', function () {
			var expanded = toggle.getAttribute('aria-expanded') === 'true';
			toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
			mobileNav.hidden = expanded;
		});

		window.addEventListener('resize', function () {
			if (!header.classList.contains(mobileClass)) {
				closeMobileNav();
			}
		});
	}

	document.querySelectorAll('[data-sog-rebrand-component="header"]').forEach(setupHeader);
	document.querySelectorAll('[data-sog-rebrand-component="footer"]').forEach(setupResponsiveComponent);
});
