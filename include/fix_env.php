<?php
foreach ($_SERVER as $k => $v) {
  if(preg_match('/^(REDIRECT_)+(.*)$/',$k, $m)) {
    $nk = $m[2];
    if( ! array_key_exists($nk,$_ENV) ) {
      $_SERVER[$nk] = $v;
    }
  }
}
