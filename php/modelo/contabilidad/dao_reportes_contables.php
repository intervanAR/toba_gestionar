<?php
class dao_reportes_contables {
	
	static public function get_lov_nivel_cuenta_x_nivel ($nivel){
		if (isset($nivel)){
			$sql ="SELECT CPNICU.NIVEL ||' - '|| CPNICU.DESCRIPCION AS LOV_DESCRIPCION
				   FROM CP_NIVELES_CUENTA CPNICU
				   WHERE CPNICU.NIVEL = $nivel
				   ORDER BY NIVEL";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['lov_descripcion'];
		}else return null;
	}
	static public function get_lov_nivel_cuenta_x_nombre ($nombre, $filtro = array()){
		$where ="";
    	if (isset($nombre)) {
			$trans_nivel = ctr_construir_sentencias::construir_translate_ilike('nivel', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
			$where = "($trans_nivel OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }
		$sql = "SELECT CPNICU.*, CPNICU.NIVEL ||' - '|| CPNICU.DESCRIPCION AS LOV_DESCRIPCION
				FROM CP_NIVELES_CUENTA CPNICU
				WHERE $where
				ORDER BY NIVEL";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}
	static public function get_lov_nivel_recursos_x_nivel ($nivel){
		if (isset($cod_recurso)){
			$sql ="SELECT PRNIRE.NIVEL ||' - '|| PRNIRE.DESCRIPCION LOV_DSECRIPCION
				   FROM PR_NIVELES_RECURSO PRNIRE
				   WHERE PRNIRE.NIVEL = $nivel";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['lov_descripcion'];
		}else return null;
	}
	static public function get_lov_nivel_recurso_x_nombre ($nombre, $filtro = array()){
		$where ="";
    	if (isset($nombre)) {
			$trans_nivel = ctr_construir_sentencias::construir_translate_ilike('nivel', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
			$where = "($trans_nivel OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }
        if (isset($filtro['cod_fuente'])){
        	if ($filtro['cod_fuente'] == '0')
        	 	$filtro['cod_fuente'] = 'null';
        	$where .=" and (".$filtro['cod_fuente']." IS NULL OR EXISTS (SELECT 1 
						                                                 FROM PR_RECURSOS 
						                                                 WHERE nivel = PRNIRE.nivel 
						                                                      AND cod_fuente_financiera = ".$filtro['cod_fuente']."))"; 	
        }
		$sql = "SELECT PRNIRE.NIVEL ||' - '|| PRNIRE.DESCRIPCION LOV_DSECRIPCION
				FROM PR_NIVELES_RECURSO PRNIRE
				WHERE $where
				ORDER BY NIVEL";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}
	static public function get_lov_cuentas_bancos_x_id ($id_cuenta_banco){
		if (isset($id_cuenta_banco)){
			$sql ="SELECT 'ID: '|| KRCUBA.ID_CUENTA_BANCO ||' - Nro: '|| KRCUBA.NRO_CUENTA ||' - '|| KRCUBA.DESCRIPCION LOV_DESCRIPCION
				   FROM KR_CUENTAS_BANCO KRCUBA
				   WHERE KRCUBA.ID_CUENTA_BANCO = $id_cuenta_banco";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['lov_descripcion'];
		}else return null;
	}
	static public function get_lov_cuentas_bancos_x_nombre ($nombre, $filtro = array()){
		$where ="";
    	if (isset($nombre)) {
			$trans_id = ctr_construir_sentencias::construir_translate_ilike('id_cuenta_banco', $nombre);
			$trans_nro = ctr_construir_sentencias::construir_translate_ilike('nro_cuenta', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
			$where = "($trans_id OR $trans_nro OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }
		
        if (isset($filtro['cod_unidad_administracion'])){
        	if ($filtro['cod_unidad_administracion'] == '0')
        		$filtro['cod_unidad_administracion'] = 'null';
        	$where .=" and KRCUBA.COD_UNIDAD_ADMINISTRACION = ".$filtro['cod_unidad_administracion']."";
        	unset($filtro['cod_unidad_administracion']);	
        }
		$sql = "SELECT KRCUBA.*, 'ID: ' || KRCUBA.ID_CUENTA_BANCO ||' - Nro: '|| KRCUBA.NRO_CUENTA ||' - '|| KRCUBA.DESCRIPCION LOV_DESCRIPCION
				FROM KR_CUENTAS_BANCO KRCUBA
				WHERE $where 
				ORDER BY ID_CUENTA_BANCO";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}
	static public function get_lov_cuentas_a_nivel_nro ($nro_cuenta){
		if (isset($nro_cuenta)){
			$sql = "SELECT VCN.NIVEL_CUENTA ||' - '|| VCN.NRO_CUENTA_CONTABLE ||' - '|| VCN.DESCRIPCION LOV_DESCRIPCION
					FROM V_CP_CUENTAS_A_NIVEL VCN
					WHERE VCN.NRO_CUENTA_CONTABLE = $nro_cuenta";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['lov_descripcion'];
		}
		else return null;
	}
	static public function get_lov_cuentas_a_nivel_x_nombre ($nombre, $filtro = array()){
		$where ="";
    	if (isset($nombre)) {
			$trans_nro = ctr_construir_sentencias::construir_translate_ilike('nro_cuenta_contable', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
			$where = "($trans_nro OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }
		
        if (isset($filtro['cod_cuenta_desde'])){
        	if ($filtro['cod_cuenta_desde'] == '0')
        		$filtro['cod_cuenta_desde'] = 'null';
        	$where .=" and VCN.nro_cuenta_contable >= ".$filtro['cod_cuenta_desde']."";
        	unset($filtro['cod_cuenta_desde']);	
        }
        if (isset($filtro['nivel_cuenta'])){
        	$where .=" and VCN.nivel_cuenta = ".$filtro['nivel_cuenta']."";
        	unset($filtro['nivel_cuenta']);
        }               
		$sql = "SELECT VCN.*, VCN.NIVEL_CUENTA ||' - '|| VCN.NRO_CUENTA_CONTABLE ||' - '|| VCN.DESCRIPCION LOV_DESCRIPCION
				FROM V_CP_CUENTAS_A_NIVEL VCN
				WHERE $where 
				ORDER BY NRO_CUENTA_CONTABLE";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}
	static public function get_lov_cuentas_corriente_x_id ($id_cuenta_corriente){
		if (isset($id_cuenta_corriente)){
			$sql = "SELECT 'ID: '|| KRCTCT.ID_CUENTA_CORRIENTE ||' - Nro: '|| KRCTCT.NRO_CUENTA_CORRIENTE ||' - '|| KRCTCT.DESCRIPCION ||' - '|| CG.RV_MEANING as lov_descripcion
					FROM KR_CUENTAS_CORRIENTE KRCTCT,
					     CG_REF_CODES CG
					WHERE CG.RV_DOMAIN = 'KR_TIPO_CUENTA_CORRIENTE' AND CG.RV_LOW_VALUE = KRCTCT.TIPO_CUENTA_CORRIENTE 
					      and krctct.id_cuenta_corriente = $id_cuenta_corriente";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['lov_descripcion'];
		}else return null;
		
	}
	static public function get_lov_cuentas_corriente_x_nombre ($nombre, $filtro = array()){
		$where ="";
    	if (isset($nombre)) {
			$trans_id = ctr_construir_sentencias::construir_translate_ilike('id_cuenta_corriente', $nombre);
			$trans_nro = ctr_construir_sentencias::construir_translate_ilike('nro_cuenta_corriente', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
			$where = "($trans_id OR $trans_nro OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }
		
        if (isset($filtro['cod_unidad_administracion'])){
        	if ($filtro['cod_unidad_administracion'] == '0')
        		$filtro['cod_unidad_administracion'] = 'null';
        	$where .=" and (KRCTCT.COD_UNIDAD_ADMINISTRACION=".$filtro['cod_unidad_administracion'].")";
        	unset($filtro['cod_unidad_administracion']);	
        }
		$sql = "SELECT KRCTCT.*, 'ID: ' || KRCTCT.ID_CUENTA_CORRIENTE ||' - Nro: '|| KRCTCT.NRO_CUENTA_CORRIENTE ||' - '|| KRCTCT.DESCRIPCION ||' - '|| CG.RV_MEANING as lov_descripcion
			    FROM KR_CUENTAS_CORRIENTE KRCTCT, CG_REF_CODES CG
				WHERE $where and CG.RV_DOMAIN = 'KR_TIPO_CUENTA_CORRIENTE' 
  					  AND CG.RV_LOW_VALUE = KRCTCT.TIPO_CUENTA_CORRIENTE
				ORDER BY NRO_CUENTA_CORRIENTE";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}

}
?>