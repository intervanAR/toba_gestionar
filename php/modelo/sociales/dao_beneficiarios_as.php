<?php

class dao_beneficiarios_as {
	
	static public function get_beneficiarios ($filtro = array()){
		$where = " 1=1 ";
		if (isset($filtro) && !empty($filtro)){
			$where .= ' and '.ctr_construir_sentencias::get_where_filtro($filtro, 'benef','1=1');
		}
		
		if (isset($filtro['distinto_de'])){
			$where .=" and benef.id_beneficiario <> ".$filtro['distinto_de'];
			unset($filtro['distinto_de']);
		}

		$sql = "SELECT benef.*,
				       to_char(benef.FECHA_NACIMIENTO,'YYYY/MM/DD') fecha_nacimiento_format,
				       to_char(benef.FECHA_RESIDENCIA_LOC ,'YYYY/MM/DD') fecha_residencia_lloc_format,
				       to_char(benef.FECHA_RESIDENCIA_PAIS ,'YYYY/MM/DD') fecha_residencia_pais_format,
				       case when benef.EMBARAZO = 'S' then 'Si'
				            else 'No'
				       end embarazo_format,
				       (select rv_meaning from cg_ref_codes where rv_low_value = benef.sexo  and rv_domain = 'AS_SEXO') sexo_format,
				       (select rv_meaning from cg_ref_codes where rv_low_value = benef.ESTADO_CIVIL  and rv_domain = 'AS_ESTADO_CIVIL') ESTADO_CIVIL_FORMAT,
				       (select rv_meaning from cg_ref_codes where rv_low_value = benef.tipo_residencia  and rv_domain = 'AS_TIPO_RESIDENCIA') TIPO_RESIDENCIA_FORMAT,
				       (select rv_meaning from cg_ref_codes where rv_low_value = benef.TIPO_DOCUMENTO  and rv_domain = 'AS_TIPO_DOCUMENTO') TIPO_DOCUMENTO_FORMAT,
				       (select rv_meaning from cg_ref_codes where rv_low_value = benef.nivel_instruccion and rv_domain = 'AS_NIVEL_INSTRUCCION') NIVEL_INSTRUCCION_FORMAT,
				       pa.DESCRIPCION pais_format
				  FROM as_beneficiarios benef, as_paises pa
				 WHERE $where and benef.cod_pais = pa.COD_PAIS
						
  			  ORDER BY benef.id_beneficiario DESC ;";
		return toba::db()->consultar($sql);
	}

	static public function buscar_dni ($filtro = array()){
		$where = " 1=1 ";
		if (isset($filtro) && !empty($filtro)){
			$where .= ' and '.ctr_construir_sentencias::get_where_filtro($filtro, 'benef','1=1');
		}
		
		if (isset($filtro['distinto_de'])){
			$where .=" and benef.id_beneficiario <> ".$filtro['distinto_de'];
			unset($filtro['distinto_de']);
		}
		$sql = "SELECT benef.id_beneficiario
				  FROM as_beneficiarios benef
				 WHERE $where ";
		return toba::db()->consultar($sql);
	}
	
	public static function get_beneficiario_x_documento ($nro_documento){
		
		$sql = "SELECT benef.*,
				       to_char(benef.FECHA_NACIMIENTO,'YYYY/MM/DD') fecha_nacimiento_format,
				       to_char(benef.FECHA_RESIDENCIA_LOC ,'YYYY/MM/DD') fecha_residencia_lloc_format,
				       to_char(benef.FECHA_RESIDENCIA_PAIS ,'YYYY/MM/DD') fecha_residencia_pais_format,
				       case when benef.EMBARAZO = 'S' then 'Si'
				            else 'No'
				       end embarazo_format,
				       (select rv_meaning from cg_ref_codes where rv_low_value = benef.sexo  and rv_domain = 'AS_SEXO') sexo_format,
				       (select rv_meaning from cg_ref_codes where rv_low_value = benef.ESTADO_CIVIL  and rv_domain = 'AS_ESTADO_CIVIL') ESTADO_CIVIL_FORMAT,
				       (select rv_meaning from cg_ref_codes where rv_low_value = benef.tipo_residencia  and rv_domain = 'AS_TIPO_RESIDENCIA') TIPO_RESIDENCIA_FORMAT,
				       (select rv_meaning from cg_ref_codes where rv_low_value = benef.TIPO_DOCUMENTO  and rv_domain = 'AS_TIPO_DOCUMENTO') TIPO_DOCUMENTO_FORMAT,
				       (select rv_meaning from cg_ref_codes where rv_low_value = benef.nivel_instruccion and rv_domain = 'AS_NIVEL_INSTRUCCION') NIVEL_INSTRUCCION_FORMAT,
				       pa.DESCRIPCION pais_format
				  FROM as_beneficiarios benef, as_paises pa
				 WHERE benef.nro_documento = ".quote($nro_documento); 
		return toba::db()->consultar_fila($sql);
	}
	
	public static function get_beneficiario_x_id ($id_beneficiario){
		
		$sql = "SELECT * 
				  FROM AS_BENEFICIARIOS 
				 WHERE ID_BENEFICIARIO = ".quote($id_beneficiario);
		return toba::db()->consultar_fila($sql);
	}
	
	public static function get_lov_beneficiario_x_id ($id){
		$sql = "SELECT benef.nombre ||' - '|| benef.nro_documento as lov_descripcion
  				  FROM as_beneficiarios benef
				 WHERE benef.id_beneficiario = $id ";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}
	
	public static function get_lov_beneficiario_x_nombre ($nombre, $filtro = array()){
		if (isset($nombre)) {
    		$trans_nro = ctr_construir_sentencias::construir_translate_ilike('benef.nro_documento', $nombre);
            $trans_nom = ctr_construir_sentencias::construir_translate_ilike('benef.nombre', $nombre);
            $where = "($trans_nro OR $trans_nom )";
        } else 
            $where = '1=1';
            
        if (isset($filtro['mayor_edad'])){
        	$where .= " and (months_between(sysdate, benef.fecha_nacimiento)/12) > ".$filtro['mayor_edad'];
        	unset($filtro['mayor_edad']);
        }
          
        if (!empty($filtro))
        	$where .= " and ".ctr_construir_sentencias::get_where_filtro($filtro, "benef", "1=1");
        	
        $sql = "SELECT benef.*, benef.nombre ||' - '|| benef.nro_documento as lov_descripcion
  				  FROM as_beneficiarios benef
				 WHERE $where 
			  ORDER BY lov_descripcion asc";
        return toba::db()->consultar($sql);
	}
	
	public static function get_lov_ocupacion_x_id ($codigo){
		$sql = "SELECT OCU.COD_OCUPACION ||' - '|| OCU.DESCRIPCION AS LOV_DESCRIPCION
				  FROM AS_OCUPACIONES OCU
				 WHERE OCU.COD_OCUPACION = $codigo ";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}
	
	public static function get_lov_ocupacion_x_nombre ($nombre, $filtro = array()){
		if (isset($nombre)) {
    		$trans_cod = ctr_construir_sentencias::construir_translate_ilike('ocu.cod_ocupacion', $nombre);
            $trans_des = ctr_construir_sentencias::construir_translate_ilike('ocu.descripcion', $nombre);
            $where = "($trans_cod OR $trans_des)";
        } else 
            $where = '1=1';
        
        if (!empty($filtro))
        	$where .= " and ".ctr_construir_sentencias::get_where_filtro($filtro, "ocu", "1=1");
        	
        $sql = "SELECT OCU.*, OCU.COD_OCUPACION ||' - '|| OCU.DESCRIPCION AS LOV_DESCRIPCION
				  FROM AS_OCUPACIONES OCU
				 WHERE $where 
			  ORDER BY LOV_DESCRIPCION asc";
        return toba::db()->consultar($sql);
	}
}
?>