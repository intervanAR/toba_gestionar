<?php
  $url = toba::instancia()->get_url_proyecto('rentas');
  //$url = str_replace('principal', 'rentas', toba::vinculador()->get_url('rentas', 2));
  header("Location: $url");
?>