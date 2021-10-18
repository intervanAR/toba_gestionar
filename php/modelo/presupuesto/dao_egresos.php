<?php

class dao_egresos {
	
	static public function get_egresos($filtro= [], $orden = []){
        
      	$desde= null;
		$hasta= null;
		if(isset($filtro['numrow_desde'])){
			$desde= $filtro['numrow_desde'];
			$hasta= $filtro['numrow_hasta'];

			unset($filtro['numrow_desde']);
			unset($filtro['numrow_hasta']);
		}

        $where = self::armar_where($filtro);
        
        $sql_sel = "SELECT	pe.*,
						to_char(pe.fecha_egreso, 'dd/mm/yyyy') fecha_egreso_format,
						CASE
							WHEN pe.automatico = 'S' THEN 'Si'
							ELSE 'No'
						END automatico_format,
            DECODE (pe.confirmado, 'S', 'Si', 'No') confirmado_format,
            DECODE (pe.anulado, 'S', 'Si', 'No') anulado_format,
             (SELECT    cod_unidad_administracion
               || ' - '
               || descripcion
          FROM kr_unidades_administracion
         WHERE cod_unidad_administracion = pe.cod_unidad_administracion)
                                                        unidad_administracion,
          	pte.cod_tipo_egreso || ' - ' || pte.descripcion tipo_egreso,
            (SELECT id_ejercicio || ' - ' || nro_ejercicio
          FROM kr_ejercicios
         WHERE id_ejercicio = pe.id_ejercicio) ejercicio,
						kcc.nro_cuenta_corriente || ' - ' || kcc.descripcion as cuenta_corriente,
						(select  sum(pde.importe)
						from    pr_detalles_egresos pde
						where   pde.id_egreso = pe.id_egreso) as importe
                FROM PR_EGRESOS pe
				JOIN PR_TIPOS_EGRESOS pte ON (pe.cod_tipo_egreso = pte.cod_tipo_egreso)
				LEFT OUTER JOIN KR_CUENTAS_CORRIENTE kcc ON pe.id_cuenta_corriente = kcc.id_cuenta_corriente
				WHERE  $where";

        $sql= dao_varios::paginador($sql_sel, null, $desde, $hasta, null, $orden);

        $datos = toba::db()->consultar($sql); 
		return $datos;
    }

    static public function consultar_auditoria ($id_egreso){
      $sql = "SELECT pre.id_egreso, 
                     pre.usuario_carga, 
                     to_char(pre.fecha_carga,'DD/MM/YYYY hh:mm:ss')fecha_carga, 
                     to_char(pre.fecha_confirma,'DD/MM/YYYY hh:mm:ss')fecha_confirma,
                     to_char(pre.fecha_anula,'DD/MM/YYYY hh:mm:ss') fecha_anula, 
                     pre.usuario_confirma, 
                     pre.usuario_anula
                FROM pr_egresos pre
               WHERE pre.id_egreso = $id_egreso";
      return toba::db()->consultar_fila($sql);
    }

    static private function armar_where ($filtro= [])
    {
    	$where= " 1=1 ";
		if (isset($filtro['ids_comprobantes'])) {
			$where .= " AND pe.id_transaccion IN (" . $filtro['ids_comprobantes'] . ") ";
			unset($filtro['ids_comprobantes']);
		}
		
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'pe', '1=1');
		return $where;
    }

    static public function get_cantidad ($filtro = []){
    	$where = self::armar_where($filtro);

    	$sql = "SELECT count(pe.id_egreso) cantidad
    		    FROM pr_egresos pe JOIN PR_TIPOS_EGRESOS pte ON (pe.cod_tipo_egreso = pte.cod_tipo_egreso) LEFT OUTER JOIN KR_CUENTAS_CORRIENTE kcc ON pe.id_cuenta_corriente = kcc.id_cuenta_corriente
				WHERE $where";

    	$datos = toba::db()->consultar_fila($sql);
    	return $datos['cantidad'];
    }

    static public function get_lov_tipo_egresos_x_nombre($nombre, $filtro) {

        if (isset($nombre)) {
            $trans_cod = ctr_construir_sentencias::construir_translate_ilike('prte.cod_tipo_egreso', $nombre);
            $trans_descricpcion = ctr_construir_sentencias::construir_translate_ilike('prte.descripcion', $nombre);
            $where = "($trans_cod OR $trans_descricpcion)";
        } else {
            $where = "1=1";
        }
        
		
		$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'prte', '1=1');
        
		$sql = "SELECT prte.*, prte.cod_tipo_egreso ||' '|| prte.descripcion lov_descripcion
  				  FROM pr_tipos_egresos prte
                 WHERE $where
              ORDER BY lov_descripcion";
        $datos = toba::db()->consultar($sql);
        return $datos;
    }

    static public function get_lov_tipo_egreso_x_codigo($codigo_tipo_egreso) {

		$sql = "SELECT prte.cod_tipo_egreso ||' - '|| prte.descripcion lov_descripcion
  				  FROM pr_tipos_egresos prte
                 WHERE prte.cod_tipo_egreso = '".$codigo_tipo_egreso."'
              ORDER BY lov_descripcion";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['lov_descripcion'];
    }


    static public function get_lov_comp_egresos_x_id ($id_egreso){
        $sql = "SELECT    '#'
                       || preg.id_egreso
                       || ' - '
                       || l_prtieg.descripcion
                       || ' '
                       || l_krej.nro_ejercicio lov_descripcion
                  FROM pr_egresos preg, pr_tipos_egresos l_prtieg, kr_ejercicios l_krej
                 WHERE preg.cod_tipo_egreso = l_prtieg.cod_tipo_egreso
                   AND preg.id_ejercicio = l_krej.id_ejercicio and preg.id_egreso = $id_egreso";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['lov_descripcion'];
    }

    static public function get_lov_comp_egresos_x_nombre ($nombre, $filtro = []){

        if (isset($nombre)) {
            $trans_cod = ctr_construir_sentencias::construir_translate_ilike('preg.id_egreso', $nombre);
            $trans_descricpcion = ctr_construir_sentencias::construir_translate_ilike('l_prtieg.descripcion', $nombre);
            $where = "($trans_cod OR $trans_descricpcion)";
        } else {
            $where = "1=1";
        }

        if (isset($filtro['cod_etapa_egreso']))
        {
            $where .= "AND EXISTS(SELECT 1 
                                    FROM PR_TIPOS_EGRESOS TE 
                                   WHERE TE.COD_TIPO_EGRESO = PREG.COD_TIPO_EGRESO 
                                     AND TE.COD_ETAPA_EGRESO = (SELECT prte.".$filtro['cod_etapa_egreso']."
                             FROM pr_tipos_egresos prte
                            WHERE prte.cod_tipo_egreso = '".$filtro['cod_tipo_egreso']."'))";

            if (isset($filtro['filtra_egreso_resta']))
            {
                if ($filtro['id_cuenta_corriente'] == '-999'){
                  $where .= "AND FILTRAR_EGRESOS_RESTA((SELECT prte.tipo_control
  FROM pr_tipos_egresos prte
 WHERE prte.cod_tipo_egreso = '".$filtro['cod_tipo_egreso']."'), null, PREG.ID_EGRESO, (SELECT prte.".$filtro['cod_etapa_egreso']."
                             FROM pr_tipos_egresos prte
                            WHERE prte.cod_tipo_egreso = '".$filtro['cod_tipo_egreso']."')) = 'S'";  
                }else{
                  $where .= "AND FILTRAR_EGRESOS_RESTA((SELECT prte.tipo_control
  FROM pr_tipos_egresos prte
 WHERE prte.cod_tipo_egreso = '".$filtro['cod_tipo_egreso']."'), ".$filtro['id_cuenta_corriente'].", PREG.ID_EGRESO, (SELECT prte.".$filtro['cod_etapa_egreso']."
                             FROM pr_tipos_egresos prte
                            WHERE prte.cod_tipo_egreso = '".$filtro['cod_tipo_egreso']."')) = 'S'";
               }
                unset($filtro['id_cuenta_corriente']);
                unset($filtro['filtra_egreso_resta']);
            }
            unset($filtro['cod_tipo_egreso']);
            unset($filtro['cod_etapa_egreso']);
        }
        if (isset($filtro['control_ejercicio'])){
            $where .=" AND L_KREJ.NRO_EJERCICIO IN (SELECT NVL(NRO_EJERCICIO-1,0) 
                                FROM KR_EJERCICIOS EJ 
                                WHERE EJ.ID_EJERCICIO = ".$filtro['control_ejercicio'].")";
            unset($filtro['control_ejercicio']);
        }
        if (isset($filtro['id_egreso_no'])){
            $where .=" AND PREG.ID_EGRESO <> NVL(".$filtro['id_egreso_no'].",0)";
            unset($filtro['id_egreso_no']);
        }

        if (isset($filtro['fecha_egreso'])){
            $where .="AND to_date('".$filtro['fecha_egreso']."','DD/MM/YYYY') >= PREG.FECHA_EGRESO ";
            unset($filtro['fecha_egreso']);
        }

        $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'preg', '1=1');
        $sql = "SELECT  preg.*,  '#'
                       || preg.id_egreso
                       || ' - '
                       || l_prtieg.descripcion
                       || ' '
                       || l_krej.nro_ejercicio lov_descripcion
                  FROM pr_egresos preg, pr_tipos_egresos l_prtieg, kr_ejercicios l_krej
                 WHERE preg.cod_tipo_egreso = l_prtieg.cod_tipo_egreso
                   AND preg.id_ejercicio = l_krej.id_ejercicio and $where
              ORDER BY lov_descripcion ";

        return toba::db()->consultar($sql);
    }
	
    public static function get_tipo_egreso($cod_tipo_egreso){
        $sql = "SELECT prte.*, pree.tiene_cuenta_corriente
                  FROM pr_tipos_egresos prte, pr_etapas_egreso pree
                 WHERE prte.cod_etapa_egreso = pree.cod_etapa_egreso
                   AND cod_tipo_egreso = '".$cod_tipo_egreso."'";
        return toba::db()->consultar_fila($sql);
    }

    static public function confirmar_comprobante_egreso ($id_egreso, $con_transaccion = true){
      $sql = "BEGIN :resultado := pkg_pr_egresos.confirmar_egreso(:id_egreso);END;";
      $parametros = array ( array(  'nombre' => 'id_egreso', 
                                       'tipo_dato' => PDO::PARAM_INT,
                                       'longitud' => 11,
                                       'valor' => $id_egreso),
                     array(  'nombre'=>'resultado', 
                                       'tipo_dato' => PDO::PARAM_STR,
                                       'longitud' => 4000,
                                       'valor' => ''));
          $resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros,'','', $con_transaccion);
          return $resultado[1]['valor'];
    } 

    static public function anular_comprobante_egreso ($id_egreso,$fecha, $con_transaccion = true){
      $sql = "BEGIN :resultado := pkg_pr_egresos.anular_egreso(:id_egreso, :fecha);END;";

      $parametros = array ( array(  'nombre' => 'id_egreso', 
                                    'tipo_dato' => PDO::PARAM_INT,
                                    'longitud' => 11,
                                    'valor' => $id_egreso),
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

    static public function get_tipos_egresos ($filtro = [])
    {
      $where = "1=1";
      $where .= " and ".ctr_construir_sentencias::get_where_filtro($filtro,'prte','1=1');
      $sql = "SELECT prte.*, prte.cod_tipo_egreso ||' '||prte.descripcion lov_descripcion
               from pr_tipos_egresos prte
               where $where";
      return toba::db()->consultar($sql);
    }

    static public function get_tipos_ingresos ($filtro = [])
    {
      $where = "1=1";
      $where .= " and ".ctr_construir_sentencias::get_where_filtro($filtro,'prti','1=1');
      $sql = "SELECT prti.*, prti.cod_tipo_ingreso ||' '||prti.descripcion lov_descripcion
               from pr_tipos_ingresos prti
               where $where";
      return toba::db()->consultar($sql);
    }

}
?>