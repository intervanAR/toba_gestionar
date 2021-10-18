<?php
class dao_organismos_financieros {
	
	static public function get_organismos_financieros ($filtro = array()){
		$where = "1=1";
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'kro', '1=1');
        $sql = "SELECT kro.*, CASE
				          WHEN kro.es_banco = 'S'
				             THEN 'Si'
				          ELSE 'No'
				       END es_banco_format,
				       CASE
				          WHEN kro.activo = 'S'
				             THEN 'Si'
				          ELSE 'No'
				       END activo_format, (SELECT id_banco || ' - ' || descripcion
				                             FROM kr_bancos
				                            WHERE id_banco = kro.id_banco) AS banco
				  FROM kr_organismos_financieros kro
				 WHERE $where 
				 ORDER BY KRO.COD_ORGANISMO_FINANCIERO ASC ";
        return toba::db()->consultar($sql);
	}
	
	static public function get_lov_organismos_financieros_x_codigo ($codigo){
		$sql = "SELECT KRO.COD_ORGANISMO_FINANCIERO ||' - '|| KRO.DESCRIPCION AS LOV_DESCRIPCION
				  FROM KR_ORGANISMOS_FINANCIEROS KRO
				 WHERE KRO.COD_ORGANISMO_FINANCIERO = $codigo";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}
	
	static public function get_lov_organismos_financieros_x_nombre ($nombre, $filtro = array()){
		if (isset($nombre)) {
			$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('kro.cod_organismo_financiero', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('kro.descripcion', $nombre);
			$where = "($trans_codigo OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }
		if (!empty($filtro)){
			$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'kro', '1=1');
		}
		$sql = "SELECT KRO.*, KRO.COD_ORGANISMO_FINANCIERO ||' - '|| KRO.DESCRIPCION AS LOV_DESCRIPCION
				  FROM KR_ORGANISMOS_FINANCIEROS KRO
				 WHERE $where
				 ORDER BY LOV_DESCRIPCION";
		$datos = toba::db()->consultar($sql);
		return $datos; 
	}
	
	
}
?>