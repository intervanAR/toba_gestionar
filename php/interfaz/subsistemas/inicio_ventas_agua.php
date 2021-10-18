<?php
  $url = toba::instancia()->get_url_proyecto('ventas_agua');
  //$url = str_replace('principal', 'administracion', toba::vinculador()->get_url('administracion', 2));
  header("Location: $url");
?>