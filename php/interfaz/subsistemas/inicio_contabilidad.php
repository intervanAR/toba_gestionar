<?php
  $url = toba::instancia()->get_url_proyecto('contabilidad');
//  $url = str_replace('principal', 'contabilidad', toba::vinculador()->get_url('contabilidad', 2));
  header("Location: $url");
?>