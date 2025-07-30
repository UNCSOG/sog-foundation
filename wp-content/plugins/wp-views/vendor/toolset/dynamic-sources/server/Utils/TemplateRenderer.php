<?php

namespace Toolset\DynamicSources\Utils;

/**
 * Handles the rendering of HTML templates.
 */
class TemplateRenderer {
	/**
	 * Either echoes or returns the rendered template.
	 *
	 * @param string $template The template file path.
	 * @param array $context   The set of variables to be injected into the template.
	 * @param bool $echo       Determines if the template will be echoed or returned.
	 *
	 * @return false|string
	 */
	public function render( $template, $context, $echo = true ) {
		$output = $this->render_template( $template, $context );

		if ( $echo ) {
			//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $output;
		}

		return $output;
	}

	/**
	 * Renders the given template.
	 *
	 * @param string $template The template file path.
	 * @param array $context   The set of variables to be injected into the template.
	 *
	 * @return string
	 */
	private function render_template( $template, $context ) {
		$template_output = '';
		if ( is_file( $template ) ) {
			$template_output = $this->get_include_file_output( $template, array( 'context' => $context ) );
		}

		return $template_output;
	}

	/**
	 * Includes the file holding the template to be rendered.
	 *
	 * @param string $template The template file path.
	 * @param array  $context  The set of variables to be injected into the template.
	 *
	 * @return false|string
	 */
	private function get_include_file_output( $template, $context = array() ) {
		ob_start();
		//phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $context );
		include $template;
		return ob_get_clean();
	}
}
