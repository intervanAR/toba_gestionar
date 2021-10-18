<?php
class dao_listados_recursos extends dao_reportes_general{

	public static function get_lov_nivel_x_nivel ($nivel){
		$sql = "SELECT PRNIEN.*, PRNIEN.NIVEL ||' - '|| SUBSTR(PKG_PR_ENTIDADES.MASCARA_NIVEL(PRNIEN.NIVEL),1,30) ||' - '|| PRNIEN.DESCRIPCION as lov_descripcion
				FROM PR_NIVELES_ENTIDAD PRNIEN
				WHERE PRNIEN.NIVEL = $nivel;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}
	public static function get_lov_nivel_x_nombre ($nombre, $filtro = array()){
	 	$where ="";
    	if (isset($nombre)) {
			$trans_nivel = ctr_construir_sentencias::construir_translate_ilike('nivel', $nombre);
			$trans_digitos = ctr_construir_sentencias::construir_translate_ilike('cant_digitos', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
			$where = "($trans_nivel OR $trans_digitos OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }
		if (isset($filtro['cod_unidad_administracion']) && $filtro['cod_unidad_administracion'] != null){
			$where .=" AND EXISTS (SELECT 1
			                  FROM PR_ENTIDADES
			                  WHERE nivel = PRNIEN.nivel AND cod_unidad_administracion = ".$filtro['cod_unidad_administracion'].")";
		}
		$sql = "SELECT PRNIEN.*, PRNIEN.NIVEL ||' - '|| SUBSTR(PKG_PR_ENTIDADES.MASCARA_NIVEL(PRNIEN.NIVEL),1,30) ||' - '|| PRNIEN.DESCRIPCION as lov_descripcion
				FROM PR_NIVELES_ENTIDAD PRNIEN
				WHERE $where
				ORDER BY NIVEL";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}


	public static function get_lov_entidad_a_nivel_x_codigo ($cod_entidad){
		$sql = "SELECT VEN.*, VEN.COD_ENTIDAD ||' - '|| VEN.DESCRIPCION as lov_descripcion
				FROM V_PR_ENTIDADES_A_NIVEL VEN
				WHERE VEN.COD_ENTIDAD = $cod_entidad
				ORDER BY COD_ENTIDAD";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}
	public static function get_lov_entidades_a_nivel_x_nombre ($nombre, $filtro){
		$where ="";
    	if (isset($nombre)) {
			$trans_cod = ctr_construir_sentencias::construir_translate_ilike('cod_entidad', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
			$where = "($trans_cod OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }


         //parametros nulos
        if (!isset($filtro['cod_unidad_administracion']))
        	$filtro['cod_unidad_administracion'] = 'null';
        if (!isset($filtro['nivel_entidad']))
        	$filtro['nivel_entidad'] = 'null';
        if (!isset($filtro['id_ejercicio']))
        	$filtro['id_ejercicio'] = 'null';

        //parametros nulos
        if ($filtro['cod_unidad_administracion'] == '0')
        	$filtro['cod_unidad_administracion'] = 'null';
        if ($filtro['nivel_entidad'] == '-1')
        	$filtro['nivel_entidad'] = 'null';
        if ($filtro['id_ejercicio'] == '0')
        	$filtro['id_ejercicio'] = 'null';

        if (isset($filtro['cod_entidad_desde']) && $filtro['cod_entidad_desde'] != null){
        	$where .= " AND VEN.COD_ENTIDAD >= ".$filtro['cod_entidad_desde']."";
        }
		$sql = "SELECT VEN.*, VEN.COD_ENTIDAD ||' - '|| VEN.DESCRIPCION as lov_descripcion
				FROM V_PR_ENTIDADES_A_NIVEL VEN
				WHERE $where and (Pkg_Kr_Ejercicios.Pertenece_Entidad_A_Estruc (".$filtro['cod_unidad_administracion']." ,".$filtro['id_ejercicio']." ,VEN.Id_Entidad ) = 'S'
				      AND VEN.NIVEL_ENTIDAD=".$filtro['nivel_entidad']." AND VEN.ENTIDAD_GASTOS = 'S')
				ORDER BY COD_ENTIDAD";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}
	public static function get_rango_entidad($filtro = [])
	{
		$where = " 1=1 ";
		$id_ejercicio = 'null';
		
		if (isset($filtro['id_ejercicio']) && !empty($filtro['id_ejercicio']))
		{
			$id_ejercicio = $filtro['id_ejercicio'];
			unset($filtro['id_ejercicio']);
		}

		if (isset($filtro['pertenece_a_estructura']))
		{

			$where .= " and pkg_kr_ejercicios.pertenece_entidad_a_estruc (".$filtro['cod_unidad_administracion'].",
				                                                          $id_ejercicio,
				                                                          ven.id_entidad
				                                                         ) = 'S' ";
			unset($filtro['pertenece_a_estructura']);
			
		}

		$where .= " and " . ctr_construir_sentencias::get_where_filtro($filtro, "ven");

		$sql = "SELECT min(cod_entidad) min
				  FROM (SELECT VEN.cod_entidad
				          FROM v_pr_entidades_a_nivel VEN
				         WHERE $where)";

        $result = toba::db()->consultar_fila($sql);
        $datos['min'] = $result['min'];

        $sql = "SELECT max(cod_entidad) max
				  FROM (SELECT VEN.cod_entidad
	                      FROM v_pr_entidades_a_nivel VEN
	                     WHERE $where)";

        $result = toba::db()->consultar_fila($sql);
        $datos['max'] = $result['max'];
	 	return $datos;
	}

	public static function get_rango_recurso($filtro = [])
	{
		$where = " 1=1 ";
		$where .= " and " . ctr_construir_sentencias::get_where_filtro($filtro, "vrn");
		$sql = "SELECT MIN (vrn.cod_recurso) MIN, MAX (vrn.cod_recurso) MAX
 				  FROM v_pr_recursos_a_nivel vrn
				 WHERE $where";
        return toba::db()->consultar_fila($sql);
	}

	public static function get_rango_partida($filtro = [])
	{
		$where = " 1=1 ";
		$where .= " and " . ctr_construir_sentencias::get_where_filtro($filtro, "vprp");
		$sql = "SELECT MIN (vprp.cod_partida) MIN, MAX (vprp.cod_partida) MAX
  				  FROM V_PR_PARTIDAS_A_NIVEL vprp
				 WHERE $where";
        return toba::db()->consultar_fila($sql);
	}


	public static function get_lov_nivel_recursos_x_nivel ($nivel){
		$sql ="SELECT PRNIRE.NIVEL ||' - '|| PRNIRE.DESCRIPCION ||' - '|| SUBSTR(PKG_PR_RECURSOS.MASCARA_NIVEL(PRNIRE.NIVEL),1,30) as lov_descripcion
			   FROM PR_NIVELES_RECURSO PRNIRE
			   WHERE PRNIRE.NIVEL = $nivel;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}

	public static function get_lov_nivel_recursos_x_nombre ($nombre, $filtro){
    	if (isset($nombre)) {
			$trans_cod = ctr_construir_sentencias::construir_translate_ilike('nivel', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
			$where = "($trans_cod OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }
        if (isset($filtro['cod_fuente']) && !empty($filtro['cod_fuente'])){
	        $where .= " AND (EXISTS(SELECT 1
						            FROM PR_RECURSOS
						            WHERE nivel = PRNIRE.nivel AND cod_fuente_financiera = ".$filtro['cod_fuente']."))";
        }
		$sql ="SELECT PRNIRE.*, PRNIRE.NIVEL ||' - '|| PRNIRE.DESCRIPCION ||' - '|| SUBSTR(PKG_PR_RECURSOS.MASCARA_NIVEL(PRNIRE.NIVEL),1,30) as lov_descripcion
			   FROM PR_NIVELES_RECURSO PRNIRE
			   WHERE $where";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}

	public static function get_lov_recurso_a_nivel_x_codigo ($cod_recurso){
		$sql ="SELECT VRN.COD_RECURSO ||' - '|| VRN.DESCRIPCION ||' - '|| VRN.NIVEL_RECURSO as lov_descripcion
			   FROM V_PR_RECURSOS_A_NIVEL VRN
			   WHERE VRN.COD_RECURSO = $cod_recurso;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}

	public static function get_lov_recurso_a_nivel_x_nombre ($nombre, $filtro){
		if (isset($nombre)) {
			$trans_cod = ctr_construir_sentencias::construir_translate_ilike('cod_recurso', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
			$trans_nivel = ctr_construir_sentencias::construir_translate_ilike('nivel_recurso', $nombre);
			$where = "($trans_cod OR $trans_descripcion OR $trans_nivel)";
        } else {
            $where = '1=1';
        }

        //Parametros nulos.
        if (isset($filtro['cod_recurso_desde']) && $filtro['cod_recurso_desde'] === '0')
        	$filtro['cod_recurso_desde'] = 'null';

        if ($filtro['cod_fuente'] === '0')
        	$filtro['cod_fuente'] =  'null';

        if ($filtro['nivel_recurso'] === '-1')
        	$filtro['nivel_recurso'] = 'null';



        $where .= " and VRN.NIVEL_RECURSO= ".$filtro['nivel_recurso']." AND
				 (".$filtro['cod_fuente']." is null OR VRN.cod_fuente_financiera = ".$filtro['cod_fuente'].")";
        if (isset($filtro['cod_recurso_desde']))
			$where .=" AND VRN.COD_RECURSO >= ".$filtro['cod_recurso_desde']."";

		$sql ="SELECT VRN.*, VRN.COD_RECURSO ||' - '|| VRN.DESCRIPCION ||' - '|| VRN.NIVEL_RECURSO as lov_descripcion
			   FROM V_PR_RECURSOS_A_NIVEL VRN
			   WHERE $where
			   ORDER BY VRN.COD_RECURSO;";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}



 	static public function get_lov_programas_x_nombre($nombre, $filtro = array()) {
       if (isset($nombre)) {
			$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('prpr.cod_programa', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('prpr.descripcion', $nombre);
			$where = "($trans_codigo OR $trans_descripcion)";
        } else {
            $where = ' 1=1 ';
        }

        //Parametros NULOS
        if (isset($filtro['cod_entidad_desde']) && $filtro['cod_entidad_desde'] == '0'){
        	$filtro['cod_entidad_desde'] = 'null';
        }
		 if (isset($filtro['cod_entidad_hasta']) && $filtro['cod_entidad_hasta'] == '0'){
        	$filtro['cod_entidad_hasta'] = 'null';
        }
		 if (isset($filtro['id_ejercicio']) && $filtro['id_ejercicio'] == '0'){
        	$filtro['id_ejercicio'] = 'null';
        }

        if (isset($filtro['cod_entidad_desde']) && isset($filtro['cod_entidad_hasta']) && isset($filtro['id_ejercicio']) ){
      		$where .= " AND PRPR.ID_ENTIDAD = L_PREN.ID_ENTIDAD (+)
       			  AND (l_pren.cod_entidad between ".$filtro['cod_entidad_desde']."
				        AND ".$filtro['cod_entidad_hasta']."
				        and Pkg_kr_Ejercicios.Pertenece_Programa_A_Estruc
				        (".$filtro['id_ejercicio']." ,prpr.id_entidad ,Prpr.Id_Programa ) = 'S'
				       )";
        	unset($filtro['cod_entidad_desde']);
        	unset($filtro['cod_entidad_hasta']);
        	unset($filtro['id_ejercicio']);
        }
       	if (isset($filtro['imputable'])){
       		$where .=" And Pkg_Pr_Programas.Imputable(Prpr.Id_Programa) = 'S'";
       		unset($filtro['imputable']);
       	}

        $where .= 'AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'pp', '1=1');
        $sql = "SELECT  prpr.*,
        				prpr.id_programa||'||'||prpr.cod_programa id_cod, 
                        pkg_pr_programas.mascara_aplicar(prpr.cod_programa) ||' - '|| pkg_pr_programas.cargar_descripcion(prpr.id_programa) as lov_descripcion
                FROM PR_PROGRAMAS prpr, PR_ENTIDADES L_PREN
                WHERE $where
                ORDER BY lov_descripcion ASC;";
        $datos = toba::db()->consultar($sql);
        return $datos;
    }


    public static function get_lov_nivel_partida_x_nivel ($nivel){
    	if (isset($nivel)){
	    	$sql = "SELECT PRNIPA.NIVEL ||' - '|| PRNIPA.DESCRIPCION ||' - '|| SUBSTR(PKG_PR_PARTIDAS.MASCARA_NIVEL(PRNIPA.NIVEL),1,30) as lov_descripcion
					FROM PR_NIVELES_PARTIDA PRNIPA
					WHERE PRNIPA.NIVEL = $nivel";
	    	$datos = toba::db()->consultar_fila($sql);
	    	return $datos['lov_descripcion'];
    	}else return null;
    }

    public static function get_lov_nivel_partida_x_nombre ($nombre, $filtro = []){
    	if (isset($nombre)) {
			$trans_nivel = ctr_construir_sentencias::construir_translate_ilike('nivel', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
			$where = "($trans_nivel OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }
    	$sql = "SELECT PRNIPA.*, PRNIPA.NIVEL ||' - '|| PRNIPA.DESCRIPCION ||' - '|| SUBSTR(PKG_PR_PARTIDAS.MASCARA_NIVEL(PRNIPA.NIVEL),1,30) as lov_descripcion
				FROM PR_NIVELES_PARTIDA PRNIPA
				WHERE $where
				ORDER BY NIVEL";
    	$datos = toba::db()->consultar($sql);
    	return $datos;
    }

    public static function get_lov_partida_a_nivel_x_codigo ($cod_partida){
    	if (isset($cod_partida)){
		    $sql ="SELECT VPA.COD_PARTIDA ||' - '|| VPA.DESCRIPCION as lov_descripcion
				   FROM V_PR_PARTIDAS_A_NIVEL VPA
				   WHERE VPA.cod_partida = $cod_partida";
		    $datos = toba::db()->consultar_fila($sql);
		    return $datos['lov_descripcion'];
    	}else return null;
    }

    public static function get_lov_partidas_a_nivel_x_nombre ($nombre, $filtro = array ()){
    	if (isset($nombre)) {
			$trans_cod = ctr_construir_sentencias::construir_translate_ilike('cod_partida', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
			$where = "($trans_cod OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }
        if (isset($filtro['cod_partida_desde']) && $filtro['cod_partida_desde'] == '0'){
        	$filtro['cod_partida_desde'] = 'null';
        }
        if (isset($filtro['cod_partida_desde'])){
        	$where .= " and VPA.COD_PARTIDA >= ".$filtro['cod_partida_desde']." ";
        	unset($filtro['cod_partida_desde']);
        }

        $where .= 'AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'vpa', ' 1=1 ');
    	$sql = "SELECT VPA.*, VPA.COD_PARTIDA ||' - '|| VPA.DESCRIPCION as lov_descripcion
				FROM V_PR_PARTIDAS_A_NIVEL VPA
				WHERE $where
				ORDER BY COD_PARTIDA";
    	$datos = toba::db()->consultar($sql);
    	return $datos;
    }


   static public function get_lov_recurso_x_cod_recurso ($cod_recurso){
   		$sql ="SELECT  pr.*, pkg_pr_recursos.mascara_aplicar(pr.cod_recurso) ||' - '|| pkg_pr_recursos.cargar_descripcion(pr.cod_recurso) as lov_descripcion
               FROM PR_RECURSOS pr
               WHERE pr.cod_recurso = $cod_recurso
               ORDER BY lov_descripcion ASC;";
   		$datos = toba::db()->consultar_fila($sql);
   		return $datos['lov_descripcion'];
   }

	static public function get_lov_recursos_x_nombre($nombre, $filtro = array()) {
	    if (isset($nombre)) {
	        $trans_codigo = ctr_construir_sentencias::construir_translate_ilike('cod_recurso', $nombre);
	        $trans_codigo_masc = ctr_construir_sentencias::construir_translate_ilike('pkg_pr_recursos.mascara_aplicar(PRRE.cod_recurso)', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
			$where = "($trans_codigo OR $trans_codigo_masc OR $trans_descripcion)";
		}else{
	        $where = '1=1';
		}
	    $where .=" AND (PRRE.COD_FUENTE_FINANCIERA = ".$filtro['cod_fuente']."
				   AND PKG_PR_FUENTES.afectacion_especifica(PRRE.COD_FUENTE_FINANCIERA) = 'S'
				   AND PKG_PR_RECURSOS.imputable(PRRE.COD_RECURSO) = 'S')";
	    $sql = "SELECT  PRRE.*, pkg_pr_recursos.mascara_aplicar(PRRE.cod_recurso) ||' - '|| pkg_pr_recursos.cargar_descripcion(PRRE.cod_recurso) as lov_descripcion
	            FROM PR_RECURSOS PRRE
	            WHERE $where
	            ORDER BY lov_descripcion ASC;";
	    $datos = toba::db()->consultar($sql);
	    return $datos;
	}

}
?>
