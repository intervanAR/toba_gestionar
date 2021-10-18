<?php
class dao_programas_sociales {
	
	public static function get_programas_sociales ($filtro = array()){
		$desde= null;
		$hasta= null;
		if(isset($filtro['desde'])){
			$desde= $filtro['desde'];
			$hasta= $filtro['hasta'];

			unset($filtro['desde']);
			unset($filtro['hasta']);
		}
		
		$where = "  1=1 ";
		if (isset($filtro) )
			$where .= " and ".ctr_construir_sentencias::get_where_filtro($filtro, 'PS', '1=1');
			
		$sql = "SELECT PS.*, 
					   TO_CHAR(PS.FECHA_ALTA, 'YYYY/MM/DD') AS FECHA_ALTA_FORMAT,
					   TO_CHAR(PS.FECHA_BAJA, 'YYYY/MM/DD') AS FECHA_BAJA_FORMAT 
				FROM AS_PROGRAMAS_SOCIALES PS
				WHERE $where
				ORDER BY COD_PROGRAMA ASC";
		$sql= dao_varios::paginador($sql, null, $desde, $hasta);
		$datos = toba::db()->consultar($sql);
		return $datos;	
	}
	
	public static function get_programa_x_id ($cod_programa){
		$sql = "SELECT PS.*, TO_CHAR(PS.FECHA_ALTA, 'YYYY/MM/DD') AS FECHA_ALTA_FORMAT,
					   		TO_CHAR(PS.FECHA_BAJA, 'YYYY/MM/DD') AS FECHA_BAJA_FORMAT 
				FROM AS_PROGRAMAS_SOCIALES PS
				WHERE PS.COD_PROGRAMA = $cod_programa
				ORDER BY COD_PROGRAMA ASC";
		$datos = toba::db()->consultar_fila($sql);
		return $datos;
	}
	
	public static function get_lov_programas_x_id ($id){
		if (!is_null($id)){
			$sql = "SELECT PS.COD_PROGRAMA ||' - '|| PS.NOMBRE AS LOV_DESCRIPCION
					FROM AS_PROGRAMAS_SOCIALES PS
					WHERE PS.COD_PROGRAMA = $id ";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['lov_descripcion'];
		}else{
			return null;
		}
	}
	
	public static function get_lov_programas_x_nombre ($nombre, $filtro = array()){
		if (isset($nombre)) {
    		$trans_cod = ctr_construir_sentencias::construir_translate_ilike('ps.cod_programa', $nombre);
            $trans_nom = ctr_construir_sentencias::construir_translate_ilike('ps.descripcion', $nombre);
            $where = "($trans_cod OR $trans_nom )";
        } else {
            $where = '1=1';
        }
        
        if (!empty($filtro))
        	$where .= " and ".ctr_construir_sentencias::get_where_filtro($filtro, "ps", "1=1");
        	
        $sql = "SELECT PS.*, PS.COD_PROGRAMA ||' - '|| PS.NOMBRE AS LOV_DESCRIPCION
				FROM AS_PROGRAMAS_SOCIALES PS
				WHERE $where ORDER BY LOV_DESCRIPCION";
        
        $datos = toba::db()->consultar($sql);
	    return $datos;
	}
	
	public static function get_proximo_id (){
		$sql = "SELECT NVL(MAX(COD_PROGRAMA),0)+1 COD_PROGRAMA
				FROM AS_PROGRAMAS_SOCIALES";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cod_programa'];
	}
	
	public static function baja_programa ($cod_programa, $fecha){
		try{
			$programa = dao_programas_sociales::get_programa_x_id($cod_programa);
			$fecha_alta = new DateTime($programa['fecha_alta_format']);
			$fecha_baja = new DateTime($fecha);
			
			if ( $fecha_baja >= $fecha_alta){
	        	$sql = "UPDATE AS_PROGRAMAS_SOCIALES SET FECHA_BAJA = to_date('".$fecha."','RR/MM/DD'), ACTIVO = 'N' WHERE COD_PROGRAMA = $cod_programa ;";
				toba::db()->abrir_transaccion();
				toba::db()->ejecutar($sql);
				toba::db()->cerrar_transaccion();
				return true;
			}else{
				toba::notificacion()->error("La fecha de Baja debe ser mayor que la fecha de Alta (".$fecha_alta->format('Y-m-d').")");
				return false;
			}
		}catch (toba_error_db $e_db) {
			toba::db()->cerrar_transaccion();
            toba::notificacion()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            toba::logger()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            return false;
        } catch (toba_error $e) {
        	toba::db()->cerrar_transaccion();
            toba::notificacion()->error('Error ' . $e->get_mensaje());
            toba::logger()->error('Error ' . $e->get_mensaje());
            return false;
        }
	}
	public static function activar_programa($cod_programa){
		try{
			toba::db()->abrir_transaccion();
			
			$sql = "SELECT SUM(F.PORCENTAJE) total_financiamiento, NVL(COUNT(1),0) cantidad
					FROM AS_FINANCIAMIENTOS F
					WHERE F.COD_PROGRAMA =$cod_programa ";
			
			$datos = toba::db()->consultar_fila($sql);
			if ($datos['cantidad'] > 0 && $datos['total_financiamiento'] <> 100){
				toba::db()->abortar_transaccion();
				toba::notificacion()->error("La suma del financiamiento no esta en el 100%");
				return false;
			}
			
			$sql = "UPDATE AS_PROGRAMAS_SOCIALES SET ACTIVO = 'S', FECHA_BAJA = NULL WHERE COD_PROGRAMA = $cod_programa ;";
			
			toba::db()->ejecutar($sql);
			toba::db()->cerrar_transaccion();
			return true;
		}catch (toba_error_db $e_db) {
			toba::db()->abortar_transaccion();
            toba::notificacion()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            toba::logger()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            return false;
        } catch (toba_error $e) {
        	toba::db()->abortar_transaccion();
            toba::notificacion()->error('Error ' . $e->get_mensaje());
            toba::logger()->error('Error ' . $e->get_mensaje());
            return false;
        }
	}
	
	
	//Carga de campos UIs
	public static function get_activo($cod_programa){
		if (!is_null($cod_programa)){
			$sql = "SELECT ACTIVO activo FROM AS_PROGRAMAS_SOCIALES WHERE COD_PROGRAMA = $cod_programa";
			return toba::db()->consultar($sql);
		}else return array();
	}
	
	
	
}
?>