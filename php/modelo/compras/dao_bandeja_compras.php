<?php
class dao_bandeja_compras {

	static public function get_v_comprobantes ($filtro = array(), $orden = array()){
	    $desde= null; $hasta= null;
	    
	    if(isset($filtro['numrow_desde'])){
	      $desde = $filtro['numrow_desde']; $hasta= $filtro['numrow_hasta'];
	      unset($filtro['numrow_desde']); unset($filtro['numrow_hasta']);
	    }
	    
	    $where = self::get_where($filtro);
	    
		$sql = "SELECT V.*,
					   trim(to_char(v.importe, '$999,999,999,990.00')) as importe_format,
						 v.nro_comprobante nro_comprobante_link,
						 TO_CHAR (v.fecha, 'YYYY/MM/DD') AS fecha_format,
				       (SELECT rv_meaning
				          FROM cg_ref_codes
				         WHERE rv_low_value = v.tipo_comprobante
				           AND rv_domain = 'CO_TIPO_COMPROBANTE') AS tipo_comprobante_format,
				       (SELECT rv_meaning
				          FROM cg_ref_codes
				         WHERE rv_low_value = v.tipo_compra
				           AND rv_domain = 'CO_TIPO_COMPRA') AS tipo_compra_format,
				       (SELECT cod_sector || ' - ' || descripcion
				          FROM co_sectores
				         WHERE cod_sector = v.cod_sector) AS sector,
				       (SELECT cod_ambito || ' - ' || descripcion
				          FROM co_ambitos
				         WHERE cod_ambito = v.cod_ambito) AS ambito,
				       (SELECT nro_expediente
				          FROM kr_expedientes
				         WHERE id_expediente = v.id_expediente) AS expediente,  
				       CASE
				          WHEN tipo_comprobante = 'SOL'
				             THEN (SELECT rv_meaning
				                     FROM cg_ref_codes
				                    WHERE rv_low_value = v.estado
				                      AND rv_domain = 'CO_ESTADO_SOLICITUD')
				          WHEN tipo_comprobante = 'COM'
				             THEN (SELECT rv_meaning
				                     FROM cg_ref_codes
				                    WHERE rv_low_value = v.estado
				                      AND rv_domain = 'CO_ESTADO_COMPRA')
				          WHEN tipo_comprobante = 'ORD'
				             THEN (SELECT rv_meaning
				                     FROM cg_ref_codes
				                    WHERE rv_low_value = v.estado
				                      AND rv_domain = 'CO_ESTADO_ORDEN')
				          WHEN tipo_comprobante = 'REC'
				             THEN (SELECT rv_meaning
				                     FROM cg_ref_codes
				                    WHERE rv_low_value = v.estado
				                      AND rv_domain = 'CO_ESTADO_RECEPCION')
				          ELSE ''
				       END estado_format
				FROM V_CO_COMPROBANTES V
				WHERE $where 
				ORDER BY v.nro_comprobante desc";
		
		$sql = dao_varios::paginador($sql, null, $desde, $hasta, null, $orden);
        $datos = toba::db()->consultar($sql);
        return $datos;
	}
	
	public static function get_cantidad($filtro = array()){
	    $where = self::get_where($filtro);
	    $sql = "SELECT COUNT(*) cant 
		        FROM V_CO_COMPROBANTES V
		        WHERE $where";
	    $datos = toba::db()->consultar_fila($sql);
	    return $datos['cant'];
	}
	
	public static function get_where ($filtro = array()){
		$usuario = toba::usuario()->get_id();
	    $where = "v.finalizada = 'N'
				 AND PKG_USUARIOS.esta_en_bandeja(upper('$usuario'),v.cod_sector,v.cod_ambito,v.tipo_comprobante,v.tipo_compra,v.presupuestario,v.interna,v.estado) = 'S'";
	    if (isset($filtro['ambito_usuario'])){
	    	$where .= " and v.cod_ambito = ".$filtro['ambito_usuario'];
	    	unset($filtro['ambito_usuario']);
	    }
	    $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'v', '1=1'); 
	    return $where;
	  }
}
?>