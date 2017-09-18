<?php
if (! $authentication->accessWithAdminPrivileges ()) {
  $authentication->logout ();
  header ( "Location: " . $configuration->url ( "login", "collections" ) );
  exit();
} else {
  
  $collection = new \Broker\Collection ( SITE_CACHE_DATABASE_DIR, $configuration );
  
  if (isset ( $_GET ["suboperation"] ) && is_string ( $_GET ["suboperation"] ) && trim ( $_GET ["suboperation"] ) != "") {
    if (preg_match ( "/^list([0-9]*)$/", $_GET ["suboperation"], $match )) {
      $smarty->assign ( "_collectionsType", "list" );
      $page = intval ( $match [1] );
      if (strtolower ( $_SERVER ["REQUEST_METHOD"] ) == "post") {
        if (isset ( $_POST ["key"] ) && is_string ( $_POST ["key"] ) && trim ( $_POST ["key"] ) != "") {
          $key = $_POST ["key"];
          if (isset ( $_POST ["action"] ) && is_string ( $_POST ["action"] ) && trim ( $_POST ["action"] ) != "") {
            $action = $_POST ["action"];
            if ($action == "delete") {
              $collection->delete ( $key );
              header ( "Location: " . $configuration->url ( "collections", "list" ) );
              exit ();
            } else if ($action == "view") {
              $smarty->assign ( "_collectionsType", "view" );
              $smarty->assign ( "_collectionsData", $collection->get ( $key ) );
            } else if ($action == "uncheck") {
              $collection->setUnchecked ( $key );
              header ( "Location: " . $configuration->url ( "collections", "list" ) );
              exit ();
            } else if ($action == "check") {
              if ($collection->doCheck ( $key )) {
                header ( "Location: " . $configuration->url ( "collections", "list" ) );
                exit ();
              } else {
                die ( "ERROR" );
              }
            } else {
              header ( "Location: " . $configuration->url ( "collections", "list" ) );
              exit ();
            }
          } else {
            header ( "Location: " . $configuration->url ( "collections", "list" ) );
            exit ();
          }
        } else if (isset ( $_POST ["action"] ) && is_string ( $_POST ["action"] )) {
          if ($_POST ["action"] == "clean") {
            $collection->clean ();
            header ( "Location: " . $configuration->url ( "collections", null ) );
            exit ();
          } else if ($_POST ["action"] == "reset") {
            $collection->reset ();
            header ( "Location: " . $configuration->url ( "collections", null ) );
            exit ();
          } else {
            header ( "Location: " . $configuration->url ( "collections", null ) );
            exit ();
          }
        } else {
          header ( "Location: " . $configuration->url ( "collections", null ) );
          exit ();
        }
      } else {
        $number = 100;
        $smarty->assign ( "_collectionsPage", $page );
        $smarty->assign ( "_collectionsNumber", $number );
        $smarty->assign ( "_collectionsTotal", $collection->number () );
        $smarty->assign ( "_collectionsList", $collection->list ( $page * $number, $number ) );
      }
    } else {
      header ( "Location: " . $configuration->url ( "collections", null ) );
      exit ();
    }
  } else {
    $smarty->assign ( "_collectionsType", null );
    $smarty->assign ( "_collectionsTotal", $collection->number () );
  }
}
?>