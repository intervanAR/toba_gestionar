<?php

class dao_egresos_extrapresupuestarios {
	
	static public function get_egresos_extrapresupuestarios($filtro= array(), $orden = array())
	{

        $desde= null;
		$hasta= null;
		if(isset($filtro['numrow_desde'])){
			$desde= $filtro['numrow_desde'];
			$hasta= $filtro['numrow_hasta'];

			unset($filtro['numrow_desde']);
			unset($filtro['numrow_hasta']);
		}

		$where = self::armar_where($filtro);

        $sql_sel = "SELECT	pee.*,
            krua.cod_unidad_administracion ||' - '|| krua.descripcion unidad_administracion,
            decode(pee.confirmado,'S','Si','No') confirmado_format,
            decode(pee.automatico,'S','Si','No') automatico_format,
            decode(pee.anulado,'S','Si','No') anulado_format,
            decode(pee.ajuste,'S','Si','No') ajuste_format,
						to_char(pee.fecha_egreso, 'dd/mm/yyyy') fecha_egreso_format,
            to_char(pee.fecha_egreso_anulacion, 'dd/mm/yyyy') fecha_anul_format,
            (SELECT nro_ejercicio
                  FROM kr_ejercicios
                 WHERE id_ejercicio = pee.id_ejercicio) ejercicio,
               TRIM (TO_CHAR (pee.importe, '$999,999,999,990.00')) AS importe_format,
						kae.cod_auxiliar || ' - ' || kae.descripcion as auxiliar
                FROM PR_EGRESOS_EXT pee
				JOIN KR_AUXILIARES_EXT kae ON pee.cod_auxiliar = kae.cod_auxiliar
        JOIN KR_UNIDADES_ADMINISTRACION KRUA ON pee.cod_unidad_administracion = krua.cod_unidad_administracion
				WHERE  $where ";
        
        $sql = dao_varios::paginador($sql_sel, null, $desde, $hasta, null, $orden);
        $datos = toba::db()->consultar($sql); 
		return $datos;
    }

    private static function armar_where ($filtro = array())
    {
    	$where= " 1=1 ";
		if (isset($filtro['ids_comprobantes'])) {
			$where .= " AND pee.id_transaccion IN (" . $filtro['ids_comprobantes'] . ") ";
			unset($filtro['ids_comprobantes']);
		}
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'pee', '1=1');
		return $where;
    }

    static public function get_cantidad ($filtro = [])
    {
    	$where = self::armar_where($filtro);
    	$sql = "SELECT	count(pee.id_egreso_ext) cantidad
				FROM PR_EGRESOS_EXT pee
				JOIN KR_AUXILIARES_EXT kae ON pee.cod_auxiliar = kae.cod_auxiliar
				WHERE $where";
    	$datos = toba::db()->consultar_fila($sql);
    	return $datos['cantidad'];
    }

    static public function consultar_auditoria ($id_egreso_ext){

      $sql = "SELECT pre.id_egreso_ext, 
                     pre.usuario_carga, 
                     to_char(pre.fecha_carga,'DD/MM/YYYY hh:mm:ss')fecha_carga, 
                     to_char(pre.fecha_confirma,'DD/MM/YYYY hh:mm:ss')fecha_confirma,
                     to_char(pre.fecha_anula,'DD/MM/YYYY hh:mm:ss') fecha_anula, 
                     pre.usuario_confirma, 
                     pre.usuario_anula
                FROM pr_egresos_ext pre
               WHERE pre.id_egreso_ext = $id_egreso_ext";
      return toba::db()->consultar_fila($sql);
    }


    static public function get_lov_egresos_ext_x_nombre ($nombre, $filtro=[]){

    	if (isset($nombre)) {
            $trans_cod = ctr_construir_sentencias::construir_translate_ilike('pre.id_egreso_ext', $nombre);
            $where = "($trans_cod)";
        } else {
            $where = "1=1";
        }

    	if (isset($filtro['no_id_egreso_ext'])){
    		$where .=" AND PRINEX.ID_INGRESO_EXT <> NVL(".$filtro['no_id_egreso_ext'].",0)";
    		unset($filtro['no_id_egreso_ext']);
    	}

    	if (isset($filtro['fecha_tope'])){
    		$where .=" AND ".$filtro['fecha_tope'].">= PRINEX.FECHA_INGRESO";
    		unset($filtro['fecha_tope']);
    	}

    	$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'pre', '1=1');

    	$sql = "SELECT pre.id_egreso_ext,
				          '#'
				       || pre.id_egreso_ext
				       || ' ('
				       || TRIM (TO_CHAR (pre.importe, '$999,999,999,990.00'))
				       || ')'
				       || ' '||to_char(pre.fecha_egreso,'dd/mm/yyyy') 
				       || ' Ejercicio '
				       || (SELECT nro_ejercicio
				             FROM kr_ejercicios
				            WHERE id_ejercicio = pre.id_ejercicio) lov_descripcion
				  from pr_egresos_ext pre
				where $where";
		return toba::db()->consultar($sql);	  
    }

    static public function get_lov_egresos_ext_x_id ($id_egreso_ext){
    	$sql = "SELECT    '#'
				       || pre.id_egreso_ext
				       || ' ('
				       || TRIM (TO_CHAR (pre.importe, '$999,999,999,990.00'))
				       || ')'
				       || ' '||to_char(pre.fecha_egreso,'dd/mm/yyyy') 
				       || ' Ejercicio '
				       || (SELECT nro_ejercicio
				             FROM kr_ejercicios
				            WHERE id_ejercicio = pre.id_ejercicio) lov_descripcion
				  from pr_egresos_ext pre
				where pre.id_egreso_ext = ".$id_egreso_ext;
		$datos = toba::db()->consultar_fila($sql);	  
		return $datos['lov_descripcion'];
    }


    static public function confirmar_comprobante_egreso_ext ($id_egreso_ext, $con_transaccion = true){
      $sql = "BEGIN :resultado := pkg_pr_egresos_ext.confirmar(:id_egreso_ext);END;";
      $parametros = array ( array(  'nombre' => 'id_egreso_ext', 
                                       'tipo_dato' => PDO::PARAM_INT,
                                       'longitud' => 11,
                                       'valor' => $id_egreso_ext),
                     array(  'nombre'=>'resultado', 
                                       'tipo_dato' => PDO::PARAM_STR,
                                       'longitud' => 4000,
                                       'valor' => ''));
          $resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros,'','', $con_transaccion);
          return $resultado[1]['valor'];
    } 


    static public function anular_comprobante_egreso_ext ($id_egreso_ext,$fecha, $con_transaccion = true){
      $sql = "BEGIN :resultado := pkg_pr_egresos_ext.anular(:id_egreso_ext, :fecha);END;";

      $parametros = array ( array(  'nombre' => 'id_egreso_ext', 
                                    'tipo_dato' => PDO::PARAM_INT,
                                    'longitud' => 11,
                                    'valor' => $id_egreso_ext),
                            array(  'nombre' => 'fecha', 
                                    'tipo_dato' => PDO::PARAM_STR,
                                    'longitud' => 40,
                                    'valor' => $fecha),
                            array(  'nombre'=>'resultado', 
                                    'tipo_dato' => PDO::PARAM_STR,
                                    'longitud' => 4000,
                                    'valor' => ''));

      $resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros,'','', $con_transaccion);
      return $resultado[2]['valor'];
    }

}
?>