<?php
class form_campo extends toba_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		{$this->objeto_js}.ef('clave').ocultar();
		//---- Eventos ---------------------------------------------
		
		function cerrarCampo(){
			var modal = document.getElementById('form_campo');
			modal.className='modalForm close';
			return false;
		}	
		
		{$this->objeto_js}.evt__cerrar = function()
		{
			cerrarCampo();
			return false;
		}
		";
	}
}

?>