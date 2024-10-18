<?php
class WPDDL_Options_Manager{
	
	private  $options = array();
	private $name;
	private $default = null;

	public function __construct($name, $default = null )
	{
		$this->name = $name;
		$this->default = $default;
		$this->options = get_option($this->name, $default);
		return $this->options;
	}

	public  function get_options( $option = null ) {
		if (!$option) {
			if( apply_filters( 'ddl_options_reset', false ) ){
				$this->options = get_option( $this->name, $this->default );
			}
			return $this->options;
		}
		if ( isset( $this->options[$option] ) ) {
			return $this->options[$option];
		} else {
			if( ! apply_filters( 'ddl_options_reset', false ) ){
				return '';
			} else {
				$this->options = get_option( $this->name, $this->default );
				if ( isset( $this->options[ $option ] ) ) {
					return $this->options[ $option ];
				} else {
					return '';
				}
			}
		}
	}

	public  function save_options( ) {
		if( update_option( $this->name, $this->options ) )
		{
			return true;
		}

		return false;
	}

	public function delete_options( $option = WPDDL_GENERAL_OPTIONS, $sub = false )
	{
		if( $sub === false )
		{
			if( delete_option( $option ) )
			{
				$this->options = array();

				return true;
			}
			return false;
		}

		else
		{
			$options = $this->get_options( $option );

			if( isset($options[$sub]) ) unset( $options[$sub] );

			if ($option) {
				$this->options[$option] = $options;
			} else {
				$this->options = $options;
			}

			return $this->save_options( );

		}

		return false;
	}

	public function delete_option( $option )
	{
		$options = $this->get_options( );

		if( isset( $options[$option] ) )
		{
			unset( $options[$option] );
			$this->options = $options;

			return $this->save_options( );
		}
		return false;
	}

	public function remove_options_item( $sub, $value, $option = WPDDL_GENERAL_OPTIONS )
	{
			$options = $this->get_options( $option );

			$options[$sub] = array_diff( $options[$sub], array($value) );

			$this->options[$option] = $options;

			return $options[$sub];
	}

	public  function update_options( $option, $data, $overwrite = false )
	{
		if ( $overwrite && isset($this->options[$option]) ) {
			unset($this->options[$option]);
		}
		
		if (isset($this->options[$option]) && is_array($this->options[$option])) {
			$this->options[$option] = array_merge( $this->options[$option], $data );
		} else {
			$this->options[$option] = $data;
		}

		return $this->save_options( );
	}

	public function options_get_name()
	{
		return $this->name;
	}
}