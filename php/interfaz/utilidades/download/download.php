<?php 

	$archivo = toba::memoria()->get_parametro('nombre_archivo');
	$path    = toba::memoria()->get_parametro('path');

	$aux = explode('.', $archivo);  //Obtengo la extension del archivo.
	$extension = $aux[1];
	
	if (!empty($archivo)){
		$path = toba::proyecto()->get_path();
		$path .= '/www/doc/'.$archivo;
	
		header('Content-Description: File Transfer');
		header("Content-Type:application/".$extension);
		header('Content-Disposition: attachment; filename='.$archivo);
 	    header('Content-Transfer-Encoding: binary');
	    header('Expires: 0');
		ob_clean();
		flush();
		readfile($path);
		exit;
	}	


?>