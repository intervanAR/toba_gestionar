<?php
class dao_matriz_economica_recurso {
	
	static public function get_recursos_economicos ($filtro = array()){
		$where = ' 1=1 ';
		
		if (isset($filtro['des_recurso'])){
			$where .= " AND REC.DESCRIPCION LIKE '%".$filtro['des_recurso']."%'";
			unset($filtro['des_recurso']);
		}
		
		if (isset($filtro['des_economico'])){
			$where .= " AND EREC.DESCRIPCION LIKE '%".$filtro['des_economico']."%'";
			unset($filtro['des_economico']);
		}
		
		if (isset($filtro) && !empty($filtro))
        	$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'prr', '1=1');
        
        $sql = "SELECT PRR.*, 
				       PKG_PR_RECURSOS.MASCARA_APLICAR(PRR.COD_RECURSO) COD_RECURSO_MASC,
				       PKG_PR_ECONOMICO_RECURSOS.MASCARA_APLICAR(PRR.COD_ECONOMICO) COD_ECONOMICO_MASC,
				       REC.DESCRIPCION RECURSO_DESCRIPCION,
				       EREC.DESCRIPCION ECONOMICO_DESCRIPCION       
				FROM PR_RECURSOS_ECONOMICO PRR, PR_RECURSOS REC, PR_ECONOMICO_RECURSOS EREC
				WHERE $where AND PRR.COD_RECURSO = REC.COD_RECURSO AND PRR.COD_ECONOMICO = EREC.COD_ECONOMICO 
                order by PRR.COD_RECURSO asc;";
       	return toba::db()->consultar($sql);
	}
	
	
	
}

?>