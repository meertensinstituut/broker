<?php
if (! $authentication->accessBasedOnLogin ()) {
  $authentication->logout ();
  header ( "Location: " . $configuration->url ( "login", "test" ) );
} 

?>