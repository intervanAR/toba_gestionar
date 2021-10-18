<?php
include_once dirname(__FILE__).'/../lib/service_provider_varios.php';
$ini = parse_ini_file(dirname(__FILE__).'/../../../../../instalacion/saml.ini',true);
$proyecto = obtener_proyecto_actual();

foreach($ini as $key => $array)
{
	if (strcmp(substr($key, 0, 4 + 1 + strlen($proyecto)), 'idp_'.$proyecto.':') != 0) {
		continue;
	}
	
	$key = trim(substr($key, 4 + 1 + strlen($proyecto)));	
	$metadata[$key] = array(
          'name' => array(
                  'en' => $array['name']
          ),
          'SingleSignOnService'  => $array['SingleSignOnService'],
          'certFingerprint'      => $array['certFingerprint']
	);
  
	if( trim($array['SingleLogoutService']) != '' )
	{
	  $metadata[$key]['SingleLogoutService'] = $array['SingleLogoutService'];
	}

}