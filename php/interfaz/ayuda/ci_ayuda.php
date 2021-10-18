<?php
class ci_ayuda extends principal_ci
{
	protected $path; //Path donde se alojan los archivos
	protected $extensiones = array(0=>'pdf',1=>'html');
	
	function ini__operacion(){
		parent::ini__operacion();
		$this->path = toba::proyecto()->get_path().'/www/doc';
	}
	
	public function get_path(){
		return $this->path;
	}

	//-----------------------------------------------------------------------------------
	//---- explorador -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	function conf__explorador(explorador $archivos)
	{
		$archivos->set_crear_archivos(false);
		$archivos->set_crear_carpetas(false);
		$archivos->set_extensiones_validas($this->extensiones);
		$archivos->set_path_absoluto($this->path);
	}
}

?>