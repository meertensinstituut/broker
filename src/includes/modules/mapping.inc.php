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
    if (strtolower ( trim ( $_GET ["suboperation"] ) ) == "api") {
      if (strtoupper ( $_SERVER ['REQUEST_METHOD'] ) == "POST") {
        $response = array ();
        header ( "Content-Type: application/json" );
        if ($mappingRequest = json_decode ( file_get_contents ( "php://input" ) )) {
          $response ["status"] = "ok";
          if (! isset ( $mappingRequest->action ) || ! is_string ( $mappingRequest->action )) {
            $response ["status"] = "error";
            $response ["error"] = "no (valid) action in request";
          } else {
            if ($mappingRequest->action == "info") {
              $response ["configurations"] = array ();
              foreach ( $configuration->config ["solr"] as $configItem => $value ) {
                if (isset ( $configuration->solr [$configItem] ) && isset ( $configuration->solr [$configItem] ["mtas"] ) && count ( $configuration->solr [$configItem] ["mtas"] ) > 0) {
                  $coreUrl = $configuration->config ["solr"] [$configItem] ["url"];
                  $ch = curl_init ( $coreUrl . "mtas?wt=json&action=files" );
                  $options = array (
                      CURLOPT_RETURNTRANSFER => true 
                  );
                  curl_setopt_array ( $ch, $options );
                  $result = curl_exec ( $ch );
                  if (($data = json_decode ( $result )) && isset ( $data->files )) {
                    $response ["configurations"] [$configItem] = array (
                        "files" => $data->files 
                    );
                  } else {
                    $response ["status"] = "error";
                    $response ["error"] = "problem with configuration " . $configItem;
                  }
                }
              }
            } else if ($mappingRequest->action == "file") {
              if (! isset ( $mappingRequest->file ) || ! is_string ( $mappingRequest->file )) {
                $response ["status"] = "error";
                $response ["error"] = "no (valid) file in request";
              } else if (! isset ( $mappingRequest->configuration ) || ! is_string ( $mappingRequest->configuration )) {
                $response ["status"] = "error";
                $response ["error"] = "no (valid) configuration in request";
              } else {
                $configItem = $mappingRequest->configuration;
                if (isset ( $configuration->config ["solr"] [$configItem] ) && isset ( $configuration->solr [$configItem] ["mtas"] ) && count ( $configuration->solr [$configItem] ["mtas"] ) > 0) {
                  $coreUrl = $configuration->config ["solr"] [$configItem] ["url"];
                  $ch = curl_init ( $coreUrl . "mtas?wt=json&action=file&file=" . urlencode ( $mappingRequest->file ) );
                  $options = array (
                      CURLOPT_RETURNTRANSFER => true 
                  );
                  curl_setopt_array ( $ch, $options );
                  $result = curl_exec ( $ch );
                  if (($data = json_decode ( $result )) && isset ( $data->file )) {
                    $response ["data"] = $data->file;
                  } else {
                    $response ["status"] = "error";
                    $response ["error"] = "problem with configuration " . $configItem;
                  }
                }
              }
            } else if ($mappingRequest->action == "mapping") {
              if (($configItem = $mappingRequest->configuration) && is_string ( $configItem )) {
                if (isset ( $configuration->config ["solr"] [$configItem] ) && isset ( $configuration->solr [$configItem] ["mtas"] ) && count ( $configuration->solr [$configItem] ["mtas"] ) > 0) {
                  $coreUrl = $configuration->config ["solr"] [$configItem] ["url"];
                  if (($mapping = $mappingRequest->mapping) && is_string ( $mapping )) {
                    if (($url = $mappingRequest->url) && is_string ( $url )) {
                      $response ["data"] = "todo";
                    } else if (($document = $mappingRequest->document) && is_string ( $document )) {
                      $data = array (
                          "configuration" => $mapping,
                          "document" => $document 
                      );
                      $ch = curl_init ( $coreUrl . "mtas?wt=json&action=mapping" );
                      $options = array (
                          CURLOPT_HTTPHEADER => array (
                              "Content-Type: application/json; charset=utf-8" 
                          ),
                          CURLOPT_RETURNTRANSFER => true,
                          CURLOPT_POST => true,
                          CURLOPT_POSTFIELDS => json_encode ( $data ) 
                      );
                      curl_setopt_array ( $ch, $options );
                      $dataString = curl_exec ( $ch );
                      if (is_object ( $dataString ) || trim ( $dataString ) == "") {
                        $response ["status"] = "error";
                        $response ["error"] = "no (valid) response";
                      } else if ($data = json_decode ( $dataString )) {
                        $mappingResult = array();
                        if(isset($data->mapping)) {
                          $mappingResult["mapping"] = $data->mapping;
                        }
                        $response ["data"] = $mappingResult;
                      } else {
                        $response ["status"] = "error";
                        $response ["error"] = "couldn't decode response";
                      }
                    }
                  } else {
                    $response ["status"] = "error";
                    $response ["error"] = "no (valid) mapping provided";
                  }
                } else {
                  $response ["status"] = "error";
                  $response ["error"] = "problem with configuration " . $configItem;
                }
              } else {
                $response ["status"] = "error";
                $response ["error"] = "no (valid) configuration provided";
              }
            } else {
              $response ["status"] = "error";
              $response ["error"] = "unrecognized action " . $mappingRequest->action;
            }
          }
        } else {
          $response ["status"] = "error";
          $response ["error"] = "no valid json";
        }
        echo json_encode ( $response );
      } else {
        // only allow post
        header ( "Location: " . $configuration->url ( "mapping" ) );
      }
      exit ();
    } else {
      // default
    }
  }
}
?>