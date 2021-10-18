<?php
  $url = toba::instancia()->get_url_proyecto('rrhh');
  //$url = str_replace('principal', 'rrhh', toba::vinculador()->get_url('rrhh', 2));
  header("Location: $url");
?>