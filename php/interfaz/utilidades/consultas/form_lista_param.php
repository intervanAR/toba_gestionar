<?php
class form_lista_param extends principal_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$id_formulario_parametro= $this->controlador()->dep('formulario_parametro')->get_id_objeto_js();
		echo "
		//---- Eventos ---------------------------------------------
		
		{$this->objeto_js}.evt__ver = function(parametros)
		{
			$id_formulario_parametro.ef('parametro').set_estado(parametros[0]);
			if (parametros[0] != '') {
				if ((parametros[1] != '')&&(parametros[1] != undefined)){
					$id_formulario_parametro.ef('orden').set_estado(parametros[1]);
				}else{
					$id_formulario_parametro.ef('orden').resetear_estado();
				}
				if ((parametros[2] != '')&&(parametros[2] != undefined)){
					$id_formulario_parametro.ef('prompt').set_estado(parametros[2]);
				}else{
					$id_formulario_parametro.ef('prompt').resetear_estado();
				}
				if ((parametros[3] != '')&&(parametros[3] != undefined)){
					$id_formulario_parametro.ef('descripcion').set_estado(parametros[3]);
				}else{
					$id_formulario_parametro.ef('descripcion').resetear_estado();
				}
				if ((parametros[4] != '')&&(parametros[4] != undefined)){
					$id_formulario_parametro.ef('tipo_dato').set_estado(parametros[4]);
				}else{
					$id_formulario_parametro.ef('tipo_dato').resetear_estado();
				}
				if ((parametros[5] != '')&&(parametros[5] != undefined)){
					$id_formulario_parametro.ef('multiple').set_estado(parametros[5]);
				}else{
					$id_formulario_parametro.ef('multiple').resetear_estado();
				}
			}else{
				$id_formulario_parametro.ef('orden').resetear_estado();
				$id_formulario_parametro.ef('prompt').resetear_estado();
				$id_formulario_parametro.ef('descripcion').resetear_estado();
				$id_formulario_parametro.ef('tipo_dato').resetear_estado();
				$id_formulario_parametro.ef('multiple').resetear_estado();
			}
			var div = document.getElementById('formulario_parametro');
			div.className='modalForm modalFormAct';
			return false;
		}
		";
	}

}

?>