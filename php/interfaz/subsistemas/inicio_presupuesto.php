<?php
  $url = toba::instancia()->get_url_proyecto('presupuesto');
//  $url = str_replace('principal', 'presupuesto', toba::vinculador()->get_url('presupuesto', 2));
  header("Location: $url");
?>