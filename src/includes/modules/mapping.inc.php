<?php
/**
 * Module mapping
 * @package Broker
 */
if (! $authentication->accessBasedOnLogin ()) {
  $authentication->logout ();
  header ( "Location: " . $configuration->url ( "login", "settings" ) );
  exit ();
} else {
  if (isset ( $_GET ["suboperation"] ) && is_string ( $_GET ["suboperation"] ) && trim ( $_GET ["suboperation"] ) != "") {  
  // do something
  }
}  
?>