<?php
class formularios extends principal_ci
{
	protected $s__catalogo;
	protected $s__id_op;
	protected $s__proyecto;

	function conf__arbol($arbol)
	{	

		$this->s__proyecto= toba::proyecto()->get_id();
		toba::logger()->debug('Valor proyecto '. json_encode($this->s__proyecto));
		//-- Se obtienen los nodos que formarán parte del arbol		
		//require_once('contrib/catalogo_items_menu/toba_catalogo_items_menu.php');
		$this->s__catalogo = new toba_catalogo_items_menu();
		$raiz = '1';		
		$this->s__catalogo->cargar(array(), $raiz);
		//$this->s__catalogo->cargar_todo();
		$nodos = $this->s__catalogo->get_hijos($raiz);
		
		unset($nodos[count($nodos)-1]);
		
		//-- Se configura el arbol
		$arbol->set_mostrar_filtro_rapido(true);
		$arbol->set_mostrar_ayuda(false);		
		$arbol->set_nivel_apertura(0);
		$arbol->set_datos($nodos);

		$formularios = $this->get_formularios2($nodos);

		$this->armar_nodos($nodos, $formularios);
	}
	//-----------------------------------------------------------------------------------
	//---- arbol ------------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	public function armar_nodos($nodos, $formularios){
		foreach ($nodos as $key => $value) {
			if (!$nodos[$key]->es_hoja()) {
				$hijos= $nodos[$key]->get_hijos();
				$this->armar_nodos($hijos, $formularios[$nodos[$key]->get_id()]);
			}else{
				if (isset($formularios[$nodos[$key]->get_id()])){
					$nodo_formularios= $formularios[$nodos[$key]->get_id()];
					foreach ($nodo_formularios as $key2 => $value) {
						
						$sql = "SELECT 	ob.*
						FROM apex_objeto_dependencias ob
						WHERE 	ob.proyecto = '".$this->s__proyecto."'
							AND	ob.objeto_proveedor = '".$nodo_formularios[$key2]."'";
						toba::logger()->debug($sql);
						//$item_objeto = toba_contexto_info::get_db()->consultar_fila($sql);
						$item_objeto= toba::instancia()->get_db()->consultar_fila($sql);

						$nodo_form = new intervan_item_menu(
							$item_objeto['identificador'], null, $nodo_formularios[$key2], $nodo_formularios[$key2]);
						$nodo_form->set_icono("objetos/formulario.gif");
						$nodos[$key]->agregar_hijo($nodo_form);
					}
					
				}
			}
		}
	}

	public function get_formularios2($carpetas){
		$formularios= array();
		foreach ($carpetas as $key => $value) {
			if (!$carpetas[$key]->es_hoja()) {
				$formularios_carpeta= $this->get_formularios2($carpetas[$key]->get_hijos());
				$formularios[$carpetas[$key]->get_id()]= $formularios_carpeta;
			}else{
				$sql = "	SELECT 	i.*
					FROM apex_item_objeto i
					WHERE 	i.proyecto = '".$this->s__proyecto."'
						AND	i.item = '".$carpetas[$key]->get_id()."'";
				toba::logger()->debug($sql);
				//$item_objetos = toba_contexto_info::get_db()->consultar($sql);
				$item_objetos=toba::instancia()->get_db()->consultar($sql);

				if (isset($item_objetos[0]['objeto'])) {
					$sql = "	SELECT 	ob.*
							FROM apex_objeto ob
							WHERE 	ob.proyecto = '".$this->s__proyecto."'
								AND	ob.objeto = '".$item_objetos[0]['objeto']."'";
					toba::logger()->debug($sql);
					//$ci_principal = toba_contexto_info::get_db()->consultar($sql);
					$ci_principal= toba::instancia()->get_db()->consultar($sql);

					$formularios[$carpetas[$key]->get_id()]= $this->get_id_formularios_ci($ci_principal[0]['objeto']);
				}
			}
		}
		return $formularios;
	}

	public function get_id_formularios_ci($id_ci){
		$sql = "	SELECT 	dep.*, ob.clase, ob.subclase
					FROM apex_objeto_dependencias dep, apex_objeto ob
					WHERE 	dep.proyecto = '".$this->s__proyecto."'
					AND	dep.objeto_consumidor = '$id_ci'
					AND ob.proyecto= dep.proyecto
					AND ob.objeto= dep.objeto_proveedor";
		toba::logger()->debug($sql);
		//$deps = toba_contexto_info::get_db()->consultar($sql);
		$deps= toba::instancia()->get_db()->consultar($sql);

		$formularios = array();

		foreach ($deps as $key => $value) {
			if ($deps[$key]['clase'] == 'toba_ci') {
				$formularios_ci= $this->get_id_formularios_ci($deps[$key]['objeto_proveedor']);

				$formularios= array_merge($formularios, $formularios_ci);
			}elseif ($deps[$key]['clase'] == 'toba_ei_formulario') {
				array_push($formularios, $deps[$key]['objeto_proveedor']);
			}
		}

		//ei_arbol($formularios);

		return $formularios;
	}

	function evt__arbol__ver_propiedades($nodo)
	{
		$this->s__id_op= $nodo;
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro($cuadro)
	{
		if (isset($this->s__id_op)) {
			$sql = "	SELECT 	ob.*
						FROM apex_objeto_ei_formulario_ef ob
						WHERE 	objeto_ei_formulario_proyecto= '".$this->s__proyecto."'
						and objeto_ei_formulario= '".$this->s__id_op."'";
			toba::logger()->debug($sql);
			//$efs = toba_contexto_info::get_db()->consultar($sql);
			$efs = toba::instancia()->get_db()->consultar($sql);
			$campos= array();
			foreach ($efs as $key => $value) {
				array_push($campos, $this->s__id_op.".".$efs[$key]['identificador']);
			}
			$prompts= dao_varios::get_prompts($campos, 'ITE');

			foreach ($efs as $key => $value) {
				if (isset($prompts[$this->s__id_op.".".$efs[$key]['identificador']])) {
					$efs[$key]['prompt']= $prompts[$this->s__id_op.".".$efs[$key]['identificador']];

					$clave= $this->s__id_op.".".$efs[$key]['identificador'];
					$etiqueta2= $efs[$key]['etiqueta'];
					$etiqueta=  preg_replace('/[^A-Za-z0-9\-]/', '--', $etiqueta2);
					$prompt= $prompts[$this->s__id_op.".".$efs[$key]['identificador']];
					$prompt=  preg_replace('/[^A-Za-z0-9\-]/', '--', $prompt);
				}else{
					$clave= $this->s__id_op.".".$efs[$key]['identificador'];
					$etiqueta2= $efs[$key]['etiqueta'];
					$etiqueta=  preg_replace('/[^A-Za-z0-9\-]/', '--', $etiqueta2);
					$prompt= "";
				}



				$efs[$key]['etiqueta']= "<a href='#' onClick=mostrar_campo('".$clave."','".$etiqueta."','".$prompt."') > $etiqueta2</a>";

				
			}

			$cuadro->set_datos($efs);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		parent::extender_objeto_js();

		$id_formulario= $this->dep('form_campo')->get_id_objeto_js();

		echo "

			function mostrar_campo(clave, etiqueta, prompt) {
				var etiqueta1 = etiqueta.replace('--', ' ');
				etiqueta1 = etiqueta1.replace('--', ' ');
				var prompt1 = prompt.replace('--', ' ');
				prompt1 = prompt1.replace('--', ' ');

				$id_formulario.ef('clave').resetear_estado();
				$id_formulario.ef('etiqueta').resetear_estado();
				$id_formulario.ef('prompt').resetear_estado();

				$id_formulario.ef('clave').set_estado(clave);
				$id_formulario.ef('etiqueta').set_estado(etiqueta1);
				$id_formulario.ef('prompt').set_estado(prompt1);

				var div_campo = document.getElementById('form_campo');
				div_campo.className='modalForm modalFormAct';
			}
		";
	}

	

	//-----------------------------------------------------------------------------------
	//---- form_campo -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__form_campo__guardar($datos)
	{
		if (isset($datos['prompt'])&&!empty($datos['prompt'])) {
			$campo= dao_varios::get_prompt($datos['clave'], 'ITE');

			if (isset($campo['objeto'])) {
				$sql= "UPDATE SSE_PROMPTS
					SET prompt= '".$datos['prompt']."'
					WHERE objeto = upper('".$datos['clave']."')";
			}else{
				$sql= "INSERT INTO SSE_PROMPTS
					(TIPO_OBJETO, OBJETO, LENGUAJE, PROMPT)
					VALUES ('ITE', upper('".$datos['clave']."'), 'LA', '".$datos['prompt']."')";
			}

		}else{
			$sql= "DELETE FROM SSE_PROMPTS
				WHERE objeto = upper('".$datos['clave']."')";
		}

		$rta= dao_varios::ejecutar_sql($sql, true);

		if ($rta != 'OK') {
			toba::notificacion()->error($rta);
		}
	}
}

?>