<?php
class CRED_Association_Form_Listing_Page extends CRED_Page_Manager_Abstract{

	const LISTING_ASSOCIATION_NONCE = 'cred_associations_form_nonce';

	public function __construct( CRED_Association_Form_Model_Interface $model, CRED_Association_Form_Relationship_API_Helper $helper, CRED_Association_Form_Repository $repository = null ) {
		parent::__construct( $model, $helper, $repository );
	}

	/**
	 * Association forms page display
	 */
	public function print_page(){

		$this->prepare_dialogs();
		$this->render_page('@associations/', 'association_forms_listing' );
	}

	public function build_page_context() {

		// Basics for the listing page which we'll merge with specific data later on.
		$base_context = $this->gui_base->get_twig_context_base(
			Toolset_Gui_Base::TEMPLATE_LISTING, $this->build_js_data()
		);

		$specific_context = array(
			'strings' => $this->build_strings_for_twig(),
			'has_relationship' => $this->helper->has_relationships(),
		);

		$context = toolset_array_merge_recursive_distinct( $base_context, $specific_context );

		return $context;
	}

	/**
	 * Prepare dialogs for single form delete, bulk delete and duplicate
	 */
	public function prepare_dialogs() {

		if ( null === $this->_dialog_box_factory ) {
			$dialog = new Toolset_Twig_Dialog_Box_Factory();
		}

		$dialog->create(
			'cred-delete-association-form',
			$this->get_twig(),
			array(),
			'@associations/dialogs/delete_form.twig'
		);

		$dialog->create(
			'cred-bulk-delete-association-form',
			$this->get_twig(),
			array(),
			'@associations/dialogs/bulk_delete.twig'
		);

		$dialog->create(
			'cred-duplicate-association-form',
			$this->get_twig(),
			array(),
			'@associations/dialogs/duplicate_form.twig'
		);

	}

	/**
	 * Build data necessary for js files
	 * @return array
	 */
	public function build_js_data() {

		$prepared_data =  array(
			'toolsetFormsVersion' => CRED_FE_VERSION,
			'itemsPerPage' => 10,
			'jsListingIncludePath' => CRED_ABSURL . '/public/association_forms/js/listing_page',
			'jsIncludePath' => CRED_ABSURL . '/public/association_forms/js',
			'has_relationships' => $this->helper->has_relationships(),
			'form_type' => CRED_Association_Form_Main::ASSOCIATION_FORMS_POST_TYPE,
			'bulkActions' => array(
				'select' => __( 'Select', 'wp-cred'),
				'bulk_actions' => __( 'Bulk Actions', 'wp-cred' ),
				'delete' => __('Delete', 'wp-cred')
			),
			'items' =>
				array(
					"data" => $this->repository->get_association_forms_as_posts_and_their_relationship_slug()
				)
		);

		return $prepared_data;
	}

	/**
	 * Prepare strings that we need for twig templates
	 * @return array
	 */
	public function build_strings_for_twig() {
		return array(
			'misc' => array(
				'pageTitle' => _x( 'Relationship Forms', 'Relationship forms page title', 'wp-cred' ),
				'addNewFormURL' => esc_url_raw(
					add_query_arg(
						array( 'page' => 'cred_relationship_form', 'action' => 'add_new' ),
						admin_url().'admin.php'
					)
				)
			),
			'rowAction' => array(
				'edit' => __( 'Edit', 'wp-cred' ),
				'activate' => __( 'Activate', 'wp-cred' ),
				'deactivate' => __( 'Deactivate', 'wp-cred' ),
				'delete' => __( 'Delete', 'wp-cred' ),
				'duplicate' => __( 'Duplicate', 'wp-cred' ),
			),
			'column' => array(
				'form_name' => __( 'Form Name', 'wp-cred' ),
				'relationship' => __( 'Relationship', 'wp-cred' ),
				'date' => __( 'Modified', 'wp-cred' ),
				'form_type' => __( 'Form Type', 'wp-cred' ),
			),

		);
	}
}
