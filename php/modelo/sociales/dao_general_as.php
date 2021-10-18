<?php
class dao_general_as {
	
	static public function get_paises (){
		$sql = "SELECT * FROM AS_PAISES";
		return toba::db()->consultar($sql);
	}
	
}
?>