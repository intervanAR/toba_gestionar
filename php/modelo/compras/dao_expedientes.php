<?php

class dao_expedientes
{
	static public function get_expedientes($filtro=array()){
		$desde= null;
		$hasta= null;
		if(isset($filtro['numrow_desde'])){
			$desde= $filtro['numrow_desde'];
			$hasta= $filtro['numrow_hasta'];

			unset($filtro['numrow_desde']);
			unset($filtro['numrow_hasta']);
		}
		
		$where = self::armar_where($filtro);
	    $sql_sel = "SELECT ke.ID_EXPEDIENTE, 
					ke.COD_TIPO_EXPEDIENTE, 
					ke.NRO_EXPEDIENTE, 
					to_char(ke.FECHA,'DD/MM/YYYY') FECHA, 
					ke.OBSERVACION, 
					ke.AUTOMATICO, 
					ke.ACTIVO
		    FROM kr_expedientes ke
		    WHERE $where
		    ORDER BY id_expediente desc ";
		
	    $sql = dao_varios::paginador($sql_sel, null, $desde, $hasta);
        $datos = toba::db()->consultar($sql);
        
	    return $datos;
	}
	static public function armar_where ($filtro = array())
	{
		$where = "1=1 ";
		
		if (isset($filtro['observacion']) && !empty($filtro['observacion'])){
		    $where .= " AND UPPER(ke.observacion) LIKE UPPER('%".$filtro['observacion']."%')";
			unset($filtro['observacion']);
		}
		if (isset($filtro['fecha_desde']) && !empty($filtro['fecha_desde']) && !isset($filtro['fecha_hasta']) && empty($filtro['fecha_hasta'])){	
			
			$where .= " AND ke.fecha = to_date('".$filtro['fecha_desde']."','YYYY/MM/DD')";
			unset($filtro['fecha_desde']);
		}
		
		if (isset($filtro['fecha_desde']) && !empty($filtro['fecha_desde']) && isset($filtro['fecha_hasta']) && !empty($filtro['fecha_hasta'])){
			
			$where .= " AND ke.fecha between to_date('".$filtro['fecha_desde']."','YYYY/MM/DD') and to_date('".$filtro['fecha_hasta']."','YYYY/MM/DD') ";
			unset($filtro['fecha_desde']);
			unset($filtro['fecha_hasta']);
		}		

		if (isset($filtro['nro_expediente']) && !empty($filtro['nro_expediente'])){
		    $where .= " AND UPPER(ke.nro_expediente) LIKE UPPER('%".$filtro['nro_expediente']."%')";
			unset($filtro['nro_expediente']);
		}
	    
		$where .= " AND ". ctr_construir_sentencias::get_where_filtro($filtro, 'ke', '1=1');
		return $where;
	}
	
	static public function get_cantidad ($filtro = array())
	{
		$where = self::armar_where($filtro);
		$sql = "select count(*) cantidad
				  FROM kr_expedientes ke
				where $where ";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cantidad'];
	}
	
	static public function get_tipos_expedientes ($filtro = array()){
		$where = "1=1 ";
		
		if (!empty($filtro))
			$where .= " AND ". ctr_construir_sentencias::get_where_filtro($filtro, 'ke', '1=1');
			
	    $sql = "SELECT KRTE.*
				FROM KR_TIPOS_EXPEDIENTES KRTE
			    WHERE $where
			    ORDER BY KRTE.DESCRIPCION ASC";
		
	    return toba::db()->consultar($sql);
	}
	
	
	static public function get_expedientes_x_id ($id_expediente)
	{
	    $sql = "SELECT ke.*
			    FROM kr_expedientes ke
			    WHERE id_expediente = ".quote($id_expediente);
	    $datos = toba::db()->consultar_fila($sql);
	    return $datos;
	}
	
	static public function get_lov_expedientes_x_id($id_expediente){     
        
        if (isset($id_expediente)) {
            $sql = "SELECT krex.NRO_EXPEDIENTE ||' - '|| substr(krex.OBSERVACION,0,100) ||'(#'|| krex.ID_EXPEDIENTE ||')' lov_descripcion
                    FROM KR_EXPEDIENTES krex
                    WHERE id_expediente = ".quote($id_expediente) .";";
            
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

    static public function get_lov_expedientes_x_numero($nro_expediente){     
        
        if (isset($nro_expediente)) {
            $sql = "SELECT krex.NRO_EXPEDIENTE ||' - '|| substr(krex.OBSERVACION,0,100) ||'(#'|| krex.ID_EXPEDIENTE ||')' lov_descripcion
                    FROM KR_EXPEDIENTES krex
                    WHERE nro_expediente = ".quote($nro_expediente) .";";
            
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
    
    static public function get_lov_expedientes_x_nombre($nombre, $filtro= array()) {   
        
        if (isset($nombre)) {
            $trans_codigo = ctr_construir_sentencias::construir_translate_ilike('id_expediente', $nombre);
            $trans_nro = ctr_construir_sentencias::construir_translate_ilike('nro_expediente', $nombre);
            $trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('observacion', $nombre);
            $where = "($trans_codigo OR $trans_descripcion OR $trans_nro)";
        } else {
            $where = '1=1';
        }        
        if (isset($filtro['activo'])){
            $where.= " AND krex.activo = 'S'";
            unset($filtro['activo']);
        }
        $where .= 'AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'krex', '1=1');

        $sql = "SELECT  krex.*, 
						krex.id_expediente || ' - ' ||krex.nro_expediente || ' - ' || krex.observacion as lov_descripcion
                FROM KR_EXPEDIENTES krex
                WHERE $where
                ORDER BY lov_descripcion ASC;";

        $datos = toba::db()->consultar($sql);

        return $datos;
    }         
	
	static public function get_nro_expedientes_x_nombre($nombre, $filtro= array()) {   
        
        if (isset($nombre)) {
            $trans_nro = ctr_construir_sentencias::construir_translate_ilike('nro_expediente', $nombre);
            $where = "($trans_nro)";
        } else {
            $where = '1=1';
        }        

        $where .= 'AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'krex', '1=1');

        $sql = "SELECT  krex.*,
        				krex.NRO_EXPEDIENTE ||' - '|| substr(krex.OBSERVACION,0,100) ||'(#'|| krex.ID_EXPEDIENTE ||')' lov_descripcion
                FROM KR_EXPEDIENTES krex
                WHERE $where
                ORDER BY id_expediente DESC;";

        $datos = toba::db()->consultar($sql);

        return $datos;
    }

	static public function get_nro_expedientes_x_id($id_expediente){     
        
        if (isset($id_expediente)) {
            $sql = "SELECT krex.nro_expediente
                    FROM KR_EXPEDIENTES krex
                    WHERE id_expediente = ".quote($id_expediente) .";";
            
            $datos = toba::db()->consultar_fila($sql);
            
            if (isset($datos) && !empty($datos) && isset($datos['nro_expediente'])) {
                return $datos['nro_expediente'];
            } else {
                return '';
            }
        } else {
            return '';
        }
    }

	static public function get_lov_tipo_expedientes($filtro=  array()){
        $where = ctr_construir_sentencias::get_where_filtro($filtro, 'ke', '1=1');
	    $sql = "SELECT kte.*, kte.descripcion ||' - '||kte.mascara lov_descripcion
		    FROM KR_TIPOS_EXPEDIENTES kte
		    WHERE $where
		    ORDER BY kte.cod_tipo_expediente asc;";
	    $datos = toba::db()->consultar($sql);
	    return $datos;
    }    
	
	static public function get_nro_expedientes_x_tipo($nro_expediente, $cod_tipo_expediente, $usa_auto_numeracion) {				
		//$usa_auto_numeracion = $this->usa_auto_numeracion();
        if (empty($nro_expediente) || $nro_expediente == 0) {
			if (isset($cod_tipo_expediente) && !empty($cod_tipo_expediente) && $usa_auto_numeracion == 'S') {				
				$sql = "SELECT ke1.nro_expediente    
						  FROM kr_expedientes ke1
						 WHERE ke1.ID_EXPEDIENTE = (SELECT MAX(ke2.id_expediente)
												   FROM kr_expedientes ke2
												   WHERE ke2.cod_tipo_expediente =" . $cod_tipo_expediente . ");";

				$datos = toba::db()->consultar_fila($sql);

				if (isset($datos) && !empty($datos) && isset($datos['nro_expediente'])) {
					return $datos['nro_expediente'];
				} else {
					return '';
				}
			} else {
				return '';
			}
		} else {
			return '';
		}
	}	
	
	static public function get_mascara($cod_tipo_expediente) {		
		
			if (isset($cod_tipo_expediente) && !empty($cod_tipo_expediente)) {				
				$sql = "SELECT kte.mascara mascara   
                          FROM kr_tipos_expedientes  kte
                         WHERE kte.cod_tipo_expediente =" . $cod_tipo_expediente . ";";

				$datos = toba::db()->consultar_fila($sql);

				if (isset($datos) && !empty($datos) && isset($datos['mascara'])) {
					return $datos['mascara'];
				} else {
					return '';
				}
			} else {
				return '';
			}		
	}	
	
	static public function validar_mascara_expedientes($nro_expediente, $mascara) {			
		$consulta = "select (pkg_kr_general.validar_mascara('".$nro_expediente."','".$mascara."')) valor from dual;";		
		$resultado = toba::db()->consultar_fila($consulta);				
		return $resultado;		
	}
	
	static public function usa_auto_numeracion() {	
		$consulta = "select (pkg_general.valor_parametro_kr('BUSCAR_MAX_EXPEDIENTE_X_TIPO')) valor from dual;";
		$resultado = toba::db()->consultar_fila($consulta);
		return $resultado['valor'];
	}		
}
?>