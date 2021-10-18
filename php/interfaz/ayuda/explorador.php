<?php
class explorador extends principal_ei_archivos
{
	protected $path;
	
	function ini()
	{
		parent::ini();
		$this->path = $this->controlador()->get_path();
	}
	
	function extender_objeto_js()
	{
		echo "
		{$this->objeto_js}.evt__seleccionar_archivo = function(archivo)
		{
			abrir_popup('general',
						vinculador.get_url(null, 109000216, null,
											{nombre_archivo:archivo,
											 path:'".$this->path."',
											}
										   ), 
						{'width': '1px', 'scrollbars' : '0', 'height': '1px', 'resizable': '0'},
						null, 
						null,
						'DescargarRetenciones');
			return false;
		}
		";
	}
}

?>