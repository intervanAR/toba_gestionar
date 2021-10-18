<?php

$ini = parse_ini_file(dirname(__FILE__).'/../../../../../instalacion/saml.ini',true);

$config = array(

	// This is a authentication source which handles admin authentication.
	'admin' => array(
		// The default is to use core:AdminPassword, but it can be replaced with
		// any authentication source.

		'core:AdminPassword',
	),


	
	'usuarios_intervan' => array(
		'intervanauth:INTERVANUP',
		'dsn' => 'oci:dbname=//'.$ini['idp']['host'].':'.$ini['idp']['post'].'/'.$ini['idp']['dbname'],
		'username' => $ini['idp']['username'],
		'password' => $ini['idp']['password'],
		'query' => '',
	),
	
	'example-userpass' => array(
		'exampleauth:UserPass',

		// Give the user an option to save their username for future login attempts
		// And when enabled, what should the default be, to save the username or not
		//'remember.username.enabled' => FALSE,
		//'remember.username.checked' => FALSE,

		'fbohn:fbohn' => array(
			'uid' => array('fbohn'),
			'eduPersonAffiliation' => array('member', 'employee'),
		),
		'jgarcia:jgarcia' => array(
			'uid' => array('jgarcia'),
			'eduPersonAffiliation' => array('member', 'employee'),
		),
		'ddirazar:ddirazar' => array(
			'uid' => array('ddirazar'),
			'eduPersonAffiliation' => array('member', 'employee'),
		),
		'lmelzi:lmelzi' => array(
			'uid' => array('lmelzi'),
			'eduPersonAffiliation' => array('member', 'employee'),
		),
		'lwalcan:lwalcan' => array(
			'uid' => array('lwalcan'),
			'eduPersonAffiliation' => array('member', 'employee'),
		),
		'toba:toba' => array(
			'uid' => array('toba'),
			'eduPersonAffiliation' => array('member', 'employee'),
		),
	),
	


);
