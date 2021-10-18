<?php
  $url = toba::instancia()->get_url_proyecto('compras');
//  $url = str_replace('principal', 'compras', toba::vinculador()->get_url('compras', 2));
  header("Location: $url");
?>