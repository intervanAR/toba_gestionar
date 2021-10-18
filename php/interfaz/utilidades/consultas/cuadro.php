<?php
class cuadro extends principal_ei_cuadro
{
	//---- Config. EVENTOS sobre fila ---------------------------------------------------

	function conf_evt__generar($evento, $fila)
	{
		$datos = $this->get_datos()[$fila];
		$evento->set_parametros($datos['reporte']);
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$id_formulario_parametros= $this->controlador()->dep('form_parametros')->get_id_objeto_js();
		echo "
		//---- Eventos ---------------------------------------------
		{$this->objeto_js}.retorno_generar = function(resultado){
			var parametros= Object.values(resultado['parametros']);
			console.log(parametros);
			var obj= $('#form_parametros')[0];
			obj= obj.firstElementChild.firstElementChild.firstElementChild;
			obj.textContent= '\\n\\t\\t\\t\\tParametros  -  '+resultado['nombre'];
			var efs= $id_formulario_parametros.efs();
			var j= 0;
			var no_ocultar= false;
			for (i in efs){
				var ef= efs[i]._id;
				if ((ef != 'reporte')&&(ef != 'tipo_salida')){
					if ((ef.search('multiple') == -1)&&(ef.search('lista') == -1)&&(ef.search('tipo') == -1)){
						nodo= $id_formulario_parametros.ef(ef).nodo().firstElementChild;
						var input_item= $id_formulario_parametros.ef(ef).nodo().lastElementChild;
						input_item= input_item.firstElementChild.id;
						var ef_multiple= 'param_multiple_'+ef.substr(ef.search('_')+1);
						var nodo_multiple= $id_formulario_parametros.ef(ef_multiple).nodo().firstElementChild;
						var ef_lista= 'param_lista_'+ef.substr(ef.search('_')+1);
						var nodo_lista= $id_formulario_parametros.ef(ef_lista).nodo().firstElementChild;
						var ef_tipo= 'param_tipo_'+ef.substr(ef.search('_')+1);
						$id_formulario_parametros.ef(ef_tipo).ocultar();
						if ((j <= parametros.length)&&(parametros[j] != '')&&(parametros[j] != undefined)) {
							
							if (parametros[j]['multiple'] == 'S') {
								
								nodo_multiple.textContent= parametros[j]['prompt'];
								if (parametros[j]['descripcion'] != undefined) {
									nodo_multiple.title= parametros[j]['descripcion'];
								}
								no_ocultar= true;
								var opciones= [];
								var valores= parametros[j]['valores'];
								for (var i=0; i < valores.length; i++) {
									var clave= Object.keys(valores[i])[0];
									var descripcion= Object.keys(valores[i])[1];
									opciones[valores[i][clave]]= valores[i][descripcion];
								}
								$id_formulario_parametros.ef(ef).ocultar();
								$id_formulario_parametros.ef(ef).resetear_estado();
								$id_formulario_parametros.ef(ef_lista).ocultar();
								$id_formulario_parametros.ef(ef_lista).borrar_opciones();
								$id_formulario_parametros.ef(ef_multiple).mostrar();
								$id_formulario_parametros.ef(ef_multiple).set_opciones(opciones);
							}else{
								if (parametros[j]['query'] == undefined){ //valor simple
									no_ocultar= false;
									if (parametros[j]['tipo_dato'] == 'N') {
										$('#'+input_item).inputmask('999999', { 'placeholder': '' });
									}else if (parametros[j]['tipo_dato'] == 'A') {
										$('#'+input_item).inputmask('a{200}', { 'placeholder': '' });
									}else if (parametros[j]['tipo_dato'] == 'D') {
										$('#'+input_item).inputmask('dd/mm/yyyy');
									}else{
										$('#'+input_item).inputmask('remove');
									}
									nodo.textContent= parametros[j]['prompt'];
									if (parametros[j]['descripcion'] != undefined) {
										nodo.title= parametros[j]['descripcion'];
									}
									$id_formulario_parametros.ef(ef).resetear_estado();
									$id_formulario_parametros.ef(ef).mostrar();
									$id_formulario_parametros.ef(ef_lista).ocultar();
									$id_formulario_parametros.ef(ef_lista).borrar_opciones();
									$id_formulario_parametros.ef(ef_multiple).ocultar();
								}else{ //lista simple
									
									no_ocultar= true;
									nodo_lista.textContent= parametros[j]['prompt'];
									if (parametros[j]['descripcion'] != undefined) {
										nodo_lista.title= parametros[j]['descripcion'];
									}

									$id_formulario_parametros.ef(ef_lista).set_solo_lectura(false);
									$id_formulario_parametros.ef(ef_tipo).set_estado(parametros[j]['tipo_dato']);

									$id_formulario_parametros.ef(ef).ocultar();
									$id_formulario_parametros.ef(ef).resetear_estado();
									$id_formulario_parametros.ef(ef_multiple).ocultar();
									$id_formulario_parametros.ef(ef_lista).set_opciones(opciones);

									$id_formulario_parametros.ef(ef_lista).mostrar();
									$id_formulario_parametros.ef(ef_lista).seleccionar('0');
								}
							}
						}else{
							$id_formulario_parametros.ef(ef).resetear_estado();
							$id_formulario_parametros.ef(ef_lista).borrar_opciones();
							$id_formulario_parametros.ef(ef).ocultar();
							$id_formulario_parametros.ef(ef_multiple).ocultar();
							$id_formulario_parametros.ef(ef_lista).ocultar();
						}

						j++;
					}else{
						if (!no_ocultar) {
							$id_formulario_parametros.ef(ef).resetear_estado();
							$id_formulario_parametros.ef(ef).ocultar();
							nodo_lista.title='';
							nodo_multiple.title='';
						}
					}
				}
			}
			var div_parametros = document.getElementById('form_parametros');
			div_parametros.className='modalForm modalFormAct';
		}

		{$this->objeto_js}.evt__generar = function(reporte)
		{
			var parametros = [];
			parametros[0] = reporte;
			$id_formulario_parametros.ef('reporte').set_estado(reporte);
			this.controlador.ajax('obtener_reporte', parametros, this, this.retorno_generar);
			return false;
		}
		";
	}

	/*

	*/

}
?>