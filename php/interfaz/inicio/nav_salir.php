<?php
  //js = toba_editor::modo_prueba() ?  'salir()' : 'window.close()';
  //echo "<script languaje='javascript' type='text/javascript'>$js;</script>";
  //echo "<body>";
  //echo "<script languaje='javascript' type='text/javascript'>";
  //echo "window.close();";
  //echo "</script>";
  //echo "</body>";
  //$url=toba::instancia()->get_url_proyecto("");
  //header("Location: $url");
	$item = toba::memoria()->get_item_solicitado_original();
	
	//-- Si originalmente no se pidio salir, ir a la página inicial
	if ($item[1] != '12000147') {
		toba::vinculador()->navegar_a('principal', 2);
	} else {
	 	$js = toba_editor::modo_prueba() ? 'window.close()' : 'salir()';		
	    echo "<script language='javascript'>";
		echo $js;
	    echo "</script>";
	}
?>