<?php
if (! $authentication->accessBasedOnLogin ()) {
  $authentication->logout ();
  header ( "Location: " . $configuration->url ( "login", "expansion" ) );
  exit();
} else {
  $expansion = new \Broker\ExpansionCache ( SITE_CACHE_DATABASE_DIR, $configuration );
  
  if (isset ( $_GET ["suboperation"] ) && is_string ( $_GET ["suboperation"] ) && trim ( $_GET ["suboperation"] ) != "") {
    if (preg_match ( "/^list([0-9]*)$/", $_GET ["suboperation"], $match )) {
      $smarty->assign ( "_expansionType", "list" );
      $page = intval ( $match [1] );
      if (strtolower ( $_SERVER ["REQUEST_METHOD"] ) == "post") {
        if (isset ( $_POST ["key"] ) && is_string ( $_POST ["key"] ) && trim ( $_POST ["key"] ) != "") {
          $key = $_POST ["key"];
          if (isset ( $_POST ["action"] ) && is_string ( $_POST ["action"] ) && trim ( $_POST ["action"] ) != "") {
            $action = $_POST ["action"];
            if ($action == "delete") {
              $expansion->delete ( $key );
              header ( "Location: " . $configuration->url ( "expansion", "list" ) );
              exit ();
            } else if ($action == "view") {
              $smarty->assign ( "_expansionType", "view" );
              $smarty->assign ( "_expansionData", $expansion->get ( $key ) );
            } else {
              header ( "Location: " . $configuration->url ( "expansion", "list" ) );
              exit ();
            }
          } else {
            header ( "Location: " . $configuration->url ( "expansion", "list" ) );
            exit ();
          }
        } else if (isset ( $_POST ["action"] ) && is_string ( $_POST ["action"] )) {
          if ($_POST ["action"] == "clean") {
            $expansion->clean ();
            header ( "Location: " . $configuration->url ( "expansion", null ) );
            exit ();
          } else if ($_POST ["action"] == "reset") {
            $expansion->reset ();
            header ( "Location: " . $configuration->url ( "expansion", null ) );
            exit ();
          } else {
            header ( "Location: " . $configuration->url ( "expansion", null ) );
            exit ();
          }
        } else {
          header ( "Location: " . $configuration->url ( "expansion", null ) );
          exit ();
        }
      } else {
        $number = 100;
        $smarty->assign ( "_expansionPage", $page );
        $smarty->assign ( "_expansionNumber", $number );
        $smarty->assign ( "_expansionTotal", $expansion->number () );
        $smarty->assign ( "_expansionList", $expansion->list ( $page * $number, $number ) );
      }
    } else {
      header ( "Location: " . $configuration->url ( "expansion", null ) );
      exit ();
    }
  } else {
    $smarty->assign ( "_expansionType", null );
    $smarty->assign ( "_expansionTotal", $expansion->number () );
  }
}
?>