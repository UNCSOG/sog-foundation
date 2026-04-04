jQuery(function ($) {
	function initializeColorPickers($scope) {
		if (!$.fn.wpColorPicker) {
			return;
		}

		($scope || $(document)).find('.sog-rebrand__color-field').each(function () {
			const $field = $(this);

			if ($field.data('sogColorInit')) {
				return;
			}

			$field.wpColorPicker();
			$field.data('sogColorInit', true);
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
		$('[data-condition-field]').each(function () {
			const $node = $(this);
			const isVisible = matchesCondition($node);

			$node.toggleClass('hidden', !isVisible);
			$node.attr('aria-hidden', isVisible ? 'false' : 'true');
		});
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
});
