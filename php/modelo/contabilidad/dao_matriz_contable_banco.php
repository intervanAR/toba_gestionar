<?php
class dao_matriz_contable_banco {
	static public function get_matrices_contables_bancos ($filtro = array ())
	{
		$where = " 1=1 ";
		
		if (isset($filtro['nro_cuenta']) && !empty($filtro['nro_cuenta'])){
			$where .= " and krcb.nro_cuenta = ".quote($filtro['nro_cuenta']);
			unset($filtro['nro_cuenta']);
		}
		
		if (isset($filtro['tipo_cuenta_banco'])){
			$where .= " and KRCB.tipo_cuenta_banco = '".$filtro['tipo_cuenta_banco']."'";
			unset($filtro['tipo_cuenta_banco']);
		}
		
        if (isset($filtro) && !empty($filtro)) {
			$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'KRMCB', '1=1');
		}
		$sql = "SELECT KRMCB.*,
					   KRCB.nro_cuenta ||' - '|| krcb.DESCRIPCION nro_cuenta,
				       TO_CHAR(KRMCB.FECHA_VIGENCIA,'YYYY/MM/DD') FECHA_VIGENCIA_FORMAT,
				       pkg_cp_cuentas.mascara_aplicar(KRMCB.nro_cuenta_contable) cuenta_contable_format,
				        CASE
				           WHEN KRCB.TIPO_CUENTA_BANCO = 'BAN'
				              THEN 'Banco'
				           WHEN KRCB.TIPO_CUENTA_BANCO = 'CAJ'
				               THEN 'Caja'
				           ELSE ''
				       END AS tipo_cuenta_banco_format,
				       KRCB.COD_UNIDAD_ADMINISTRACION ||' - '|| KRUA.DESCRIPCION UNIDAD_ADMINISTRACION     
				FROM KR_MATRIZ_CONTAB_BCO KRMCB
				     JOIN KR_CUENTAS_BANCO KRCB ON KRCB.ID_CUENTA_BANCO = KRMCB.ID_CUENTA_BANCO
				     JOIN KR_UNIDADES_ADMINISTRACION KRUA ON KRUA.COD_UNIDAD_ADMINISTRACION = KRCB.COD_UNIDAD_ADMINISTRACION
				     JOIN CP_CUENTAS CPC ON CPC.NRO_CUENTA_CONTABLE = KRMCB.NRO_CUENTA_CONTABLE
				WHERE $where;";
						
		$datos = toba::db()->consultar($sql);
		return $datos;
	}
	
	
	static public function get_lov_cuentas_banco_x_nombre($nombre, $filtro = array()) 
	{
		
		if (isset($nombre)) {
			$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('KRCUBA.id_cuenta_banco', $nombre);
			$trans_nro = ctr_construir_sentencias::construir_translate_ilike('KRCUBA.nro_cuenta', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('KRCUBA.descripcion', $nombre);
			$where = "($trans_codigo OR $trans_nro OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }
		
		$where .= ' AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'KRCUBA', '1=1');

        $sql = "SELECT	KRCUBA.*,
						KRCUBA.nro_cuenta ||' - '|| KRCUBA.descripcion as lov_descripcion
				FROM kr_cuentas_banco krcuba left join kr_bancos l_krba on krcuba.ID_BANCO = l_krba.ID_BANCO
				WHERE $where
				ORDER BY KRCUBA.ID_CUENTA_BANCO";		
		
        $datos = toba::db()->consultar($sql);

        return $datos;
		
	}
	
	static public function get_ui_cuenta ($id_cuenta){
		return array("ui_cuenta"=>$id_cuenta);
	}
	
	static public function get_ui_unidad_administracion ($id_cuenta){
		$sql = "SELECT KRUA.COD_UNIDAD_ADMINISTRACION as ui_unidad_administracion
				FROM KR_MATRIZ_CONTAB_BCO KRMCB
				     JOIN KR_CUENTAS_BANCO KRCB ON (KRCB.ID_CUENTA_BANCO = KRMCB.ID_CUENTA_BANCO AND KRCB.ID_CUENTA_BANCO = ".quote($id_cuenta).")
				     JOIN KR_UNIDADES_ADMINISTRACION KRUA ON KRUA.COD_UNIDAD_ADMINISTRACION = KRCB.COD_UNIDAD_ADMINISTRACION";
		$datos = toba::db()->consultar_fila($sql);
		return $datos;
	}
	
	static public function get_ui_tipo_cuenta ($id_cuenta){
		$sql = "SELECT krcb.tipo_cuenta_banco ui_tipo_cuenta
				  FROM kr_matriz_contab_bco krmcb JOIN kr_cuentas_banco krcb
				       ON (    krcb.id_cuenta_banco = krmcb.id_cuenta_banco
				           AND krcb.id_cuenta_banco = ".quote($id_cuenta).")";
		$datos = toba::db()->consultar_fila($sql);
		return $datos;
	}
	
	
}

?>