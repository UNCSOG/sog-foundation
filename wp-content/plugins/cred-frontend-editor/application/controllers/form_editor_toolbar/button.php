<?php
namespace OTGS\Toolset\CRED\Controller\FormEditorToolbar;

class Button {

    /**
     * Arguments for this toolbar button, mainly attributes for printing.
     *
     * @var array
     * 
     * @since 2.1
     */
    private $args;

    /**
     * Domain of the editor.
     *
     * @var string
     * 
     * @since 2.1
     */
    private $editor_domain;

     /**
     * Editor target, ie, editor ID.
     *
     * @var string
     * 
     * @since 2.1
     */
    private $editor_target;

    /**
     * Button slug.
     *
     * @var string
     * 
     * @since 2.1
     */
    private $slug;

    /**
     * Button label.
     *
     * @var string
     * 
     * @since 2.1
     */
    private $label;

    /**
     * Button icon, be it a Font Awesome or a Dashicon HTML tag.
     *
     * @var string
     * 
     * @since 2.1
     */
    private $icon;

    function __construct( $args = array() ) {
        $defaults = array(
            'editor_domain' => '',
            'editor_target' => '',
			'slug' => '',
			'label' => '',
            'icon' => '',
            'class' => '',
            'data' => array()
		);
        $this->args = wp_parse_args( $args, $defaults );
        
        $this->editor_domain = $this->args['editor_domain'];
        $this->editor_target = $this->args['editor_target'];

        $this->slug = $this->args['slug'];
        $this->label = $this->args['label'];
        $this->icon = $this->args['icon'];
    }

    /**
     * Get the ID attribute for the button.
     *
     * @return string
     * 
     * @since 2.1
     */
    private function get_id_attribute() {
        return sprintf(
            'cred-%1$s-%2$s-%3$s',
            $this->editor_domain,
            $this->editor_target,
            $this->slug
        );
    }

     /**
     * Get the class attribute for the button.
     *
     * @return string
     * 
     * @since 2.1
     */
    private function get_class_attribute() {
        $class = sprintf(
            'button button-secondary cred-form-content-%1$s js-cred-form-content-%2$s',
            $this->slug,
            $this->slug
        );
        $class = empty( $this->args['class'] )
            ? $class
            : sprintf(
                '%1$s %2$s',
                $class,
                $this->args['class']
            );
        return $class;
    }

     /**
     * Get the data attributes for the button.
     *
     * @return string
     * 
     * @since 2.1
     */
    private function get_data_attributes() {
        $data = array_merge(
            array( 'target' => $this->editor_target ),
            $this->args['data']
        );
        $data_attributes_array = array();
        foreach( $data as $data_tag => $data_value ) {
            $data_attributes_array[] = sprintf(
                'data-%1$s="%2$s"',
                esc_attr( $data_tag ),
                esc_attr( $data_value )
            );
        }
        return implode( ' ', $data_attributes_array );
    }

     /**
     * Print the button.
     * 
     * @since 2.1
     */
    public function render() {
        echo sprintf(
            '<button %1$s %2$s %3$s %4$s>%5$s %6$s</button>',
            'id="' . esc_attr( $this->get_id_attribute() ) . '"',
            'class="' . esc_attr( $this->get_class_attribute() ) . '"',
            'title="' . esc_attr( $this->label ) . '"',
            $this->get_data_attributes(),
            $this->icon,
            esc_html( $this->label )
        );
    }
}