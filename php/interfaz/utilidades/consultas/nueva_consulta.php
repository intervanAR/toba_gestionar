<?php
class nueva_consulta extends principal_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		{$this->objeto_js}.ef('reporte').ocultar();
		{$this->objeto_js}.ef('nombre').ocultar();
		{$this->objeto_js}.ef('descripcion').ocultar();
		{$this->objeto_js}.ef('query').ocultar();
		//---- Eventos ---------------------------------------------
		function cerrarNueva(){
			var modal = document.getElementById('nueva_consulta');
			modal.className='modalForm close';
			{$this->objeto_js}.ef('reporte').ocultar();
			{$this->objeto_js}.ef('nombre').ocultar();
			{$this->objeto_js}.ef('descripcion').ocultar();
			{$this->objeto_js}.ef('query').ocultar();		
			return false;
		}	
		
		{$this->objeto_js}.evt__cerrar = function()
		{
			cerrarNueva();
			return false;
		}
		";
	}

}

?>