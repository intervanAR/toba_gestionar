<?php

class dao_matriz_contable_cuentas_corrientes {
	
	
	public static function get_matrices_contables_cc ($filtro = array()){
		$where = " 1=1 ";
		
		if (isset($filtro['ui_unidad_administracion']) && !empty($filtro['ui_unidad_administracion'])){
			$where .= " and krua.cod_unidad_administracion = ".quote($filtro['ui_unidad_administracion']);
			unset($filtro['ui_unidad_administracion']);
		}
		
		if (isset($filtro['ui_tipo_cuenta']) && !empty($filtro['ui_tipo_cuenta'])){
			$where .= " and krcc.tipo_cuenta_corriente = ".quote($filtro['ui_tipo_cuenta']);
			unset($filtro['ui_tipo_cuenta']);
		}
		
        if (isset($filtro) && !empty($filtro)) {
			$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'krmcc', '1=1');
		}
		
		$sql = "SELECT krmcc.*, krcc.nro_cuenta_corriente || ' - ' || krcc.descripcion cuenta_corriente_format,
				       TO_CHAR (krmcc.fecha_vigencia, 'YYYY/MM/DD') fecha_vigencia_format,
				       pkg_cp_cuentas.mascara_aplicar(krmcc.nro_cuenta_contable) ||' - '|| cpc.DESCRIPCION cuenta_contable_format,
				       krcc.cod_unidad_administracion ||' - '|| krua.descripcion unidad_administracion
				  FROM kr_matriz_contab_cue krmcc JOIN kr_cuentas_corriente krcc
				       ON krcc.id_cuenta_corriente = krmcc.id_cuenta_corriente
				       JOIN kr_unidades_administracion krua
				       ON krua.cod_unidad_administracion = krcc.cod_unidad_administracion
				       JOIN cp_cuentas cpc ON cpc.nro_cuenta_contable = krmcc.nro_cuenta_contable
				       LEFT JOIN cg_ref_codes rc ON (rc.RV_LOW_VALUE = krcc.TIPO_CUENTA_CORRIENTE and rc.RV_DOMAIN = 'KR_TIPO_CUENTA_CORRIENTE')
				 WHERE $where;";
						
		$datos = toba::db()->consultar($sql);
		return $datos;
	}

    static public function get_lov_cuentas_contables_x_nombre ($nombre, $filtro = array()){
    	$where = "";
		if (isset($nombre)) {
            $trans_nro = ctr_construir_sentencias::construir_translate_ilike('CPC.NRO_CUENTA_CONTABLE', $nombre);
            $trans_des = ctr_construir_sentencias::construir_translate_ilike('CPC.DESCRIPCION', $nombre);
            $where = "($trans_nro OR $trans_des)";
        } else {
            $where = "1=1";
        }
        if (isset($filtro) && !empty($filtro))
			$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'CPC', '1=1');
		$sql = "SELECT CPC.*, pkg_cp_cuentas.mascara_aplicar(CPC.NRO_CUENTA_CONTABLE) ||' - '|| CPC.DESCRIPCION ||'- CtaCte: '|| pkg_cp_cuentas.CTA_CTE(cpc.nro_cuenta_contable) 
								||' - CtaBco: '|| pkg_cp_cuentas.CTA_BCO(cpc.nro_cuenta_contable) as lov_descripcion
				 FROM CP_CUENTAS CPC
				WHERE $where 
      		    ORDER BY LOV_DESCRIPCION;";    
		$datos = toba::db()->consultar($sql);
		return $datos;	
    }
    
    static public function get_ui_unidad_administracion ($id_cuenta){
		$sql = "SELECT krcc.cod_unidad_administracion AS ui_unidad_administracion
  			   	  FROM kr_matriz_contab_cue krmcc JOIN kr_cuentas_corriente krcc
       			       ON krcc.id_cuenta_corriente = krmcc.id_cuenta_corriente and krcc.id_cuenta_corriente = ".quote($id_cuenta);
 		$datos = toba::db()->consultar_fila($sql);
		return $datos;
	}
	
	 static public function get_ui_tipo_cuenta ($id_cuenta){
		$sql = "SELECT krcc.tipo_cuenta_corriente AS ui_tipo_cuenta
				  FROM KR_CUENTAS_CORRIENTE krcc 
				 WHERE krcc.id_cuenta_corriente = ".quote($id_cuenta);
 		$datos = toba::db()->consultar_fila($sql);
		return $datos;
	}

	static public function get_codigo_a_nivel_hasta ($cod_cuenta_hasta, $nivel_cuenta){
		$sql = "select pkg_cp_cuentas.codigo_a_nivel_hasta($cod_cuenta_hasta,$nivel_cuenta) codigo from dual";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['codigo'];

	}
	
}

?>