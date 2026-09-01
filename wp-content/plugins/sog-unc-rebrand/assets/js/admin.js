jQuery(function ($) {
	function initializeColorPickers($scope) {
		if (!$.fn.wpColorPicker) {
			return;
		}

		// UNC brand colors: Primary, Secondary, and Supporting palettes.
		const uncPalette = [
			// SOG custom colors
			'#1E3A57', '#414141', '#e5e5e5e5', '#DDF1FE',
			'#1E74AF', '#CBD5E1',
			// Primary
			'#4b9cd3', '#13294b', '#ffffff', '#007FAE',
			// Secondary
			'#000000', '#2c5080', '#b7d7ed', '#edf5fb',
			'#5b6670', '#a2aaad', '#cfd3d5', '#eceded',
			// Supporting
			'#e95550', '#fbc8b6', '#ff9e1b', '#fecf86',
			'#ffd340', '#ffeec2', '#006d6a', '#b6ddc0',
			'#9f237e', '#ebcae1', '#4e3c98', '#b1b8dd',
		];

		($scope || $(document)).find('.sog-rebrand__color-field').each(function () {
			const $field = $(this);

			if ($field.data('sogColorInit')) {
				return;
			}

			$field.wpColorPicker({ palettes: uncPalette });
			$field.data('sogColorInit', true);

			// Add hex code tooltips to palette swatches.
			$field.closest('.wp-picker-container').find('.iris-palette').each(function () {
				$(this).attr('title', String($(this).data('color') || ''));
			});
		});
	}

	function fieldSelector(field) {
		return '[name="sog_unc_rebrand_settings[' + field + ']"]';
	}

	function currentValue(field) {
		const $field = $(fieldSelector(field));

		if (!$field.length) {
			return '';
		}

		if ($field.first().is(':checkbox')) {
			return $field.first().is(':checked') ? '1' : '';
		}

		return String($field.first().val() || '');
	}

	function matchesCondition($node) {
		// OR condition: show if any of the listed {field, value} pairs match.
		const conditionFields = $node.data('condition-fields');
		if (conditionFields) {
			const pairs = typeof conditionFields === 'string' ? JSON.parse(conditionFields) : conditionFields;
			if (Array.isArray(pairs) && pairs.length) {
				return pairs.some(function (pair) {
					const actual = currentValue(String(pair.field || ''));
					const expected = String(pair.value || '');
					return actual === expected;
				});
			}
		}

		const field = String($node.data('condition-field') || '');
		const operator = String($node.data('condition-operator') || 'equals');
		const expectedRaw = String($node.data('condition-value') || '');
		const actual = currentValue(field);

		if (!field) {
			return true;
		}

		if (operator === 'not-empty') {
			return actual !== '';
		}

		const expected = expectedRaw.split(',').map(function (value) {
			return value.trim();
		}).filter(Boolean);

		if (!expected.length) {
			return actual !== '';
		}

		return expected.indexOf(actual) !== -1;
	}

	function syncConditionalFields() {
		$('[data-condition-field], [data-condition-fields]').each(function () {
			const $node = $(this);
			const isVisible = matchesCondition($node);

			$node.toggleClass('hidden', !isVisible);
			$node.attr('aria-hidden', isVisible ? 'false' : 'true');
		});
	}

	function syncBackButtonIconModeWarning() {
		const modeName = 'sog_unc_rebrand_settings[header_mobile_back_button_icon_mode]';
		const glyphName = 'sog_unc_rebrand_settings[header_mobile_back_button_icon_glyph]';
		const $mode = $('[name="' + modeName + '"]');
		const $glyph = $('[name="' + glyphName + '"]');

		if (!$mode.length || !$glyph.length) {
			return;
		}

		const mode = String($mode.val() || 'unicode').toLowerCase();
		const glyph = String($glyph.val() || '').trim();
		const isSvg = /<\s*svg\b/i.test(glyph);
		const isHtml = /<\s*\/?\s*[a-z][^>]*>/i.test(glyph);
		const isUnicode = /^(\\[uUxX][0-9a-fA-F]{2,6}|\\[0-9a-fA-F]{3,6}|U\+[0-9A-Fa-f]{4,6}|&#x[0-9a-fA-F]{2,6};?|&#[0-9]{2,7};?)$/.test(glyph);
		let warning = '';

		if (!glyph) {
			warning = '';
		} else if (mode === 'svg' && !isSvg) {
			warning = 'Mode is SVG, but the value does not look like SVG markup.';
		} else if (mode === 'html' && !isHtml) {
			warning = 'Mode is HTML, but the value does not look like HTML markup.';
		} else if (mode === 'unicode' && !isUnicode) {
			warning = 'Mode is Unicode, but the value does not look like a Unicode codepoint.';
		} else if (mode === 'glyph' && (isHtml || isUnicode)) {
			warning = 'Mode is Glyph/Text, but the value looks like HTML or Unicode notation.';
		} else if (mode !== 'html' && isHtml) {
			warning = 'HTML markup detected, but mode is not set to HTML.';
		} else if (mode !== 'svg' && isSvg) {
			warning = 'SVG markup detected, but mode is not set to SVG.';
		}

		$glyph.nextAll('.sog-rebrand__icon-mode-warning').remove();
		if (warning) {
			$glyph.after('<p class="description sog-rebrand__icon-mode-warning" style="color:#b32d2e; font-weight:600;">' + warning + '</p>');
		}
	}

	function nextSocialLinkIndex($editor) {
		const current = parseInt(String($editor.attr('data-next-index') || '0'), 10) || 0;
		$editor.attr('data-next-index', String(current + 1));

		return current;
	}

	function addSocialLinkItem($editor) {
		const template = $editor.find('.sog-rebrand__social-links-template').html();
		const index = nextSocialLinkIndex($editor);
		const markup = String(template || '').replace(/__index__/g, String(index));
		const $item = $(markup);

		$editor.find('.sog-rebrand__social-links-list').append($item);
		initializeColorPickers($item);
	}

	$(document).on('change input', '[name^="sog_unc_rebrand_settings["]', syncConditionalFields);
	$(document).on('change input', '[name="sog_unc_rebrand_settings[header_mobile_back_button_icon_mode]"], [name="sog_unc_rebrand_settings[header_mobile_back_button_icon_glyph]"]', syncBackButtonIconModeWarning);
	$(document).on('click', '.sog-rebrand__social-link-add', function () {
		addSocialLinkItem($(this).closest('[data-sog-rebrand-social-links]'));
	});
	$(document).on('click', '.sog-rebrand__social-link-remove', function () {
		$(this).closest('[data-sog-rebrand-social-link-item]').remove();
	});

	$(document).on('click', '.sog-rebrand__media-select', function () {
		const $input = $(this).closest('td').find('.sog-rebrand__media-input').first();
		const frame = wp.media({
			title: 'Select Image',
			button: {
				text: 'Use image'
			},
			library: {
				type: ['image']
			},
			multiple: false
		});

		frame.on('select', function () {
			const attachment = frame.state().get('selection').first().toJSON();
			const url = attachment.url || '';

			$input.val(url).trigger('input');
		});

		frame.open();
	});

	$(document).on('click', '.sog-rebrand__media-remove', function () {
		const $input = $(this).closest('td').find('.sog-rebrand__media-input').first();

		$input.val('').trigger('input');
	});

	initializeColorPickers($(document));
	syncConditionalFields();
	syncBackButtonIconModeWarning();
});
