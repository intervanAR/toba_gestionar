<?php

class dao_partidas_presupuestarias
{

	static public function get_datos($filtro=array())
    {
    	$where = " 1=1 ";
    	if (isset($filtro['id_padre'])){
    		$where .= " and prp.cod_partida_padre = ".$filtro['id_padre'];
    		unset($filtro['id_padre']);
    	}

	    $where .= " and ".ctr_construir_sentencias::get_where_filtro($filtro, 'prp', '1=1');
	    $sql = "SELECT prp.*, pkg_pr_partidas.activa (prp.cod_partida) ui_activa,
				       pkg_pr_partidas.FIGURATIVA(prp.cod_partida) ui_figurativa,
				       pkg_pr_partidas.imputable (prp.cod_partida) ui_imputable,
				       pkg_pr_partidas.sin_control(prp.cod_partida) ui_sin_control,
				       pkg_pr_partidas.para_reimputar(prp.cod_partida) ui_reimputacion,
				       pkg_pr_partidas.rrhh (prp.cod_partida) ui_rrhh,
				       pkg_pr_partidas.mascara_aplicar (prp.cod_partida) cod_masc,
				          '['
				       || pkg_pr_partidas.mascara_aplicar (prp.cod_partida)
				       || '] '
				       || prp.descripcion AS descripcion_2
		    FROM pr_partidas PRP
		    WHERE $where
		    ORDER BY prp.cod_partida ASC;";
	    $datos = toba::db()->consultar($sql);
	    return $datos;
	}

	static public function get_partidas_presupuestarias($filtro=array())
    {
    	$where = " 1=1 ";
    	if (isset($filtro['sin_padre'])){
    		$where .= " and prp.cod_partida_padre is null ";
    		unset($filtro['sin_padre']);
    	}

	    $where .= " and ".ctr_construir_sentencias::get_where_filtro($filtro, 'prp', '1=1');
	    $sql = "SELECT PRP.*, '['|| PKG_PR_PARTIDAS.MASCARA_APLICAR (PRP.COD_PARTIDA) || '] '|| PRP.DESCRIPCION AS DESCRIPCION_2
		    FROM pr_partidas PRP
		    WHERE $where
		    ORDER BY prp.cod_partida ASC;";
	    $datos = toba::db()->consultar($sql);
	    return $datos;
	}

	static public function get_partidas_presupuestarias_x_nombre($nombre, $filtro = array()) {
	    if (isset($nombre)) {
			$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('cod_partida', $nombre);
			$trans_codigo_masc = ctr_construir_sentencias::construir_translate_ilike('pkg_pr_partidas.mascara_aplicar(pp.cod_partida)', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('pkg_pr_partidas.cargar_descripcion(pp.cod_partida)', $nombre);
			$where = "($trans_codigo OR $trans_codigo_masc OR $trans_descripcion)";
	    } else {
			$where = '1=1';
	    }
	    if (isset($filtro['cod_tipo_comprobante'])) {
			$where .= " AND kcc.tipo_cuenta_corriente = (SELECT atcg.tipo_cuenta_corriente FROM ad_tipos_comprobante_gasto atcg WHERE atcg.cod_tipo_comprobante = ". quote($filtro['cod_tipo_comprobante']) . ") ";
			unset($filtro['cod_tipo_comprobante']);
	    }

		if (isset($filtro['partida_activa'])) {
			$where .= " AND PKG_PR_PARTIDAS.ACTIVA (PP.COD_PARTIDA) = 'S' ";
			unset($filtro['partida_activa']);
		}

		if (isset($filtro['imputable'])) {
			$where .= " AND PKG_PR_PARTIDAS.IMPUTABLE (PP.COD_PARTIDA) = 'S' ";
			unset($filtro['imputable']);
		}

		if (isset($filtro['partidas_compra']) && isset($filtro['gasto_propio'])) {
			$where .= " AND ((Pkg_Pr_Partidas.Imputable (Cod_Partida) = 'S' and  ". quote($filtro['gasto_propio']) . " = 'S')
						Or (Pkg_Pr_Partidas.Imputable (Cod_Partida) = 'S' And  ". quote($filtro['gasto_propio']) . " = 'N' And
							substr(Cod_Partida,0,1)  = Substr(To_Number(Pkg_General.Valor_Parametro ('PARTIDA_GASTO_TERCEROS')),0,1))) ";
			unset($filtro['partidas_compra']);
			unset($filtro['gasto_propio']);
		}

	    $where .= 'AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'pp', '1=1');
	    $sql = "SELECT  pp.*,
						pkg_pr_partidas.mascara_aplicar(pp.cod_partida) ||' - '|| pkg_pr_partidas.cargar_descripcion(pp.cod_partida) as cod_des_partida
				FROM pr_partidas pp
				WHERE $where
				ORDER BY cod_des_partida ASC;";
	    $datos = toba::db()->consultar($sql);
	    return $datos;
	}

    static public function get_lov_partida_x_cod_partida($cod_partida) {

        if (isset($cod_partida)) {
            $sql = "SELECT pp.*,pp.cod_partida||' - '||pp.descripcion lov_descripcion
                    FROM PR_PARTIDAS pp
                    WHERE cod_partida = " . quote($cod_partida) . ";";

            $datos = toba::db()->consultar_fila($sql);

            if (isset($datos) && !empty($datos) && isset($datos['lov_descripcion'])) {
                return $datos['lov_descripcion'];
            } else {
                return '';
            }
        } else {
            return '';
        }
    }

	static public function get_lov_niveles_partidas_x_id($nivel) {
    	$sql = "SELECT prnipa.nivel
    			|| ' - '|| prnipa.descripcion
    			|| ' ['|| SUBSTR (pkg_pr_partidas.mascara_nivel (prnipa.nivel), 1, 30)|| ']' lov_descripcion
			   FROM pr_niveles_partida prnipa
			   WHERE PRNIPA.NIVEL = $nivel";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
    }

    static public function get_lov_niveles_partidas_x_nombre ($nombre , $filtro = array()){
       if (isset($nombre)) {
			$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('prnipa.nivel', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('prnipa.descripcion', $nombre);
			$where = "($trans_codigo OR $trans_descripcion)";
	    } else {
			$where = '1=1';
	    }

	    if (!empty($filtro))
	    	$where .= 'AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'prnipa', '1=1');

	    $sql = "SELECT prnipa.*, prnipa.nivel
		        || ' - '|| prnipa.descripcion
			    || ' [' || SUBSTR (pkg_pr_partidas.mascara_nivel (prnipa.nivel), 1, 30)|| ']' lov_descripcion
			   FROM pr_niveles_partida prnipa
			   WHERE $where
			   ORDER BY lov_descripcion  ASC;";
	    $datos = toba::db()->consultar($sql);
	    return $datos;
    }

	static public function get_descripcion_partida_x_cod_partida($cod_partida)
    	{
	    if (isset($cod_partida)) {
		$sql = "SELECT pkg_pr_partidas.mascara_aplicar(pp.cod_partida) ||' - '|| pkg_pr_partidas.cargar_descripcion(pp.cod_partida) as cod_des_partida
		FROM pr_partidas pp
		WHERE cod_partida = ".quote($cod_partida) .";";
		$datos = toba::db()->consultar_fila($sql);
		if (isset($datos) && !empty($datos) && isset($datos['cod_des_partida'])) {
		    return $datos['cod_des_partida'];
		} else {
		    return '';
		}
	    } else {
		return '';
	    }
	}

    static public function get_lov_partidas_x_nombre($nombre, $filtro = array()) {
        if (isset($nombre)) {
            $trans_codigo = ctr_construir_sentencias::construir_translate_ilike('cod_partida', $nombre);
            $trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
            $where = "($trans_codigo OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }



        if (isset($filtro['imputable']) && (!empty($filtro['imputable']))) {
            $where.= " AND pkg_pr_partidas.imputable(pp.cod_partida) =  '" . $filtro['imputable']."'";
            unset($filtro['imputable']);
        }

        if (isset($filtro['activa']) && (!empty($filtro['activa']))) {
            $where.= " AND PKG_PR_PARTIDAS.ACTIVA(COD_PARTIDA) = '" . $filtro['activa']."'";
            unset($filtro['activa']);
        }

        if (isset($filtro['ui_sin_control_pres']) && ($filtro['ui_sin_control_pres'] = 'N')) {
            $where .= " and exists(select 1 from pr_movimientos_egresos where cod_unidad_administracion = " . $filtro['cod_unidad_administracion'] . "
                        and id_ejercicio = " . $filtro['ui_id_ejercicio'] . " and cod_partida = pp.cod_partida)";

            unset($filtro['cod_unidad_administracion']);
            unset($filtro['ui_sin_control_pres']);
            unset($filtro['ui_id_ejercicio']);
        }

        if (isset($filtro['ui_sin_control_pres_devengado'])){
            if (empty($filtro['id_compromiso']))
                $filtro['id_compromiso'] = 'NULL';
            $where .= " and ('".$filtro['ui_sin_control_pres_devengado']."' = 'S' OR  ('".$filtro['ui_sin_control_pres_devengado']."' = 'N' and ((".$filtro['id_compromiso']." is null                                                     or (".$filtro['id_compromiso']." is not null
                                                     and exists(select 1
                                                                from ad_compromisos adco, ad_compromisos_det adcode
                                                                where (adco.id_compromiso = ".$filtro['id_compromiso']." OR adco.id_compromiso_aju = ".$filtro['id_compromiso'].")
                                                                        and adco.aprobado = 'S' and adco.anulado = 'N'
                                                                        and adco.id_compromiso = adcode.id_compromiso
                                                                        and adcode.cod_partida = pp.cod_partida))))))";
            unset($filtro['ui_sin_control_pres_devengado']);
            unset($filtro['id_compromiso']);
        }
        if (isset($filtro['id_preventivo']) && !empty($filtro['id_preventivo'])) {
            $where.= " and exists(select 1 from ad_preventivos adpr, ad_preventivos_det adprde
                    where (adpr.id_preventivo =" . $filtro['id_preventivo'] . " OR adpr.id_preventivo_aju =" . $filtro['id_preventivo'] . ")
                                    AND adpr.ANULADO = 'N' AND adpr.APROBADO = 'S' AND adpr.id_preventivo = adprde.id_preventivo
                                    AND adprde.cod_partida = pp.cod_partida)";

            unset($filtro['id_preventivo']);
        }
        $where .= 'AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'pp', '1=1');

        $sql = "SELECT  pp.cod_partida,
                        pp.cod_partida || ' - ' || pp.descripcion as lov_descripcion
                FROM pr_partidas pp
                WHERE $where
                ORDER BY lov_descripcion ASC;";
        $datos = toba::db()->consultar($sql);
        return $datos;
    }

    static public function es_imputable($cod_partida){
    	if (!is_null($cod_partida)){
    		$sql = "SELECT PKG_PR_PARTIDAS.IMPUTABLE($cod_partida) IMPUTABLE FROM DUAL;";
    		$datos = toba::db()->consultar_fila($sql);
    		if ($datos['imputable'] == 'S')
    			return true;
    	}
    	return false;
    }


    /*
     *
     *  ------------------------------
     */
  	static public function cant_niveles (){
	   $sql ="SELECT PKG_PR_PARTIDAS.cant_niveles NIVELES FROM DUAL;";
	   $datos = toba::db()->consultar_fila($sql);
	   return $datos['niveles'];
   	}
	static public function cant_digitos_niv($nivel){
	   	$sql ="SELECT PKG_PR_PARTIDAS.cant_digitos_niv($nivel) AS digitos FROM DUAL;";
	   	$datos = toba::db()->consultar_fila($sql);
	   	return $datos['digitos'];

   }
    static public function rrhh ($cod_partida){
    	if (is_null($cod_partida)){
			$cod_partida = 'NULL';
		}
   		$sql ="SELECT PKG_PR_PARTIDAS.RRHH($cod_partida) AS rrhh FROM DUAL;";
	   	$datos = toba::db()->consultar_fila($sql);
	   	return $datos['rrhh'];
   }
    static public function cargar_codigo_programa($id_partida){
	   	$sql ="SELECT PKG_PR_PARTIDAS.CARGAR_CODIGO($id_partida) AS codigo FROM DUAL;";
	   	$datos = toba::db()->consultar_fila($sql);
	   	return $datos['codigo'];
   }
    static public function cargar_codigo_unidad_ejecutora ($id_partida){
   $sql ="SELECT PKG_PR_PARTIDAS.COD_EJECUTORA($id_partida) codigo from dual;";
   $datos = toba::db()->consultar_fila($sql);
   return $datos['codigo'];
   }
	static public function cargar_codigo_proyecto ($id_partida){
	   $sql ="SELECT PKG_PR_PARTIDAS.COD_PROYECTO($id_partida) codigo from dual;";
	   $datos = toba::db()->consultar_fila($sql);
	   return $datos['codigo'];
    }
	static public function cargar_codigo_funcion ($id_partida){
   		$sql ="SELECT PKG_PR_PARTIDAS.COD_FUNCION($id_partida) codigo from dual;";
   		$datos = toba::db()->consultar_fila($sql);
   		return $datos['codigo'];
    }
	static public function cargar_descripcion ($id_partida){
		if ($id_partida != null){
	   		$sql ="SELECT PKG_PR_PARTIDAS.CARGAR_DESCRIPCION($id_partida) descripcion from dual;";
	   		$datos = toba::db()->consultar_fila($sql);
	   		return $datos['descripcion'];
		}else return null;
    }
    static public function ultimo_del_nivel ($id_partida_padre, $id_entidad){
    	if ($id_partida_padre == null)
    		$id_partida_padre = 'null';
    	if ($id_entidad == null)
    		$id_entidad = 'null';
    	$sql = "SELECT pkg_pr_partidas.ultimo_del_nivel ($id_partida_padre, $id_entidad) valor from dual;";
	   	$datos = toba::db()->consultar_fila($sql);
	   	return $datos['valor'];

    }
	static public function ultimo_del_nivel_especifico ($id_partida_padre, $id_entidad, $nivel){
    	if ($id_partida_padre == null)
    		$id_partida_padre = 'null';
    	if ($id_entidad == null)
    		$id_entidad = 'null';
    	if ($nivel == null)
    		$nivel ='null';
    	$sql = "SELECT pkg_pr_partidas.ultimo_del_nivel_especifico($id_partida_padre, $id_entidad, $nivel) valor from dual;";
	   	$datos = toba::db()->consultar_fila($sql);
	   	return $datos['valor'];

    }
    static public function valor_del_nivel ($cod_programa, $nivel){
    	if ($cod_programa == null)
    		$cod_programa = 'null';
    	if ($nivel == null)
    		$nivel = 'null';
    	if (isset($nivel)  && !empty($nivel)){
	    	$sql ="SELECT pkg_pr_partidas.valor_del_nivel($cod_programa, $nivel) valor from dual;";
	   		$datos = toba::db()->consultar_fila($sql);
	   		return $datos['valor'];
    	}else return null;
    }


	static public function tiene_hijos ($cod_partida){
		if (isset($cod_partida)){
			$sql = "select pkg_pr_partidas.tiene_hijos($cod_partida) as resultado from dual;";
			$datos = toba::db()->consultar_fila($sql);
			if ($datos['resultado'] === 'S')
				return true;
			elseif($datos['resultado'] === 'N')
				return false;
			else return null;
		}
	}
	static public function activo ($cod_partida){
		if (isset($cod_partida) && $cod_partida != null)
			$sql = "select pkg_pr_partidas.activa($cod_partida) as activa from dual;";
		else
			$sql = "select pkg_pr_partidas.activa(NULL) as activa from dual;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['activa'];
	}

	static public function get_hijos($cod_partida){
		$sql = "SELECT   prp.*,
			            '['
			         || pkg_pr_partidas.mascara_aplicar (prp.cod_partida)
			         || '] '
			         || prp.descripcion AS descripcion_2
			    FROM pr_partidas prp
			   WHERE prp.cod_partida_padre = $cod_partida
			ORDER BY prp.cod_partida ASC;";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}

	static public function es_hoja ($cod_partida){
		$sql = "SELECT PKG_PR_PARTIDAS.ES_HOJA($cod_partida) AS ES_HOJA FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['es_hoja'];
	}

	static public function get_nivel ($cod_partida){
	     $sql = "SELECT NIVEL
	             FROM PR_PARTIDAS
	             WHERE cod_partida = $cod_partida;";
	     $datos = toba::db()->consultar_fila($sql);
	     return $datos['nivel'];
    }

	static public function mascara_aplicar ($cod_partida){
		$sql = "SELECT PKG_PR_PARTIDAS.mascara_aplicar($cod_partida) cod_partida FROM DUAL";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cod_partida'];
	}

	static public function figurativa ($cod_partida){
		if (is_null($cod_partida)){
			$cod_partida = 'NULL';
		}
		$sql = "SELECT PKG_PR_PARTIDAS.figurativa($cod_partida) figurativa FROM DUAL";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['figurativa'];
	}

	static public function para_reimputar ($cod_partida){
		try{
            $sql = "BEGIN :resultado := PKG_PR_PARTIDAS.para_reimputar(:cod_partida); END;";
            $parametros = array ( array(  'nombre' => 'cod_partida',
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 20,
                                          'valor' => $cod_partida),
                                  array(  'nombre' => 'resultado',
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 4000,
                                          'valor' => ''),
                            );
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
            return $resultado[1]['valor'];
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
        } catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
        }

	}

	static public function sin_control ($cod_partida){
		if (is_null($cod_partida)){
			$cod_partida = 'NULL';
		}
		$sql = "SELECT PKG_PR_PARTIDAS.sin_control($cod_partida) sin_control FROM DUAL";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['sin_control'];
	}
	static public function control_nivel ($cod_partida){
		if (is_null($cod_partida)){
			$cod_partida = 'NULL';
		}
		$sql = "SELECT PKG_PR_PARTIDAS.control_nivel($cod_partida) control_nivel FROM DUAL";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['control_nivel'];
	}
	static public function cod_grupo_cf ($cod_partida){
		$sql = "SELECT PKG_PR_PARTIDAS.cod_grupo_cf($cod_partida) cod_grupo FROM DUAL";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cod_grupo'];
	}

	static public function existe($cod_partida){
		$sql = "SELECT NVL (MIN (1), 0) cant
        		FROM PR_PARTIDAS
       			WHERE COD_PARTIDA = $cod_partida";
		$datos = toba::db()->consultar_fila($sql);
		if ($datos['cant'] > 0)
			return true;
		else
			return false;
	}

	static public function armar_codigo ($nivel, $cod_partida, $cod_partida_padre){
		if ($cod_partida_padre == null)
			$cod_partida_padre = 'NULL';
		$sql ="SELECT  PKG_PR_PARTIDAS.armar_codigo($nivel, $cod_partida, $cod_partida_padre) AS valor FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['valor'];
	}

	static public function eliminar($cod_partida)
	{
        $sql = "BEGIN
         			:resultado := PKG_PR_PARTIDAS.eliminar_partida(:cod_partida);
          		END;";
        $parametros = [
    		[ 'nombre' => 'resultado',
              'tipo_dato' => PDO::PARAM_STR,
              'longitud' => 4000,
              'valor' => ''],
    		[ 'nombre' => 'cod_partida',
              'tipo_dato' => PDO::PARAM_STR,
              'longitud' => 20,
              'valor' => $cod_partida],
        ];
        return ctr_procedimientos::ejecutar_procedimiento(null,$sql,$parametros);
	}

	static public function crear_partida($codigo, $descripcion,$nivel,$cod_partida_padre,$cod_grupo_cf,$figurativa,
										 $sin_control,$activa,$reimputacion,$nivel_control,$rrhh,$con_transaccion = true)
	{
		try{
        $sql = "BEGIN :resultado := PKG_PR_PARTIDAS.CREAR_PARTIDA(:codigo, :descripcion, :nivel, :cod_padre, :cod_grupo_cf, :figurativa, :sin_control, :activa, :reimputacion, :nivel_control, :rrhh);END;";

		if (is_null($cod_grupo_cf))
			$cod_grupo_cf = '';

	    $parametros = array (array(  'nombre' => 'codigo',
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 9,
                                     'valor' => $codigo),
       						 array(  'nombre' => 'descripcion',
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 100,
                                     'valor' => $descripcion),
      						 array(  'nombre' => 'nivel',
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 3,
                                     'valor' => $nivel),
      						 array(  'nombre' => 'cod_padre',
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 9,
                                     'valor' => $cod_partida_padre),
      						 array(  'nombre' => 'cod_grupo_cf',
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 11,
                                     'valor' => $cod_grupo_cf),
      						 array(  'nombre' => 'figurativa',
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 11,
                                     'valor' => $figurativa),
      						 array(  'nombre' => 'sin_control',
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => $sin_control),
      						 array(  'nombre' => 'activa',
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => $activa),
      						 array(  'nombre' => 'reimputacion',
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => $reimputacion) ,
      						 array(  'nombre' => 'nivel_control',
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 11,
                                     'valor' => $nivel_control),
      						 array(  'nombre' => 'rrhh',
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => $rrhh),
      						 array(  'nombre' => 'resultado',
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 400,
                                     'valor' => ''));

      	if ($con_transaccion)
      		toba::db()->abrir_transaccion();

        $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
        if ($con_transaccion){
        	if ($resultado[11]['valor'] != 'OK'){
        		toba::db()->abortar_transaccion();
        		toba::notificacion()->error($resultado[11]['valor']);
        	}else{
        		toba::db()->cerrar_transaccion();
        	}
        }

        return $resultado[11]['valor'];

        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            if ($con_transaccion)
            	toba::db()->abortar_transaccion();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
            if ($con_transaccion)
            	toba::db()->abortar_transaccion();
        }
   }



   static public function get_lov_grupo_cf_x_id ($cod_grupo){
   		$sql = "SELECT GCF.COD_GRUPO_CF ||' - '|| GCF.DESCRIPCION AS LOV_DESCRIPCION
				  FROM PR_GRUPOS_CF GCF
				 WHERE GCF.COD_GRUPO_CF = $cod_grupo";
   		$datos = toba::db()->consultar_fila($sql);
   		return $datos['lov_descripcion'];
   }

   static public function get_lov_grupo_cf_x_nombre ($nombre, $filtro = array()){
   	  	if (isset($nombre)) {
            $trans_codigo = ctr_construir_sentencias::construir_translate_ilike('cod_grupo_cf', $nombre);
            $trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
			$where = "($trans_codigo OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }

        if (isset($filtro) && !empty($filtro))
        	$where .= 'AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'GCF', '1=1');

   		$sql = "
   			SELECT 	GCF.*,
   					GCF.COD_GRUPO_CF ||' - '|| GCF.DESCRIPCION AS LOV_DESCRIPCION
			FROM PR_GRUPOS_CF GCF
			WHERE $where
			ORDER BY LOV_DESCRIPCION
		";
   		return toba::db()->consultar($sql);
   }



	static public function cargar_partida($cod_partida){
       $sql = "BEGIN pkg_pr_partidas.cargar_partida(:codigo, :descripcion, :nivel, :cod_padre, :cod_grupo_cf, :figurativa, :sin_control, :activa, :reimp, :nivel_control, :rrhh);END;";
       $parametros = array ( array(  'nombre' => 'codigo',
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 9,
                                     'valor' => $cod_partida),
       						 array(  'nombre' => 'descripcion',
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 100,
                                     'valor' => ''),
      						 array(  'nombre' => 'nivel',
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 3,
                                     'valor' => ''),
      						 array(  'nombre' => 'cod_padre',
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 9,
                                     'valor' => ''),
      						 array(  'nombre' => 'cod_grupo_cf',
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 3,
                                     'valor' => ''),
      						 array(  'nombre' => 'figurativa',
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => ''),
      						 array(  'nombre' => 'sin_control',
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => ''),
      						 array(  'nombre' => 'activa',
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => ''),
      						 array(  'nombre' => 'reimp',
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => '') ,
      						 array(  'nombre' => 'nivel_control',
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 3,
                                     'valor' => ''),
      						 array(  'nombre' => 'rrhh',
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => ''));
        $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
        $datos = array (	"cod_partida"=>$cod_partida,
	        				"descripcion"=>$resultado[1]['valor'],
	        				"nivel"=>$resultado[2]['valor'],
	        				"cod_partida_padre"=>$resultado[3]['valor'],
	      	  				"cod_grupo_cf"=>$resultado[4]['valor'],
	        				"figurativa"=>$resultado[5]['valor'],
	        				"sin_control"=>$resultado[6]['valor'],
	        				"activa"=>$resultado[7]['valor'],
					        "reimputacion"=>$resultado[8]['valor'],
					        "nivel_control"=>$resultado[9]['valor'],
					        "rrhh"=>$resultado[10]['valor']);
        return $datos;
   }

	static public function actualizar_partida($cod_partida,$descripcion, $cod_grupo_cf, $figurativa, $sin_control, $activa, $reimp, $nivel_control, $rrhh, $con_transaccion = true){
		if (is_null($cod_grupo_cf)){
			$cod_grupo_cf = '';
		}
		try{

	       $sql = "BEGIN :resultado := pkg_pr_partidas.actualizar_partida(:codigo, :descripcion, :cod_grupo_cf, :figurativa, :sin_control, :activa, :reimp, :nivel_control, :rrhh);END;";
	       $parametros = array ( array(  'nombre' => 'codigo',
	                                     'tipo_dato' => PDO::PARAM_INT,
	                                     'longitud' => 9,
	                                     'valor' => $cod_partida),
	       						 array(  'nombre' => 'descripcion',
	                                     'tipo_dato' => PDO::PARAM_STR,
	                                     'longitud' => 100,
	                                     'valor' => $descripcion),
	      						 array(  'nombre' => 'cod_grupo_cf',
	                                     'tipo_dato' => PDO::PARAM_INT,
	                                     'longitud' => 3,
	                                     'valor' => $cod_grupo_cf),
	      						 array(  'nombre' => 'figurativa',
	                                     'tipo_dato' => PDO::PARAM_STR,
	                                     'longitud' => 1,
	                                     'valor' => $figurativa),
	      						 array(  'nombre' => 'sin_control',
	                                     'tipo_dato' => PDO::PARAM_STR,
	                                     'longitud' => 1,
	                                     'valor' => $sin_control),
	      						 array(  'nombre' => 'activa',
	                                     'tipo_dato' => PDO::PARAM_STR,
	                                     'longitud' => 1,
	                                     'valor' => $activa),
	      						 array(  'nombre' => 'reimp',
	                                     'tipo_dato' => PDO::PARAM_STR,
	                                     'longitud' => 1,
	                                     'valor' => $reimp) ,
	      						 array(  'nombre' => 'nivel_control',
	                                     'tipo_dato' => PDO::PARAM_INT,
	                                     'longitud' => 3,
	                                     'valor' => $nivel_control),
	      						 array(  'nombre' => 'rrhh',
	                                     'tipo_dato' => PDO::PARAM_STR,
	                                     'longitud' => 1,
	                                     'valor' => $rrhh),
	      						  array( 'nombre' => 'resultado',
	                                     'tipo_dato' => PDO::PARAM_STR,
	                                     'longitud' => 4000,
	                                     'valor' => ''));

	       	if ($con_transaccion)
	      		toba::db()->abrir_transaccion();

	        $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
	        if ($con_transaccion){
	        	if ($resultado[9]['valor'] != 'OK'){
	        		toba::db()->abortar_transaccion();
	        		toba::notificacion()->error($resultado[9]['valor']);
	        	}else{
	        		toba::db()->cerrar_transaccion();
	        	}
	        }

	        return $resultado[9]['valor'];

	        } catch (toba_error_db $e_db) {
	            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
	            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
	            if ($con_transaccion)
	            	toba::db()->abortar_transaccion();
	        } catch (toba_error $e) {
	            toba::notificacion()->error('Error '.$e->get_mensaje());
	            toba::logger()->error('Error '.$e->get_mensaje());
	            if ($con_transaccion)
	            	toba::db()->abortar_transaccion();
	        }
   }


   static public function cambiar_estado_activo_hijos ($cod_partida, $con_transaccion = true){
		 try{
            $sql = "BEGIN PKG_PR_PARTIDAS.cambiar_estado_activo_hijos(:cod_partida); END;";
            $parametros = array ( array(  'nombre' => 'cod_partida',
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 20,
                                          'valor' => $cod_partida));
            if ($con_transaccion)
            	toba::db()->abrir_transaccion();
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
            if ($con_transaccion)
	            toba::db()->cerrar_transaccion();

        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::db()->abortar_transaccion();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
            toba::db()->abortar_transaccion();
        }
	}
	static public function cambiar_estado_figurativo_hijos ($cod_partida, $con_transaccion = true){
		 try{
            $sql = "BEGIN PKG_PR_PARTIDAS.cambiar_estado_figur_hijos(:cod_partida, PKG_PR_PARTIDAS.FIGURATIVA(:cod_partida)); END;";
            $parametros = array ( array(  'nombre' => 'cod_partida',
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 20,
                                          'valor' => $cod_partida));
            if ($con_transaccion)
            	toba::db()->abrir_transaccion();
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
            if ($con_transaccion)
	            toba::db()->cerrar_transaccion();

        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::db()->abortar_transaccion();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
            toba::db()->abortar_transaccion();
        }
	}

	static public function cambiar_estado_sin_control_hijos ($cod_partida, $con_transaccion = true){
		 try{
            $sql = "BEGIN PKG_PR_PARTIDAS.cambiar_estado_sin_ctrol_hijos(:cod_partida, PKG_PR_PARTIDAS.sin_control(:cod_partida)); END;";
              $parametros = array ( array(  'nombre' => 'cod_partida',
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 20,
                                          'valor' => $cod_partida));
            if ($con_transaccion)
            	toba::db()->abrir_transaccion();
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
            if ($con_transaccion)
	            toba::db()->cerrar_transaccion();

        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::db()->abortar_transaccion();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
            toba::db()->abortar_transaccion();
        }
	}

	static public function cambiar_estado_reimputacion_hijos ($cod_partida, $con_transaccion = true){
		 try{
            $sql = "BEGIN PKG_PR_PARTIDAS.cambiar_estado_reimp_hijos(:cod_partida, PKG_PR_PARTIDAS.para_reimputar(:cod_partida)); END;";
             $parametros = array ( array(  'nombre' => 'cod_partida',
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 20,
                                          'valor' => $cod_partida));
            if ($con_transaccion)
            	toba::db()->abrir_transaccion();
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
            if ($con_transaccion)
	            toba::db()->cerrar_transaccion();

        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::db()->abortar_transaccion();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
            toba::db()->abortar_transaccion();
        }
	}

	static public function cambiar_estado_ctrl_nvl_hijos ($cod_partida, $con_transaccion = true){
		 try{
            $sql = "BEGIN PKG_PR_PARTIDAS.cambiar_estado_ctrl_nvl_hijos(:cod_partida); END;";
            $parametros = array ( array(  'nombre' => 'cod_partida',
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 20,
                                          'valor' => $cod_partida));
            if ($con_transaccion)
            	toba::db()->abrir_transaccion();
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
            if ($con_transaccion)
	            toba::db()->cerrar_transaccion();

        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::db()->abortar_transaccion();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
            toba::db()->abortar_transaccion();
        }
	}

	static public function codigo_a_nivel_hasta ($cod_partida_hasta, $nivel_partida){
		$sql = "SELECT Pkg_Pr_Partidas.CODIGO_A_NIVEL_HASTA($cod_partida_hasta, $nivel_partida) AS codigo FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['codigo'];
	}
}
?>
