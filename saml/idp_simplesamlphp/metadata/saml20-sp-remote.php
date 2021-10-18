<?php
/**
 * SAML 2.0 remote SP metadata for simpleSAMLphp.
 *
 * See: http://simplesamlphp.org/docs/trunk/simplesamlphp-reference-sp-remote
 */

/*
 * Example simpleSAMLphp SAML 2.0 SP
 */
$metadata['https://saml2sp.example.org'] = array(
	'AssertionConsumerService' => 'https://saml2sp.example.org/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp',
	'SingleLogoutService' => 'https://saml2sp.example.org/simplesaml/module.php/saml/sp/saml2-logout.php/default-sp',
);

/*
 * This example shows an example config that works with Google Apps for education.
 * What is important is that you have an attribute in your IdP that maps to the local part of the email address
 * at Google Apps. In example, if your google account is foo.com, and you have a user that has an email john@foo.com, then you
 * must set the simplesaml.nameidattribute to be the name of an attribute that for this user has the value of 'john'.
 */
$metadata['google.com'] = array(
	'AssertionConsumerService' => 'https://www.google.com/a/g.feide.no/acs',
	'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
	'simplesaml.nameidattribute' => 'uid',
	'simplesaml.attributes' => FALSE,
);


//////////////////////////////////////////////////////////////////////////////////////////////////////

$metadata['http://idp_principal_localhost'] = array(
	'SingleLogoutService'  => 'http://servicios_intervan_localhost/sp_principal/module.php/saml/sp/saml2-logout.php/example-userpass',
	'AssertionConsumerService' => 
		array (
				0 => 
				array (
				  'index' => 0,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
				  'Location' => 'http://servicios_intervan_localhost/sp_principal/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				1 => 
				array (
				  'index' => 1,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post',
				  'Location' => 'http://servicios_intervan_localhost/sp_principal/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				2 => 
				array (
				  'index' => 2,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
				  'Location' => 'http://servicios_intervan_localhost/sp_principal/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				3 => 
				array (
				  'index' => 3,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01',
				  'Location' => 'http://servicios_intervan_localhost/sp_principal/module.php/saml/sp/saml2-acs.php/example-userpass/artifact',
				),
			  ),
);

$metadata['http://idp_principal_test:8888'] = array(
	'SingleLogoutService'  => 'http://servicios.intervan.com.ar:8888/sp_principal/module.php/saml/sp/saml2-logout.php/example-userpass',
	'AssertionConsumerService' => 
		array (
				0 => 
				array (
				  'index' => 0,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_principal/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				1 => 
				array (
				  'index' => 1,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_principal/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				2 => 
				array (
				  'index' => 2,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_principal/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				3 => 
				array (
				  'index' => 3,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_principal/module.php/saml/sp/saml2-acs.php/example-userpass/artifact',
				),
			  ),
);

//////////////////////////////////////////////////////////////////////////////////////////////////////

$metadata['http://idp_prueba_localhost'] = array(
	'SingleLogoutService'  => 'http://servicios_intervan_localhost/sp_prueba/module.php/saml/sp/saml2-logout.php/example-userpass',
	'AssertionConsumerService' => 
		array (
				0 => 
				array (
				  'index' => 0,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
				  'Location' => 'http://servicios_intervan_localhost/sp_prueba/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				1 => 
				array (
				  'index' => 1,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post',
				  'Location' => 'http://servicios_intervan_localhost/sp_prueba/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				2 => 
				array (
				  'index' => 2,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
				  'Location' => 'http://servicios_intervan_localhost/sp_prueba/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				3 => 
				array (
				  'index' => 3,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01',
				  'Location' => 'http://servicios_intervan_localhost/sp_prueba/module.php/saml/sp/saml2-acs.php/example-userpass/artifact',
				),
			  ),
);

$metadata['http://idp_prueba_test:8888'] = array(
	'SingleLogoutService'  => 'http://servicios.intervan.com.ar:8888/sp_prueba/module.php/saml/sp/saml2-logout.php/example-userpass',
	'AssertionConsumerService' => 
		array (
				0 => 
				array (
				  'index' => 0,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_prueba/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				1 => 
				array (
				  'index' => 1,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_prueba/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				2 => 
				array (
				  'index' => 2,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_prueba/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				3 => 
				array (
				  'index' => 3,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_prueba/module.php/saml/sp/saml2-acs.php/example-userpass/artifact',
				),
			  ),
);

////////////////////////////////////////////////////////////////////////////////////////////////////

$metadata['http://idp_administracion_localhost'] = array(
	'SingleLogoutService'  => 'http://servicios_intervan_localhost/sp_administracion/module.php/saml/sp/saml2-logout.php/example-userpass',
	'AssertionConsumerService' => 
		array (
				0 => 
				array (
				  'index' => 0,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
				  'Location' => 'http://servicios_intervan_localhost/sp_administracion/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				1 => 
				array (
				  'index' => 1,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post',
				  'Location' => 'http://servicios_intervan_localhost/sp_administracion/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				2 => 
				array (
				  'index' => 2,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
				  'Location' => 'http://servicios_intervan_localhost/sp_administracion/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				3 => 
				array (
				  'index' => 3,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01',
				  'Location' => 'http://servicios_intervan_localhost/sp_administracion/module.php/saml/sp/saml2-acs.php/example-userpass/artifact',
				),
			  ),
);

$metadata['http://idp_administracion_test:8888'] = array(
	'SingleLogoutService'  => 'http://servicios.intervan.com.ar:8888/sp_administracion/module.php/saml/sp/saml2-logout.php/example-userpass',
	'AssertionConsumerService' => 
		array (
				0 => 
				array (
				  'index' => 0,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_administracion/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				1 => 
				array (
				  'index' => 1,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_administracion/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				2 => 
				array (
				  'index' => 2,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_administracion/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				3 => 
				array (
				  'index' => 3,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_administracion/module.php/saml/sp/saml2-acs.php/example-userpass/artifact',
				),
			  ),
);

//////////////////////////////////////////////////////////////////////////////////////////////////////

$metadata['http://idp_compras_localhost'] = array(
	'SingleLogoutService'  => 'http://servicios_intervan_localhost/sp_compras/module.php/saml/sp/saml2-logout.php/example-userpass',
	'AssertionConsumerService' => 
		array (
				0 => 
				array (
				  'index' => 0,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
				  'Location' => 'http://servicios_intervan_localhost/sp_compras/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				1 => 
				array (
				  'index' => 1,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post',
				  'Location' => 'http://servicios_intervan_localhost/sp_compras/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				2 => 
				array (
				  'index' => 2,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
				  'Location' => 'http://servicios_intervan_localhost/sp_compras/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				3 => 
				array (
				  'index' => 3,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01',
				  'Location' => 'http://servicios_intervan_localhost/sp_compras/module.php/saml/sp/saml2-acs.php/example-userpass/artifact',
				),
			  ),
);

$metadata['http://idp_compras_test:8888'] = array(
	'SingleLogoutService'  => 'http://servicios.intervan.com.ar:8888/sp_compras/module.php/saml/sp/saml2-logout.php/example-userpass',
	'AssertionConsumerService' => 
		array (
				0 => 
				array (
				  'index' => 0,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_compras/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				1 => 
				array (
				  'index' => 1,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_compras/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				2 => 
				array (
				  'index' => 2,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_compras/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				3 => 
				array (
				  'index' => 3,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_compras/module.php/saml/sp/saml2-acs.php/example-userpass/artifact',
				),
			  ),
);

//////////////////////////////////////////////////////////////////////////////////////////////////////

$metadata['http://idp_costos_localhost'] = array(
	'SingleLogoutService'  => 'http://servicios_intervan_localhost/sp_costos/module.php/saml/sp/saml2-logout.php/example-userpass',
	'AssertionConsumerService' => 
		array (
				0 => 
				array (
				  'index' => 0,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
				  'Location' => 'http://servicios_intervan_localhost/sp_costos/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				1 => 
				array (
				  'index' => 1,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post',
				  'Location' => 'http://servicios_intervan_localhost/sp_costos/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				2 => 
				array (
				  'index' => 2,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
				  'Location' => 'http://servicios_intervan_localhost/sp_costos/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				3 => 
				array (
				  'index' => 3,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01',
				  'Location' => 'http://servicios_intervan_localhost/sp_costos/module.php/saml/sp/saml2-acs.php/example-userpass/artifact',
				),
			  ),
);

$metadata['http://idp_costos_test:8888'] = array(
	'SingleLogoutService'  => 'http://servicios.intervan.com.ar:8888/sp_costos/module.php/saml/sp/saml2-logout.php/example-userpass',
	'AssertionConsumerService' => 
		array (
				0 => 
				array (
				  'index' => 0,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_costos/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				1 => 
				array (
				  'index' => 1,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_costos/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				2 => 
				array (
				  'index' => 2,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_costos/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				3 => 
				array (
				  'index' => 3,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_costos/module.php/saml/sp/saml2-acs.php/example-userpass/artifact',
				),
			  ),
);

//////////////////////////////////////////////////////////////////////////////////////////////////////

$metadata['http://idp_contabilidad_localhost'] = array(
	'SingleLogoutService'  => 'http://servicios_intervan_localhost/sp_contabilidad/module.php/saml/sp/saml2-logout.php/example-userpass',
	'AssertionConsumerService' => 
		array (
				0 => 
				array (
				  'index' => 0,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
				  'Location' => 'http://servicios_intervan_localhost/sp_contabilidad/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				1 => 
				array (
				  'index' => 1,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post',
				  'Location' => 'http://servicios_intervan_localhost/sp_contabilidad/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				2 => 
				array (
				  'index' => 2,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
				  'Location' => 'http://servicios_intervan_localhost/sp_contabilidad/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				3 => 
				array (
				  'index' => 3,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01',
				  'Location' => 'http://servicios_intervan_localhost/sp_contabilidad/module.php/saml/sp/saml2-acs.php/example-userpass/artifact',
				),
			  ),
);

$metadata['http://idp_contabilidad_test:8888'] = array(
	'SingleLogoutService'  => 'http://servicios.intervan.com.ar:8888/sp_contabilidad/module.php/saml/sp/saml2-logout.php/example-userpass',
	'AssertionConsumerService' => 
		array (
				0 => 
				array (
				  'index' => 0,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_contabilidad/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				1 => 
				array (
				  'index' => 1,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_contabilidad/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				2 => 
				array (
				  'index' => 2,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_contabilidad/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				3 => 
				array (
				  'index' => 3,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_contabilidad/module.php/saml/sp/saml2-acs.php/example-userpass/artifact',
				),
			  ),
);

//////////////////////////////////////////////////////////////////////////////////////////////////////

$metadata['http://idp_presupuesto_localhost'] = array(
	'SingleLogoutService'  => 'http://servicios_intervan_localhost/sp_presupuesto/module.php/saml/sp/saml2-logout.php/example-userpass',
	'AssertionConsumerService' => 
		array (
				0 => 
				array (
				  'index' => 0,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
				  'Location' => 'http://servicios_intervan_localhost/sp_presupuesto/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				1 => 
				array (
				  'index' => 1,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post',
				  'Location' => 'http://servicios_intervan_localhost/sp_presupuesto/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				2 => 
				array (
				  'index' => 2,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
				  'Location' => 'http://servicios_intervan_localhost/sp_presupuesto/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				3 => 
				array (
				  'index' => 3,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01',
				  'Location' => 'http://servicios_intervan_localhost/sp_presupuesto/module.php/saml/sp/saml2-acs.php/example-userpass/artifact',
				),
			  ),
);

$metadata['http://idp_presupuesto_test:8888'] = array(
	'SingleLogoutService'  => 'http://servicios.intervan.com.ar:8888/sp_presupuesto/module.php/saml/sp/saml2-logout.php/example-userpass',
	'AssertionConsumerService' => 
		array (
				0 => 
				array (
				  'index' => 0,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_presupuesto/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				1 => 
				array (
				  'index' => 1,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_presupuesto/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				2 => 
				array (
				  'index' => 2,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_presupuesto/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				3 => 
				array (
				  'index' => 3,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_presupuesto/module.php/saml/sp/saml2-acs.php/example-userpass/artifact',
				),
			  ),
);

//////////////////////////////////////////////////////////////////////////////////////////////////////

$metadata['http://idp_rrhh_localhost'] = array(
	'SingleLogoutService'  => 'http://servicios_intervan_localhost/sp_rrhh/module.php/saml/sp/saml2-logout.php/example-userpass',
	'AssertionConsumerService' => 
		array (
				0 => 
				array (
				  'index' => 0,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
				  'Location' => 'http://servicios_intervan_localhost/sp_rrhh/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				1 => 
				array (
				  'index' => 1,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post',
				  'Location' => 'http://servicios_intervan_localhost/sp_rrhh/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				2 => 
				array (
				  'index' => 2,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
				  'Location' => 'http://servicios_intervan_localhost/sp_rrhh/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				3 => 
				array (
				  'index' => 3,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01',
				  'Location' => 'http://servicios_intervan_localhost/sp_rrhh/module.php/saml/sp/saml2-acs.php/example-userpass/artifact',
				),
			  ),
);

$metadata['http://idp_rrhh_test:8888'] = array(
	'SingleLogoutService'  => 'http://servicios.intervan.com.ar:8888/sp_rrhh/module.php/saml/sp/saml2-logout.php/example-userpass',
	'AssertionConsumerService' => 
		array (
				0 => 
				array (
				  'index' => 0,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_rrhh/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				1 => 
				array (
				  'index' => 1,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_rrhh/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				2 => 
				array (
				  'index' => 2,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_rrhh/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				3 => 
				array (
				  'index' => 3,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_rrhh/module.php/saml/sp/saml2-acs.php/example-userpass/artifact',
				),
			  ),
);

//////////////////////////////////////////////////////////////////////////////////////////////////////

$metadata['http://idp_rentas_localhost'] = array(
	'SingleLogoutService'  => 'http://servicios_intervan_localhost/sp_rentas/module.php/saml/sp/saml2-logout.php/example-userpass',
	'AssertionConsumerService' => 
		array (
				0 => 
				array (
				  'index' => 0,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
				  'Location' => 'http://servicios_intervan_localhost/sp_rentas/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				1 => 
				array (
				  'index' => 1,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post',
				  'Location' => 'http://servicios_intervan_localhost/sp_rentas/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				2 => 
				array (
				  'index' => 2,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
				  'Location' => 'http://servicios_intervan_localhost/sp_rentas/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				3 => 
				array (
				  'index' => 3,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01',
				  'Location' => 'http://servicios_intervan_localhost/sp_rentas/module.php/saml/sp/saml2-acs.php/example-userpass/artifact',
				),
			  ),
);

$metadata['http://idp_rentas_test:8888'] = array(
	'SingleLogoutService'  => 'http://servicios.intervan.com.ar:8888/sp_rentas/module.php/saml/sp/saml2-logout.php/example-userpass',
	'AssertionConsumerService' => 
		array (
				0 => 
				array (
				  'index' => 0,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_rentas/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				1 => 
				array (
				  'index' => 1,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_rentas/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				2 => 
				array (
				  'index' => 2,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_rentas/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				3 => 
				array (
				  'index' => 3,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01',
				  'Location' => 'http://servicios.intervan.com.ar:8888/sp_rentas/module.php/saml/sp/saml2-acs.php/example-userpass/artifact',
				),
			  ),
);

//////////////////////////////////////////////////////////////////////////////////////////////////////

$metadata['http://idp_liferay_test:8888'] = array(
	'SingleLogoutService'  => 'http://portal.intervan.com.ar/sp_liferay/module.php/saml/sp/saml2-logout.php/example-userpass',
	'AssertionConsumerService' => 
		array (
				0 => 
				array (
				  'index' => 0,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
				  'Location' => 'http://portal.intervan.com.ar/sp_liferay/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				1 => 
				array (
				  'index' => 1,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post',
				  'Location' => 'http://portal.intervan.com.ar/sp_liferay/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				2 => 
				array (
				  'index' => 2,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
				  'Location' => 'http://portal.intervan.com.ar/sp_liferay/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				3 => 
				array (
				  'index' => 3,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01',
				  'Location' => 'http://portal.intervan.com.ar/sp_liferay/module.php/saml/sp/saml2-acs.php/example-userpass/artifact',
				),
			  ),
);

//////////////////////////////////////////////////////////////////////////////////////////////////////

$metadata['http://idp_bonita_test:8888'] = array(
	'SingleLogoutService'  => 'http://procesos.intervan.com.ar/sp_bonita/module.php/saml/sp/saml2-logout.php/example-userpass',
	'AssertionConsumerService' => 
		array (
				0 => 
				array (
				  'index' => 0,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
				  'Location' => 'http://procesos.intervan.com.ar/sp_bonita/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				1 => 
				array (
				  'index' => 1,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post',
				  'Location' => 'http://procesos.intervan.com.ar/sp_bonita/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				2 => 
				array (
				  'index' => 2,
				  'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
				  'Location' => 'http://procesos.intervan.com.ar/sp_bonita/module.php/saml/sp/saml2-acs.php/example-userpass',
				),
				3 => 
				array (
				  'index' => 3,
				  'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01',
				  'Location' => 'http://procesos.intervan.com.ar/sp_bonita/module.php/saml/sp/saml2-acs.php/example-userpass/artifact',
				),
			  ),
);

//////////////////////////////////////////////////////////////////////////////////////////////////////
