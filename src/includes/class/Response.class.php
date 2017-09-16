<?php

namespace Broker;

class Response {
  private $response;
  private $responseJoins;
  private $configuration = null;
  private $collection = null;
  private $cache = null;
  public function __construct($response, $responseJoins, $configuration, $cache, $collection) {
    $this->response = $response;
    $this->configuration = $configuration;
    $this->cache = $cache;
    $this->collection = $collection;
    if (isset ( $this->response ["response"]->responseHeader )) {
      unset ( $this->response ["response"]->responseHeader );
    }
    $this->responseJoins = $responseJoins;
  }
  public function process(): array {
    $this->processFacetFieldJoins ();
    $this->processDocumentsJoins ();
    return $this->response;
  }
  private function processFacetFieldJoins() {
    if ($this->responseJoins && is_object ( $this->responseJoins ) && isset ( $this->responseJoins->facetfield )) {
      $facetfieldJoins = $this->responseJoins->facetfield;      
      foreach ( $facetfieldJoins as $facetfieldJoin ) {
        if (isset ( $this->response ["response"]->facet_counts->facet_fields->{$facetfieldJoin->key} )) {
          $values = $this->collectFacetFieldValues ( $this->response ["response"]->facet_counts->facet_fields->{$facetfieldJoin->key} );
          $updateValues = $this->collectJoinFacetFieldValues ( $values, $facetfieldJoin );
          $this->response ["response"]->facet_counts->facet_fields->{$facetfieldJoin->key} = $this->updateFacetFieldValues ( $this->response ["response"]->facet_counts->facet_fields->{$facetfieldJoin->key}, $facetfieldJoin->to, $updateValues );
        }
      }
    }
  }
  private function collectFacetFieldValues(array $list): array {
    $values = array ();
    for($i = 0; $i < count ( $list ); $i += 2) {
      $values [] = $list [$i];
    }
    return $values;
  }
  private function collectJoinFacetFieldValues(array $values, $facetFieldJoin): array {
    $allFields = $facetFieldJoin->fields;
    $allFields [] = $facetFieldJoin->to;
    $subRequest = new \stdClass ();
    $subRequest->configuration = $facetFieldJoin->configuration;
    $subRequest->filter = new \stdClass ();
    $subRequest->filter->condition = new \stdClass ();
    $subRequest->filter->condition->type = "equals";
    $subRequest->filter->condition->field = $facetFieldJoin->to;
    $subRequest->filter->condition->value = $values;
    $subRequest->response = new \stdClass ();
    $subRequest->response->documents = new \stdClass ();
    $subRequest->response->documents->start = 0;
    $subRequest->response->documents->rows = 1000000;
    $subRequest->response->documents->fields = $allFields;
    $subParser = new \Broker\Parser ( $subRequest, $this->configuration, $this->cache, $this->collection, null );
    // get data
    try {
      $solr = new \Broker\Solr ( $subParser->getConfiguration(), $subParser->getUrl (), "select", $subParser->getRequest (), implode ( ",", $subParser->getShards () ), $this->cache );
      $solrResponse = $solr->getResponse ();
      if ($solrResponse && is_object ( $solrResponse )) {
        if (! isset ( $solrResponse->error ) && isset ( $solrResponse->response ) && isset ( $solrResponse->response->docs )) {
          $subResponse = array();
          $subResponse ["status"] = "OK";
          $subResponse ["response"] = clone $solrResponse;
          $subResponse = (new \Broker\Response($subResponse, $subParser->getResponseJoins(), $this->configuration, $this->cache, $this->collection))->process();
          return $subResponse["response"]->response->docs;          
        }
      }
    } catch ( \Broker\SolrException $se ) {
      // do nothing
    } catch ( \Exception $e ) {
      // do nothing
    }
    return array ();
  }
  private function updateFacetFieldValues(array $list, string $to, array $updates): array {
    for($i = 0; $i < count ( $list ); $i += 2) {
      $key = $list [$i];
      $value = array ();
      foreach ( $updates as $update ) {
        if (isset ( $update->{$to} ) && (is_string ( $update->{$to} ) && $update->{$to} == $key) || (is_array ( $update->{$to} ) && in_array ( $key, $update->{$to} ))) {
          $value [] = $update;
        }
      }
      $list [$i] = array (
          "key" => $key,
          "value" => $value 
      );
    }
    return $list;
  }
  private function processDocumentsJoins() {
    if ($this->responseJoins && is_object ( $this->responseJoins ) && isset ( $this->responseJoins->documents )) {
      $documentsJoins = $this->responseJoins->documents;
      foreach ( $documentsJoins as $documentsJoin ) {
        if (isset ( $this->response ["response"]->response->docs )) {
          $values = $this->collectDocumentsValues ( $this->response ["response"]->response->docs, $documentsJoin->from );
          $updateValues = $this->collectJoinDocumentsValues ( $values, $documentsJoin );
          $this->response ["response"]->response->docs = $this->updateDocuments ( $this->response ["response"]->response->docs, $documentsJoin->from, $documentsJoin->to, $documentsJoin->name, $updateValues );
        }
      }
    } 
  }
  private function collectDocumentsValues(array $documents, string $from): array {
    $values = array ();
    foreach ( $documents as $document ) {
      if (isset ( $document->{$from} ) && is_string ( $document->{$from} )) {
        $values [] = $document->{$from};
      }
    }
    return $values;
  }
  private function collectJoinDocumentsValues(array $values, $documentsJoin): array {
    $allFields = $documentsJoin->fields;
    $allFields [] = $documentsJoin->to;  
    $subRequest = new \stdClass ();
    $subRequest->configuration = $documentsJoin->configuration;
    $subRequest->filter = array();
    $filter = new \stdClass ();
    $filter->condition = new \stdClass ();
    $filter->condition->type = "equals";
    $filter->condition->field = $documentsJoin->to;
    $filter->condition->value = $values;
    $subRequest->filter[] = $filter;
    if(isset($documentsJoin->filter)) {
      $subRequest->filter[] = $documentsJoin->filter;      
    }
    if(isset($documentsJoin->condition)) {
      $subRequest->condition = $documentsJoin->condition;
    }
    $subRequest->response = new \stdClass ();
    $subRequest->response->documents = new \stdClass ();
    $subRequest->response->documents->start = 0;
    $subRequest->response->documents->rows = 1000000;
    $subRequest->response->documents->fields = $allFields;
    $subParser = new \Broker\Parser ( $subRequest, $this->configuration, $this->cache, $this->collection, null );
    // get data
    if(count($subParser->getErrors())==0) {
      try {
        $solr = new \Broker\Solr ( $subParser->getConfiguration(), $subParser->getUrl (), "select", $subParser->getRequest (), implode ( ",", $subParser->getShards () ), $this->cache );
        $solrResponse = $solr->getResponse ();
        if ($solrResponse && is_object ( $solrResponse )) {
          if (! isset ( $solrResponse->error ) && isset ( $solrResponse->response ) && isset ( $solrResponse->response->docs )) {
            $subResponse = array();
            $subResponse ["status"] = "OK";
            $subResponse ["response"] = clone $solrResponse;
            $subResponse = (new \Broker\Response($subResponse, $subParser->getResponseJoins(), $this->configuration, $this->cache, $this->collection))->process();
            return $subResponse["response"]->response->docs;
          } 
        }
      } catch ( \Broker\SolrException $se ) {
        // do nothing
      } catch ( \Exception $e ) {
        // do nothing
      }
    } 
    return array ();
  }
  private function updateDocuments(array $documents, string $from, string $to, string $name, array $updates): array {
    foreach ( $documents as $document ) {
      if (is_object ( $document )) {
        if (isset ( $document->{$from} ) && (is_string ( $document->{$from} ) || is_array ( $document->{$from} ))) {
          $key = $document->{$from};
          if (! isset ( $document->{$name} )) {
            $document->{$name} = array ();
          }
          if (is_array ( $document->{$name} )) {
            foreach ( $updates as $update ) {
              if (is_string ( $key )) {
                if (isset ( $update->{$to} ) && (is_string ( $update->{$to} ) && $update->{$to} == $key) || (is_array ( $update->{$to} ) && in_array ( $key, $update->{$to} ))) {
                  $document->{$name} [] = $update;
                }
              } else if (is_array ( $key )) {
                if (isset ( $update->{$to} ) && (is_string ( $update->{$to} ) && in_array ( $update->{$to}, $key )) || (is_array ( $update->{$to} ) && count ( array_intersect ( $key, $update->{$to} ) ) > 0)) {
                  $document->{$name} [] = $update;
                }
              }
            }
          }
        }
      }
    }
    return $documents;
  }
}

?>