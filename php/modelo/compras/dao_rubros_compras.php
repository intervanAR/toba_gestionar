<?php
class dao_rubros_compras {
	
	
	static function get_registros ($filtro = array()){
		
		$usuario = strtoupper(toba::usuario()->get_id());
		$where = " 1=1 ";
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'repr', '1=1');
		$sql = "SELECT repr.*
				  FROM co_registros_proveedores repr
				 WHERE repr.cod_registro IN (
				          SELECT amre.cod_registro
				            FROM co_usuarios usu,
				                 co_sectores sec,
				                 co_ambitos amb,
				                 co_ambitos_registros amre,
				                 co_registros_proveedores reg
				           WHERE sec.cod_sector = usu.cod_sector
				             AND amb.cod_ambito = sec.cod_ambito
				             AND amre.cod_ambito = amb.cod_ambito
				             AND reg.cod_registro = amre.cod_registro
				             AND usu.usuario = ".quote($usuario).")";
		
		return toba::db()->consultar($sql);
	}
	
	static function get_rubros ($filtro = array()){
		$where = " 1=1 ";
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'rub', '1=1');
		$sql = "select rub.*
				from co_rubros rub
				where $where 
				order by rub.cod_rubro asc";
		return toba::db()->consultar($sql);
	}
	
	static function get_proveedores ($filtro = array()){
		$where = " 1=1 ";
		if (isset($filtro['cod_registro']) && !empty($filtro['cod_registro'])){
			$where .= " and pro.cod_registro = ".$filtro['cod_registro']; 
			unset($filtro['cod_registro']);
		}
		if (isset($filtro['cod_proveedor']) && !empty($filtro['cod_proveedor'])){
			$where .= " and pro.cod_proveedor = ".$filtro['cod_proveedor']; 
			unset($filtro['cod_proveedor']);
		}
		if (isset($filtro['id_proveedor']) && !empty($filtro['id_proveedor'])){
			$where .= " and pro.id_proveedor = ".$filtro['id_proveedor']; 
			unset($filtro['id_proveedor']);
		}	
		if (isset($filtro['razon_social']) && !empty($filtro['razon_social'])){
			$where .= " and pro.razon_social like '%".$filtro['razon_social']."%'"; 
			unset($filtro['razon_social']);
		}
		
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'proru', '1=1');
		$sql = "select proru.*, pro.RAZON_SOCIAL, pro.cod_proveedor
				from CO_PROVEEDORES_RUBROS proru, co_proveedores pro
				where proru.id_proveedor = pro.id_proveedor and $where 
				order by pro.cod_proveedor asc";
		return toba::db()->consultar($sql);
	}

	public static function insertar_rubro ($id_rubro,$cod_registro,$cod_rubro,$cod_actividad_re,$descripcion){


		toba::db()->abrir_transaccion();

      

         $sql = ("INSERT INTO co_rubros
				(id_rubro, cod_registro,cod_rubro,descripcion,COD_ACTIVIDAD_RE)
				VALUES
				(".$id_rubro.",".$cod_registro.",".$cod_rubro.",'".$descripcion."',".$cod_actividad_re.") ");
        // toba::notificacion()->error($sql);

          $rta = dao_varios::ejecutar_sql($sql, false);

          if ($rta !== "OK"){
            toba::db()->abortar_transaccion();
            toba::notificacion()->error($rta);
            return;
        }

        toba::db()->cerrar_transaccion();
		
	}

	public static function get_max_rubros(){
	
		$sql = "SELECT max(id_rubro + 1)id_rubro,max(cod_rubro+1)cod_rubro,max(COD_ACTIVIDAD_RE+1)cod_actividad_re
			from co_rubros";

		$datos = toba::db()->consultar_fila($sql);

		return $datos;
	}
	
	public static function validar_rubro($descripcion){
	
		$sql = "SELECT count(1)cantidad
				from co_rubros
				where descripcion = '".$descripcion."'";

		$datos = toba::db()->consultar_fila($sql);

		return $datos;
	}
}
?>