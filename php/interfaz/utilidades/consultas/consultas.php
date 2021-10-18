<?php
class consultas extends ci_abm_complejo_listado
{
	protected $clase_carga = 'dao_consultas_dinamicas';
	protected $metodo_carga = 'get_consultas';

	protected $ci_abm_complejo_edicion = 'consulta_edicion'; // dependencia CI de edicion

	public $dt_encabezado = 'ge_consultas'; // Datos tabla de la tabla encabezado
	//public $dt_detalle = 'ad_compromisos_det'; // Datos tabla de la tabla detalle
	//public $dt_imputacion_pre = 'ad_compromisos_imp'; // Datos tabla de la tabla de imputacion presupuestaria
	//public $dt_imputacion_cos = ''; // Datos tabla de la tabla de imputacion por centro de costos

	public $campo_id_comprobante = 'reporte'; // campo de la tabla maestra que indica la clave del comprobante

	////////////////////////////////////////
    // pant_inicial
    ////////////////////////////////////////
    public function conf__pant_inicial(toba_ei_pantalla $pantalla)
    {
    	parent::conf__pant_inicial($pantalla);
    	$url= toba::instancia()->get_url_proyectos(array('principal'));
    	//echo "<link rel=\"STYLESHEET\" type=\"text/css\" href='".$url['principal']."/js/dhtmlxcombo/dhtmlxcombo.css'>";
    	//echo "<script type=\"text/javascript\" src='".$url['principal']."/js/dhtmlxcombo/dhtmlxcombo.js'></script>";
    	/*$text= "{left,200,1}";
    	$aux= $this->strpos_r($text, ',');
    	print_r($aux);
    	print(substr($text, $aux[0]+1, strpos($text,'}') - $aux[0] - 1));
    	print(substr($text, $aux[1]+1, $aux[0] - $aux[1] - 1));*/
    		
    }
	//-----------------------------------------------------------------------------------
	//---- form_parametros --------------------------------------------------------------
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

	function reemplazar_parametros($query, $parametros){

		for ($i=0; $i < count($parametros); $i++) {
			$query= str_replace($parametros[$i]['parametro'], $parametros[$i]['valor'], $query);
		}

		return $query;
	}

	function escanear_prompts($query){
		$ini= $this->strpos_r($query, "{");
		$fin= $this->strpos_r($query, "}");
		$prompts= array();
		for ($i=0; $i < count($ini); $i++) {
			$param= substr($query, $ini[$i], $fin[$i] - $ini[$i] + 1);

			if (!isset($prompts[$param]))
				$prompts[$param]= 1;
			else
				$prompts[$param]= $prompts[$param] + 1;
		}

		return $prompts;
	}

	public function get_lov_dinamica($nombre, $tipo_dato){
		$filtro= array('tipo_dato' => $tipo_dato);
		$tipo_datos= dao_consultas_dinamicas::get_tipos_datos($filtro);
		$datos= array();
		if (isset($tipo_datos[0]['query'])) {
			$query= $tipo_datos[0]['query'];
			/*$descripcion= substr($query, strpos($query, 'select')+7, strpos($query, 'from')-8);
			$campos= explode(",", $descripcion);
			$codigo= explode(" ", $campos[0]);
			foreach ($codigo as $key => $value) {
				if (($value != " ")&&($value != "")) {
					$codigo= $value;
					break;
				}
			}

			$descripcion= explode(" ", $campos[1]);

			foreach ($descripcion as $key => $value) {
				if (($value != " ")&&($value != "")) {
					$descripcion= $value;
					break;
				}
			}*/
			$where = "(upper(subq.lov_descripcion) like upper('%$nombre%'))";

			$query= "SELECT *
					FROM ($query) subq
					WHERE ".$where;
			$datos= toba::db()->consultar($query);
		}
		return $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- AJAX -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	public function ajax__obtener_reporte($parametros, toba_ajax_respuesta $respuesta) {
		if (isset($parametros[0])) {
			$consulta= dao_consultas_dinamicas::get_consulta_x_reporte($parametros[0]);
			//$parametros= dao_consultas_dinamicas::get_parametros_x_reporte($parametros[0]);
			$parametros= $this->escanear_parametros($consulta['query']);
			/*foreach ($parametros as $key => $value) {
				$parametros[$key]= str_replace("[", "", $parametros[$key]);
				$parametros[$key]= str_replace("]", "", $parametros[$key]);
			}*/
			//si los parametros estan definidos por tabla, usar esos
			$parametros_salida= array();
			$parametros_salida2= array();
			$last_order=  -1;
			$parametros2= $parametros;
			foreach ($parametros as $key => $value) {
				$filtro= array("reporte" => $consulta['reporte'], "parametro" => $value);
				$tabla_parametros= dao_consultas_dinamicas::get_parametros_reportes($filtro);


				if (isset($tabla_parametros[0])&&(!empty($tabla_parametros[0]))) {
					if (!isset($parametros_salida[$tabla_parametros[0]['orden']]))
						$parametros_salida[$tabla_parametros[0]['orden']]= $tabla_parametros[0];
					else
						array_push($parametros_salida, $tabla_parametros[0]);
				}else{
					array_push($parametros_salida, array("parametro" => $value, "prompt" => $value));
				}
			}
			/*foreach ($parametros_salida as $key => $value) {
				array_unshift($parametros_salida2, $parametros_salida[$key]);
			}*/
			$respuesta->set(array('parametros' => $parametros_salida, 'nombre' => $consulta['nombre'], 'parametros2' => $parametros));
		}else{
			$respuesta->set(array('parametros' => ''));
		}
	}

	public function ajax__test_ajax($parametros, toba_ajax_respuesta $respuesta) {
//var_dump($parametros);
		foreach ($parametros as $key => $value) {
			//print("----- $key -----\n");
			//$datos= explode(",", $parametros[$key]);
			//$obj= json_decode($parametros[$key]);
			//$aux= str_replace("'", "\"", $parametros[$key]);
			$obj= json_decode($parametros[$key]);
			//print($obj->nombre);
			$campos= "-1";
			foreach ($obj as $key2 => $value) {
				if ($campos == "-1") {
					$campos= "$key2= ".quote($obj->$key2);
				}else{
					$campos.= ", $key2= ".quote($obj->$key2);
				}
			}

			$sql= "UPDATE GE_CONSULTAS SET $campos WHERE reporte= ".quote($key);

			$rta= dao_varios::ejecutar_sql($sql);
		}

		$respuesta->set(array('parametros' => "OK"));
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------S

	function evt__generar()
	{

	}

	//-----------------------------------------------------------------------------------
	//---- nueva_consulta ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	public function evt__nueva_consulta__guardar($datos)
	{
		try {
			dao_consultas_dinamicas::agregar_consulta(
				$datos['reporte'],
				$datos['nombre'],
				$datos['descripcion'],
				$datos['query']
			);
		} catch (Exception $e) {
			toba::notificacion()->error($e->getMessage());
		}
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
		$id_formulario_nueva= $this->dep('nueva_consulta')->get_id_objeto_js();
		echo "
		//---- Eventos ---------------------------------------------
		var editados= [];
		{$this->objeto_js}.evt__nueva = function()
		{
			$id_formulario_nueva.ef('reporte').mostrar();
			$id_formulario_nueva.ef('nombre').mostrar();
			$id_formulario_nueva.ef('descripcion').mostrar();
			$id_formulario_nueva.ef('query').mostrar();
			var div_consulta = document.getElementById('nueva_consulta');
			div_consulta.className='modalForm modalFormAct';
			return false;
		}

		
		";
//+'?default='+5
		/*echo "
			$(window).load(function(){
					console.log('se cargo');
					var myCombo = new dhtmlXCombo('combo_zone', \"combo\", 230);
					//myCombo.enableFilteringMode(true, \"http://10.1.1.104/rentas/1.0/complete.php\", true);
				}
			);
		";*/
	}

	/*
		{$this->objeto_js}.test_ajax_retorno = function(resultado){
			console.log(resultado);
			location.reload(); 
			return true;
		}

		{$this->objeto_js}.evt__test = function()
		{
			var data = $(\"#tabla_dinamica\").tabulator(\"getData\");
			
	        var parametros = [];
			for(var key in editados) {
			  if(editados.hasOwnProperty(key)) { //to be safe
			    //var aux= [editados[key].descripcion, editados[key].nombre];
			    var aux= '{';
			    for(var campo in editados[key]) {
			    	if(aux == '{') {
			    		aux= aux +'\"' +campo+'\":\"'+editados[key][campo]+'\"';
			    	}else{
			    		aux= aux + ',\"' + campo+'\":\"'+editados[key][campo]+'\"';
			    	}
			    }
			    aux= aux+'}'
			    parametros[key]= aux;
			  }
			}
			//console.log(parametros);
	        {$this->objeto_js}.ajax('test_ajax', parametros, this, {$this->objeto_js}.test_ajax_retorno);
	        return false;
		}
		
		$(\"#tabla_dinamica\").tabulator(
			{
			    columns:[
			        {title:\"Reporte\", field:\"reporte\", sorter:\"string\", width:200, editor:false},
			        {title:\"Nombre\", field:\"nombre\", sorter:\"number\", align:\"left\", editor:true},
			        {title:\"Descripcion\", field:\"descripcion\", sorter:\"string\", cellClick:function(e, cell){console.log(\"cell click\")}, editor:true},
			        {title:\"Tipo Cuenta\", field:\"tipo\", 
								        formatter:function(cell, formatterParams){
					        //cell - the cell component
					        //formatterParams - parameters set for the column
					        return \"<div class='combo_zone'></div>\"
					    },align:\"left\", editor:true},
			        {title:\"Query\", field:\"query\", sorter:\"boolean\", width:200, align:\"center\"},
			    ],
			    cellEdited:function(data){
			    	var row= data.getRow();
			    	var row_data= row.getData();

			    	var cell_data= data.getValue();
			    	var cell_column= data.getColumn().getField();

			    	if(editados[row_data.reporte] != undefined) {
			    		var data_obj= editados[row_data.reporte];
			    		
			    		data_obj[cell_column]= cell_data;
			    		editados[row_data.reporte]= data_obj;
			    	}else{
			    		editados[row_data.reporte]= {[cell_column]: cell_data};
			    	}
			    	console.log(editados);
			    },
			    renderComplete:function(){
			    	$('.combo_zone').each(function(index, element) {
			    		if((this != null)&&(this != '')){
						    $( this ).attr('id', 'combo_'+index);
						    var myCombo = new dhtmlXCombo('combo_'+index, \"combo\", 230);
							myCombo.enableFilteringMode(true, \"http://10.1.1.104/rentas/1.0/complete.php\", true);
						}
					});
 			   },
			}
		);
		$(\"#tabla_dinamica\").tabulator(\"setData\",\"http://10.1.1.104/rentas/1.0/tabla_dinamica.php\", {key1:\"value1\", key2:\"value2\"});

	*/
		/*

	$(window).load(function(){
				console.log('se cargo');
				//var myCombo = new dhtmlXCombo('combo_zone', \"combo\", 230);
				//myCombo.enableFilteringMode(true, \"http://10.1.1.104/rentas/1.0/complete.php\", true);
			}
		);
	
	var myCombo;
		window.load = function() {
			myCombo = new dhtmlXCombo('combo_zone', \"combo\", 230);
			myCombo.enableFilteringMode(true, \"http://10.1.1.104/rentas/1.0/complete.php\", true);
		}	
	

	 $.ajax({
	            type : \"POST\",  //type of method
	            url  : \"consultas.php\",  //your page
	            data : { name : 'hugo', email : 'email', password : 'password' },// passing the values
	            success: function(res){  
	                                    console.log('paso');
	                    }
	        });

	        */

	/*
var tableData = [
		    {id:1, name:\"Billy Bob\", age:\"12\", gender:\"male\", height:1, col:\"red\", dob:\"\", cheese:1},
		    {id:2, name:\"Mary May\", age:\"1\", gender:\"female\", height:2, col:\"blue\", dob:\"14/05/1982\", cheese:true},
		]

		$(\"#tabla_dinamica\").tabulator(\"setData\", tableData);
	*/

	//-----------------------------------------------------------------------------------
	//---- cuadro_dinamico --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_dinamico($form)
	{
		$form->ef('tabla')->set_estado("<div id='tabla_dinamica'></div>");
	}

}
?>