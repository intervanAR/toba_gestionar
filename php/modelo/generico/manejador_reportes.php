<?php
/**
 * @author hmargiotta
 */
class manejador_reportes
{
	public static function generar_pdf($reporte, $titulo, $subtitulo, $parametros = [])
	{
		ini_set("display_errors", 0);

		$report = new toba_vista_jasperreports($reporte);
		$usuario = strtoupper(toba::usuario()->get_id());
		$nombre_reporte = $reporte;

		if (!isset($titulo)||empty($titulo)) {
			$rep = dao_reportes_general::get_reporte($nombre_reporte);

			if (empty($rep['titulo']) || is_null($rep['titulo']))
				$titulo = ' ';
			else
				$titulo = $rep['titulo'];

			if (empty($rep['subtitulo'])  || is_null($rep['subtitulo']))
				$subtitulo = ' ';
			else
				$subtitulo = $rep['subtitulo'];
		}

		if (!isset($subtitulo)||empty($subtitulo)) {
			$subtitulo= '';
		}

		$bases_ini = toba_dba::get_bases_definidas();

		if (isset($bases_ini['reportes jasper']['p_municipio'])){
			$municipio = $bases_ini['reportes jasper']['p_municipio'];
		}else{
			$municipio = '';
			toba::logger()->warning('Falta setear p_municipio en el bases.ini');
		}

		if (isset($bases_ini['reportes jasper']['p_sistema'])){
			$sistema = $bases_ini['reportes jasper']['p_sistema'];
		}else{
			$sistema = '';
			toba::logger()->warning('Falta setear p_sistema en el bases.ini');
		}

		if (isset($bases_ini['reportes jasper']['ruta_reportes'])){
			$path = $bases_ini['reportes jasper']['ruta_reportes'];
		}else{
			$path = '';
			toba::logger()->warning('Falta setear el path de los reportes jasper en el bases.ini');
		}

		if (isset($bases_ini['reportes jasper']['p_logo'])){
			$logo = $bases_ini['reportes jasper']['p_logo'];
		}else{
			$logo = '';
			toba::logger()->warning('Falta setear el path del logo en el bases.ini');
		}

		$proyecto = toba::proyecto()->get_id();

		if ($proyecto == 'administracion' || $proyecto ==  'presupuesto' || $proyecto == 'contabilidad'){
			$proyecto = 'financiero';
		}

		$path .= $proyecto."/";

                $nombre_reporte_lowercase = strtolower($reporte);
                $nombre_reporte_uppercase = strtoupper($reporte);
                $generic_path_lowercase =
                        "{$path}/$nombre_reporte_lowercase.jasper";
                $generic_path_uppercase =
                        "{$path}/$nombre_reporte_uppercase.jasper";
 

                $custom_report = isset($bases_ini['reportes jasper']['custom_reportes'])
                        ? $bases_ini['reportes jasper']['custom_reportes']
                        : 'undefined';
                $custom_path_lowercase =
                        "{$path}/$custom_report/$nombre_reporte_lowercase.jasper";
                $custom_path_uppercase =
                        "{$path}/$custom_report/$nombre_reporte_uppercase.jasper";

                /*
             * 2.1. Verifico si el archivo existe en
             *      la carpeta custom del municipio.
             */
                if (file_exists($custom_path_lowercase)) {
                        $nombre_reporte = $nombre_reporte_lowercase;
                        $jasper_path = $custom_path_lowercase;
                } elseif (file_exists($custom_path_uppercase)) {
                        $nombre_reporte = $nombre_reporte_uppercase;
                        $jasper_path = $custom_path_uppercase;
                } elseif (file_exists($generic_path_lowercase)) {
                        $nombre_reporte = $nombre_reporte_lowercase;
                        $jasper_path = $generic_path_lowercase;
                } elseif (file_exists($generic_path_uppercase)) {
                        $nombre_reporte = $nombre_reporte_uppercase;
                        $jasper_path = $generic_path_uppercase;
                } else {
                        $error_mensaje = "El reporte $nombre_reporte_lowercase no está en Jasper ni en mayúsculas ni minúsculas.";
                        /*
                 * Este reporte en particular no existe en Jasper.
                 * Usar Oracle.
                 */
                        toba::logger()->info($error_mensaje);

                        throw new Exception($error_mensaje, 404);
                }

		$report->set_path_reporte($jasper_path);
		$proyecto_id = toba::proyecto()->get_id();
		//Recupero una conexion db para el proyecto
		$db = toba::db($proyecto_id);

		//Parametros fijos.
		$report->set_parametro('p_usuario', 'S', $usuario);
		$report->set_parametro('p_reporte', 'S', $nombre_reporte);
		$report->set_parametro('p_titulo', 'S', $titulo);
		$report->set_parametro('p_subtitulo', 'S', $subtitulo);
		$report->set_parametro('p_sistema', 'S', $sistema);
		$report->set_parametro('p_municipio', 'S', $municipio);
		$report->set_parametro('p_logo', 'S', $logo);


		//Saco los parametros que no recibe el reporte
		unset($parametros['path']);
		unset($parametros['reporte']);

		/*
		 * Agrego los parametros restantes
		 * que corresponden al reporte.
		 */
		foreach ($parametros as $key => $value) {
			toba::logger()->info($key." : ".$value);
			$report->set_parametro($key, 'S', $value);
		}

		$report->set_conexion($db);

		$report->generar_salida();

		return $report->get_nombre_archivo_generado();
	}

	public static function send_mail_pear($host, $username, $password, $port, $to, $email_from, $email_subject, $email_body, $email_address, $file){

		error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_STRICT);

		//set_include_path("." . PATH_SEPARATOR . ($UserDir = dirname($_SERVER['DOCUMENT_ROOT'])) . "/pear/php" . PATH_SEPARATOR . get_include_path());

		require_once "Mail.php";
		require_once "Mail/mime.php";

		$mime = new Mail_mime();
		$mime -> setTXTBody($email_body);
		$mime -> setHTMLBody($email_body);
		$mime -> addAttachment($file, 'application/pdf');

		$headers = array ('From' => $email_from, 'To' => $to, 'Subject' => $email_subject, 'Reply-To' => $email_address);

		$body = $mime -> get();
		$headers = $mime -> headers($headers);

		$smtp = Mail::factory('smtp', array ('host' => $host, 'port' => $port, 'auth' => true, 'username' => $username, 'password' => $password));
		$mail = $smtp->send($to, $headers, $body);

		if (PEAR::isError($mail)) {
			return  $mail->getMessage();
		}

		return "OK";
	}

	public static function send_mail($host, $username, $password, $port, $to, $email_from, $email_subject, $email_body, $email_address, $archivo){

		/* Email Detials */
	  $mail_to = $to;
	  $from_mail = $email_from;
	  $from_name = $username;
	  $reply_to = $email_from;
	  $subject = $email_subject;
	  $message = "Envio de factura.";

	/* Attachment File */
	  // Attachment location
	  $file_name = "factura.pdf";

	  // Read the file content
	  $file = $archivo;
	  $file_size = filesize($file);
	  $handle = fopen($file, "r");
	  $content = fread($handle, $file_size);
	  fclose($handle);
	  $content = chunk_split(base64_encode($content));

	/* Set the email header */
	  // Generate a boundary
	  $boundary = md5(uniqid(time()));

	  // Email header
	  $header = "From: ".$from_name." <".$from_mail.">".PHP_EOL;
	  $header .= "Reply-To: ".$reply_to.PHP_EOL;
	  $header .= "MIME-Version: 1.0".PHP_EOL;

	  // Multipart wraps the Email Content and Attachment
	  $header .= "Content-Type: multipart/mixed; boundary=\"".$boundary."\"".PHP_EOL;
	  $header .= "This is a multi-part message in MIME format.".PHP_EOL;
	  $header .= "--".$boundary.PHP_EOL;

	  // Email content
	  // Content-type can be text/plain or text/html
	  $header .= "Content-type:text/plain; charset=iso-8859-1".PHP_EOL;
	  $header .= "Content-Transfer-Encoding: 7bit".PHP_EOL.PHP_EOL;
	  $header .= "$message".PHP_EOL;
	  $header .= "--".$boundary.PHP_EOL;

	  // Attachment
	  // Edit content type for different file extensions
	  $header .= "Content-Type: application/xml; name=\"".$file_name."\"".PHP_EOL;
	  $header .= "Content-Transfer-Encoding: base64".PHP_EOL;
	  $header .= "Content-Disposition: attachment; filename=\"".$file_name."\"".PHP_EOL.PHP_EOL;
	  $header .= $content.PHP_EOL;
	  $header .= "--".$boundary."--";

	  // Send email
	  if (mail($mail_to, $subject, "", $header)) {
		return "OK";
	  } else {
		return "Error";
	  }

	}

	public static function enviar_reporte_x_mail($archivo, $to, $email_subject, $email_body){
		/*$bases_ini = toba_dba::get_bases_definidas();
		$host = $bases_ini['servidor smtp']['host'];
		$username = $bases_ini['servidor smtp']['username'];
		$password = $bases_ini['servidor smtp']['password'];
		$port = $bases_ini['servidor smtp']['port'];
		$email_from = $bases_ini['servidor smtp']['email_from'];

		if (!isset($host)||empty($host)||!isset($username)||empty($username)||!isset($password)||empty($password)||!isset($port)||empty($port)||!isset($email_from)||empty($email_from)) {
			return "Falta definir algun parametro del servidor smtp en bases.ini";
		}

		$rta_mail= self::send_mail_pear($host, $username, $password, $port, $to, $email_from, $email_subject, $email_body, $email_address, $archivo);

		if ($rta_mail != 'OK') {
			return  $rta_mail;
		}
		return $rta_mail;*/

		toba::logger()->info('-------------ENVIO DE MAIL --------------');
		toba::logger()->info($to);
		toba::logger()->info($email_subject);
		toba::logger()->info($email_body);
		toba::logger()->info('-------------ENVIO DE MAIL --------------');

		$mail = new toba_mail($to, $email_subject, $email_body);
		//$mail->debug='true';
		$mail->Debugoutput='echo';
		$mail->set_configuracion_smtp("mail");
		$mail->agregar_adjunto('Factura', $archivo, 'base64', 'pdf');
		$mail->enviar();
	}
}