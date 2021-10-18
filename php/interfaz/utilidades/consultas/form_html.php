<?php
class form_html extends principal_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Eventos ---------------------------------------------
		function cerrarHtml(){
			var modal = document.getElementById('formulario_html');
			modal.className='modalForm close';			
			return false;
		}
		
		{$this->objeto_js}.evt__cerrar = function()
		{
			cerrarHtml();
			return false;
		}
		";
	}

}

?>