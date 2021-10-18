<?php

class toba_autenticacion_saml_intervan  extends toba_autenticacion_saml
{
	protected function recuperar_usuario_toba()
	{
		$id_usuario = parent::recuperar_usuario_toba();
		return $id_usuario;
	}	
		
}
?>
