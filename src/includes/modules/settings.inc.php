<?php
if (!$authentication->accessBasedOnLogin ()) {
  $authentication->logout ();
  header ( "Location: " . $configuration->url ("login", "settings") );
  exit();
} else {
  //var_dump($configuration);
  //die();  
}
?>