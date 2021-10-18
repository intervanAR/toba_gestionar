<?php
class consulta_edicion extends ci_abm_complejo_edicion
{
	/////////////////////////////
	// Configuraciones
	/////////////////////////////
	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		$proy = toba::instancia()->get_url_proyectos(array('principal'));
	}
	//-----------------------------------------------------------------------------------
	//---- form_param_edicion -----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	function strpos_r($haystack, $needle) {
	    if(strlen($needle) > strlen($haystack))
	        trigger_error(sprintf("%s: length of argument 2 must be <= argument 1", __FUNCTION__), E_USER_WARNING);

	    $seeks = array();
	    while($seek = strrpos($haystack, $needle))
	    {
	        array_push($seeks, $seek);
	        $haystack = substr($haystack, 0, $seek);
	    }
	    return $seeks;
	}

	function escanear_parametros($query){
		$ini= $this->strpos_r($query, "[");
		$fin= $this->strpos_r($query, "]");
		$parametros= array();
		for ($i=0; $i < count($ini); $i++) {
			$param= substr($query, $ini[$i], $fin[$i] - $ini[$i] + 1);

			if (!in_array($param, $parametros))
				array_push($parametros, $param);
		}

		return $parametros;
	}

	function conf__form_lista_param(principal_ei_formulario $form)
	{
		$form->desactivar_efs(array("no"));
		$reporte= $this->controlador()->get_clave_relacion()['reporte'];
		$consulta= dao_consultas_dinamicas::get_consulta_x_reporte($reporte);
		$parametros= $this->escanear_parametros($consulta['query']);
		foreach ($parametros as $key => $value) {
			$form->agregar_ef( $value, "ef_fijo", $value, "", array());
			$form->ef($value)->set_estilo_etiqueta("param_query");
			$form->agregar_ef( $value."_bot", "ef_fijo", "", "", array());
			$form->ef($value."_bot")->set_permitir_html(true);
			$form->ef($value."_bot")->set_tamano(5);
			$filtro= array('reporte' => $reporte, 'parametro' => $value);
			$param= dao_consultas_dinamicas::get_parametros_reportes($filtro);
			if ((count($param) > 0)&&isset($param[0])) {
				$orden= $param[0]['orden'];
				$prompt= $param[0]['prompt'];
				$descripcion= $param[0]['descripcion'];
				$tipo_dato= $param[0]['tipo_dato'];
				$multiple= $param[0]['multiple'];
			}else{
				$orden= "";
				$prompt= "";
				$descripcion= "";
				$tipo_dato= "";
				$multiple= "";
			}
			$form->evento('ver')->set_parametros(array($value, $orden, $prompt, $descripcion, $tipo_dato, $multiple));
			$form->ef($value."_bot")->set_estado($form->evento('ver')->get_html($form->_submit, $form->objeto_js, $form->_id));
		}
	}

	public function get_lov_tipo_dato(){
		return dao_consultas_dinamicas::get_tipos_datos();
	}

	public function get_lov_logico(){
		return dao_valor_dominios::get_dominio('RE_LOGICO');
	}

	public function get_lov_tipo(){
		return dao_valor_dominios::get_dominio('RE_TIPO_GE_REPORTE');
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		parent::extender_objeto_js();
		$id_form_lista_param= $this->dep('form_lista_param')->get_id_objeto_js();
		$id_formulario_parametro= $this->dep('formulario_parametro')->get_id_objeto_js();
		$id_formulario= $this->dep('formulario')->get_id_objeto_js();
		$id_formulario_query= $this->dep('formulario_query')->get_id_objeto_js();
		echo "
			function resaltar_parametro(parametro){
				var query= $id_formulario.ef('query2').get_estado();
				//console.log($('#ef_form_104001114_formularioquery'));
				$('#ef_form_104001114_formularioquery2').removeHighlight();
				$('#ef_form_104001114_formularioquery2').highlight(parametro.trim());
			}
			window.onload = function() {
				$id_formulario.ef('query').ocultar();
				$id_formulario.ef('html').ocultar();
				$('#ef_form_104001114_formularioquery').css({'width': '500px', 'height': '200px', 'white-space': 'pre'});
				var efs= $id_form_lista_param.efs();
				var j= 0;
				for (i in efs){
					var ef= efs[i]._id;
					nodo= $id_form_lista_param.ef(ef).nodo().firstElementChild;
					
					nodo.onclick= function(e){resaltar_parametro(e.target.textContent)};
					if (ef.search('_bot') === -1) {
						nodo.parentNode.setAttribute('style', 'float: left; clear: none; padding: 0px;');
					}else{
						nodo.removeAttribute('style');
						var btn= $id_form_lista_param.ef(ef).nodo().lastElementChild;
						btn.setAttribute('style','margin-left: 0px;');
						nodo.parentNode.setAttribute('style', 'clear: none; padding: 0px');
					}
				}

				var query= $id_formulario.ef('query2').nodo();
				//console.log(query.children[1].children[0].children[0].children[0]);
				var code= query.children[1].children[0].children[0].children[0];
				var labels= code.children;
				//console.log(labels);
				for (var i=0; i < labels.length; i++) {
					if ((labels[i].className == 'token punctuation')&&(labels[i].innerText=='[')&&(labels[i].nextElementSibling.innerText==']')) {
						var texto= labels[i].nextSibling;
						texto.textContent= '['+texto.textContent+']';
						code.removeChild(labels[i].nextElementSibling);
						code.removeChild(labels[i]);
					}
				}
			}
		//---- Eventos ---------------------------------------------

		{$this->objeto_js}.evt__editar_query = function()
		{
			var div = document.getElementById('formulario_query');
			div.className='modalForm modalFormAct';
			return false;
		}

		{$this->objeto_js}.evt__editar_html = function()
		{
			var div = document.getElementById('formulario_html');
			div.className='modalForm modalFormAct';
			return false;
		}
		";
	}


	//-----------------------------------------------------------------------------------
	//---- formulario_query -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario_query(formulario_query $form)
	{
		$datos= $this->controlador()->tabla('ge_consultas')->get();
		$consulta= dao_consultas_dinamicas::get_consulta_x_reporte($datos['reporte']);
		
		$datos['query']= $consulta['query'];
		$form->set_datos($datos);
	}

	function evt__formulario_query__guardar($datos)
	{
		 /*$consulta= $this->controlador()->tabla('ge_consultas')->get();
		 $consulta['query']= $datos['query'];
		 $this->controlador()->tabla($this->controlador()->dt_encabezado)->set($consulta);
		 $this->controlador()->guardar();*/

		 ///Escribir CLOB
		 $consulta= $this->controlador()->tabla('ge_consultas')->get();
		 $rta= dao_consultas_dinamicas::actualizar_consulta($consulta['reporte'], $datos['query']);

		 if ($rta != 'OK'){
		 	toba::notificacion()->error($rta);
		 }
	}

	//-----------------------------------------------------------------------------------
	//---- formulario_html --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario_html(form_html $form)
	{
		$datos= $this->controlador()->tabla('ge_consultas')->get();
		$form->ef('html')->set_estado($datos['html']);
	}

	function evt__formulario_html__guardar($datos)
	{
		$consulta= $this->controlador()->tabla('ge_consultas')->get();
		$consulta['html']= $datos['html'];
		$this->controlador()->tabla($this->controlador()->dt_encabezado)->set($consulta);
		$this->controlador()->guardar();
	}

	//-----------------------------------------------------------------------------------
	//---- formulario_parametro ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__formulario_parametro__guardar($datos)
	{
		$reporte= $this->controlador()->get_clave_relacion()['reporte'];
		$filtro= array('reporte' => $reporte, 'parametro' => $datos['parametro']);
		$param= dao_consultas_dinamicas::get_parametros_reportes($filtro);

		if ((count($param) > 0)&&isset($param[0])) {
			dao_consultas_dinamicas::actualizar_parametro($reporte, $datos['parametro'], $datos['prompt'], $datos['descripcion'], $datos['orden'], $datos['tipo_dato'], $datos['multiple']);
		}else{
			dao_consultas_dinamicas::agregar_parametro($reporte, $datos['parametro'], $datos['prompt'], $datos['descripcion'], $datos['orden'], $datos['tipo_dato'], $datos['multiple']);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario($form)
	{
		$datos= parent::conf__formulario($form);
		$consulta= dao_consultas_dinamicas::get_consulta_x_reporte($datos['reporte']);
		$datos['query']= $consulta['query'];
		$datos['query2']= "<pre><code class='language-sql' style='font-size: larger'>".$datos['query']."</code></pre>";
		$html_code= str_replace("<", "&lt;", $datos['html']);
		$html_code= str_replace(">", "&gt;", $html_code);
		$html_code= str_replace('\"', '"', $html_code);
		$datos['html2']= "<pre><code class='language-markup' style='font-size: larger'>".$html_code."</code></pre>";
		return $datos;
	}

	public function evt__formulario__modificacion($datos)
    {
        unset($datos['html']);
        $this->controlador()->tabla($this->controlador()->dt_encabezado)->set($datos);
    }

}
?>
