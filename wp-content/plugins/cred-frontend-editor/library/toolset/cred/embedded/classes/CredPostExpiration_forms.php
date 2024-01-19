<?php

/**
 * Returns HTML formatted output for elements and handles form submission.
 *
 */
class CRED_PostExpiration_Form
{

    /**
     * @var string
     */
    private $_id;

    /**
     * @var array
     */
    private $_errors = array();

    /**
     * @var array
     */
    private $_elements = array();

    /**
     * @var array
     */
    private $_count = array();

    /**
     * @var string
     */
    public $css_class = 'cred-pe';

    /**
     * Counts element types.
     *
     * @param type $type
     * @return type
     */
    private function _count( $type ) {
        if ( !isset( $this->_count[$type] ) ) {
            $this->_count[$type] = 0;
        }
        $this->_count[$type] += 1;
        return $this->_count[$type];
    }

    /**
     * Check if element is of valid type
     *
     * @param string $type
     * @return boolean
     */
    private function _isValidType( $type )
    {
        return in_array( $type,
				array('textfield', 'hidden') );
    }

    /**
     * Renders elements.
     *
     * @param type $elements
     * @return type
     */
    public function renderElements( $elements )
    {
        $output = '';
        foreach ( $elements as $key => $element ) {
            if ( !isset( $element['#type'] )
                    || !$this->_isValidType( $element['#type'] ) ) {
                continue;
            }
            if ( $element['#type'] != 'fieldset' ) {
                $output .= $this->renderElement( $element );
            } else if ( is_array( $element ) ) {
                $output .= $this->renderElements( $element );
            }
		}
		static $date_output = false;
		if (!$date_output) {
			$date_format = $this->_date_convert_wp_to_js( get_option( 'date_format' ) );
			$output .= '
		<script type="text/javascript">
			//<![CDATA[
			wptDateData = {
				buttonImage: "' . CRED_ASSETS_URL . '/images/calendar.gif",
				buttonText: "' . __( 'Select date', 'wp-cred' ) . '",
				dateFormat: "' . $date_format . '",
				altFormat: "' . $date_format . '"
			};
			//]]>
		</script>
			';
			$date_output = true;
		}

        return $output;
    }
	private function _date_convert_wp_to_js( $date_format ) {
		$date_format = str_replace( 'd', 'dd', $date_format );
		$date_format = str_replace( 'j', 'd', $date_format );
		$date_format = str_replace( 'l', 'DD', $date_format );
		$date_format = str_replace( 'm', 'mm', $date_format );
		$date_format = str_replace( 'n', 'm', $date_format );
		$date_format = str_replace( 'F', 'MM', $date_format );
		$date_format = str_replace( 'Y', 'yy', $date_format );
		$date_format = preg_replace( '/(\s)*[:@aAghGHisTcr]+(\s)*/', '', $date_format);
		$date_format = preg_replace( '/[,-\/\s]+$/', '', $date_format);

		return $date_format;
	}

    /**
     * Renders element.
     *
     * Depending on element type, it calls class methods.
     *
     * @param array $element
     * @return HTML formatted output
     */
    public function renderElement( $element )
    {
        $method = $element['#type'];
        if ( is_callable( array($this, $method) ) ) {
            if ( !isset( $element['#id'] ) ) {
                if ( isset( $element['#attributes']['id'] ) ) {
                    $element['#id'] = $element['#attributes']['id'];
                } else {
                    $element['#id'] = $element['#type'] . '-'
                            . $this->_count( $element['#type'] );
                }
            }
            if ( isset( $this->_errors[$element['#id']] ) ) {
                $element['#error'] = $this->_errors[$element['#id']];
            }
            return $this->{$method}( $element );
        }
    }

    /**
     * Sets other element attributes.
     *
     * @param array $element
     * @return string
     */
    private function _setElementAttributes( $element )
    {
        $attributes = '';
        $error_class = isset( $element['#error'] ) ? ' ' . $this->css_class . '-error ' . $this->css_class . '-' . $element['#type'] . '-error ' . ' form-' . $element['#type'] . '-error ' . $element['#type'] . '-error form-error ' : '';
        $class = $this->css_class . '-' . $element['#type']
                . ' form-' . $element['#type'] . ' ' . $element['#type'];
        if ( isset( $element['#attributes'] ) ) {
            foreach ( $element['#attributes'] as $attribute => $value ) {
                // Prevent undesired elements
                if ( in_array( $attribute, array('id', 'name') ) ) {
                    continue;
                }
                // Append class values
                if ( $attribute == 'class' ) {
                    $value = $value . ' ' . $class . $error_class;
                }
                // Set return string
                $attributes .= ' ' . $attribute . '="' . $value . '"';
            }
        }
        if ( !isset( $element['#attributes']['class'] ) ) {
            $attributes .= ' class="' . $class . $error_class . '"';
        }
        return $attributes;
    }

    /**
     * Sets render elements.
     *
     * @param array $element
     */
    private function _setRender( $element )
    {
        if ( !isset( $element['#id'] ) ) {
            if ( isset( $element['#attributes']['id'] ) ) {
                $element['#id'] = $element['#attributes']['id'];
            } else {
                $element['#id'] = 'form-' . md5( serialize( $element ) ) . '-'
                        . $this->_count( $element['#type'] );
            }
        }
        $element['_attributes_string'] = $this->_setElementAttributes( $element );
        $element['_render'] = array();
        $element['_render']['prefix'] = isset( $element['#prefix'] ) ? $element['#prefix'] . "\r\n" : '';
        $element['_render']['suffix'] = isset( $element['#suffix'] ) ? $element['#suffix'] . "\r\n" : '';
        $element['_render']['before'] = isset( $element['#before'] ) ? $element['#before'] . "\r\n" : '';
        $element['_render']['after'] = isset( $element['#after'] ) ? $element['#after'] . "\r\n" : '';
        $element['_render']['label'] = isset( $element['#title'] ) ? '<label class="'
                . $this->css_class . '-label ' . $this->css_class . '-'
                . $element['#type'] . '-label" for="' . $element['#id'] . '">'
                . stripslashes( $element['#title'] )
                . '</label>' . "\r\n" : '';
        $element['_render']['title'] = $this->_setElementTitle( $element );
        $element['_render']['description'] = !empty( $element['#description'] ) ? $this->_setElementDescription( $element ) : '';
        $element['_render']['error'] = $this->renderError( $element ) . "\r\n";

        return $element;
    }

    /**
     * Applies pattern to output.
     *
     * Pass element property #pattern to get custom renedered element.
     *
     * @param array $pattern
     *      Accepts: <prefix><suffix><label><title><desription><error>
     * @param array $element
     */
    private function _pattern( $pattern, $element )
    {
        foreach ( $element['_render'] as $key => $value ) {
            $pattern = str_replace( '<' . strtoupper( $key ) . '>', $value,
                    $pattern );
        }
        return $pattern;
    }

    /**
     * Wrapps element in <div></div>.
     *
     * @param arrat $element
     * @param string $output
     * @return string
     */
    private function _wrapElement( $element, $output )
    {
        if ( empty( $element['#inline'] ) ) {
            $wrapped = '<div id="' . $element['#id'] . '-wrapper"'
                    . ' class="form-item form-item-' . $element['#type'] . ' '
                    . $this->css_class . '-item '
                    . $this->css_class . '-item-' . $element['#type']
                    . '">' . $output . '</div>';
            return $wrapped;
        }
        return $output;
    }

    /**
     * Returns HTML formatted output for element's title.
     *
     * @param string $element
     * @return string
     */
    private function _setElementTitle( $element )
    {
        $output = '';
        if ( isset( $element['#title'] ) ) {
            $output .= '<div class="title '
                    . $this->css_class . '-title '
                    . $this->css_class . '-title-' . $element['#type'] . ' '
                    . 'title-' . $element['#type'] . '">'
                    . stripslashes( $element['#title'] )
                    . "</div>\r\n";
        }
        return $output;
    }

    /**
     * Returns HTML formatted output for element's description.
     *
     * @param array $element
     * @return string
     */
    private function _setElementDescription( $element )
    {
        $element['#description'] = stripslashes( $element['#description'] );
        $output = "\r\n"
                . '<div class="description '
                . $this->css_class . '-description '
                . $this->css_class . '-description-' . $element['#type'] . ' '
                . 'description-' . $element['#type'] . '">'
                . $element['#description'] . "</div>\r\n";
        return $output;
    }

    /**
     * Returns HTML formatted element's error message.
     *
     * Pass #supress_errors in #form element to avoid error rendering.
     *
     * @param array $element
     * @return string
     */
    public function renderError( $element )
    {
        if ( !isset( $element['#error'] ) ) {
            return '';
        }
        $output = '<div class="form-error '
                . $this->css_class . '-error '
                . $this->css_class . '-form-error '
                . $this->css_class . '-' . $element['#type'] . '-error '
                . $element['#type'] . '-error form-error-label'
                . '">' . $element['#error'] . '</div>'
                . "\r\n";
        return $output;
    }

    /**
     * Returns HTML formatted output for textfield element.
     *
     * @param array $element
     * @return string
     */
    public function textfield( $element )
    {
        $element['#type'] = 'textfield';
        $element = $this->_setRender( $element );
        $element['_render']['element'] = '<input type="text" id="'
                . $element['#id'] . '" name="' . $element['#name'] . '" value="';
        $element['_render']['element'] .= isset( $element['#value'] ) ? esc_html( stripslashes( $element['#value'] ) ) : '';
        $element['_render']['element'] .= '"' . $element['_attributes_string'];
        if ( isset( $element['#disable'] ) && $element['#disable'] ) {
            $element['_render']['element'] .= ' disabled="disabled"';
        }
        $element['_render']['element'] .= ' />';
        $pattern = isset( $element['#pattern'] ) ? $element['#pattern'] : '<BEFORE><LABEL><ERROR><PREFIX><ELEMENT><SUFFIX><DESCRIPTION><AFTER>';
        $output = $this->_pattern( $pattern, $element );
        $output = $this->_wrapElement( $element, $output );
        return $output . "\r\n";
    }

	/**
     * Returns HTML formatted output for hidden element.
     *
     * @param array $element
     * @return string
     */
    public function hidden($element)
    {
        $element['#type'] = 'hidden';
        $element = $this->_setRender($element);
        $output = '<input type="hidden" id="' . $element['#id'] . '"  name="'
                . $element['#name'] . '" value="';
        $output .= isset($element['#value']) ? $element['#value'] : 1;
        $output .= '"' . $element['_attributes_string'] . ' />';
        return $output;
    }
}
