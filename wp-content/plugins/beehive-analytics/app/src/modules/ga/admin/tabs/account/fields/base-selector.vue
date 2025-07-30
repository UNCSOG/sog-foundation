<template>
	<div class="sui-form-field">
		<label :for="selectId" class="sui-label" v-text="label"></label>
		<select
			v-bind:id="selectId"
			class="sui-select"
			v-model="selectedValue"
			@change="$emit('change', selectedValue)"
		></select>
		<span
			v-if="endpoint === 'streams' && !selectedValue"
			class="sui-description"
			v-html="$i18n.desc.account_not_here"
		></span>
	</div>
</template>

<script>
export default {
	name: 'BaseSelector',

	props: {
		label: {
			type: String,
			required: true,
		},
		placeholder: {
			type: String,
			default: '',
		},
		endpoint: {
			type: String,
			required: true,
		},
		parentKey: {
			type: String,
			default: null,
		},
		parentValue: {
			type: String,
			default: null,
		},
	},

	data() {
		return {
			selectedValue: null,
			error: true,
		}
	},

	computed: {
		selectId() {
			return `beehive-${this.endpoint}-selector`
		},

		generateKey() {
			switch (this.endpoint) {
				case 'streams':
					return 'stream'
				case 'properties':
					return 'property'
				case 'accounts':
					return 'account'
				default:
					return ''
			}
		},
	},
	methods: {
		loadSavedValue() {
			const key = this.generateKey
			const value = this.getOption(key, 'google')
			const label = this.getOption(key, 'misc')
			if (!value) return
			let newOption
			if (label) {
				newOption = new Option(label.text, label.id, true, true)
			} else {
				newOption = new Option(value, value, true, true)
			}
			const element = document.getElementById(this.selectId)
			element.append(newOption)
		},

		formatResponse(items, key) {
			if (this.endpoint === 'streams') {
				return {
					id: items[key].name, // Use the `name` property for ID
					text: `${items[key].url} (${items[key].title})`, // Combine `url` and `title`
					measurement: items[key].measurement,
					url: items[key].url,
				}
			}
			return { id: key, text: items[key] }
		},

		setupSelect2(selectElement) {
			selectElement.SUIselect2({
				placeholder: `${this.placeholder}`,
				minimumResultsForSearch: Infinity,
				dropdownParent: jQuery(selectElement).closest(
					'.sui-form-field'
				), // Set the modal container as the dropdown parent
				ajax: {
					url: `${beehiveVars.rest.base}v1/data/${this.endpoint}`,
					dataType: 'json',
					delay: 250,
					data: (params) => {
						const data = {
							pageToken:
								localStorage.getItem(
									`${this.endpoint}NextPageToken`
								) || null,
						}
						if (this.parentKey) {
							if (this.parentValue) {
								data[this.parentKey] = this.parentValue
							} else {
								data[this.parentKey] = this.getOption(
									this.parentKey,
									'google'
								)
							}
						}
						return data
					},
					processResults: (response) => {
						// Use dynamic endpoint to extract data
						const items = response[this.endpoint] || []
						const pageToken = response.pageToken

						if (pageToken) {
							localStorage.setItem(
								`${this.endpoint}NextPageToken`,
								pageToken
							)
						} else {
							localStorage.removeItem(
								`${this.endpoint}NextPageToken`
							)
						}

						const results = Object.keys(items).map((key) =>
							this.formatResponse(items, key)
						)

						return {
							results,
							pagination: { more: !!pageToken },
						}
					},
				},
			})

			// Adjust z-index for the dropdown in modal.
			selectElement.on('select2:open', () => {
				const dropdown = jQuery('.select2-container')
				dropdown.css('z-index', 1050) // Bootstrap modal z-index is 1040
			})

			selectElement.on('select2:select', (e) => {
				this.selectedValue = e.params.data.id
				this.$emit('change', e.params.data)
			})

			selectElement.on('select2:opening', () => {
				localStorage.removeItem(`${this.endpoint}NextPageToken`)
			})
		},

		initializeSelect2() {
			const selectElement = jQuery(`#${this.selectId}`)
			this.setupSelect2(selectElement)
		},

		refreshSelect2() {
			const propertyElement = jQuery(`#${this.selectId}`)
			propertyElement.SUIselect2('destroy').val(null).trigger('change')
			this.setupSelect2(propertyElement)
			if ('beehive-properties-selector' === this.selectId) {
				const streamElement = jQuery('#beehive-streams-selector')
				streamElement.val(null).trigger('change')
			}
		},
	},

	mounted() {
		this.loadSavedValue()
		this.initializeSelect2()
	},

	watch: {
		parentValue(newVal, oldVal) {
			if (newVal !== oldVal) {
				this.refreshSelect2()
			}
		},
	},
}
</script>
