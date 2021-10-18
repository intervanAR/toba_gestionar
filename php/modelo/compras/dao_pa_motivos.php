<?php 

/**
* @author ddiluca	
* Motivos para hoja de cargo y descargo.
*/

class dao_pa_motivos 
{
	public static function get_motivos($filtro = [])
	{

		$where = " 1=1 ";

		$where .= " and " . ctr_construir_sentencias::get_where_filtro($filtro, 'PAMC');
		$sql = "SELECT PAMC.* 
				  FROM PA_MOTIVOS_CARGO PAMC
				 WHERE $where ";
		return principal_ei_tabulator_consultar::todos_los_datos($sql);
	}
}

?>