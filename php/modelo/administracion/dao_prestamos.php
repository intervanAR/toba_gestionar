<?php

/**
 * Description of dao_prestamos
 *
 * @author ddiluca
 */
class dao_prestamos {
    
	static public function get_prestamos ($filtro = array()){
		$where = '';
		$sql1 = "SELECT PKG_KR_USUARIOS.in_ua_tiene_acceso(upper('".toba::usuario()->get_id()."')) unidades_ad FROM DUAL";
        $res = toba::db()->consultar_fila($sql1); 
        $where = "(adpm.COD_UNIDAD_ADMINISTRACION IN ".$res['unidades_ad'].")";
		
		$where.= 'AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'ADPM', '1=1');
		$sql = "SELECT adpm.*, 
					   trim(to_char(adpm.importe, '$999,999,999,990.00')) as importe_format,
					   krua.COD_UNIDAD_ADMINISTRACION ||' - '|| krua.descripcion unidad_administracion,
				       krorg.COD_ORGANISMO_FINANCIERO ||' - '|| krorg.DESCRIPCION organismo_financiero
				  FROM ad_prestamos adpm left join KR_ORGANISMOS_FINANCIEROS krorg on adpm.COD_ORGANISMO_FINANCIERO = krorg.COD_ORGANISMO_FINANCIERO,
				       kr_unidades_administracion krua
				 WHERE $where and krua.COD_UNIDAD_ADMINISTRACION = adpm.COD_UNIDAD_ADMINISTRACION
				 ORDER BY ADPM.ID_PRESTAMO DESC";
		return toba::db()->consultar($sql);
	}
	
    static public function get_lov_prestamo_x_id($id_prestamo){     
        if (isset($id_prestamo)) {
            $sql = "SELECT ADPM.*, ADPM.ID_PRESTAMO ||' - '|| ADPM.DESCRIPCION || ' (' || trim(to_char(ADPM.IMPORTE, '$999,999,999,990.00')) || ')' as lov_descripcion
                    FROM AD_PRESTAMOS ADPM
                    WHERE ADPM.ID_PRESTAMO = ".quote($id_prestamo) .";";  
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
     
    static public function get_lov_prestamos_x_nombre($nombre, $filtro = array()){
          if (isset($nombre)) {
              $trans_cod = ctr_construir_sentencias::construir_translate_ilike('id_prestamo', $nombre);
              $trans_des = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
              $trans_imp = ctr_construir_sentencias::construir_translate_ilike('importe', $nombre);
              $where = "($trans_cod OR $trans_des OR $trans_imp)";
          } else {
              $where = '1=1';
          } 
          if (isset($filtro['devengado']) && isset($filtro['cod_unidad_administracion'])){ //Condicion para el formulario Devengado Gasto
            $where.= " AND (ADPM.cod_unidad_administracion = '".$filtro['cod_unidad_administracion']."'  
                       AND EXISTS (SELECT 1 
                                   FROM kr_cuentas_corriente cuco, kr_organismos_financieros orfi, ad_prestamos pre 
                                   WHERE cuco.cod_organismo_financiero = orfi.cod_organismo_financiero 
                                        AND orfi.cod_organismo_financiero = pre.cod_organismo_financiero 
                                        AND pre.id_prestamo = ADPM.id_prestamo))";
            unset($filtro['devengado']);
			unset($filtro['cod_unidad_administracion']);
          }
          
          $where.= 'AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'ADPM', '1=1');
          $sql=   "SELECT ADPM.*, ADPM.ID_PRESTAMO ||' - '|| ADPM.DESCRIPCION || ' (' || trim(to_char(ADPM.IMPORTE, '$999,999,999,990.00')) || ')'  as lov_descripcion
                   FROM AD_PRESTAMOS ADPM
                   WHERE $where
                   ORDER BY lov_descripcion";            
          $datos = toba::db()->consultar($sql);
          return $datos;
      }


}

?>
