<?php
$status = new \Broker\Status ( SITE_CACHE_DATABASE_DIR, $configuration, null );

if (isset ( $_GET ["suboperation"] ) && is_string ( $_GET ["suboperation"] ) && trim ( $_GET ["suboperation"] ) != "") {
  if ($_GET ["suboperation"] == "create") {
    if (strtoupper ( $_SERVER ['REQUEST_METHOD'] ) == "POST") {
      header ( "Content-Type: text/javascript; charset=utf-8" );
      header ( "Access-Control-Allow-Origin: *" );
      header ( "Access-Control-Allow-Headers: content-type" );
      echo (json_encode ( $status->create ( file_get_contents ( "php://input" ) ) ));
    } else {
      header ( "Location: " . $configuration->url ( "status", null ) );
    }
    exit ();
  } else if ($_GET ["suboperation"] == "start") {
    if (strtoupper ( $_SERVER ['REQUEST_METHOD'] ) == "POST") {
      header ( "Content-Type: text/javascript; charset=utf-8" );
      header ( "Access-Control-Allow-Origin: *" );
      header ( "Access-Control-Allow-Headers: content-type" );
      $json_data = json_decode ( file_get_contents ( "php://input" ), true );
      $response = array ();
      $respons ["status"] = "ERROR";
      if ($json_data == null || json_last_error () !== JSON_ERROR_NONE) {
        $response ["error"] = "no valid json";
      } else {
        try {
          $response = $status->start ( $json_data ["key"] );
        } catch ( \Exception $e ) {
          $response = array ();
          $respons ["status"] = "ERROR";
          $response ["error"] = $e->getMessage ();
        }
      }
      if ($response ["status"] !== "OK") {
        header ( "HTTP/1.0 500 Internal Server Error" );
        if (isset ( $response ["error"] )) {
          echo (json_encode ( $response ["error"] ));
        }
      } else {
        if (isset ( $response ["response"] )) {
          if (strpos ( $_SERVER ["HTTP_ACCEPT_ENCODING"], "gzip" ) !== false) {
            header ( "Content-Encoding: gzip" );
            $content = gzencode ( json_encode ( $response ["response"] ) );
          } else {
            $content = json_encode ( $response ["response"] );
          }
          header ( "Vary: Accept-Encoding" );
          header ( "Content-Length: " . strlen ( $content ) );
          echo ($content);
        }
      }
    } else {
      header ( "Location: " . $configuration->url ( "status", null ) );
    }
    exit ();
  } else if ($_GET ["suboperation"] == "update") {
    if (strtoupper ( $_SERVER ['REQUEST_METHOD'] ) == "POST") {
      header ( "Content-Type: text/javascript; charset=utf-8" );
      header ( "Access-Control-Allow-Origin: *" );
      header ( "Access-Control-Allow-Headers: content-type" );
      $json_data = json_decode ( file_get_contents ( "php://input" ), true );
      if ($json_data == null || json_last_error () !== JSON_ERROR_NONE) {
        $response = array (
            "status" => "ERROR",
            "error" => "no valid json" 
        );
        echo (json_encode ( $response ));
      } else {
        echo (json_encode ( $status->update ( $json_data ["key"] ) ));
      }
    } else {
      header ( "Location: " . $configuration->url ( "status", null ) );
    }
    exit ();
  } else {
    if (! $authentication->accessBasedOnLogin ()) {
      $authentication->logout ();
      header ( "Location: " . $configuration->url ( "login", "status" ) );
      exit();
    } else if (preg_match ( "/^list([0-9]*)$/", $_GET ["suboperation"], $match )) {
      $smarty->assign ( "_statusType", "list" );
      $page = intval ( $match [1] );
      if (strtolower ( $_SERVER ["REQUEST_METHOD"] ) == "post") {
        if (isset ( $_POST ["key"] ) && is_string ( $_POST ["key"] ) && trim ( $_POST ["key"] ) != "") {
          $key = $_POST ["key"];
          if (isset ( $_POST ["action"] ) && is_string ( $_POST ["action"] ) && trim ( $_POST ["action"] ) != "") {
            $action = $_POST ["action"];
            if ($action == "delete") {
              $status->delete ( $key );
              header ( "Location: " . $configuration->url ( "status", "list" ) );
              exit ();
            } else if ($action == "view") {
              $smarty->assign ( "_statusType", "view" );
              $smarty->assign ( "_statusData", $status->get ( $key ) );
            } else {
              header ( "Location: " . $configuration->url ( "status", "list" ) );
              exit ();
            }
          } else {
            header ( "Location: " . $configuration->url ( "status", "list" ) );
            exit ();
          }
        } else if (isset ( $_POST ["action"] ) && is_string ( $_POST ["action"] )) {
          if ($_POST ["action"] == "clean") {
            $status->clean ();
            header ( "Location: " . $configuration->url ( "status", null ) );
            exit ();
          } else if ($_POST ["action"] == "reset") {
            $status->reset ();
            header ( "Location: " . $configuration->url ( "status", null ) );
            exit ();
          } else {
            header ( "Location: " . $configuration->url ( "status", null ) );
            exit ();
          }
        } else {
          header ( "Location: " . $configuration->url ( "status", null ) );
          exit ();
        }
      } else {
        $number = 100;
        $smarty->assign ( "_statusPage", $page );
        $smarty->assign ( "_statusNumber", $number );
        $smarty->assign ( "_statusTotal", $status->number () );
        $smarty->assign ( "_statusList", $status->list ( $page * $number, $number ) );
      }
    } else {
      header ( "Location: " . $configuration->url ( "status", null ) );
      exit ();
    }
  }
} else {
  if (! $authentication->accessBasedOnLogin ()) {
    $authentication->logout ();
    header ( "Location: " . $configuration->url ( "login", "status" ) );
  } else {
    $smarty->assign ( "_statusType", null );
    $smarty->assign ( "_statusTotal", $status->number () );
  }  
}

?>