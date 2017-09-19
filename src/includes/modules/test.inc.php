<?php
/**
 * Module test
 */
if (! $authentication->accessBasedOnLogin ()) {
  $authentication->logout ();
  header ( "Location: " . $configuration->url ( "login", "test" ) );
} else {
  
  if (isset ( $_GET ["suboperation"] ) && is_string ( $_GET ["suboperation"] ) && trim ( $_GET ["suboperation"] ) != "") {
    if ($_GET ["suboperation"] == "examples") {
      $smarty->assign ( "_testType", "examples" );
    } else {
      header ( "Location: " . $configuration->url ( "test", null ) );
      exit ();
    }
  } else {
    $smarty->assign ( "_testType", null );
  }
}

?>