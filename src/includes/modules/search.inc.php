<?php
/**
 * Module search
 * @package Broker
 */
if (strtoupper ( $_SERVER ['REQUEST_METHOD'] ) == "POST") {
  header ( "Content-Type: text/javascript; charset=utf-8" );
  header ( "Access-Control-Allow-Origin: *" );
  header ( "Access-Control-Allow-Headers: content-type" );
  $brokerRequest = file_get_contents ( "php://input" );
  $response = array ();
  $response ["status"] = "ERROR";
  try {
    $parser = new \Broker\Parser ( $brokerRequest, $configuration, null, null, null );
    header ( "X-Broker-errors: " . count ( $parser->getErrors () ) );
    header ( "X-Broker-warnings: " . count ( $parser->getWarnings () ) );
    header ( "X-Broker-shards: " . count ( $parser->getShards () ) );
    header ( "X-Broker-configuration: " . urlencode ( $parser->getConfiguration () ) );
    if (count ( $parser->getErrors () ) > 0) {
      header ( "HTTP/1.0 400 Bad request" );
      $response ["brokerErrors"] = array (
          "data" => $parser->getErrors () 
      );
      $response ["solrStatus"] = \Broker\Response::createSolrStatus ( "broker", "request couldn't be parsed by broker" );
      if (count ( $parser->getWarnings () ) > 0) {
        $response ["brokerWarnings"] = array (
            "data" => $parser->getWarnings () 
        );
      }
      echo json_encode ( $response );
    } else {
      $collectionIds = $parser->getCollectionIds ();
      if (count ( $collectionIds ) > 0) {
        $collection = $parser->getCollection ();
        foreach ( $collectionIds as $collectionId ) {
          $checkInfo = $collection->check ( $collectionId );
          if (! $checkInfo) {
            $response ["error"] = "collection " . $collectionId . " not found";
            return $response;
          } else if (! $checkInfo ["initialised"]) {
            $response ["error"] = "collection " . $collectionId . " not initialised";
            return $response;
          } else if ($checkInfo ["check"]) {
            if (! $collection->doCheck ( $collectionId )) {
              $response ["error"] = "collection " . $collectionId . " couldn't be checked";
              return $response;
            }
          }
        }
      }
      try {
        $solr = new \Broker\Solr ( $parser->getConfiguration (), $parser->getUrl (), "select", $parser->getRequest (), $parser->getShards () != null ? implode ( ",", $parser->getShards () ) : null, $parser->getCache () );
        $solrResponse = $solr->getResponse ();
        if ($solrResponse && is_object ( $solrResponse )) {
          if (isset ( $solrResponse->error )) {
            $response ["error"] = $solrResponse->error;
          } else if (isset ( $solrResponse->response )) {
            $response ["status"] = "OK";
            $response ["response"] = clone $solrResponse;
            $responseObject = new \Broker\Response ( $response, $parser->getResponseJoins (), $configuration, $parser->getCache (), null );
            $response = $responseObject->process ();
          } else {
            $response ["error"] = $solrResponse;
          }
        } else {
          $response ["error"] = $solrResponse;
        }
      } catch ( \Broker\SolrException $se ) {
        $response ["error"] = $se->getMessage ();
      } catch ( \Exception $e ) {
        $response ["error"] = $solr->getResponse ();
      }
      if ($response ["status"] !== "OK") {
        header ( "HTTP/1.0 500 Internal Server Error" );
        header ( "X-Broker-errors: " . (count ( $parser->getErrors () ) + 1) );
        $response ["solrStatus"] = \Broker\Response::createSolrStatus ( "broker", "request parsed by broker" );
        if (count ( $parser->getWarnings () ) > 0) {
          $response ["brokerWarnings"] = array (
              "data" => $parser->getWarnings () 
          );
        }
        echo json_encode ( $response );
      } else {
        if (isset ( $response ["response"] )) {
          if (isset ( $_SERVER ["HTTP_ACCEPT_ENCODING"] ) && strpos ( $_SERVER ["HTTP_ACCEPT_ENCODING"], "gzip" ) !== false) {
            header ( "Content-Encoding: gzip" );
            $content = gzencode ( json_encode ( $response ["response"] ) );
          } else {
            $content = json_encode ( $response ["response"] );
          }
          header ( "Vary: Accept-Encoding" );
          header ( "Content-Length: " . strlen ( $content ) );
          echo $content;
        }
      }
    }
  } catch ( Exception $e ) {
    header ( "HTTP/1.0 400 Bad request" );
    $response ["brokerErrors"] = array (
        "data" => $e->getMessage () 
    );
    $response ["solrStatus"] = \Broker\Response::createSolrStatus ( "broker", "request couldn't be parsed by broker" );
    echo json_encode ( $response );
  }
  exit ();
}

?>