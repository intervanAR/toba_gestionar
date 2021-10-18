<?php
class form_parametro_edicion extends principal_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Eventos ---------------------------------------------
		
		{$this->objeto_js}.evt__cerrar = function()
		{
			var modal = document.getElementById('formulario_parametro');
			modal.className='modalForm close';
			return false;
		}
		";
	}

}

?>