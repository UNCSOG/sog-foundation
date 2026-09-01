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
		// Support both .sog-rebrand__mobile-nav and .sog-rebrand__navigation-cluster for mobile nav
		var mobileNav = header.querySelector('.sog-rebrand__mobile-nav') || header.querySelector('.sog-rebrand__navigation-cluster');

		function setupMobileSubmenuDrilldown(container) {
			if (!container || !container.classList.contains('sog-rebrand__mobile-nav')) {
				return;
			}

			container.querySelectorAll('.sog-rebrand__nav').forEach(function (nav) {
				var rootMenu = nav.querySelector(':scope > ul');

				if (!rootMenu || nav.dataset.sogRebrandDrilldownReady === 'true') {
					return;
				}

				var panel = document.createElement('div');
				panel.className = 'sog-rebrand__mobile-submenu-panel';
				panel.hidden = true;

				var backButton = document.createElement('button');
				backButton.type = 'button';
				backButton.className = 'sog-rebrand__submenu-back';

				var cssStyles = window.getComputedStyle(nav);
				var backButtonText = (container.dataset.sogRebrandMobileBackButtonText || '').trim() || cssStyles.getPropertyValue('--sog-rebrand-mobile-back-button-text').trim() || 'Back';
				// var backButtonGlyph = (container.dataset.sogRebrandMobileBackButtonIconGlyph || '').trim() || cssStyles.getPropertyValue('--sog-rebrand-mobile-back-button-icon-glyph').trim();
				// var backButtonIconMode = (container.dataset.sogRebrandMobileBackButtonIconMode || '').trim() || cssStyles.getPropertyValue('--sog-rebrand-mobile-back-button-icon-mode').trim() || 'unicode';
				// var backButtonIconFamily = (container.dataset.sogRebrandMobileBackButtonIconFamily || '').trim() || cssStyles.getPropertyValue('--sog-rebrand-mobile-back-button-icon-family').trim() || 'none';
				// var backButtonIconPackFontAwesome = (container.dataset.sogRebrandMobileBackButtonIconPackFontAwesome || '').trim() || cssStyles.getPropertyValue('--sog-rebrand-mobile-back-button-icon-pack-font-awesome').trim() || 'classic';

				// var iconFamilyMap = {
				// 	'font-awesome': '"Font Awesome 6 Free", "Font Awesome 5 Free", FontAwesome',
				// 	'bootstrap-icons': '"bootstrap-icons"',
				// 	'material-icons': '"Material Icons", "Material Symbols Outlined"'
				// };

				// var fontAwesomePackFamilyMap = {
				// 	'brands': '"Font Awesome 6 Brands"',
				// 	'chisel': '"Font Awesome 6 Chisel"',
				// 	'classic': '"Font Awesome 6 Free", "Font Awesome 5 Free", FontAwesome',
				// 	'duotone': '"Font Awesome 6 Duotone"',
				// 	'etch': '"Font Awesome 6 Etch"',
				// 	'graphite': '"Font Awesome 6 Graphite"',
				// 	'jelly': '"Font Awesome 6 Jelly"',
				// 	'mosaic': '"Font Awesome 6 Mosaic"',
				// 	'notdog': '"Font Awesome 6 Notdog"',
				// 	'pixel': '"Font Awesome 6 Pixel"',
				// 	'sharp': '"Font Awesome 6 Sharp"',
				// 	'sharp-duotone': '"Font Awesome 6 Sharp Duotone"',
				// 	'slab': '"Font Awesome 6 Slab"',
				// 	'thumbprint': '"Font Awesome 6 Thumbprint"',
				// 	'utility': '"Font Awesome 6 Utility"',
				// 	'vellum': '"Font Awesome 6 Vellum"',
				// 	'whiteboard': '"Font Awesome 6 Whiteboard"'
				// };

				// function decodeUnicodeGlyph(input) {
				// 	var value = String(input || '').trim();

				// 	if (!value) {
				// 		return '';
				// 	}

				// 	if (/^\\[uU][0-9a-fA-F]{4,6}$/.test(value)) {
				// 		return String.fromCodePoint(parseInt(value.slice(2), 16));
				// 	}

				// 	if (/^\\[xX][0-9a-fA-F]{2,6}$/.test(value)) {
				// 		return String.fromCodePoint(parseInt(value.slice(2), 16));
				// 	}

				// 	if (/^\\[0-9a-fA-F]{3,6}$/.test(value)) {
				// 		return String.fromCodePoint(parseInt(value.slice(1), 16));
				// 	}

				// 	if (/^U\+[0-9a-fA-F]{4,6}$/.test(value)) {
				// 		return String.fromCodePoint(parseInt(value.slice(2), 16));
				// 	}

				// 	if (/^&#x[0-9a-fA-F]{2,6};?$/.test(value)) {
				// 		return String.fromCodePoint(parseInt(value.replace(/^&#x/i, '').replace(/;$/, ''), 16));
				// 	}

				// 	if (/^&#[0-9]{2,7};?$/.test(value)) {
				// 		return String.fromCodePoint(parseInt(value.replace(/^&#/, '').replace(/;$/, ''), 10));
				// 	}

				// 	return value;
				// }

				// function createBackButtonIconNode(mode, glyph, iconFamily, iconPackFontAwesome) {
				// 	var iconSpan = document.createElement('span');
				// 	var normalizedMode = (mode || 'unicode').toLowerCase();
				// 	var iconMarkup;

				// 	iconSpan.className = 'sog-rebrand__submenu-back-icon';

				// 	if (!glyph) {
				// 		return null;
				// 	}

				// 	if (normalizedMode === 'svg') {
				// 		iconMarkup = document.createRange().createContextualFragment(glyph);
				// 		iconSpan.appendChild(iconMarkup);
				// 		var inlineSvg = iconSpan.querySelector('svg');
				// 		if (inlineSvg) {
				// 			if (!inlineSvg.getAttribute('width')) {
				// 				inlineSvg.setAttribute('width', '1em');
				// 			}
				// 			if (!inlineSvg.getAttribute('height')) {
				// 				inlineSvg.setAttribute('height', '1em');
				// 			}
				// 			if (!inlineSvg.getAttribute('fill')) {
				// 				inlineSvg.setAttribute('fill', 'currentColor');
				// 			}
				// 		}
				// 		return iconSpan;
				// 	}

				// 	if (normalizedMode === 'html') {
				// 		iconSpan.innerHTML = glyph;
				// 		return iconSpan;
				// 	}

				// 	if (normalizedMode === 'unicode') {
				// 		iconSpan.textContent = decodeUnicodeGlyph(glyph);
				// 	} else {
				// 		iconSpan.textContent = glyph;
				// 	}

				// 	if (iconFamily !== 'none' && iconFamilyMap[iconFamily]) {
				// 		if (iconFamily === 'font-awesome' && fontAwesomePackFamilyMap[iconPackFontAwesome]) {
				// 			iconSpan.style.fontFamily = fontAwesomePackFamilyMap[iconPackFontAwesome];
				// 		} else {
				// 			iconSpan.style.fontFamily = iconFamilyMap[iconFamily];
				// 		}
				// 		if (iconFamily === 'font-awesome') {
				// 			iconSpan.style.fontWeight = '900';
				// 		}
				// 	}

				// 	return iconSpan;
				// }

				// if (backButtonGlyph) {
				// 	var iconNode = createBackButtonIconNode(backButtonIconMode, backButtonGlyph, backButtonIconFamily, backButtonIconPackFontAwesome);
				// 	if (iconNode) {
				// 		backButton.appendChild(iconNode);
				// 	}
				// }

				var textSpan = document.createElement('span');
				textSpan.className = 'sog-rebrand__submenu-back-text';
				textSpan.textContent = backButtonText;
				backButton.appendChild(textSpan);
				panel.appendChild(backButton);

				var parentLinkWrap = document.createElement('div');
				parentLinkWrap.className = 'sog-rebrand__submenu-parent-link-wrap';
				panel.appendChild(parentLinkWrap);

				var submenuWrap = document.createElement('div');
				submenuWrap.className = 'sog-rebrand__submenu-items-wrap';
				panel.appendChild(submenuWrap);

				nav.appendChild(panel);

				function closePanel() {
					nav.classList.remove('is-submenu-active');
					panel.hidden = true;
					parentLinkWrap.innerHTML = '';
					submenuWrap.innerHTML = '';
					nav.querySelectorAll('li.sog-rebrand__submenu-parent-active').forEach(function (item) {
						item.classList.remove('sog-rebrand__submenu-parent-active');
					});
					nav.querySelectorAll('.sog-rebrand__submenu-toggle[aria-expanded="true"]').forEach(function (btn) {
						btn.setAttribute('aria-expanded', 'false');
					});
				}

				function openPanel(parentLi) {
					var parentAnchor = parentLi.querySelector(':scope > a');
					var submenu = parentLi.querySelector(':scope > ul');

					if (!parentAnchor || !submenu) {
						return;
					}

					nav.querySelectorAll('li.sog-rebrand__submenu-parent-active').forEach(function (item) {
						item.classList.remove('sog-rebrand__submenu-parent-active');
					});

					nav.querySelectorAll('.sog-rebrand__submenu-toggle[aria-expanded="true"]').forEach(function (btn) {
						btn.setAttribute('aria-expanded', 'false');
					});

					parentLi.classList.add('sog-rebrand__submenu-parent-active');
					var toggleButton = parentLi.querySelector(':scope > .sog-rebrand__submenu-toggle');

					if (toggleButton) {
						toggleButton.setAttribute('aria-expanded', 'true');
					}

					parentLinkWrap.innerHTML = '';
					submenuWrap.innerHTML = '';

					var clickableParent = parentAnchor.cloneNode(true);
					clickableParent.classList.add('sog-rebrand__submenu-parent-link');
					parentLinkWrap.appendChild(clickableParent);

					submenuWrap.appendChild(submenu.cloneNode(true));
					nav.classList.add('is-submenu-active');
					panel.hidden = false;
				}

				backButton.addEventListener('click', closePanel);

				rootMenu.querySelectorAll(':scope > li.menu-item-has-children').forEach(function (parentLi) {
					parentLi.classList.add('sog-rebrand__mobile-has-submenu');

					var parentAnchor = parentLi.querySelector(':scope > a');
					var submenu = parentLi.querySelector(':scope > ul');

					if (!parentAnchor || !submenu) {
						return;
					}

					if (!parentLi.querySelector(':scope > .sog-rebrand__submenu-toggle')) {
						var submenuToggle = document.createElement('button');
						submenuToggle.type = 'button';
						submenuToggle.className = 'sog-rebrand__submenu-toggle';
						submenuToggle.setAttribute('aria-label', 'Open submenu for ' + parentAnchor.textContent.trim());
						submenuToggle.setAttribute('aria-expanded', 'false');
						submenuToggle.textContent = '';
						parentLi.insertBefore(submenuToggle, submenu);
					}
				});

			rootMenu.addEventListener('click', function (event) {
				var toggleButton = event.target.closest('.sog-rebrand__submenu-toggle');

				if (toggleButton && rootMenu.contains(toggleButton)) {
					event.preventDefault();
					var toggleLi = toggleButton.closest('li.menu-item-has-children');
					if (toggleLi) {
						openPanel(toggleLi);
					}
					return;
				}

				var link = event.target.closest('a');
				if (!link || !rootMenu.contains(link) || nav.classList.contains('is-submenu-active')) {
					return;
				}

				var li = link.closest('li.menu-item-has-children');
				if (!li || li.parentElement !== rootMenu) {
					return;
				}

				event.preventDefault();
				openPanel(li);
			});

			container.addEventListener('sogRebrand:mobileNavClosed', closePanel);
			nav.dataset.sogRebrandDrilldownReady = 'true';
		});
		}

		function closeMobileNav() {
			if (!toggle || !mobileNav) {
				return;
			}
			toggle.setAttribute('aria-expanded', 'false');
			header.classList.remove('is-mobile-menu-open');
			if (mobileNav.classList.contains('sog-rebrand__mobile-nav')) {
				mobileNav.hidden = true;
				mobileNav.dispatchEvent(new CustomEvent('sogRebrand:mobileNavClosed'));
			}
		}

		setupResponsiveComponent(header);

		if (!toggle || !mobileNav) {
			return;
		}

		setupMobileSubmenuDrilldown(mobileNav);

		toggle.addEventListener('click', function () {
			var expanded = toggle.getAttribute('aria-expanded') === 'true';
			toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
			if (mobileNav.classList.contains('sog-rebrand__mobile-nav')) {
				mobileNav.hidden = expanded;
				if (expanded) {
					mobileNav.dispatchEvent(new CustomEvent('sogRebrand:mobileNavClosed'));
				}
			}
			header.classList.toggle('is-mobile-menu-open', !expanded);
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
