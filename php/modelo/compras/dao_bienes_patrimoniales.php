<?php 

class dao_bienes_patrimoniales {

	static public function get_bienes_patrimoniales ($filtro = [], $orden = [])
	{
		$desde= null; 
		$hasta= null;
	    if(isset($filtro['numrow_desde'])){
	      $desde = $filtro['numrow_desde']; $hasta= $filtro['numrow_hasta'];
	      unset($filtro['numrow_desde']); unset($filtro['numrow_hasta']);
	    }
	    
	    $where = self::get_where($filtro);

	    $sql = "
			SELECT pab.*
				   ,(SELECT rv_meaning
			          FROM cg_ref_codes
			         WHERE rv_low_value = pab.estado_conservacion
			           AND rv_domain = 'PA_ESTADO_CONS') est_cons_format
			       ,(SELECT rv_meaning
			          FROM cg_ref_codes
			         WHERE rv_low_value = pab.estado
			           AND rv_domain = 'PA_ESTADO_BIEN') estado_format
			       ,(SELECT rv_meaning
			          FROM cg_ref_codes
			         WHERE rv_low_value = pab.origen
			           AND rv_domain = 'PA_ORIGEN_BIEN') origen_format
			       ,CASE
			          WHEN pab.tipo_amortizacion = 'MES'
			             THEN 'Mensual'
			          WHEN pab.tipo_amortizacion = 'ANIO'
			             THEN 'Anual'
			        END tipo_amortizacion_format
			        ,(SELECT pacb.descripcion
			          FROM pa_clases_bienes pacb
			         WHERE pacb.id_clase = pab.id_clase) clase
			  FROM PA_BIENES pab
			 WHERE $where
	    ";
	    $sql = dao_varios::paginador($sql, null, $desde, $hasta, null, $orden);
        $datos = toba::db()->consultar($sql);
        return $datos;
	}

	/**
	* Para ei_tabulador_cuadro
	*
	*/
	public static function get_bienes()
    {
        $sql = " SELECT pabie.*, to_char(pabie.FECHA_ADQ,'dd/mm/yyyy') fecha_format
                   FROM pa_bienes pabie ";
                   
        return principal_ei_tabulator_consultar::todos_los_datos($sql);
    }

	static public function get_clases_bienes ($filtro = [], $orden = []){
		$desde= null; 
		$hasta= null;
	    if(isset($filtro['numrow_desde'])){
	      $desde = $filtro['numrow_desde']; $hasta= $filtro['numrow_hasta'];
	      unset($filtro['numrow_desde']); unset($filtro['numrow_hasta']);
	    }
	    
	    $where = " 1=1 ";

	    if (isset($filtro['descripcion']) && !empty($filtro['descripcion'])){
	    	$where .=" and upper(pacb.descripcion) like upper('%".$filtro['descripcion']."%')";
	    	unset($filtro['descripcion']);
	    }
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'pacb', '1=1');

	    $sql = "
			SELECT PACB.*
					, case 
					     WHEN PACB.TIPO_AMORTIZACION = 'MES' THEN
					        'Mensual'
					     ELSE
					        'Anual'
					  END tipo_amortizacion_format
			  FROM pa_clases_bienes pacb
			 WHERE $where 
			 ORDER BY PACB.ID_CLASE DESC";
	    $sql = dao_varios::paginador($sql, null, $desde, $hasta, null, $orden);
        $datos = toba::db()->consultar($sql);
        return $datos;
	}
	static public function get_comprobantes_cargo ($id_bien_patrimonial)
	{
		$sql = "SELECT   pacc.id_bien_patrimonial, pacc.id_comprobante_cargo, to_char(pacg.fecha,'DD/MM/YYYY') fecha,
			         (SELECT rv_meaning
			            FROM cg_ref_codes
			           WHERE rv_domain = 'PA_TIPO_COMPROBANTE'
			             AND rv_low_value = pacg.tipo) tipo,
			         pare.nombre depositario, pare.nro_legajo,
			         (SELECT rv_meaning
			            FROM cg_ref_codes
			           WHERE rv_domain = 'PA_ESTADO_GUARDA'
			             AND rv_low_value = pacc.estado) estado
			    FROM pa_bien_comp_cargo pacc,
			         pa_comprobantes_cargo pacg,
			         pa_responsables pare
			   WHERE pacc.id_comprobante_cargo = pacg.id_comprobante_cargo
			     AND pacg.id_responsable_dep_dest = pare.id_responsable and pacc.id_bien_patrimonial = ".$id_bien_patrimonial."
				 ORDER BY pacc.id_comprobante_cargo";
		return toba::db()->consultar($sql);
	}

	static public function get_responsables ($filtro = [], $orden = [])
	{
		$where = "1=1";
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'pares', '1=1');
		$sql = "SELECT PARES.*,
		substr(to_char(pares.cuil),1,2)||'-'||to_CHAR(substr(to_char(pares.cuil),3,8))||'-'||substr(to_char(pares.cuil),11,11) cuil_format
				  FROM pa_responsables PARES
				  WHERE $where
				 order by pares.id_responsable desc";
		return toba::db()->consultar($sql);
	}

	static public function tiene_bienes($id_factura, $id_detalle)
	{
		$sql = "SELECT count(1) cantidad 
		    	  FROM pa_bienes
		    	 WHERE id_factura = $id_factura
		    	   AND id_detalle = $id_detalle";
	   	$datos = toba::db()->consultar_fila($sql);
	   	return intval($datos['cantidad']);
	}

	static public function get_bien_patrimonial_x_factura($id_factura, $id_detalle)
	{
		$sql = "SELECT id_bien_patrimonial FROM pa_bienes WHERE id_factura = $id_factura and id_detalle = $id_detalle ";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['id_bien_patrimonial'];
	}
	static public function get_nro_expediente_x_factura($id_factura)
	{
		$sql = "SELECT nro_expediente
				  FROM kr_expedientes a, ad_facturas b
				 WHERE a.id_expediente = b.id_expediente AND b.id_factura = $id_factura";

		$datos = toba::db()->consultar_fila($sql);
		return $datos['nro_expediente'];
	}
	static private function get_where ($filtro = [])
	{

		$usuario = toba::usuario()->get_id();
	    $where = "1=1"; 

	    if (isset($filtro['descripcion'])){
	    	$where .=" and upper(pab.descripcion) like upper('%".$filtro['descripcion']."%')";
	    	unset($filtro['descripcion']);
	    }

	    if (isset($filtro['notas'])){
	    	$where .=" and upper(pab.notas) like upper('%".$filtro['notas']."%')";
	    	unset($filtro['notas']);
	    }

	    if (isset($filtro['nro_patrimonial'])){
	    	$where .=" and upper(pab.nro_patrimonial) like upper('%".$filtro['nro_patrimonial']."%')";
	    	unset($filtro['nro_patrimonial']);
	    }

	    $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'pab', '1=1');
	    return $where;
	}

	static public function get_cantidad ($filtro = [])
	{
		$where = self::get_where($filtro);
	    $sql = "SELECT COUNT(*) cant 
		        FROM PA_BIENES PAB
		        WHERE $where";
	    $datos = toba::db()->consultar_fila($sql);
	    return $datos['cant'];

	}

	static public function get_bien_patrimonial ($id_bien_patrimonial)
	{
		$sql = "select *
				  from pa_bienes
				 where id_bien_patrimonial = ".quote($id_bien_patrimonial);
		return toba::db()->consultar_fila($sql);
	}
	static public function get_lov_bienes_x_id ($id_bien_patrimonial)
	{
		$sql =" SELECT pabie.id_bien_patrimonial || ' - '||pabie.descripcion lov_descripcion
				  FROM pa_bienes pabie
				 where pabie.id_bien_patrimonial = ".quote($id_bien_patrimonial);
	    $datos = toba::db()->consultar_fila($sql);
	    return $datos['lov_descripcion'];
	}
	static public function get_lov_bienes_x_nombre ($id_bien_patrimonial, $filtro = [])
	{
		$where = " 1=1 ";
		$lov_descripcion = "pabie.id_bien_patrimonial";
		if (isset($filtro['campos']))
		{
			foreach ($filtro['campos'] as $campo => $chequeado) {
				if ($chequeado == '1')
				{
					$lov_descripcion .= " ||' - '|| pabie.".$campo;
				}
			}
			unset($filtro['campos']);
		}
		$lov_descripcion .=" as lov_descripcion ";

		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'pabie', '1=1');

		$sql =" SELECT pabie.*,
					   $lov_descripcion
				  FROM pa_bienes pabie
				 where $where";

	    return toba::db()->consultar($sql);
	}

	static public function get_lov_clases_bienes_x_nombre ($nombre, $filtro = [])
	{
		$where = ' 1=1 ';
        if (isset($nombre)) {
            $trans_codigo = ctr_construir_sentencias::construir_translate_ilike('pacb.codigo', $nombre);
            $trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('pacb.descripcion', $nombre);
            $where .= " AND ($trans_codigo OR $trans_descripcion)";
        }

        $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'pacb', '1=1');

		$sql = "SELECT pacb.*, '#'||pacb.id_clase ||' - '|| pacb.CODIGO ||' - '|| pacb.descripcion lov_descripcion
          		  FROM pa_clases_bienes pacb
          		 WHERE $where";

      	return toba::db()->consultar($sql);
	}

	static public function get_lov_clases_bienes_x_id ($id_clase)
	{
		$sql = "SELECT pacb.*, '#'||pacb.id_clase ||' - '|| pacb.CODIGO ||' - '|| pacb.descripcion lov_descripcion
          		  FROM pa_clases_bienes pacb
          		 WHERE pacb.id_clase = ".quote($id_clase);
  		$datos = toba::db()->consultar_fila($sql);
  		return $datos['lov_descripcion'];
	}

	static public function get_clase_bien_x_id ($id_clase)
	{
		$sql = "SELECT pacb.*, '#'||pacb.id_clase ||' - '|| pacb.CODIGO ||' - '|| pacb.descripcion lov_descripcion
          		  FROM pa_clases_bienes pacb
          		 WHERE pacb.id_clase = ".quote($id_clase);
  		return toba::db()->consultar_fila($sql);
	}

	static public function get_datos_recepcion_compra ($nro_recepcion)
	{
		$sql = "SELECT to_char(cr.fecha,'dd/mm/yyyy') as FECHA_ADQ
				         , co.numero || '/' || co.anio as NRO_OCOMPRA
				         , kr.NRO_EXPEDIENTE as NRO_EXP_ADQ
				         , cp.razon_social  as proveedor
				    FROM CO_RECEPCIONES cr,
				        CO_ORDENES co,
				        KR_EXPEDIENTES kr,
				        CO_PROVEEDORES cp
				    WHERE cr.nro_orden = co.nro_orden
				        AND kr.id_expediente (+) = co.id_expediente
				        AND cp.id_proveedor =  co.id_proveedor
				        AND cr.nro_recepcion = ".quote($nro_recepcion);
        
        $datos = toba::db()->consultar_fila($sql);
        return $datos;
	}

	static public function get_item_recepcion ($nro_recepcion, $nro_renglon)
	{
		$sql = "SELECT 
			        substr(ART.DESCRIPCION || REC.DETALLE,1,254) as descripcion
			        , ART.COD_PARTIDA  PARTIDA_PRES
			        , REC.PRECIO precio
			    FROM
				    CO_ITEMS_RECEPCION REC,
				    CO_ARTICULOS ART
				WHERE 
				    ART.COD_ARTICULO = REC.COD_ARTICULO
				    AND REC.NRO_RECEPCION = ".quote($nro_recepcion)."  
				    AND REC.NRO_RENGLON = ".quote($nro_renglon);

		$datos = toba::db()->consultar_fila($sql);
		return $datos;
	}

	static public function get_descripcion_clase ($id_clase)
	{
		$sql = "SELECT pacb.*
          		  FROM pa_clases_bienes pacb
          		 WHERE pacb.id_clase = ".quote($id_clase);

  		$datos = toba::db()->consultar_fila($sql);
  		return $datos['descripcion'];
	}

	public static function desactivar_bien ($id_bien_patrimonial, $con_transaccion = true){
    	
		$sql = "BEGIN :resultado := PKG_PATRIMONIO.desactivarBIEN(:id_bien_patrimonial); END;";
		$parametros = array(
							array(	'nombre' => 'id_bien_patrimonial',
									'tipo_dato' => PDO::PARAM_INT,
									'longitud' => 32,
									'valor' => $id_bien_patrimonial),
							array(	'nombre' => 'resultado',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 4000,
									'valor' => ''),);
		
		$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', '', $con_transaccion);
		return $resultado[1]['valor'];
    }
    public static function activar_bien ($id_bien_patrimonial, $con_transaccion = true){
    	
		$sql = "BEGIN :resultado := PKG_PATRIMONIO.activarBIEN(:id_bien_patrimonial); END;";
		$parametros = array(
							array(	'nombre' => 'id_bien_patrimonial',
									'tipo_dato' => PDO::PARAM_INT,
									'longitud' => 32,
									'valor' => $id_bien_patrimonial),
							array(	'nombre' => 'resultado',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 4000,
									'valor' => ''),);
		
		$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', '', $con_transaccion);
		return $resultado[1]['valor'];
    }

    public static function crear_bien_mejora ($id_bien_patrimonial, $fecha_mejora, $con_transaccion = true){
		$sql = "BEGIN :resultado := PKG_PATRIMONIO.CrearBienMejora(:id_bien_patrimonial, :fecha_mejora, :id_bien_mejora);END;";
		$parametros = array(array(	'nombre' => 'id_bien_patrimonial',
									'tipo_dato' => PDO::PARAM_INT,
									'longitud' => 32,
									'valor' => $id_bien_patrimonial),
							array(	'nombre' => 'fecha_mejora',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 100,
									'valor' => $fecha_mejora),
							array(	'nombre' => 'id_bien_mejora',
									'tipo_dato' => PDO::PARAM_INT,
									'longitud' => 32,
									'valor' => ''),
							array(	'nombre' => 'resultado',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 4000,
									'valor' => ''),);
		
		$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', '', $con_transaccion);
		return ["mensaje"=>$resultado[3]['valor'], "id_bien_patrimonial"=>$resultado[2]['valor']];
		
    }

    public static function baja_bien ($id_bien_patrimonial, $fecha, $causa, $nro_expediente,$observacion, $con_transaccion = true){

    	if (is_null($nro_expediente))
    		$nro_expediente = '';
    	
    	if (is_null($observacion))
    		$observacion = '';


		$sql = "BEGIN :resultado := PKG_PATRIMONIO.BAJABIEN(:id_bien_patrimonial, :fecha, :causa, :nro_expediente, :observacion);END;";
		$parametros = array(array(	'nombre' => 'id_bien_patrimonial',
									'tipo_dato' => PDO::PARAM_INT,
									'longitud' => 32,
									'valor' => $id_bien_patrimonial),
							array(	'nombre' => 'fecha',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 100,
									'valor' => $fecha),
							array(	'nombre' => 'causa',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 3,
									'valor' => $causa),
							array(	'nombre' => 'nro_expediente',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 100,
									'valor' => $nro_expediente),
							array(	'nombre' => 'observacion',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 4000,
									'valor' => $observacion),
							array(	'nombre' => 'resultado',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 4000,
									'valor' => ''),);
		
		$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, null, null, $con_transaccion);
		return $resultado[5]['valor'];
		
    }

    public static function copias ($id_bien_patrimonial, $copias, $con_transaccion = true){
		$sql = "BEGIN :resultado := PKG_PATRIMONIO.AGREGARBIEN(:id_bien_patrimonial, :copias);END;";
		$parametros = array(array(	'nombre' => 'id_bien_patrimonial',
									'tipo_dato' => PDO::PARAM_INT,
									'longitud' => 32,
									'valor' => $id_bien_patrimonial),
							array(	'nombre' => 'copias',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 32,
									'valor' => $copias),
							array(	'nombre' => 'resultado',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 4000,
									'valor' => ''),);
		
		$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', '', $con_transaccion);
		return $resultado[2]['valor'];
		
    }
}
?>
