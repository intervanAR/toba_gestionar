<?php
class dao_formulaciones_presupuestarias {

	static public function get_formulaciones_presupuestarias ($filtro = array ()){
		$where =' 1=1 ';
		if(isset($filtro))
			$where .= " and ".ctr_construir_sentencias::get_where_filtro($filtro, 'PRFO', '1=1');
		$sql = "SELECT PRFO.*,
				       to_char(PRFO.fecha_aprueba, 'dd/mm/yyyy') fecha_aprueba_format,
				       to_char(PRFO.fecha_anula, 'dd/mm/yyyy') fecha_anula_format,
				       to_char(PRFO.fecha_carga, 'dd/mm/yyyy') fecha_carga_format,
				       KREJ.DESCRIPCION EJERCICIO_DES,
				       KREJ.NRO_EJERCICIO EJERCICIO_NUM
				FROM PR_FORMULACIONES PRFO
				     LEFT JOIN KR_EJERCICIOS KREJ ON PRFO.ID_EJERCICIO = KREJ.ID_EJERCICIO
				WHERE $where
				ORDER BY PRFO.ID_FORMULACION DESC;";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}

	static public function get_formulaciones_presupuestarias_x_id ($id_formulacion){
		$sql = "SELECT PRFO.*,
				       to_char(PRFO.fecha_aprueba, 'dd/mm/yyyy') fecha_aprueba_format,
				       to_char(PRFO.fecha_anula, 'dd/mm/yyyy') fecha_anula_format,
				       to_char(PRFO.fecha_carga, 'dd/mm/yyyy') fecha_carga_format,
				       KREJ.DESCRIPCION EJERCICIO_DES,
				       KREJ.NRO_EJERCICIO EJERCICIO_NUM
				FROM PR_FORMULACIONES PRFO
				     LEFT JOIN KR_EJERCICIOS KREJ ON PRFO.ID_EJERCICIO = KREJ.ID_EJERCICIO
				WHERE PRFO.ID_FORMULACION = ".quote($id_formulacion)."
				ORDER BY PRFO.ID_FORMULACION DESC;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos;
	}

	static public function get_ejercicios_x_id ($id_formulacion){
		$sql = "SELECT PRFO.id_ejercicio as id_ejercicio_nuevo,(SELECT ID_EJERCICIO FROM KR_EJERCICIOS
                       WHERE NRO_EJERCICIO = (SELECT distinct(KREJ.NRO_EJERCICIO - 1)
                       FROM KR_EJERCICIOS KREJ 
                        LEFT JOIN PR_FORMULACIONES PRFO ON PRFO.ID_EJERCICIO = KREJ.ID_EJERCICIO
                        WHERE PRFO.ID_FORMULACION = ".$id_formulacion.")) as id_ejercicio_ant   
                FROM PR_FORMULACIONES PRFO 
                     LEFT JOIN KR_EJERCICIOS KREJ ON PRFO.ID_EJERCICIO = KREJ.ID_EJERCICIO
                WHERE PRFO.ID_FORMULACION = ".$id_formulacion." ";
		$datos = toba::db()->consultar_fila($sql);
		return $datos;
	}
	
	static public function get_lov_formulaciones_presupuestarias_x_id ($id_formulacion){
		$sql = "SELECT PRFO.*, PRFO.ID_FORMULACION || ' - ' || KREJ.DESCRIPCION ||' - ' || PRFO.DESCRIPCION AS LOV_DESCRIPCION
				FROM PR_FORMULACIONES PRFO LEFT JOIN KR_EJERCICIOS KREJ ON PRFO.ID_EJERCICIO = KREJ.ID_EJERCICIO
				WHERE PRFO.ID_FORMULACION = ".quote($id_formulacion)."
				ORDER BY PRFO.ID_FORMULACION DESC;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}

	static public function get_lov_formulaciones_presupuestarias_x_nombre ($nombre, $filtro){
		if (isset($nombre)) {
				$trans_id = ctr_construir_sentencias::construir_translate_ilike('prfo.id_formulacion', $nombre);
				$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('prfo.descripcion', $nombre);
				$where = "($trans_id OR $trans_descripcion)";
			} else {
				$where = '1=1';
			}
		$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'PRFO', '1=1');
		$sql = "SELECT PRFO.*, PRFO.ID_FORMULACION || ' - ' || KREJ.DESCRIPCION ||' - ' || PRFO.DESCRIPCION AS LOV_DESCRIPCION
				FROM PR_FORMULACIONES PRFO LEFT JOIN KR_EJERCICIOS KREJ ON PRFO.ID_EJERCICIO = KREJ.ID_EJERCICIO
				WHERE $where
				ORDER BY PRFO.ID_FORMULACION DESC;";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}

	static public function copiar_formulacion ($id_formulacion, $ui_id_formulacion)
	{
        $sql = "BEGIN
        			:resultado := pkg_pr_formulacion.copiar_formulacion(:ui_id_formulacion, :id_formulacion);
        		END;";
        $parametros = [
        	['nombre' => 'resultado',
             'tipo_dato' => PDO::PARAM_STR,
             'longitud' => 4000,
             'valor' => ''],
        	['nombre' => 'ui_id_formulacion',
             'tipo_dato' => PDO::PARAM_INT,
             'longitud' => 32,
             'valor' => $ui_id_formulacion],
			['nombre' => 'id_formulacion',
             'tipo_dato' => PDO::PARAM_INT,
             'longitud' => 32,
             'valor' => $id_formulacion],
        ];
        ctr_procedimientos::ejecutar_procedimiento(
			null,
			$sql,
			$parametros
		);
	}
	static public function aprobar_formulacion_presupuestaria($id_formulacion, $fecha_fin_reconducido = null)
	{
        if (!is_null($fecha_fin_reconducido))
        {
          	/* Actualiza campo fecha_fin_reconducido del ejercicio antes de aprobar
        	 * El proceso que aprueba la formulacion, toma esa fecha.
         	 */
         	$sql = "UPDATE kr_ejercicios set fecha_fin_reconducido = to_date('$fecha_fin_reconducido','yyyy-mm-dd') WHERE id_ejercicio in (select id_ejercicio from pr_formulaciones where id_formulacion = $id_formulacion)";
        	toba::db()->abrir_transaccion();
        	$res = toba::db()->ejecutar($sql);
        	if (!$res){
        		toba::db()->abortar_transaccion();
        		throw new toba_error('No se pude actualizar la Fecha de Finalizacion.');
        	}
        }

        //--- Aprueba Formulacion ----
        $sql = "BEGIN
        			:resultado := pkg_pr_formulacion.aprobar_formulacion(:id_formulacion);
        		END;";

        $parametros =
        [
		   ['nombre' => 'resultado',
            'tipo_dato' => PDO::PARAM_STR,
            'longitud' => 4000,
            'valor' => ''],
		   ['nombre' => 'id_formulacion',
            'tipo_dato' => PDO::PARAM_INT,
            'longitud' => 32,
            'valor' => $id_formulacion],
        ];
        ctr_procedimientos::ejecutar_procedimiento(
			null,
			$sql,
			$parametros
		);
		if (toba::db()->transaccion_abierta())
			toba::db()->cerrar_transaccion();
    }

	static public function chequear_balanceo ($id_formulacion)
	{
        $sql = "BEGIN
        			:resultado := pkg_pr_formulacion.chequear_balanceo(:id_formulacion);
        		END;";
        $parametros = [
        	['nombre' => 'resultado',
             'tipo_dato' => PDO::PARAM_STR,
             'longitud' => 32767,
             'valor' => ''],
        	['nombre' => 'id_formulacion',
             'tipo_dato' => PDO::PARAM_INT,
             'longitud' => 32,
             'valor' => $id_formulacion],
        ];

        ctr_procedimientos::ejecutar_procedimiento(
			'La formulación no se encuentra balanceada',
			$sql,
			$parametros
		);
		toba::notificacion()->info('Formulación balanceada.');
    }

	static public function anular_formulacion_presupuestaria ($id_formulacion) {
	    $sql = "BEGIN
	    			:resultado := pkg_pr_formulacion.anular_formulacion(:id_formulacion);
	    		END;";
	    $parametros = [
	    	['nombre' => 'resultado',
	         'tipo_dato' => PDO::PARAM_STR,
	         'longitud' => 4000,
	         'valor' => ''],
	    	['nombre' => 'id_formulacion',
	         'tipo_dato' => PDO::PARAM_INT,
	         'longitud' => 32,
	         'valor' => $id_formulacion],
	    ];
        ctr_procedimientos::ejecutar_procedimiento(
			null,
			$sql,
			$parametros
		);
    }

	static public function get_lov_entidades_x_nombre ($nombre, $filtro = []){
		if (isset($nombre)) {
			$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('cod_entidad', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
			$where = "($trans_codigo OR $trans_descripcion)";
		} else {
			$where = '1=1';
		}
		if (isset($filtro['cod_unidad_administracion']) && isset($filtro['id_ejercicio'])){
			$where .= " and ((Pkg_Pr_Entidades.Usuario_Pertenece_Ua (" . quote(toba::usuario()->get_id()) . ", PREN.ID_ENTIDAD) = 'S') Or ((Pkg_Pr_Entidades.Usuario_Pertenece_Ua (" . quote(toba::usuario()->get_id()) . ", PREN.ID_ENTIDAD) = 'N')
				        And Not Exists (Select 1
				                        From Pr_Entidades
				                        Where (Pkg_Pr_Entidades.Usuario_Pertenece_Ua (" . quote(toba::usuario()->get_id()) . ", Id_Entidad) = 'S'))))
				        AND
				      (Pkg_kr_Ejercicios.Pertenece_Entidad_A_Estruc (".$filtro['cod_unidad_administracion'].", ".$filtro['id_ejercicio'].",Pren.Id_Entidad ) = 'S'
				      And pkg_pr_entidades.IMPUTABLE(Pren.Id_entidad) = 'S')";
			unset($filtro['cod_unidad_administracion']);
			unset($filtro['id_ejercicio']);
		}
		$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'PREN', '1=1');
		$sql = "SELECT  PREN.id_entidad, PREN.COD_ENTIDAD || ' - ' || PREN.DESCRIPCION lov_descripcion
				FROM PR_ENTIDADES PREN
				WHERE $where
				ORDER BY lov_descripcion ASC";
		toba::logger()->debug('valor sql entidades '. $sql);
		$datos = toba::db()->consultar($sql);
		return $datos;
	}

	static public function get_unidades_administracion_x_id_formulacion ($id_formulacion){
		$sql = "SELECT prfua.*, krua.cod_unidad_administracion ||' - '||krua.DESCRIPCION cod_unidad_administracion_lv
				,(SELECT NVL (SUM (importe), 0) importe
		          FROM pr_formulaciones_recursos
		         WHERE cod_unidad_administracion =
		                               prfua.cod_unidad_administracion
		           AND id_formulacion = prfua.id_formulacion) ui_importe_rec,
		       (SELECT NVL (SUM (importe), 0) importe
		          FROM pr_formulaciones_partidas
		         WHERE id_formulacion = prfua.id_formulacion
		           AND cod_unidad_administracion = prfua.cod_unidad_administracion)
		                                                               ui_importe_pro
				  FROM pr_formulaciones_ua prfua, kr_unidades_administracion krua
				 WHERE prfua.cod_unidad_administracion = krua.cod_unidad_administracion and prfua.ID_FORMULACION = ".$id_formulacion;
		return principal_ei_tabulator_consultar::todos_los_datos($sql);
	}

	static public function get_entidades_x_formulacion_ua ($id_formulacion, $cod_unidad_administracion){
		$sql = "SELECT prfe.*, pkg_pr_entidades.MASCARA_APLICAR(prent.cod_entidad) ||' - '|| prent.DESCRIPCION id_entidad_lov_desc
		, (SELECT NVL (SUM (importe), 0) importe
          FROM pr_formulaciones_recursos
         WHERE id_formulacion = prfe.id_formulacion
           AND cod_unidad_administracion = prfe.cod_unidad_administracion
           AND id_detalle_entidad = prfe.id_detalle_entidad) ui_importe_rec,
       (SELECT NVL (SUM (importe), 0) importe
          FROM pr_formulaciones_partidas
         WHERE id_formulacion = prfe.id_formulacion
           AND cod_unidad_administracion = prfe.cod_unidad_administracion
           AND id_detalle_entidad = prfe.id_detalle_entidad) ui_importe_pro
				  FROM pr_formulaciones_entidades prfe, pr_entidades prent
				 WHERE prfe.id_entidad = prent.id_entidad and prfe.ID_FORMULACION = ".$id_formulacion."
				    AND prfe.COD_UNIDAD_ADMINISTRACION = ".$cod_unidad_administracion;
		return principal_ei_tabulator_consultar::todos_los_datos($sql);
	}
	/**
	*
	* @param array clave (id_formulacion, 'cod_unidad_administracion', 'id_detalle_entidad')
	* @return array
	**/
	static public function get_formulacion_entidad ($clave = []){
		$where = ctr_construir_sentencias::get_where_filtro($clave,'prfen', '1=1');
		$sql = "SELECT prfen.*
		          FROM pr_formulaciones_entidades prfen
				 WHERE $where ";
		return toba::db()->consultar_fila($sql);
	}

	static public function get_formulaciones_recursos ($filtro = []){
		$where = " 1=1 ";
		$where .=" and ".ctr_construir_sentencias::get_where_filtro($filtro, 'prfr', '1=1');
		$sql = "SELECT prfr.*, pkg_pr_recursos.MASCARA_APLICAR(prrec.cod_recurso) ||' - '|| prrec.descripcion cod_recurso_lov_desc
				  FROM pr_formulaciones_recursos prfr, pr_recursos prrec
				 WHERE prfr.cod_recurso = prrec.cod_recurso and  $where ";
		return principal_ei_tabulator_consultar::todos_los_datos($sql);
	}

	static public function get_formulaciones_programas ($filtro = []){
		$where = " 1=1 ";
		$where .=" and ".ctr_construir_sentencias::get_where_filtro($filtro, 'prfp', '1=1');
		$sql = "SELECT prfp.*,
				       pkg_pr_programas.mascara_aplicar(prpr.cod_programa) || ' - ' || prpr.descripcion id_programa_lov_desc,
				       (SELECT NVL (SUM (importe), 0) importe
				          FROM pr_formulaciones_partidas
				         WHERE id_formulacion = prfp.id_formulacion
				           AND cod_unidad_administracion = prfp.cod_unidad_administracion
				           AND id_detalle_entidad = prfp.id_detalle_entidad
				           AND id_detalle_programa = prfp.id_detalle_programa) ui_importe_pro
				  FROM pr_formulaciones_programas prfp, pr_programas prpr
				 WHERE prfp.id_programa = prpr.id_programa and $where ";
		return principal_ei_tabulator_consultar::todos_los_datos($sql);
	}

	static public function get_formulaciones_partidas ($filtro = []){
		$where = " 1=1 ";
		$where.=" and ".ctr_construir_sentencias::get_where_filtro($filtro, 'prfpa', '1=1');
		$sql = "SELECT prfpa.*,
				       prpa.cod_partida || ' - ' || prpa.descripcion cod_partida_lov_desc,
				          prfue.cod_fuente_financiera
				       || ' - '
				       || prfue.descripcion cod_fuente_financiera_lov_desc,
				       (SELECT cod_recurso || ' - ' || descripcion
				          FROM pr_recursos
				         WHERE cod_recurso = prfpa.cod_recurso) cod_recurso_lov_desc
				  FROM pr_formulaciones_partidas prfpa,
				       pr_partidas prpa,
				       pr_fuentes_financieras prfue
				 WHERE prfpa.cod_partida = prpa.cod_partida
				   AND prfpa.cod_fuente_financiera = prfue.cod_fuente_financiera and $where ";
		return principal_ei_tabulator_consultar::todos_los_datos($sql);
	}



	//------------------------------------------------------------------------------------
	//----------- UI_ITEMS ---------------------------------------------------------------
	//------------------------------------------------------------------------------------
	static public function get_tiene_afectacion_especifica ($cod_fuente_financiera){
		$sql = "SELECT pkg_pr_fuentes.afectacion_especifica(".$cod_fuente_financiera.") AS ui_afectacion_especifica
				FROM dual;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['ui_afectacion_especifica'];
	}
	//------------------------------------------------------------------------------------------------------------------
	//------------    Para formulario_ml_ua   -------------------------------------------------------------------
	//------------------------------------------------------------------------------------------------------------------
	static public function calcular_imoporte_unidad_programa ($id_formulacion, $cod_unidad_administracion){
		$sql = "SELECT NVL(SUM(IMPORTE),0) importe
				FROM PR_FORMULACIONES_PARTIDAS
				WHERE ID_FORMULACION = ".quote($id_formulacion)." AND COD_UNIDAD_ADMINISTRACION = ".$cod_unidad_administracion.";";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['importe'];
	}

	static public function calcular_imoporte_unidad_recurso ($id_formulacion, $cod_unidad_administracion){
		$sql = "SELECT  NVL(SUM(importe),0)  importe
				FROM    pr_formulaciones_recursos
				WHERE   id_formulacion = ".quote($id_formulacion)."
					    AND cod_unidad_administracion = ".$cod_unidad_administracion.";";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['importe'];
	}

	//------------------------------------------------------------------------------------------------------------------
	//------------    Para formulario_ml_entidades   -------------------------------------------------------------------
	//------------------------------------------------------------------------------------------------------------------

	static public function calcular_imoporte_programa ($id_formulacion, $cod_unidad_administracion, $id_detalle_entidad){
		$sql = "SELECT  NVL(SUM(importe),0) importe
				FROM    pr_formulaciones_partidas
				WHERE   id_formulacion = ".quote($id_formulacion)."
				        and cod_unidad_administracion = ".$cod_unidad_administracion."
				        and id_detalle_entidad = ".quote($id_detalle_entidad).";";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['importe'];
	}

	static public function calcular_imoporte_recurso ($id_formulacion, $cod_unidad_administracion, $id_detalle_entidad){
		$sql = "select  nvl(sum(importe),0) importe
				from    pr_formulaciones_recursos
				where   id_formulacion = ".quote($id_formulacion)."
				    and cod_unidad_administracion = ".$cod_unidad_administracion."
				    and id_detalle_entidad = ".quote($id_detalle_entidad).";";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['importe'];
	}

	//------------------------------------------------------------------------------------------------------------------
	//------------    Para formulario_ml_programas   -------------------------------------------------------------------
	//------------------------------------------------------------------------------------------------------------------

	static public function calcular_imoporte_programa_prog ($id_formulacion, $cod_unidad_administracion, $id_detalle_entidad, $id_detalle_programa){
		$sql = "select  nvl(sum(importe),0) importe
			    from    pr_formulaciones_partidas
			    where   id_formulacion = ".quote($id_formulacion)."
			            and cod_unidad_administracion = ".$cod_unidad_administracion."
			            and id_detalle_entidad = ".quote($id_detalle_entidad)."
			            and id_detalle_programa = ".quote($id_detalle_programa).";";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['importe'];
	}

	//------------------------------------------------------------------------------------------------------------------
	//------------    UI_ITEMS   ---------------------------------------------------------------------------------------
	//------------------------------------------------------------------------------------------------------------------

	static public function get_ui_ejercicio_formulacion ($id_ejercicio){
		//Devuelve 'S' si el ejercicio tiene formulacion y 'N' en caso contrario
		$sql = "SELECT pkg_pr_formulacion.existe_formu_ejer_aprobada(".quote($id_ejercicio).") ui_ejercicio_formulacion
				FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['ui_ejercicio_formulacion'];
	}


	static  public function get_ui_ejercicio_x_formulacion ($id_formulacion){
		$sql = "SELECT krej.id_ejercicio
				FROM pr_formulaciones prfo LEFT JOIN kr_ejercicios krej
         				ON prfo.id_ejercicio = krej.id_ejercicio
				WHERE prfo.ID_FORMULACION = ".quote($id_formulacion)."";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['id_ejercicio'];
	}

	
	   public static function modificaciones_ejercicio($id_ejercicio_ant,$id_ejercicio_nuevo){

        //toba::db()->abrir_transaccion();
        
        $mensaje_error = 'Error al aplicar modificaciones';
		

		  $sql = ("BEGIN :resultado:= pkg_pr_formulacion.modif_presp_reconducido($id_ejercicio_ant,$id_ejercicio_nuevo, '02/12/2020'); END;");

	//	print_r($sql);

		$parametros =
			[
               ['nombre' => 'resultado',
                     'tipo_dato' => PDO::PARAM_STR,
                     'longitud' => 4000,
                     'valor' => ''],
                ];
        
       $respuesta = ctr_procedimientos::ejecutar_procedimiento(
			$mensaje_error,
			$sql,
			$parametros
		);

		$rta= $respuesta[0]['valor'];

		//print('RTA - '.$rta);

		// $datos = toba::db()->consultar($sql);

        //toba::db()->cerrar_transaccion();

    }
	
}
?>