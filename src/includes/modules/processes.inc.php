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
      if ($_GET ["suboperation"] == "running" || $_GET ["suboperation"] == "history" || $_GET ["suboperation"] == "error") {
        $smarty->assign ( "_processesType", $_GET ["suboperation"] );
      } else if ($_GET ["suboperation"] == "api") {
        if (strtoupper ( $_SERVER ['REQUEST_METHOD'] ) == "POST") {
          $response = array ();
          header ( "Content-Type: application/json" );
          if ($apiRequest = json_decode ( file_get_contents ( "php://input" ) )) {
            $response ["status"] = "ok";
            if (! isset ( $apiRequest->type ) || ! is_string ( $apiRequest->type )) {
              $response ["status"] = "error";
              $response ["error"] = "no (valid) type in request";
            } if (! isset ( $apiRequest->configuration ) || ! is_string ( $apiRequest->configuration ) || !isset($configuration->config ["solr"] [$apiRequest->configuration])) {
              $response ["status"] = "error";
              $response ["error"] = "no (valid) configuration in request";
            } else if($configuration->solr[$apiRequest->configuration]["mtasHandler"]) {
              $coreUrl = $configuration->config ["solr"] [$apiRequest->configuration] ["url"].$configuration->solr[$apiRequest->configuration]["mtasHandler"];
              $ch = curl_init ( $coreUrl . "?action=".urlencode($apiRequest->type)."&key=".((isset($apiRequest->key)&&is_string($apiRequest->key))?urlencode($apiRequest->key):"") );
              $options = array (
                  CURLOPT_RETURNTRANSFER => true
              );
              curl_setopt_array ( $ch, $options );
              $result = curl_exec ( $ch ); 
              if ($data = json_decode ( $result )) {
                $response ["data"] = $data; 
              } else {
                $response ["status"] = "error";
                $response ["data"] = $result;
                $response ["error"] = "problem with status";
              }
            }
          } else {
            $response ["status"] = "error";
            $response ["error"] = "no valid json";
          }
          echo json_encode ( $response );
        } else {
          // only allow post
          header ( "Location: " . $configuration->url ( "api" ) );
        }
        exit ();
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