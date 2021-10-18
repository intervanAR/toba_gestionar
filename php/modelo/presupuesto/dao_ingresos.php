<?php

class dao_ingresos {
	
	static public function get_ingresos($filtro= [], $orden = []){
        
		$desde= null;
		$hasta= null;
		if(isset($filtro['numrow_desde'])){
			$desde= $filtro['numrow_desde'];
			$hasta= $filtro['numrow_hasta'];

			unset($filtro['numrow_desde']);
			unset($filtro['numrow_hasta']);
		}

        $where = self::armar_where($filtro);

        
        
        $sql_sel = "SELECT	pi.*,
						to_char(pi.fecha_ingreso, 'dd/mm/yyyy') fecha_ingreso_format,
						to_char(pi.fecha_ingreso_anulacion, 'dd/mm/yyyy') fecha_ingreso_anulacion_format,
						(select cod_unidad_administracion ||' - '|| descripcion
						from kr_unidades_administracion where cod_unidad_administracion = pi.cod_unidad_administracion) unidad_administracion_format,
						(select id_ejercicio ||' - '||nro_ejercicio from kr_ejercicios where id_ejercicio = pi.id_ejercicio) ejercicio,
						CASE
							WHEN pi.automatico = 'S' THEN 'Si'
							ELSE 'No'
						END automatico_format,
						decode(pi.anulado, 'S','Si','No') anulado_format,
						decode(pi.confirmado, 'S','Si','No') confirmado_format,
						pti.cod_tipo_ingreso || ' - ' || pti.descripcion tipo_ingreso,
						kcc.nro_cuenta_corriente || ' - ' || kcc.descripcion as cuenta_corriente,
						(select  sum(pdi.importe)
						from    pr_detalles_ingresos pdi
						where   pdi.id_ingreso = pi.id_ingreso) as importe
	                FROM PR_INGRESOS pi
					JOIN PR_TIPOS_INGRESOS pti ON (pi.cod_tipo_ingreso = pti.cod_tipo_ingreso)
					LEFT OUTER JOIN KR_CUENTAS_CORRIENTE kcc ON pi.id_cuenta_corriente = kcc.id_cuenta_corriente
					WHERE  $where";
        
        $sql= dao_varios::paginador($sql_sel, null, $desde, $hasta, null, $orden);
        $datos = toba::db()->consultar($sql); 
		return $datos;
    }

    static private function armar_where ($filtro = [])
    {
    	$where= " 1=1 ";
		if (isset($filtro['ids_comprobantes'])) {
			$where .= " AND pi.id_transaccion IN (" . $filtro['ids_comprobantes'] . ") ";
			unset($filtro['ids_comprobantes']);
		}
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'pi', '1=1');

		return $where;
    }

    static public function get_cantidad ($filtro = []){

    	$where = self::armar_where($filtro);

    	$sql = "SELECT count(pi.id_ingreso) cantidad
    		      FROM PR_INGRESOS pi
    		      JOIN PR_TIPOS_INGRESOS pti ON (pi.cod_tipo_ingreso = pti.cod_tipo_ingreso)
					LEFT OUTER JOIN KR_CUENTAS_CORRIENTE kcc ON pi.id_cuenta_corriente = kcc.id_cuenta_corriente
				 WHERE $where";

    	$datos = toba::db()->consultar_fila($sql);
    	return $datos['cantidad'];
    }


    public static function get_tipo_ingreso($cod_tipo_ingreso){
        $sql = "SELECT prti.*, prei.tiene_cuenta_corriente
                  FROM pr_tipos_ingresos prti, pr_etapas_ingresos prei
                 WHERE prti.cod_etapa_ingreso = prei.cod_etapa_ingreso
                   AND cod_tipo_ingreso = '".$cod_tipo_ingreso."'";
        return toba::db()->consultar_fila($sql);
    }

    static public function consultar_auditoria ($id_ingreso){
      $sql = "SELECT pri.id_ingreso, 
                     pri.usuario_carga, 
                     to_char(pri.fecha_carga,'DD/MM/YYYY hh:mm:ss')fecha_carga, 
                     to_char(pri.fecha_confirma,'DD/MM/YYYY hh:mm:ss')fecha_confirma,
                     to_char(pri.fecha_anula,'DD/MM/YYYY hh:mm:ss') fecha_anula, 
                     pri.usuario_confirma, 
                     pri.usuario_anula
                FROM pr_ingresos pri
               WHERE pri.id_ingreso = $id_ingreso";
      return toba::db()->consultar_fila($sql);
    }

    static public function confirmar_comprobante_ingreso ($id_ingreso, $con_transaccion = true){
      $sql = "BEGIN :resultado := pkg_pr_ingresos.confirmar_ingreso(:id_ingreso);END;";
      $parametros = array ( array(  'nombre' => 'id_ingreso', 
                                       'tipo_dato' => PDO::PARAM_INT,
                                       'longitud' => 11,
                                       'valor' => $id_ingreso),
                     array(  'nombre'=>'resultado', 
                                       'tipo_dato' => PDO::PARAM_STR,
                                       'longitud' => 4000,
                                       'valor' => ''));
          $resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros,'','', $con_transaccion);
          return $resultado[1]['valor'];
    } 


    static public function anular_comprobante_ingreso ($id_ingreso,$fecha, $con_transaccion = true){
      $sql = "BEGIN :resultado := pkg_pr_ingresos.anular_ingreso(:id_ingreso, :fecha);END;";

      $parametros = array ( array(  'nombre' => 'id_ingreso', 
                                    'tipo_dato' => PDO::PARAM_INT,
                                    'longitud' => 11,
                                    'valor' => $id_ingreso),
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

    static public function get_lov_tipo_ingresos_x_nombre($nombre, $filtro) {

        if (isset($nombre)) {
            $trans_cod = ctr_construir_sentencias::construir_translate_ilike('prti.cod_tipo_ingreso', $nombre);
            $trans_descricpcion = ctr_construir_sentencias::construir_translate_ilike('prti.descripcion', $nombre);
            $where = "($trans_cod OR $trans_descricpcion)";
        } else {
            $where = "1=1";
        }
        
		
		$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'prti', '1=1');
        
		$sql = "SELECT prti.*, prti.cod_tipo_ingreso ||' '|| prti.descripcion lov_descripcion
  				  FROM pr_tipos_ingresos prti
                 WHERE $where
              ORDER BY lov_descripcion";
        $datos = toba::db()->consultar($sql);
        return $datos;
    }

    static public function get_lov_tipo_ingreso_x_codigo($codigo_tipo_ingreso) {

		$sql = "SELECT prti.cod_tipo_ingreso ||' - '|| prti.descripcion lov_descripcion
  				  FROM pr_tipos_ingresos prti
                 WHERE prti.cod_tipo_ingreso = '".$codigo_tipo_ingreso."'
              ORDER BY lov_descripcion";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['lov_descripcion'];
    }


    static public function get_lov_comp_ingresos_x_id ($id_ingreso){
        $sql = "SELECT    '#'
                       || pring.id_ingreso
                       || ' - '
                       || l_prting.descripcion
                       || ' '
                       || l_krej.nro_ejercicio lov_descripcion
                  FROM pr_ingresos pring, pr_tipos_ingresos l_prting, kr_ejercicios l_krej
                 WHERE pring.cod_tipo_ingreso = l_prting.cod_tipo_ingreso
                   AND pring.id_ejercicio = l_krej.id_ejercicio and pring.id_ingreso = $id_ingreso";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['lov_descripcion'];
    }

    static public function get_lov_comp_ingresos_x_nombre ($nombre, $filtro = []){

        if (isset($nombre)) {
            $trans_cod = ctr_construir_sentencias::construir_translate_ilike('pring.id_ingreso', $nombre);
            $trans_descricpcion = ctr_construir_sentencias::construir_translate_ilike('l_prting.descripcion', $nombre);
            $where = "($trans_cod OR $trans_descricpcion)";
        } else {
            $where = "1=1";
        }

       
        if (isset($filtro['cod_etapa_ingreso']))
        {
            $where .= "AND EXISTS(SELECT 1 
                                    FROM PR_TIPOS_INGRESOS TI 
                                   WHERE TI.cod_tipo_ingreso = pring.cod_tipo_ingreso 
                                     AND TI.cod_etapa_ingreso = (SELECT prti.".$filtro['cod_etapa_ingreso']."
                             FROM pr_tipos_ingresos prti
                            WHERE prti.cod_tipo_ingreso = '".$filtro['cod_tipo_ingreso']."'))";

            if (isset($filtro['filtra_ingreso_resta']))
            {
                if ($filtro['id_cuenta_corriente'] == '-999'){
                  $where .= "AND FILTRAR_INGRESOS_RESTA((SELECT prti.tipo_control
  FROM pr_tipos_ingresos prti
 WHERE prti.cod_tipo_ingreso = '".$filtro['cod_tipo_ingreso']."'), null, pring.id_ingreso, (SELECT prti.".$filtro['cod_etapa_ingreso']."
                             FROM pr_tipos_ingresos prti
                            WHERE prti.cod_tipo_ingreso = '".$filtro['cod_tipo_ingreso']."')) = 'S'";  
                }else{
                  $where .= "AND FILTRAR_INGRESOS_RESTA((SELECT prti.tipo_control
  FROM pr_tipos_ingresos prti
 WHERE prti.cod_tipo_ingreso = '".$filtro['cod_tipo_ingreso']."'), ".$filtro['id_cuenta_corriente'].", pring.id_ingreso, (SELECT prti.".$filtro['cod_etapa_ingreso']."
                             FROM pr_tipos_ingresos prti
                            WHERE prti.cod_tipo_ingreso = '".$filtro['cod_tipo_ingreso']."')) = 'S'";
               }
                unset($filtro['id_cuenta_corriente']);
                unset($filtro['filtra_ingreso_resta']);
            }
            unset($filtro['cod_tipo_ingreso']);
            unset($filtro['cod_etapa_ingreso']);
        }

        if (isset($filtro['control_ejercicio'])){
            $where .=" AND l_krej.NRO_EJERCICIO IN (SELECT NVL(NRO_EJERCICIO-1,0) 
                                FROM KR_EJERCICIOS EJ 
                                WHERE EJ.ID_EJERCICIO = ".$filtro['control_ejercicio'].")";
            unset($filtro['control_ejercicio']);
        }

        if (isset($filtro['id_ingreso_no'])){
            $where .=" AND pring.ID_inGRESO <> NVL(".$filtro['id_ingreso_no'].",0)";
            unset($filtro['id_ingreso_no']);
        }

        if (isset($filtro['fecha_ingreso'])){
            $where .="AND to_date('".$filtro['fecha_ingreso']."','DD/MM/YYYY') >= pring.fecha_ingreso ";
            unset($filtro['fecha_ingreso']);
        }

        $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'pring', '1=1');
        $sql = "SELECT  pring.*,  '#'
                       || pring.id_ingreso
                       || ' - '
                       || l_prting.descripcion
                       || ' '
                       || l_krej.nro_ejercicio lov_descripcion
                  FROM pr_ingresos pring, pr_tipos_ingresos l_prting, kr_ejercicios l_krej
                 WHERE pring.cod_tipo_ingreso = l_prting.cod_tipo_ingreso
                   AND pring.id_ejercicio = l_krej.id_ejercicio and $where
              ORDER BY lov_descripcion ";

        return toba::db()->consultar($sql);
    }
}
?>