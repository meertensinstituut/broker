<?php
/**
 * Module processes
 */
if (! $authentication->accessBasedOnLogin ()) {
  $authentication->logout ();
  header ( "Location: " . $configuration->url ( "login", "processes" ) );
} else {
  
  if (isset ( $_GET ["suboperation"] ) && is_string ( $_GET ["suboperation"] ) && trim ( $_GET ["suboperation"] ) != "") {
    if (isset ( $_GET ["subsuboperation"] ) && is_string ( $_GET ["subsuboperation"] ) && trim ( $_GET ["subsuboperation"] ) != "") {
      if(isset($configuration->solr) && isset($configuration->solr[$_GET["subsuboperation"]])) {
        $smarty->assign ( "_processesConfiguration", $_GET ["subsuboperation"] );
      } else {
        header ( "Location: " . $configuration->url ( "processes") );
        exit ();
      }      
      if ($_GET ["suboperation"] == "running") {
        $smarty->assign ( "_processesType", "running" );
      } else if ($_GET ["suboperation"] == "history") {
        $smarty->assign ( "_processesType", "history" );
      } else if ($_GET ["suboperation"] == "error") {
        $smarty->assign ( "_processesType", "error" );
      } else if ($_GET ["suboperation"] == "api") {
        $response = array ();
        header ( "Content-Type: application/json" );
        echo json_encode ( $response );
        exit();
      } else {
        header ( "Location: " . $configuration->url ( "processes") );
        exit ();
      }
    } else {
      header ( "Location: " . $configuration->url ( "processes") );
    }
  } else {
    $smarty->assign ( "_processesConfiguration", null );
    $smarty->assign ( "_processesType", null );
  }
  
}

?>