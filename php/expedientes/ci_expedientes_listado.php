<?php
class ci_expedientes_listado extends ci_abm_complejo_listado {

	protected $clase_carga = 'dao_expedientes';
	protected $metodo_carga = 'get_expedientes';
	protected $ci_abm_complejo_edicion = 'edicion'; // dependencia CI de edicion
	public $dt_encabezado = 'KR_EXPEDIENTES'; // Datos tabla de la tabla encabezado
	public $dt_detalle = ''; // Datos tabla de la tabla detalle
	public $dt_imputacion_pre = ''; // Datos tabla de la tabla de imputacion presupuestaria
	public $dt_imputacion_cos = ''; // Datos tabla de la tabla de imputacion por centro de costos
	public $campo_id_comprobante = 'id_expediente'; // campo de la tabla maestra que indica la clave del comprobante
	public $campo_aprobado = 'activo'; // campo de la tabla maestra que indica que esta aprobado el comprobante

	public function get_nro_expedientes_x_tipo($cod_tipo_expediente) {
		$datos = $this->controlador()->tabla($this->controlador()->dt_encabezado)->get();
        return dao_expedientes::get_nro_expedientes_x_tipo($datos['nro_expediente'], $cod_tipo_expediente);
    }
	
	
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro__imprimir($seleccion)
	{
		foreach ($seleccion as $clave => $valor) {
			$seleccion[$clave] = strip_tags($valor);
		}
		$parametros = array ("P_ID_EXPEDIENTE"=>$seleccion['id_expediente']);
		$llamador = new reporte_llamador('rep_kr_expediente');
		$respuesta  = $llamador->llamar_reporte($parametros, 'PDF', 'oracle');
		if (isset($respuesta['nombre_archivo']) && !empty($respuesta['nombre_archivo'])){
    		$this->url_reporte = $respuesta['url'];
    		$this->reporte_generado = true;
    	}else{
    		toba::notificacion()->info("No se puede generar el Reporte");
    	}
	}
	

	function evt__cuadro__ordenes($seleccion)
	{
		foreach ($seleccion as $clave => $valor) {
			$seleccion[$clave] = strip_tags($valor);
		}
		$datos_exp = dao_expedientes::get_expedientes_x_id($seleccion['id_expediente']);
		$paramtro = "Resumen_por_ctas_de_Expediente_".$datos_exp['nro_expediente'];
		$parametros = array ("P_ID_EXPEDIENTE"=>$seleccion['id_expediente'], "P_PARAMETROS"=>$paramtro);
		$llamador = new reporte_llamador('REP_AD_LIST_ORD_PAGO_EXPEDIENTE');
		$respuesta  = $llamador->llamar_reporte($parametros, 'PDF', 'oracle');
		if (isset($respuesta['nombre_archivo']) && !empty($respuesta['nombre_archivo'])){
    		$this->url_reporte = $respuesta['url'];
    		$this->reporte_generado = true;
    	}else{
    		toba::notificacion()->info("No se puede generar el Reporte");
    	}
	}

	function evt__cuadro__resumen($seleccion)
	{
		foreach ($seleccion as $clave => $valor) {
			$seleccion[$clave] = strip_tags($valor);
		}
		$datos_exp = dao_expedientes::get_expedientes_x_id($seleccion['id_expediente']);
		$paramtro = "Resumen_por_ctas_de_Expediente_".$datos_exp['nro_expediente'];
		$parametros = array ("P_ID_EXPEDIENTE"=>$seleccion['id_expediente'], "P_PARAMETROS"=>$paramtro);
		$llamador = new reporte_llamador('REP_KR_EXPEDIENTE_RESUMEN');
		$respuesta  = $llamador->llamar_reporte($parametros, 'PDF', 'oracle');
		if (isset($respuesta['nombre_archivo']) && !empty($respuesta['nombre_archivo'])){
    		$this->url_reporte = $respuesta['url'];
    		$this->reporte_generado = true;
    	}else{
    		toba::notificacion()->info("No se puede generar el Reporte");
    	}
	}

	function evt__cuadro__asientos($seleccion)
	{
		foreach ($seleccion as $clave => $valor) {
			$seleccion[$clave] = strip_tags($valor);
		}
		$datos_exp = dao_expedientes::get_expedientes_x_id($seleccion['id_expediente']);
		$paramtro = "Asientos_de_Expediente:".$datos_exp['nro_expediente'];
		$parametros = array ("P_ID_EXPEDIENTE"=>$seleccion['id_expediente'], "P_PARAMETROS"=>$paramtro);
		$llamador = new reporte_llamador('REP_KR_EXPEDIENTE_ASTO');
		$respuesta  = $llamador->llamar_reporte($parametros, 'PDF', 'oracle');
		if (isset($respuesta['nombre_archivo']) && !empty($respuesta['nombre_archivo'])){
    		$this->url_reporte = $respuesta['url'];
    		$this->reporte_generado = true;
    	}else{
    		toba::notificacion()->info("No se puede generar el Reporte");
    	}
	}

}
?>