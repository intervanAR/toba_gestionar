<?php

class dao_lugares_entrega {

    public static function get_lugares_entrega($filtro=array()) {
		$where = " 1=1 ";
		
		if (isset($filtro['nro_orden']) && isset($filtro['nro_renglon_orden'])) {
			$where .= "AND cle.cod_lugar IN (SELECT iole.cod_lugar
											  FROM co_items_orden_lug_ent iole
											  WHERE iole.nro_orden = " . quote($filtro['nro_orden']) . "
											  AND iole.nro_renglon = " . quote($filtro['nro_renglon_orden']) . ")";
			unset($filtro['nro_orden']);
			unset($filtro['nro_renglon_orden']);
		}
        
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'cle', '1=1');
		$sql_sel = "SELECT  cle.*,
							cle.cod_lugar || ' - ' || cle.descripcion as lov_descripcion
					FROM co_lugares_entrega cle
					WHERE $where
					ORDER BY cle.cod_lugar ASC;";
		$datos = toba::db()->consultar($sql_sel);
		return $datos;
    }
	
	public static function get_lugar_entrega_principal_x_sector($cod_sector) {
		if (isset($cod_sector)) {
			$sql_sel = "SELECT  cle.cod_lugar
						FROM co_lugares_entrega cle
						WHERE cle.cod_sector = " . quote($cod_sector) ."
						AND cle.principal = 'S';";
			$datos = toba::db()->consultar_fila($sql_sel);
			if (isset($datos['cod_lugar'])) {
				return $datos['cod_lugar'];
			} else {
				return null;
			}
		} else {
			return null;
		}
    }
	
	
	
}

?>
