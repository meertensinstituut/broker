<?php
if (! $authentication->accessBasedOnLogin ()) {
  $authentication->logout ();
  header ( "Location: " . $configuration->url ( "login", "cache" ) );
  exit();
} else {
  
  $cache = new \Broker\Cache ( SITE_CACHE_DATABASE_DIR, $configuration );
  
  if (isset ( $_GET ["suboperation"] ) && is_string ( $_GET ["suboperation"] ) && trim ( $_GET ["suboperation"] ) != "") {
    if (preg_match ( "/^list([0-9]*)$/", $_GET ["suboperation"], $match )) {
      $smarty->assign ( "_cacheType", "list" );
      $page = intval ( $match [1] );
      if (strtolower ( $_SERVER ["REQUEST_METHOD"] ) == "post") {
        if (isset ( $_POST ["key"] ) && is_string ( $_POST ["key"] ) && trim ( $_POST ["key"] ) != "") {
          $key = $_POST ["key"];
          if (isset ( $_POST ["action"] ) && is_string ( $_POST ["action"] ) && trim ( $_POST ["action"] ) != "") {
            $action = $_POST ["action"];
            if ($action == "delete") {
              $cache->delete ( $key );
              header ( "Location: " . $configuration->url ( "cache", "list" ) );
              exit ();
            } else if ($action == "view") {
              $smarty->assign ( "_cacheType", "view" );
              $smarty->assign ( "_cacheData", $cache->get ( $key ) );
            } else {
              header ( "Location: " . $configuration->url ( "cache", "list" ) );
              exit ();
            }
          } else {
            header ( "Location: " . $configuration->url ( "cache", "list" ) );
            exit ();
          }
        } else if (isset ( $_POST ["action"] ) && is_string ( $_POST ["action"] )) {
          if ($_POST ["action"] == "clean") {
            $cache->clean ();
            header ( "Location: " . $configuration->url ( "cache", null ) );
            exit ();
          } else if ($_POST ["action"] == "reset") {
            $cache->reset ();
            header ( "Location: " . $configuration->url ( "cache", null ) );
            exit ();
          } else {
            header ( "Location: " . $configuration->url ( "cache", null ) );
            exit ();
          }
        } else {
          header ( "Location: " . $configuration->url ( "cache", null ) );
          exit ();
        }
      } else {
        $number = 100;
        $smarty->assign ( "_cachePage", $page );
        $smarty->assign ( "_cacheNumber", $number );
        $smarty->assign ( "_cacheTotal", $cache->number () );
        $smarty->assign ( "_cacheList", $cache->list ( $page * $number, $number ) );
      }
    } else {
      header ( "Location: " . $configuration->url ( "cache", null ) );
      exit ();
    }
  } else {
    $smarty->assign ( "_cacheType", null );
    $smarty->assign ( "_cacheTotal", $cache->number () );
  }
}
?>