<?php

abstract class CRED_Api_Handler_Abstract implements CRED_Api_Handler_Interface{
	protected $domain_data = array(
		CRED_Form_Domain::POSTS => array(
			'post_type' => \OTGS\Toolset\CRED\Controller\Forms\Post\Main::POST_TYPE,
			'transient' => \OTGS\Toolset\CRED\Controller\Forms\Post\Main::TRANSIENT_KEY
		),
		CRED_Form_Domain::USERS => array(
			'post_type' => \OTGS\Toolset\CRED\Controller\Forms\User\Main::POST_TYPE,
			'transient' => \OTGS\Toolset\CRED\Controller\Forms\User\Main::TRANSIENT_KEY
		),
		CRED_Form_Domain::ASSOCIATIONS => array(
			'post_type' => \CRED_Association_Form_Main::ASSOCIATION_FORMS_POST_TYPE,
			'transient'	=> \CRED_Association_Form_Main::TRANSIENT_KEY
		)
	);
}