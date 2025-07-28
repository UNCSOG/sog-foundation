<?php

namespace OTGS\Toolset\CRED\Controller\Compatibility\Wpml\Integration\FormsTranslation;

use OTGS\Toolset\Common\Field\FieldDefinitionRepositoryFactory;
use Toolset_Element_Domain;

class ATE {

	const TOP_LEVEL_GROUP      = 'Toolset Forms';
	const TOP_LEVEL_GROUP_SLUG = 'toolset-forms';

	const PREFIX             = 'toolset-forms-';

	const MESSAGE_PREFIX     = 'message-';
	const MESSAGE_GROUP      = 'Form Messages';
	const MESSAGE_GROUP_SLUG = 'messages';

	const NOTIFICATION_REGEX      = '/(?<notificationId>\d+)-notification-(?<notificationField>.+)/';
	const NOTIFICATION_GROUP      = 'Form Notification';
	const NOTIFICATION_GROUP_SLUG = 'notification';

	const FORM_FIELDS       = [
		'title'           => 'Form Title',
		'display-message' => 'Form Message After Submission',
	];
	const FORM_SETTINGS_GROUP      = 'Form Settings';
	const FORM_SETTINGS_GROUP_SLUG = 'settings';

	const ACTION_FIELDS       = [
		'form_submit-default-value' => 'Submit Button Label',   // Post and User Forms.
		'submit'                    => 'Submit Button Label',   // Relationship Forms.
		'message-cancel'            => 'Cancel Button Label',   // Relationship Forms.
	];
	const ACTION_FIELDS_GROUP      = 'Form Actions';
	const ACTION_FIELDS_GROUP_SLUG = 'actions';
	
	const FIELD_GROUP       = 'Form Field';
	const FIELD_GROUP_SLUG  = 'field';
	const FIELD_DATA_SUFFIX = [
		'-label'               => 'Field Label',
		'-select_text'         => 'Field Select Text',
		// The default value ebtry comes from Forms, or defaults to the Types field definition value, if set.
		'-default-value'       => 'Field Default Value',
		// The next entries come from Types settings, as we can override their translations.
		'-validation-required' => 'Field Required Message',
		'-description'         => 'Field Description',
		'-placeholder'         => 'Field Placeholder',
	];

	const FIELD_VALIDATION_REGEX = '/(?<fieldName>.+)(?:-validation-)(?:.+)/';
	const FIELD_VALIDATION       = 'Field Validation Message';

	const FIELD_OPTION_REGEX        = '/(?<fieldName>.+)(?:-option-)(?<fieldOptionSlug>.+)/';
	const FIELD_OPTION_TYPES_PREFIX = 'wpcf-fields-';
	const FIELD_OPTION_TYPES_REGEX  = '/(?:wpcf-fields-)(?<fieldType>.+)(?:-option-)(?<fieldOptionSlug>.+)/';
	const FIELD_OPTION              = 'Field Option';

	/** @var array */
	private $formTypes = [];

	public function initialize() {
		add_filter( 'wpml_tm_adjust_translation_fields', [ $this, 'addGroupsAndLabels' ], 10, 2 );
		add_filter( 'wpml_tm_adjust_translation_job', [ $this, 'applyFieldsOrder' ], 10, 2 );
	}

	/**
	 * @param \stdClass $job
	 *
	 * @return bool
	 */
	private function isOurJob( $job ) {
		if ( ! property_exists( $job, 'original_post_type' ) ) {
			return false;
		}
		return 'package_' . Packages::PACKAGE_SLUG === $job->original_post_type;
	}

	/**
	 * @param array[]   $fields
	 * @param \stdClass $job
	 *
	 * @return array[]
	 */
	public function addGroupsAndLabels( $fields, $job ) {
		if ( ! $this->isOurJob( $job ) ) {
			return $fields;
		}

		foreach ( $fields as &$field ) {
			$field = $this->processField( $field );
		}

		return $fields;
	}

	/**
	 * @param array     $fields
	 * @param \stdClass $job
	 *
	 * @return array
	 */
	public function applyFieldsOrder( $fields, $job ) {
		if ( ! $this->isOurJob( $job ) ) {
			return $fields;
		}

		$jobArray = (array) $job;
		$formId   = apply_filters( 'wpml_element_id_from_package', null, toolset_getarr( $jobArray, 'original_doc_id', null ) );

		// Patterns define the interfix of the slug of the internal group for any given field.
		$sectionPatterns = [
			self::FORM_SETTINGS_GROUP_SLUG,
			self::FIELD_GROUP_SLUG,
			self::ACTION_FIELDS_GROUP_SLUG,
			self::NOTIFICATION_GROUP_SLUG,
			self::MESSAGE_GROUP_SLUG
		];

		$fieldsBySection             = array_fill_keys( $sectionPatterns, [] );
		$fieldsBySection['orphaned'] = [];

		/**
		 * @param array $field
		 *
		 * @return int|null
		 */
		$getGroupId = function( $field ) {
			// See WPML on WPML_TM_Xliff_Writer::get_translation_unit_data.
			$extraData      = toolset_getnest( $field, [ 'attributes', 'extradata' ], '' );
			$fieldExtraData = json_decode( str_replace( '&quot;', '"', $extraData ) );
			if ( null === $fieldExtraData ) {
				return null;
			}

			$fieldExtraDataList = (array) $fieldExtraData;
			return toolset_getarr( $fieldExtraDataList, 'group_id', null );
		};

		$addToSection = function( $field, $section, $fieldId, $itemId ) use ( &$fieldsBySection ) {
			$fieldsBySection[ $section ][ $fieldId ][ $itemId ] = $field;
		};

		array_walk( $fields, function( $field, $fieldKey ) use ( $sectionPatterns, $getGroupId, $addToSection ) {
			$groupId = $getGroupId( $field );

			if ( null === $groupId ) {
				$addToSection( $field, 'orphaned', $fieldKey, $fieldKey );
				return;
			}

			foreach ( $sectionPatterns as $section ) {
				if ( 0 === strpos( $groupId, self::TOP_LEVEL_GROUP_SLUG . '/' . self::TOP_LEVEL_GROUP_SLUG . '-' . $section ) ) {
					$fieldName = trim( str_replace( self::TOP_LEVEL_GROUP_SLUG . '/' . self::TOP_LEVEL_GROUP_SLUG . '-' . $section, '', $groupId ), '-' );
					$addToSection( $field, $section, $fieldName, $fieldKey );
					return;
				}
			}

			$addToSection( $field, 'orphaned', $fieldKey, $fieldKey );
		} );

		$fieldsBySection[ self::FIELD_GROUP_SLUG ] = $this->orderFields( $fieldsBySection[ self::FIELD_GROUP_SLUG ], $formId );

		foreach ( $fieldsBySection as $sectionKey => $sectionFields ) {
			$fieldsBySection[ $sectionKey ] = $this->flatten( $sectionFields, 1 );
		}

		// Return in order: form fields, other (orphaned) form items, submit button, settings, notifications, confirmations.
		return array_merge(
			$fieldsBySection[ self::FORM_SETTINGS_GROUP_SLUG ],
			$fieldsBySection[ self::FIELD_GROUP_SLUG ],
			$fieldsBySection[ self::ACTION_FIELDS_GROUP_SLUG ],
			$fieldsBySection[ self::NOTIFICATION_GROUP_SLUG ],
			$fieldsBySection[ self::MESSAGE_GROUP_SLUG ],
			$fieldsBySection[ 'orphaned' ]
		);
	}

	/**
	 * @param array $groups
	 *
	 * @return array
	 */
	private function addTopLevelGroup( $groups ) {
		return array_merge(
			[ self::TOP_LEVEL_GROUP_SLUG => self::TOP_LEVEL_GROUP ],
			$groups
		);
	}

	/**
	 * @param int $formId
	 *
	 * @return string|false
	 */
	private function getFormType( $formId ) {
		if ( ! array_key_exists( $formId, $this->formTypes ) ) {
			$this->formTypes[ $formId ] = get_post_type( $formId );
		}
		return $this->formTypes[ $formId ];
	}

	/**
	 * @param array $field
	 *
	 * @return array|null
	 */
	private function getFieldInfo( $field ) {
		$fieldType = toolset_getarr( $field, 'field_type', '' );
		$hasFieldData = preg_match( '/' . self::PREFIX . '(?<formId>\d+)-(?<fieldData>.+)/', $fieldType, $matches );
		if ( $hasFieldData ) {
			return [
				'formId'     => toolset_getarr( $matches, 'formId' ),
				'fieldData'  => toolset_getarr( $matches, 'fieldData' ),
				'fieldTitle' => toolset_getarr( $field, 'title', '' ),
			];
		}
		return null;
	}

	/**
	 * @param array $fieldData
	 * @param string $formType
	 *
	 * @return string|null
	 */
	private function getMessage( $fieldData, $formType ) {
		if ( 0 === strpos( $fieldData, self::MESSAGE_PREFIX ) ) {
			// Return the message descriptions, they are human readable already
			$messageId = substr_replace( $fieldData, '', 0, strlen( self::MESSAGE_PREFIX ) );
			switch ( $formType ) {
				case \OTGS\Toolset\CRED\Controller\Forms\Post\Main::POST_TYPE:
					$model = \CRED_Loader::get('MODEL/Forms');
					$messageDescriptions = $model->getDefaultMessageDescriptions();
					return toolset_getarr( $messageDescriptions, $messageId, null );
				case \OTGS\Toolset\CRED\Controller\Forms\User\Main::POST_TYPE:
					$model = \CRED_Loader::get('MODEL/UserForms');
					$messageDescriptions = $model->getDefaultMessageDescriptions();
					return toolset_getarr( $messageDescriptions, $messageId, null );
				case \CRED_Association_Form_Main::ASSOCIATION_FORMS_POST_TYPE:
					$model    = new \OTGS\Toolset\CRED\Model\Forms\Association\Messages\DefaultMessages();
					$messages = $model->getDefaultMessages();
					$messageData = toolset_getarr( $messages, $messageId, [] );
					return toolset_getarr( $messageData, 'description', null );
				default:
					return null;
			}
		}
		return null;
	}

	/**
	 * @param array $fieldData
	 *
	 * @return array|null
	 */
	private function getNotificationData( $fieldData ) {
		if ( preg_match( self::NOTIFICATION_REGEX, $fieldData, $matches ) ) {
			$title = $matches['notificationField'];
			switch( $matches['notificationField'] ) {
				case 'subject':
					$title = 'Email Subject';
					break;
				case 'body':
					$title = 'Email Body';
					break;
				case 'from':
					$title = 'Sender Name';
					break;
			}
			return [
				'id'    => $matches['notificationId'],
				'title' => $title,
			];
		}
		return null;
	}

	/**
	 * @param string $domain
	 *
	 * @return array
	 */
	private function getTypesFieldsWithOptions( $domain ) {
		static $fieldsByDomain = [];

		if ( array_key_exists( $domain, $fieldsByDomain ) ) {
			return $fieldsByDomain[ $domain ];
		}

		$fieldsFactory             = new FieldDefinitionRepositoryFactory();
		$fieldsRepository          = $fieldsFactory->get_repository( $domain );
		$fieldsByDomain[ $domain ] = $fieldsRepository->get_types_fields_with_options();

		return $fieldsByDomain[ $domain ];
	}

	/**
	 * @param string $defaultName
	 * @param string $optionSlug
	 * @param string $formType
	 *
	 * @return string
	 */
	private function getTypesFieldNameFromOption( $defaultName, $optionSlug, $formType ) {
		if ( ! preg_match( self::FIELD_OPTION_TYPES_REGEX, $optionSlug, $matches ) ) {
			return $defaultName;
		}

		$fieldsWithOptions = [];
		switch ( $formType ) {
			case \OTGS\Toolset\CRED\Controller\Forms\Post\Main::POST_TYPE:
				$fieldsWithOptions = $this->getTypesFieldsWithOptions( Toolset_Element_Domain::POSTS );
				break;
			case \OTGS\Toolset\CRED\Controller\Forms\User\Main::POST_TYPE:
				$fieldsWithOptions = $this->getTypesFieldsWithOptions( Toolset_Element_Domain::USERS );
				break;
		}

		foreach ( $fieldsWithOptions as $field ) {
			$metaKey = self::FIELD_OPTION_TYPES_PREFIX . toolset_getarr( $field, 'type', 'generic');
			$metaOptions = toolset_getnest( $field, [ 'data', 'options' ], [] );
			if ( array_key_exists( $optionSlug, $metaOptions ) ) {
				return toolset_getarr( $field, 'slug', $metaKey );
			}
		}

		return self::FIELD_OPTION_TYPES_PREFIX . $matches['fieldType'];
	}

	/**
	 * @param string $defaultName
	 * @param array  $fieldData
	 * @param string $formType
	 *
	 * @return string
	 */
	private function getNameForFieldWithOptions( $defaultName, $fieldData, $formType ) {
		if ( 0 !== strpos( $defaultName, self::FIELD_OPTION_TYPES_PREFIX ) ) {
			return $defaultName;
		}

		return $this->getTypesFieldNameFromOption( $defaultName, $fieldData, $formType );
	}

	/**
	 * @param array  $fieldData
	 * @param string $formType
	 *
	 * @return string
	 */
	private function getFieldWithOptions( $fieldData, $formType ) {
		if ( preg_match( self::FIELD_OPTION_REGEX, $fieldData, $matches ) ) {
			return $this->getNameForFieldWithOptions( $matches['fieldName'], $fieldData, $formType );
		}
		return null;
	}

	/**
	 * @param array $field
	 *
	 * @return array
	 */
	public function processField( $field ) {
		$fieldInfo = $this->getFieldInfo( $field );
		if ( ! $fieldInfo ) {
			return $field;
		}

		$formId = toolset_getarr( $fieldInfo, 'formId' );
		if ( ! $formId ) {
			return $fields;
		}

		$formType = $this->getFormType( $formId );
		if ( ! $formType ) {
			return $fields;
		}

		$fieldData = toolset_getarr( $fieldInfo, 'fieldData', '' );

		// Match field name formats: first fixed values, then regex.

		foreach ( self::ACTION_FIELDS as $actionFieldSlug => $actionFieldTitle  ) {
			if ( $actionFieldSlug === $fieldData ) {
				$field['group'] = $this->addTopLevelGroup( [
					self::TOP_LEVEL_GROUP_SLUG . '-' . self::ACTION_FIELDS_GROUP_SLUG => self::ACTION_FIELDS_GROUP,
				] );
				$field['title'] = $actionFieldTitle;
				return $field;
			}
		}

		foreach ( self::FORM_FIELDS as $fixedFieldSlug => $fixedFieldTitle  ) {
			if ( $fixedFieldSlug === $fieldData ) {
				$field['group'] = $this->addTopLevelGroup( [
					self::TOP_LEVEL_GROUP_SLUG . '-' . self::FORM_SETTINGS_GROUP_SLUG => self::FORM_SETTINGS_GROUP,
				] );
				$field['title'] = $fixedFieldTitle;
				return $field;
			}
		}

		$message = $this->getMessage( $fieldData, $formType );
		if ( $message ) {
			$field['group'] = $this->addTopLevelGroup( [
				self::TOP_LEVEL_GROUP_SLUG . '-' . self::MESSAGE_GROUP_SLUG => self::MESSAGE_GROUP,
			] );
			$field['title'] = ucwords( $message, "( \t\r\n\f\v" );
			return $field;
		}

		foreach ( self::FIELD_DATA_SUFFIX as $fieldDataSuffixSlug => $fieldDataSuffixTitle ) {
			$length = strlen( $fieldDataSuffixSlug );
			if ( substr( $fieldData, -$length ) === $fieldDataSuffixSlug ) {
				$fieldSlug = substr_replace( $fieldData, '', -$length );
				$field['group'] = $this->addTopLevelGroup( [
					self::TOP_LEVEL_GROUP_SLUG . '-' . self::FIELD_GROUP_SLUG . '-' . $fieldSlug => self::FIELD_GROUP,
				] );
				$field['title'] = $fieldDataSuffixTitle;
				return $field;
			}
		}

		if ( preg_match( self::FIELD_VALIDATION_REGEX, $fieldData, $matches ) ) {
			$field['group'] = $this->addTopLevelGroup( [
				self::TOP_LEVEL_GROUP_SLUG . '-' . self::FIELD_GROUP_SLUG . '-' . $matches['fieldName'] => self::FIELD_GROUP,
			] );
			$field['title'] = self::FIELD_VALIDATION;
			return $field;
		}

		$fieldName = $this->getFieldWithOptions( $fieldData, $formType );
		if ( $fieldName ) {
			$field['group'] = $this->addTopLevelGroup( [
				self::TOP_LEVEL_GROUP_SLUG . '-' . self::FIELD_GROUP_SLUG . '-' . $fieldName => self::FIELD_GROUP,
			] );
			$field['title'] = self::FIELD_OPTION;
			return $field;
		}

		$notificationData = $this->getNotificationData( $fieldData );
		if ( $notificationData ) {
			$field['group'] = $this->addTopLevelGroup( [
				self::TOP_LEVEL_GROUP_SLUG . '-' . self::NOTIFICATION_GROUP_SLUG . '-' . $notificationData['id'] => self::NOTIFICATION_GROUP,
			] );
			$field['title'] = $notificationData['title'];
			return $field;
		}

		$field['group'] = $this->addTopLevelGroup( [] );
		return $field;
	}

	/**
	 * @param int $formId
	 *
	 * @return string[]
	 */
	private function extractFields( $formId ) {
		$formContent = get_post_field( 'post_content', $formId );
		if ( empty( $formContent ) ) {
			return [];
		}

		global $shortcode_tags;
		$fields = [];
		// Back up current registered shortcodes and clear them all out
		$orig_shortcode_tags = $shortcode_tags;
		remove_all_shortcodes();

		$fieldShortcodes         = [ 'cred-field', 'cred_field', 'cred_generic_field' ];
		$fieldShortcodesCallback = function( $atts, $content ) use ( &$fields ) {
			if ( isset( $atts['field'] ) ) {
				$fields[] = $atts['field'];
			}
		};
		foreach ( $fieldShortcodes as $shortcode ) {
			add_shortcode( $shortcode, $fieldShortcodesCallback );
		}

		$nameShortcodes         = [ 'cred-relationship-field' ];
		$nameShortcodesCallback = function( $atts, $content ) use ( &$fields ) {
			if ( isset( $atts['name'] ) ) {
				$fields[] = $atts['name'];
			}
		};
		foreach ( $nameShortcodes as $shortcode ) {
			add_shortcode( $shortcode, $nameShortcodesCallback );
		}

		$formContent = stripslashes( $formContent );
		do_shortcode( $formContent );
		$shortcode_tags = $orig_shortcode_tags;

		return $fields;
	}

	/**
	 * @param array $fields
	 * @param int   $formId
	 *
	 * @return array
	 */
	private function orderFields( $fields, $formId ) {
		$fieldsInForm = $this->extractFields( $formId );

		// Reorder our form fields matching the stored fields order.
		$relevantFields = array_intersect( $fieldsInForm, array_keys( $fields ) );
		$sortedFields   = array_replace_recursive( array_flip( $relevantFields ), $fields );

		return $sortedFields;
	}

	/**
	 * @param array[] $sectionFields
	 * @param int     $depth
	 *
	 * @return array
	 */
	private function flatten( $sectionFields, $depth = INF ) {
		return array_reduce( $sectionFields, function( $result, $item ) use ( $depth ) {
			if ( ! is_array( $item ) ) {
					return array_merge( $result, [ $item ] );
			} elseif ( $depth === 1 ) {
					return array_merge( $result, array_values( $item ) );
			} else {
					return array_merge( $result, $this->flatten( $item, $depth - 1 ) );
			}
		}, []);
	}

}
