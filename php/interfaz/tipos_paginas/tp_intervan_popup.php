<?php

/**
 * Formato de página pensado para un popup. 
 * Se incluye un javascript para poder comunicarse con la ventana padre y además se almacena
 * en la memoria el ef que origino la apertura del popup para poder hacer esta comunicación.
 *
 * @package SalidaGrafica
 */
class tp_intervan_popup extends toba_tp_popup
{
	
	protected function plantillas_css()
	{
		parent::plantillas_css();	
		echo $this->get_html_css_intervan();
	}
	
	public function encabezado()
	{
		parent::encabezado();
		
		//--incluir scripts
                $proy  =   toba::instancia()->get_url_proyectos(array('principal'));
                if (!empty($proy['principal'])){
                    echo "<SCRIPT language='JavaScript1.4' type='text/javascript' src='" . $proy['principal'] . "/js/custom.js'></SCRIPT>";
                    echo "<SCRIPT language='JavaScript1.4' type='text/javascript' src='" . $proy['principal'] . "/js/moment/moment.js'></SCRIPT>";                    
                }
	}
	
	public function get_html_css_intervan()
	{
		$link = '';
		$archivo = 'intervan';
		$rol = 'screen';
		$proyecto = 'principal';
		$version = toba::memoria()->get_dato_instancia('toba_revision_recursos_cliente');
		$agregado_url = (!  is_null($version)) ? "?av=$version": '';		
		
		$path = toba::instancia()->get_path_proyecto($proyecto)."/www/css/$archivo.css";
		if (file_exists($path)) {
			$url = toba_recurso::url_proyecto($proyecto) . "/css/$archivo.css$agregado_url";
			$link .= "<link href='$url' rel='stylesheet' type='text/css' media='$rol'/>\n";
		}
		return $link;
	}
}
?>