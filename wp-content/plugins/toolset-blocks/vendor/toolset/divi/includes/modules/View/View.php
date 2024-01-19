<?php

namespace Toolset\Compatibility\Divi\Modules;

class View extends \ET_Builder_Module {

	public $slug       = 'toolset_divi_view';
	public $vb_support = 'on';

	protected $module_credits = array(
		'module_uri' => '',
		'author'     => '',
		'author_uri' => '',
	);

	public function init() {
		$this->name = esc_html__( 'Toolset View', 'toolset-divi' );
	}

	public function get_fields() {
		$views = get_posts( [
			'post_type'        => 'view',
			'post_status'      => 'publish',
			'suppress_filters' => false,
			'numberposts'      => 999,
		] );

		$options = [ '' => esc_attr__( 'Select View', 'toolset-divi' ) ];
		foreach ( $views as $view ) {
			$options[ $view->post_name ] = esc_html( $view->post_title );
		}

		return [
			'toolset_view' => [
				'label'           => esc_html__( 'Select View', 'toolset-divi' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'description'     => esc_html__( 'Select the View to display here.', 'toolset-divi' ),
				'toggle_slug'     => 'main_content',
				'options'         => $options,
			],
		];
	}

	/**
	 * Render the module.
	 * 
	 * Note that rendering a View requires disabling the cache: search forms can get a faulty action attribute.
	 * See https://onthegosystems.myjetbrains.com/youtrack/issue/views-4066.
	 *
	 * @param mixed[] $attrs
	 * @param string|null $content
	 * @param string  $render_slug
	 * @return string
	 */
	public function render( $attrs, $content = null, $render_slug ) {
		$return_true = function() { return true; };
		
		add_filter( 'wpv_filter_disable_caching', $return_true );
		$output = render_view( [ 'name' => $this->props['toolset_view'] ] );
		remove_filter( 'wpv_filter_disable_caching', $return_true );
		
		return $output;
	}

}
