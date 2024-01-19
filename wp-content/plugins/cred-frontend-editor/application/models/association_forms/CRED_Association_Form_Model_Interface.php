<?php
interface CRED_Association_Form_Model_Interface{
	public function populate( array $data );
	public function process_data();
	public function to_array();
}