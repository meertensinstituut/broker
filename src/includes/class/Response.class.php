<?php

/**
 * Broker
 * @package Broker
 */
namespace Broker;

/**
 * Processing response
 */
class Response {
  /**
   * Response
   *
   * @var unknown
   */
  private $response;
  /**
   * Response joins
   *
   * @var unknown
   */
  private $responseJoins;
  /**
   * Configuration
   *
   * @var unknown
   */
  private $configuration = null;
  /**
   * Collection
   *
   * @var \Broker\Collection
   */
  private $collection = null;
  /**
   * Cache
   *
   * @var \Broker\Cache
   */
  private $cache = null;
  /**
   * Constructor
   *
   * @param unknown $response          
   * @param unknown $responseJoins          
   * @param unknown $configuration          
   * @param \Broker\Cache $cache          
   * @param \Broker\Collection $collection          
   */
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
  /**
   * Process
   *
   * @return array
   */
  public function process() {
    $this->processFacetFieldJoins ();
    $this->processDocumentsJoins ();
    return $this->response;
  }
  /**
   * Process facet field joins
   */
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
  /**
   * Collect facet field values
   *
   * @param array $list          
   * @return array
   */
  private function collectFacetFieldValues($list) {
    $values = array ();
    for($i = 0; $i < count ( $list ); $i += 2) {
      $values [] = $list [$i];
    }
    return $values;
  }
  /**
   * Collect join facet field values
   *
   * @param array $values          
   * @param unknown $facetFieldJoin          
   * @return array
   */
  private function collectJoinFacetFieldValues($values, $facetFieldJoin) {
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
    $subParser = new \Broker\Parser ( $subRequest, $this->configuration, $this->cache, $this->collection, null, null );
    // get data
    try {
      $solr = new \Broker\Solr ( $subParser->getConfiguration (), $subParser->getUrl (), "select", $subParser->getRequest (), null, implode ( ",", $subParser->getShards () ), $this->cache );
      $solrResponse = $solr->getResponse ();
      if ($solrResponse && is_object ( $solrResponse )) {
        if (! isset ( $solrResponse->error ) && isset ( $solrResponse->response ) && isset ( $solrResponse->response->docs )) {
          $subResponse = array ();
          $subResponse ["status"] = "OK";
          $subResponse ["response"] = clone $solrResponse;
          $subResponseObject = new \Broker\Response ( $subResponse, $subParser->getResponseJoins (), $this->configuration, $this->cache, $this->collection );
          $subResponse = $subResponseObject->process ();
          return $subResponse ["response"]->response->docs;
        }
      }
    } catch ( \Broker\SolrException $se ) {
      // do nothing
    } catch ( \Exception $e ) {
      // do nothing
    }
    return array ();
  }
  /**
   * Update facet field values
   *
   * @param array $list          
   * @param string $to          
   * @param array $updates          
   * @return array
   */
  private function updateFacetFieldValues($list, $to, $updates) {
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
  /**
   * Process documents joins
   */
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
  /**
   * Collect documents values
   *
   * @param array $documents          
   * @param string $from          
   * @return array
   */
  private function collectDocumentsValues($documents, $from) {
    $values = array ();
    foreach ( $documents as $document ) {
      if (isset ( $document->{$from} ) && is_string ( $document->{$from} )) {
        $values [] = $document->{$from};
      } else if (isset ( $document->{$from} ) && is_array ( $document->{$from} )) {
        foreach($document->{$from} AS $subValue) {
          if($subValue && is_string($subValue)) {
            $values [] = $subValue;
          }
        }
      }
    }
    return $values;
  }
  /**
   * Collect join documents values
   *
   * @param array $values          
   * @param unknown $documentsJoin          
   * @return array
   */
  private function collectJoinDocumentsValues($values, $documentsJoin) {
    if($values && is_array($values) && count($values)>0) {      
      $allFields = $documentsJoin->fields;
      $allFields [] = $documentsJoin->to;
      $subRequest = new \stdClass ();
      $subRequest->configuration = $documentsJoin->configuration;
      $subRequest->filter = array ();
      $filter = new \stdClass ();
      $filter->condition = new \stdClass ();
      $filter->condition->type = "equals";
      $filter->condition->field = $documentsJoin->to;
      $filter->condition->value = $values;
      $subRequest->filter [] = $filter;
      if (isset ( $documentsJoin->filter )) {
        $subRequest->filter [] = $documentsJoin->filter;
      }
      if (isset ( $documentsJoin->condition )) {
        $subRequest->condition = $documentsJoin->condition;
      } 
      $subRequest->response = new \stdClass ();
      $subRequest->response->documents = new \stdClass ();
      $subRequest->response->documents->start = 0;
      $subRequest->response->documents->rows = 1000000;
      $subRequest->response->documents->fields = $allFields;
      $subParser = new \Broker\Parser ( $subRequest, $this->configuration, $this->cache, $this->collection, null, null );
      // get data
      if (count ( $subParser->getErrors () ) == 0) {
        try {
          $solr = new \Broker\Solr ( $subParser->getConfiguration (), $subParser->getUrl (), "select", $subParser->getRequest (), null, implode ( ",", $subParser->getShards () ), $this->cache );
          $solrResponse = $solr->getResponse ();
          if ($solrResponse && is_object ( $solrResponse )) {
            if (! isset ( $solrResponse->error ) && isset ( $solrResponse->response ) && isset ( $solrResponse->response->docs )) {
              $subResponse = array ();
              $subResponse ["status"] = "OK";
              $subResponse ["response"] = clone $solrResponse;
              $subResponseObject = new \Broker\Response ( $subResponse, $subParser->getResponseJoins (), $this->configuration, $this->cache, $this->collection );
              $subResponse = $subResponseObject->process ();
              return $subResponse ["response"]->response->docs;
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
  }
  /**
   * Update documents
   *
   * @param array $documents          
   * @param string $from          
   * @param string $to          
   * @param string $name          
   * @param array $updates          
   * @return array
   */
  private function updateDocuments($documents, $from, $to, $name, $updates) {
    if($updates) {
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
    }
    return $documents;
  }
  /**
   * Create solr status
   *
   * @param string $id          
   * @param string $description          
   * @return array
   */
  static function createSolrStatus($id, $description) {
    $result = array ();
    $result ["description"] = $description;
    $result ["data"] = array ();
    $result ["data"] [$id] = date ( "h:i:s" ) . " - " . $description;
    return $result;
  }
}

?>