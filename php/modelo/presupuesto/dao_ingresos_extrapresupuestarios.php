<?php

class dao_ingresos_extrapresupuestarios {
	
	static public function get_ingresos_extrapresupuestarios($filtro= array(), $orden = array()){
        
		$desde= null;
		$hasta= null;
		if(isset($filtro['numrow_desde'])){
			$desde= $filtro['numrow_desde'];
			$hasta= $filtro['numrow_hasta'];

			unset($filtro['numrow_desde']);
			unset($filtro['numrow_hasta']);
		}

		$where = self::armar_where($filtro);

    	$sql_sel = "SELECT pie.*, TO_CHAR (pie.fecha_ingreso, 'dd/mm/yyyy') fecha_ingreso_format,
    	TO_CHAR (pie.fecha_ingreso_anulacion, 'dd/mm/yyyy') fecha_ingreso_anul_format,
				       DECODE (pie.automatico, 'S', 'Si', 'No') automatico_format,
				       DECODE (pie.ajuste, 'S', 'Si', 'No') ajuste_format,
				       DECODE (pie.confirmado, 'S', 'Si', 'No') confirmado_format,
				       DECODE (pie.anulado, 'S', 'Si', 'No') anulado_format,
				       kae.cod_auxiliar || ' - ' || kae.descripcion AS auxiliar_ext,
				       (SELECT nro_ejercicio
				          FROM kr_ejercicios
				         WHERE id_ejercicio = pie.id_ejercicio) ejercicio,
				       TRIM (TO_CHAR (pie.importe, '$999,999,999,990.00')) AS importe_format,
				          krua.cod_unidad_administracion
				       || ' - '
				       || krua.descripcion unidad_administracion
				  FROM pr_ingresos_ext pie JOIN kr_auxiliares_ext kae
				       ON pie.cod_auxiliar = kae.cod_auxiliar
				       JOIN kr_unidades_administracion krua
				       ON krua.cod_unidad_administracion = pie.cod_unidad_administracion
				WHERE  $where ";
        
        $sql = dao_varios::paginador($sql_sel, null, $desde, $hasta, null, $orden);
        $datos = toba::db()->consultar($sql);
		return $datos;
    }

    private static function armar_where ($filtro = array()){
    	$where= " 1=1 ";
		if (isset($filtro['ids_comprobantes'])) {
			$where .= " AND pie.id_transaccion IN (" . $filtro['ids_comprobantes'] . ") ";
			unset($filtro['ids_comprobantes']);
		}
		
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'pie', '1=1');
		return $where;
    }

    static public function get_cantidad ($filtro = []){
    	$where = self::armar_where($filtro);
    	$sql = "SELECT	count(pie.id_ingreso_ext) cantidad
				FROM PR_INGRESOS_EXT pie
				JOIN KR_AUXILIARES_EXT kae ON pie.cod_auxiliar = kae.cod_auxiliar
				WHERE  $where";
    	$datos = toba::db()->consultar_fila($sql);
    	return $datos['cantidad'];
    }

    static public function consultar_auditoria ($id_ingreso_ext){

      $sql = "SELECT pri.id_ingreso_ext, 
                     pri.usuario_carga, 
                     to_char(pri.fecha_carga,'DD/MM/YYYY hh:mm:ss')fecha_carga, 
                     to_char(pri.fecha_confirma,'DD/MM/YYYY hh:mm:ss')fecha_confirma,
                     to_char(pri.fecha_anula,'DD/MM/YYYY hh:mm:ss') fecha_anula, 
                     pri.usuario_confirma, 
                     pri.usuario_anula
                FROM pr_ingresos_ext pri
               WHERE pri.id_ingreso_ext = $id_ingreso_ext";
      return toba::db()->consultar_fila($sql);
    }

    static public function get_lov_ingresos_ext_x_nombre ($nombre, $filtro=[]){

    	if (isset($nombre)) {
            $trans_cod = ctr_construir_sentencias::construir_translate_ilike('prinex.id_ingreso_ext', $nombre);
            $where = "($trans_cod)";
        } else {
            $where = "1=1";
        }

    	if (isset($filtro['no_id_ingreso_ext'])){
    		$where .=" AND PRINEX.ID_INGRESO_EXT <> NVL(".$filtro['no_id_ingreso_ext'].",0)";
    		unset($filtro['no_id_ingreso_ext']);
    	}

    	if (isset($filtro['fecha_tope'])){
    		$where .=" AND ".$filtro['fecha_tope'].">= PRINEX.FECHA_INGRESO";
    		unset($filtro['fecha_tope']);
    	}

    	$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'prinex', '1=1');

    	$sql = "SELECT prinex.id_ingreso_ext,
				          '#'
				       || prinex.id_ingreso_ext
				       || ' ('
				       || TRIM (TO_CHAR (prinex.importe, '$999,999,999,990.00'))
				       || ')'
				       || ' '||to_char(prinex.fecha_ingreso,'dd/mm/yyyy') 
				       || ' Ejercicio '
				       || (SELECT nro_ejercicio
				             FROM kr_ejercicios
				            WHERE id_ejercicio = prinex.id_ejercicio) lov_descripcion
				  from pr_ingresos_ext prinex
				where $where";
		return toba::db()->consultar($sql);	  
    }

    static public function get_lov_ingresos_ext_x_id ($id_ingreso_ext){
    	$sql = "SELECT    '#'
				       || prinex.id_ingreso_ext
				       || ' ('
				       || TRIM (TO_CHAR (prinex.importe, '$999,999,999,990.00'))
				       || ')'
				       || ' '||to_char(prinex.fecha_ingreso,'dd/mm/yyyy') 
				       || ' Ejercicio '
				       || (SELECT nro_ejercicio
				             FROM kr_ejercicios
				            WHERE id_ejercicio = prinex.id_ejercicio) lov_descripcion
				  from pr_ingresos_ext prinex
				where prinex.id_ingreso_ext = ".$id_ingreso_ext;
		$datos = toba::db()->consultar_fila($sql);	  
		return $datos['lov_descripcion'];
    }


    static public function confirmar_comprobante_ingreso_ext ($id_ingreso_ext, $con_transaccion = true){
      $sql = "BEGIN :resultado := pkg_pr_ingresos_ext.confirmar(:id_ingreso_ext);END;";
      $parametros = array ( array(  'nombre' => 'id_ingreso_ext', 
                                       'tipo_dato' => PDO::PARAM_INT,
                                       'longitud' => 11,
                                       'valor' => $id_ingreso_ext),
                     array(  'nombre'=>'resultado', 
                                       'tipo_dato' => PDO::PARAM_STR,
                                       'longitud' => 4000,
                                       'valor' => ''));
          $resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros,'','', $con_transaccion);
          return $resultado[1]['valor'];
    } 


    static public function anular_comprobante_ingreso_ext ($id_ingreso_ext,$fecha, $con_transaccion = true){
      $sql = "BEGIN :resultado := pkg_pr_ingresos_ext.anular(:id_ingreso_ext, :fecha);END;";

      $parametros = array ( array(  'nombre' => 'id_ingreso_ext', 
                                    'tipo_dato' => PDO::PARAM_INT,
                                    'longitud' => 11,
                                    'valor' => $id_ingreso_ext),
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