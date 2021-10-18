<?php
  $url = toba::instancia()->get_url_proyecto('costos');
  //$url = str_replace('principal', 'costos', toba::vinculador()->get_url('costos', 2));
  header("Location: $url");
?>