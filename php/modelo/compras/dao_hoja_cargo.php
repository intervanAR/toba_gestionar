<?php 

class dao_hoja_cargo {

	public static function cargo_total_paginas($tamanyo, $filtros)
	{
		$tabla = 'bienes';
		$from = "V_PA_BIENES_HOJA_CARGO $tabla";

		return principal_ei_tabulator_consultar::get_total_paginas(
			$tabla,
			$tamanyo,
			$from,
			$filtros
		);
	}

	public static function descargo_total_paginas($tamanyo, $filtros)
	{
		$tabla = 'bienes';
		$from = "V_PA_SELECC_BIENES_DESCARGO $tabla";

		return principal_ei_tabulator_consultar::get_total_paginas(
			$tabla,
			$tamanyo,
			$from,
			$filtros
		);
	}


	public static function get_cargos_por_pagina(
		$tamanyo,
		$pagina,
		$filtros,
		$ordenamientos
	) {
		$tabla = 'bienes';
		$sql = "
			SELECT $tabla.*
			FROM V_PA_BIENES_HOJA_CARGO $tabla
		";

		return principal_ei_tabulator_consultar::por_pagina(
			$tabla,
			$tamanyo,
			$pagina,
			$sql,
			$filtros,
			$ordenamientos
		);
	}

	public static function get_descargos_por_pagina(
		$tamanyo,
		$pagina,
		$filtros,
		$ordenamientos
	) {
		$tabla = 'bienes';
		$sql = "
			SELECT $tabla.*
			FROM V_PA_SELECC_BIENES_DESCARGO $tabla
		";

		return principal_ei_tabulator_consultar::por_pagina(
			$tabla,
			$tamanyo,
			$pagina,
			$sql,
			$filtros,
			$ordenamientos
		);
	}

	static public function get_hojas_cargo ($filtro = [], $orden = []){
		$filtro = array_merge($filtro, ['tipo'=>'CAR']);
		return self::get_hojas($filtro, $orden);
	}
	static public function get_hojas_descargo ($filtro = [], $orden = []){
		$filtro = array_merge($filtro, ['tipo'=>'DES']);
		return self::get_hojas($filtro, $orden);
	}

	static public function borrar_item ($id_bien_patrimonial, $id_comprobante_cargo)
	{
		$sql = " DELETE FROM pa_bien_comp_cargo
			      WHERE id_comprobante_cargo = $id_comprobante_cargo
			        AND id_bien_patrimonial = $id_bien_patrimonial
			        AND id_comprobante_cargo IN (SELECT c.id_comprobante_cargo
			                                       FROM pa_comprobantes_cargo c
			                                      WHERE c.estado = 'SCO')";
		$res = toba::db()->ejecutar($sql);	                                      
		return $res;
	}

	static public function existe_en_hoja($id_comprobante, $id_bien_patrimonial)
	{
		$sql = "SELECT COUNT (pabc.id_bien_patrimonial) cant
				  FROM pa_bien_comp_cargo pabc
				 WHERE pabc.id_bien_patrimonial = $id_bien_patrimonial
				   AND id_comprobante_cargo = $id_comprobante";
		$datos = toba::db()->consultar_fila($sql);
	
		if (intval($datos['cant']) > 0)
			return true;
		return false;
	}

	private static function get_hojas ($filtro = [], $orden = []){
		$desde= null; 
		$hasta= null;
	    if(isset($filtro['numrow_desde'])){
	      $desde = $filtro['numrow_desde']; $hasta= $filtro['numrow_hasta'];
	      unset($filtro['numrow_desde']); unset($filtro['numrow_hasta']);
	    }
	    
	    $where = self::get_where($filtro);

	    $sql = "SELECT pacc.*
	    		   ,to_char(pacc.fecha,'dd/mm/yyyy') fecha_format
			       ,(SELECT rv_meaning
			          FROM cg_ref_codes
			         WHERE rv_domain = 'PA_TIPO_COMPROBANTE'
			           AND rv_low_value = pacc.tipo) tipo_format
			       ,(SELECT rv_meaning
			          FROM cg_ref_codes
			         WHERE rv_domain = 'PA_ESTADO_CARGO'
			           AND rv_low_value = pacc.estado) estado_format
		           ,(select nombre from PA_RESPONSABLES where id_responsable = pacc.ID_RESPONSABLE_DEP_DEST ) responsable_dep_dest
			       ,(select nombre from PA_RESPONSABLES where id_responsable = pacc.ID_RESPONSABLE_resp_orig ) responsable_resp_orig
			       ,(select nombre from PA_RESPONSABLES where id_responsable = pacc.ID_RESPONSABLE_DEP_orig ) responsable_dep_orig
			       ,(select nombre from PA_RESPONSABLES where id_responsable = pacc.id_responsable ) responsable
			       ,(SELECT padep.id_dependencia ||' - '|| padep.descripcion
					  FROM pa_dependencias padep
					 WHERE padep.id_dependencia = pacc.id_dependencia_acargo) dependencia_acargo
			  FROM pa_comprobantes_cargo pacc
			 WHERE $where

	    ";
	    $sql = dao_varios::paginador($sql, null, $desde, $hasta, null, $orden);
        $datos = toba::db()->consultar($sql);
        return $datos;
	}

	static private function get_where ($filtro = [])
	{
		$usuario = toba::usuario()->get_id();
	    $where = "1=1"; 

	    if (isset($filtro['observaciones']))
	    {
	    	$where .=" and upper(pacc.observaciones) like upper('"+$filtro['observaciones']+"')";
	    	unset($filtro['observaciones']);
	    }

	    $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'pacc', '1=1');
	    return $where;
	}

	static public function get_cantidad ($filtro = [])
	{
		$where = self::get_where($filtro);
	    $sql = "SELECT COUNT(*) cant 
		        FROM pa_comprobantes_cargo pacc
		        WHERE $where";
	    $datos = toba::db()->consultar_fila($sql);
	    return $datos['cant'];
	}

	static public function get_bienes_carga ($id_comprobante_cargo){
		$sql = "SELECT pabcc.*,
				       (SELECT rv_meaning
				          FROM cg_ref_codes
				         WHERE rv_low_value = pab.estado_conservacion
				           AND rv_domain = 'PA_ESTADO_CONS') pab_est_cons_format,
				       (SELECT rv_meaning
				          FROM cg_ref_codes
				         WHERE rv_low_value = pab.estado
				           AND rv_domain = 'PA_ESTADO_BIEN') pab_estado_format,
				       (SELECT rv_meaning
				          FROM cg_ref_codes
				         WHERE rv_low_value = pab.origen
				           AND rv_domain = 'PA_ORIGEN_BIEN') pab_origen_format,
				       CASE
				          WHEN pab.tipo_amortizacion = 'MES'
				             THEN 'Mensual'
				          WHEN pab.tipo_amortizacion = 'ANIO'
				             THEN 'Anual'
				       END pab_tipo_amortizacion_format,
				       (SELECT pacb.descripcion
				          FROM pa_clases_bienes pacb
				         WHERE pacb.id_clase = pab.id_clase) pab_clase, pab.observaciones,
				       pab.descripcion, to_char(pab.fecha_adq,'dd/mm/yyyy') pab_fecha_adq, pab.nro_patrimonial, pab.nro_serie,
				       pab.cantidad, pab.precio pab_precio, pab.nro_patrimonial_int
				  FROM pa_bien_comp_cargo pabcc, pa_bienes pab
				 WHERE pabcc.id_bien_patrimonial = pab.id_bien_patrimonial and pabcc.id_comprobante_cargo = $id_comprobante_cargo
				 order by pab.id_bien_patrimonial ";
		return toba::db()->consultar($sql);		

	}

	static public function get_lov_responsable_x_nombre ($nombre, $filtro = [])
	{
		$where = ' 1=1 ';
        if (isset($nombre)) {
            $trans_nom = ctr_construir_sentencias::construir_translate_ilike('nombre', $nombre);
            $trans_cuil = ctr_construir_sentencias::construir_translate_ilike('cuil', $nombre);
            $where .= " AND ($trans_nom OR $trans_cuil)";
        }

        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'pares', '1=1');

        $sql = "SELECT pares.*, pares.nombre || ' - ' || pares.cuil lov_descripcion
  				  FROM pa_responsables pares
  				 WHERE $where
  				 order by lov_descripcion";
        return toba::db()->consultar($sql);
	}

	static public function get_lov_responsable_x_id ($id_responsable)
	{
        $sql = "SELECT pares.nombre || ' - ' || pares.cuil lov_descripcion
  				  FROM pa_responsables pares
  				 WHERE pares.id_responsable = $id_responsable ";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['lov_descripcion'];
	}

	static public function get_lov_dependencia_x_nombre ($nombre, $filtro = [])
	{
		$where = ' 1=1 ';
        if (isset($nombre)) {
            $trans_nom = ctr_construir_sentencias::construir_translate_ilike('id_dependencia', $nombre);
            $trans_cuil = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
            $where .= " AND ($trans_nom OR $trans_cuil)";
        }

        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'padep', '1=1');

        $sql = "SELECT padep.*, padep.ID_DEPENDENCIA || ' - ' || padep.DESCRIPCION lov_descripcion
  				  FROM pa_dependencias padep
  				 WHERE $where
  				 order by lov_descripcion";
        return toba::db()->consultar($sql);
	}

	static public function get_lov_dependencia_x_id ($id_dependencia)
	{
        $sql = "SELECT padep.*, padep.ID_DEPENDENCIA || ' - ' || padep.DESCRIPCION lov_descripcion
  				  FROM pa_dependencias padep
  				 WHERE padep.id_dependencia = $id_dependencia ";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['lov_descripcion'];
	}

	public static function confirmar ($id_comprobante_cargo, $con_transaccion = true){
		$sql = "BEGIN :resultado := pkg_patrimonio.HojaCargoConfirmar(:id_comprobante_cargo);END;";
		$parametros = array(array(	'nombre' => 'id_comprobante_cargo',
									'tipo_dato' => PDO::PARAM_INT,
									'longitud' => 32,
									'valor' => $id_comprobante_cargo),
							array(	'nombre' => 'resultado',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 4000,
									'valor' => ''),);
		$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', '', $con_transaccion);
		return $resultado[1]['valor'];
    }

    public static function cancelar ($id_comprobante_cargo, $con_transaccion = true){
		$sql = "BEGIN :resultado := pkg_patrimonio.HojaCargoAnular(:id_comprobante_cargo);END;";
		$parametros = array(array(	'nombre' => 'id_comprobante_cargo',
									'tipo_dato' => PDO::PARAM_INT,
									'longitud' => 32,
									'valor' => $id_comprobante_cargo),
							array(	'nombre' => 'resultado',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 4000,
									'valor' => ''),);
		$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', '', $con_transaccion);
		return $resultado[1]['valor'];
    }

    static public function asignar_cargos ($id_comprobante, $seleccion)
    {
    	$res = '';
    	toba::db()->abrir_transaccion();
        ctr_procedimientos::ejecutar_transaccion_compuesta(null, function () use ($id_comprobante,$seleccion, &$res){
            // Borrar items
            //$res = dao_hoja_cargo::borrar_items_hoja_cargo($id_comprobante);
            // Agregar Items.
            foreach ($seleccion as $item){
            	//Se asegura que no se carga mas de una vez el mismo item.
            	if ( ! dao_hoja_cargo::existe_en_hoja($id_comprobante, $item))
            		$res = dao_hoja_cargo::agregar_item_hoja_cargo($id_comprobante, $item);
            }
            toba::db()->cerrar_transaccion();
        });
        return $res;
    }

    static public function asignar_descargos($id_comprobante, $seleccion)
    {
    	$res = '';
    	toba::db()->abrir_transaccion();
        ctr_procedimientos::ejecutar_transaccion_compuesta(null, function () use ($id_comprobante,$seleccion, &$res){
            // Borrar items
            //$res = dao_hoja_cargo::borrar_items_hoja_cargo($id_comprobante);
            // Agregar Items.
            foreach ($seleccion as $item){
            	//Se asegura que no se carga mas de una vez el mismo item.
            	if ( ! dao_hoja_cargo::existe_en_hoja($id_comprobante, $item))
            		$res = dao_hoja_cargo::agregar_item_hoja_descargo($id_comprobante, $item);
            }
            toba::db()->cerrar_transaccion();
        });
        return $res;
    }

    public static function borrar_items_hoja_cargo ($id_comprobante_cargo){
		$sql = "BEGIN 
					:resultado := pkg_patrimonio.BorrarBienesHojaCargo(:id_comprobante_cargo);
				END;";

		$parametros = [
				[	'nombre' => 'resultado',
					'tipo_dato' => PDO::PARAM_STR,
					'longitud' => 4000,
					'valor' => ''],
				[	'nombre' => 'id_comprobante_cargo',
					'tipo_dato' => PDO::PARAM_INT,
					'longitud' => 32,
					'valor' => $id_comprobante_cargo],
				];
		ctr_procedimientos::ejecutar_procedimiento(null,$sql,$parametros);
    }

    public static function agregar_item_hoja_cargo ($id_comprobante, $id_bien_patrimonial)
    {
		$sql = "BEGIN 
					:resultado := pkg_patrimonio.AgregarBienHojaCargo(:id_comprobante, :id_bien_patrimonial);
				END;";
		$parametros = [
				[	'nombre' => 'resultado',
					'tipo_dato' => PDO::PARAM_STR,
					'longitud' => 4000,
					'valor' => ''],
				[	'nombre' => 'id_comprobante',
					'tipo_dato' => PDO::PARAM_INT,
					'longitud' => 32,
					'valor' => $id_comprobante],
				[	'nombre' => 'id_bien_patrimonial',
					'tipo_dato' => PDO::PARAM_INT,
					'longitud' => 32,
					'valor' => $id_bien_patrimonial],
		];
		ctr_procedimientos::ejecutar_procedimiento(null,$sql,$parametros);
    }

    public static function agregar_item_hoja_descargo ($id_comprobante, $id_bien_patrimonial)
    {
		$sql = "BEGIN 
					:resultado := pkg_patrimonio.AgregarBienHojaDescargo(:id_comprobante, :id_bien_patrimonial);
				END;";
		$parametros = [
				[	'nombre' => 'resultado',
					'tipo_dato' => PDO::PARAM_STR,
					'longitud' => 4000,
					'valor' => ''],
				[	'nombre' => 'id_comprobante',
					'tipo_dato' => PDO::PARAM_INT,
					'longitud' => 32,
					'valor' => $id_comprobante],
				[	'nombre' => 'id_bien_patrimonial',
					'tipo_dato' => PDO::PARAM_INT,
					'longitud' => 32,
					'valor' => $id_bien_patrimonial],
			];
		ctr_procedimientos::ejecutar_procedimiento(null,$sql,$parametros);
    }

    
    /**
	* Para ei_tabulador_cuadro
	*
	*/
	public static function get_bienes_seleccionables_cargo($filtro = [])
    {
        $where = ' 1=1 ';
        if (isset($filtro['descripcion'])) {
           $where .=" and upper(vpo.descripcion) like upper('".$filtro['descripcion']."')";
           unset($filtro['descripcion']);
        }
        
        if (isset($filtro['no_id_comprobante'])) {
           $where .=" and vpo.id_bien_patrimonial not in (
           					SELECT bcc.id_bien_patrimonial
                              FROM pa_bien_comp_cargo bcc, PA_COMPROBANTES_CARGO cc
                             WHERE bcc.id_comprobante_cargo = ".$filtro['no_id_comprobante']." 
                               AND bcc.ID_COMPROBANTE_CARGO = cc.ID_COMPROBANTE_CARGO
                               AND cc.tipo = 'CAR')";
           unset($filtro['no_id_comprobante']);
        }

        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'VPO', '1=1');

    	$sql = "SELECT VPO.* 
    			  FROM V_PA_BIENES_HOJA_CARGO VPO
    			 WHERE VPO.estado = 'ACT' AND
					   VPO.ID_COMPROBANTE_CARGO IS NULL 
					   and $where ";
                   
        return principal_ei_tabulator_consultar::todos_los_datos($sql);
    }
    
    static public function get_bienes_seleccionables_descargo($filtro = [])
    {
    	$where = ' 1=1 ';
       
        if (isset($filtro['descripcion'])) {
           $where .=" and upper(v.descripcion) like upper('".$filtro['descripcion']."')";
           unset($filtro['descripcion']);
        }
        
        if (isset($filtro['id_dep_orig'])) {
           $where .=" and v.id_dep_dest = ".$filtro['id_dep_orig'];
           unset($filtro['id_dep_orig']);
        }
        if (isset($filtro['no_id_comprobante'])) {
           $where .=" and v.id_bien_patrimonial not in (select bcc.id_bien_patrimonial
                             FROM pa_bien_comp_cargo bcc, PA_COMPROBANTES_CARGO cc
                            WHERE bcc.ID_COMPROBANTE_CARGO = cc.ID_COMPROBANTE_CARGO 
                            and bcc.id_comprobante_cargo = ".$filtro['no_id_comprobante']." and cc.tipo = 'DES')";
           unset($filtro['no_id_comprobante']);
        }


        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'v', '1=1');

    	$sql = "SELECT v.* 
    			  FROM V_PA_SELECC_BIENES_DESCARGO v
    			 WHERE $where";

		return principal_ei_tabulator_consultar::todos_los_datos($sql);
					   
    }


}

?>
