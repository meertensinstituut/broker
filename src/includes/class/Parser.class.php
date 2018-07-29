<?php

/**
 * Broker
 * @package Broker
 */
namespace Broker;

/**
 * Parser json request
 */
class Parser {
  /**
   * Request to broker
   *
   * @var string
   */
  private $brokerRequest = null;
  /**
   * Status key
   * 
   * @var string
   */
  private $statusKey = null;
  /**
   * Url solr
   *
   * @var string
   */
  private $solrUrl = null;
  /**
   * Solr shards
   *
   * @var array
   */
  private $solrShards = null;
  /**
   * Solr request
   *
   * @var string
   */
  private $solrRequest = null;
  /**
   * Solr request addition
   *
   * @var string
   */
  private $solrRequestAddition = null;
  /**
   * Solr configuration
   *
   * @var string
   */
  private $solrConfiguration = null;
  /**
   * Collection ids
   *
   * @var array
   */
  private $collectionIds = array ();
  /**
   * Response joins
   *
   * @var array
   */
  private $responseJoins = null;
  /**
   * Warnings
   *
   * @var array
   */
  private $warnings = array ();
  /**
   * Errors
   *
   * @var array errors
   */
  private $errors = array ();
  /**
   * Cache
   *
   * @var \Broker\Cache
   */
  private $cache = null;
  /**
   * Cache enables
   *
   * @var boolean
   */
  private $cacheEnabled = true;
  /**
   * Configuration
   *
   * @var object
   */
  private $configuration = null;
  /**
   * Expansion cache
   *
   * @var \Broker\ExpansionCache
   */
  private $expansionCache = null;
  /**
   * Configurations
   *
   * @var array
   */
  private $__configurations = array ();
  /**
   * Collection
   *
   * @var \Broker\Collection
   */
  private $collection = null;
  /**
   * Constructor
   *
   * @param object $request          
   * @param array $configuration          
   * @param \Broker\Cache $cache          
   * @param \Broker\Collection $collection          
   * @param \Broker\ExpansionCache $expansionCache          
   * @param string $statusKey         
   * @throws \Exception
   */
  public function __construct($request, $configuration, $cache, $collection, $expansionCache, $statusKey) {
    if ($collection != null) {
      $this->collection = $collection;
    }
    $this->cache = $cache;
    $this->brokerRequest = is_string ( $request ) ? json_decode ( $request, false ) : $request;
    if ($statusKey != null && (! is_string ( $statusKey ) || strlen ( $statusKey ) < 10)) {
      throw new \Exception ( "Could not parse request: invalid key" );
    } else if($statusKey!=null) {
      $this->statusKey = $statusKey;      
    }
    $this->configuration = $configuration;
    $this->responseJoins = new \stdClass ();
    if ($this->brokerRequest != null && json_last_error () == JSON_ERROR_NONE) {
      $this->parse ();
    } else {
      throw new \Exception ( "Could not parse request: invalid or empty json" );
    }
  }
  /**
   * Get solr request
   *
   * @return string
   */
  public function getRequest() {
    if (count ( $this->errors ) == 0) {
      return $this->solrRequest;
    } else {
      return null;
    }
  }
  /**
   * Get addition solr request
   *
   * @return string
   */
  public function getRequestAddition() {
    if (count ( $this->errors ) == 0) {
      return $this->solrRequestAddition;
    } else {
      return null;
    }
  }
  /**
   * Get status key
   *
   * @return string
   */  
  public function getStatusKey() {
    return $this->statusKey;
  }
  /**
   * Get solr url
   *
   * @return string
   */
  public function getUrl() {
    if (count ( $this->errors ) == 0) {
      return $this->solrUrl;
    } else {
      return null;
    }
  }
  /**
   * Get (or create) cache
   *
   * @return \Broker\Cache
   */
  public function getCache() {
    if ($this->cache == null && $this->cacheEnabled) {
      $this->cache = new \Broker\Cache ( SITE_CACHE_DATABASE_DIR, $this->configuration );
    }
    return $this->cache;
  }
  /**
   * Get shards
   *
   * @return array
   */
  public function getShards() {
    if (count ( $this->errors ) == 0) {
      return $this->solrShards;
    } else {
      return null;
    }
  }
  /**
   * Get solr configuration
   *
   * @return array
   */
  public function getConfiguration() {
    if (count ( $this->errors ) == 0) {
      return $this->solrConfiguration;
    } else {
      return null;
    }
  }
  /**
   * Get errors
   *
   * @return array
   */
  public function getErrors() {
    return $this->errors;
  }
  /**
   * Get warnings
   *
   * @return array
   */
  public function getWarnings() {
    return $this->warnings;
  }
  /**
   * Get (or create) collection
   *
   * @return \Broker\Collection
   */
  public function getCollection() {
    if ($this->collection == null) {
      $this->collection = new \Broker\Collection ( SITE_CACHE_DATABASE_DIR, $this->configuration );
    }
    return $this->collection;
  }
  /**
   * Get collection ids
   *
   * @return array
   */
  public function getCollectionIds() {
    return $this->collectionIds;
  }
  /**
   * Get response joins
   *
   * @return object
   */
  public function getResponseJoins() {
    return $this->responseJoins;
  }
  /**
   * parse
   */
  private function parse() {
    $this->solrConfiguration = null;
    $__facetQueries = array ();
    $__mtasStats = array ();
    $requestList = array ();
    $requestAdditionalList = array (); 
    //$requestAdditionalList[] = "debug=true";
    foreach ( $this->brokerRequest as $key => $value ) {
      if ($key == "condition") {
        $this->brokerRequest->condition = $this->checkCondition ( $value );
      } else if ($key == "filter") {
        $this->brokerRequest->filter = $this->checkFilters ( $value );
      } else if ($key == "response") {
        $this->brokerRequest->response = $this->checkResponse ( $value );
      } else if ($key == "sort") {
        $this->brokerRequest->sort = $this->checkSort ( $value );
      } else if ($key == "debug") {
        $this->brokerRequest->debug = $this->checkDebug ( $value );
      } else if ($key == "cache") {
        $this->brokerRequest->cache = $this->checkCache ( $value );
      } else if ($key == "timeAllowed") {
        $this->brokerRequest->timeAllowed = $this->checkTimeAllowed ( $value );
      } else if ($key == "configuration") {
        if ($value && is_string ( $value )) {
          $this->solrConfiguration = $value;
        } else {
          $this->errors [] = "configuration - configuration should be a string";
        }
      } else {
        $this->warnings [] = "request - '{$key}' not recognized";
      }
    }
    if (count ( $this->errors ) == 0) {
      // compute configuration
      if (($config = $this->computeConfiguration ( $this->solrConfiguration )) == null) {
        if ($this->solrConfiguration) {
          $this->errors [] = "solr - configuration '{$this->solrConfiguration}' does not match all requirements";
        } else {
          $this->errors [] = "solr - could not find configuration matching all requirements";
        }
        $this->solrUrl = null;
        $this->solrShards = null;
      } else {
        $tmpList = $this->configuration->getConfig ( "solr" );
        $__config = $tmpList [$config];
        $this->solrUrl = isset ( $__config ["url"] ) ? $__config ["url"] : "";
        $this->solrShards = isset ( $__config ["shards"] ) ? $__config ["shards"] : "";
        $this->solrConfiguration = $config;
        //check timeAllowed for configuration
        if(isset($this->brokerRequest->timeAllowed)) {
          if(isset($__config ["timeAllowed"]) && (intval($__config ["timeAllowed"])>0)) {
            $this->brokerRequest->timeAllowed = min($this->brokerRequest->timeAllowed, intval($__config ["timeAllowed"]));
          }
        } else if(isset($__config ["timeAllowed"]) && (intval($__config ["timeAllowed"])>0)) {
          $this->brokerRequest->timeAllowed = intval($__config ["timeAllowed"]);
        }
      }
      // compute query
      if (isset ( $this->brokerRequest->filter )) {
        list ( $this->brokerRequest->filter, $filter_requestList, $filter_facetQueries, $filter_mtasStats ) = $this->parseFilters ( $this->brokerRequest->filter, $this->solrConfiguration );
        if ($filter_requestList && count ( $filter_requestList ) > 0) {
          $requestList = array_merge ( $requestList, $filter_requestList );
          $__facetQueries = array_merge ( $__facetQueries, $filter_facetQueries );
          $__mtasStats = array_merge ( $__mtasStats, $filter_mtasStats );
        }
      }
      if (isset ( $this->brokerRequest->condition )) {
        $this->brokerRequest->condition = $this->parseCondition ( $this->brokerRequest->condition, $this->solrConfiguration );
        if ($this->brokerRequest->condition && is_object ( $this->brokerRequest->condition ) && isset ( $this->brokerRequest->condition->__query )) {
          $requestList [] = "q=" . urlencode ( $this->brokerRequest->condition->__query );
          $__facetQueries = array_merge ( $__facetQueries, $this->brokerRequest->condition->__facetQueries );
          $__mtasStats = array_merge ( $__mtasStats, $this->brokerRequest->condition->__mtasStats );
        }
      } else {
        $requestList [] = "q=*:*";
      }
      $__facetQueriesKeyList = array ();
      $this->checkResponseFacetQueries ( $__facetQueries, $__facetQueriesKeyList );
      if (count ( $this->errors ) == 0) {
        if (isset ( $this->brokerRequest->response ) || $this->statusKey != null) {
          if (! isset ( $this->brokerRequest->response )) {
            $this->brokerRequest->response = null;
          }
          $this->brokerRequest->response = $this->parseResponse ( $this->brokerRequest->response, $__facetQueries, $__mtasStats );
          if ($this->brokerRequest->response) {
            $requestList = array_merge ( $requestList, $this->brokerRequest->response->__requestList );
            $requestAdditionalList = array_merge ( $requestAdditionalList, $this->brokerRequest->response->__requestAdditionalList );            
          }
        }
        if (isset ( $this->brokerRequest->sort )) {
          $requestSort = $this->parseSort ( $this->brokerRequest->sort );
          if ($requestSort) {
            $requestList [] = $requestSort;
          }
        }
        if (isset ( $this->brokerRequest->debug )) {
          $requestDebug = $this->parseDebug ( $this->brokerRequest->debug );
          if ($requestDebug) {
            $requestList [] = $requestDebug;
          }
        }
        if (isset ( $this->brokerRequest->cache )) {
          $requestCache = $this->parseCache ( $this->brokerRequest->cache );
          if ($requestCache) {
            $requestList [] = $requestCache;
          }
        }
        if (isset ( $this->brokerRequest->timeAllowed )) {
          $requestTimeAllowed = $this->parseTimeAllowed ( $this->brokerRequest->timeAllowed );
          if ($requestTimeAllowed) {
            $requestAdditionalList [] = $requestTimeAllowed;
          }
        }
      }
    }
    $requestList [] = "wt=json";
    $requestList [] = "echoParams=none";
    $this->solrRequest = implode ( "&", $requestList );
    $this->solrRequestAddition = (count($requestAdditionalList)>0)?implode("&", $requestAdditionalList):null;    
  }
  /**
   * Check cache in request
   *
   * @param object $object          
   * @return object
   */
  private function checkCache($object) {
    if (is_bool ( $object )) {
      $this->cacheEnabled = $object;
      return $object;
    } else {
      $this->errors [] = "cache - unexpected type";
      return null;
    }
  }
  /**
   * Check timeAllowed in request
   *
   * @param object $object
   * @return object
   */
  private function checkTimeAllowed($object) {
    if (is_numeric ( $object )) {
      $this->timeAllowed = $object;
      return $object;
    } else {
      $this->errors [] = "timeAllowed - unexpected type";
      return null;
    }
  }
  /**
   * Check debug in request
   *
   * @param object $object          
   * @return object
   */
  private function checkDebug($object) {
    if ($object && is_string ( $object )) {
      return $object;
    } else {
      $this->errors [] = "debug - unexpected type";
      return null;
    }
  }
  /**
   * Check sort in request
   *
   * @param object $object          
   * @return object
   */
  private function checkSort($object) {
    if ($object && is_array ( $object ) && count ( $object ) > 0) {
      for($i = 0; $i < count ( $object ); $i ++) {
        $object [$i] = $this->checkSortitem ( $object [$i] );
      }
      return $object;
    } else {
      $this->errors [] = "sort - unexpected type";
      return null;
    }
  }
  /**
   * Check sortItem in sort
   *
   * @param object $object          
   * @return object
   */
  private function checkSortitem($object) {
    if ($object && is_object ( $object )) {
      if (isset ( $object->field ) && is_string ( $object->field )) {
        $configurations = $this->getConfigurationsForField ( $object->field );
        if (count ( $configurations ) > 0) {
          $this->__configurations [] = $configurations;
        } else if (preg_match ( "/^(\"[^\"]+\"|'[^']+')$/", $object->field ) || preg_match ( "/^[^\(]+\(.*\)$/", $object->field )) {
          // ignore, function
        } else if ($object->field !== "score") {
          $this->warnings [] = "sort - sortitem - field '" . $object->field . "' not found in any configuration";
        }
        $ignoreItems = array (
            "field" 
        );
        if (isset ( $object->direction )) {
          $ignoreItems [] = "direction";
          if (! is_string ( $object->direction ) || ($object->direction != "asc" && $object->direction != "desc")) {
            $this->warnings [] = "sort - sortitem - direction should be \"asc\" or \desc\"";
            unset ( $object->direction );
          }
        }
        foreach ( $object as $key => $value ) {
          if (! in_array ( $key, $ignoreItems )) {
            $this->warnings [] = "sort - sortitem - {$key} not expected";
          }
        }
      } else {
        $this->errors [] = "sort - sortitem - no (valid) field";
      }
      return $object;
    } else {
      $this->errors [] = "sort - sortitem - unexpected type";
      return null;
    }
  }
  /**
   * Check response in request
   *
   * @param object $object          
   * @return object
   */
  private function checkResponse($object) {
    if ($object && is_object ( $object )) {
      foreach ( $object as $key => $value ) {
        if ($key == "documents") {
          $object->documents = $this->checkResponseDocuments ( $value );
        } else if ($key == "facets") {
          $object->facets = $this->checkResponseFacets ( $value );
        } else if ($key == "stats") {
          $object->stats = $this->checkResponseStats ( $value );
        } else if ($key == "mtas") {
          $object->mtas = $this->checkResponseMtas ( $value );
        } else {
          $this->warnings [] = "response - {$key} not expected";
        }
      }
      return $object;
    } else {
      $this->warnings [] = "response - unexpected type";
      return null;
    }
  }
  /**
   * Check documents in response
   *
   * @param object $object          
   * @return object
   */
  private function checkResponseDocuments($object) {
    if ($object && is_object ( $object )) {
      foreach ( $object as $key => $value ) {
        if ($key == "fields") {
          if ($value != null && is_array ( $value )) {
            $__fields = array ();
            for($i = 0; $i < count ( $value ); $i ++) {
              $item = $value [$i];
              if ($item != null) {
                if (is_string ( $item )) {
                  $__fields [] = $item;
                  if ($item == "*" || preg_match ( "/^\[[^\]]*\]$/", $item )) {
                    // do nothing
                  } else {
                    if (preg_match ( "/^([^:]+):([^:]+)$/", $item, $match )) {
                      $checkItem = $match [2];
                    } else {
                      $checkItem = $item;
                    }
                    if (filter_var ( $checkItem, FILTER_VALIDATE_INT ) !== false || filter_var ( $checkItem, FILTER_VALIDATE_FLOAT ) !== false || preg_match ( "/^(\"[^\"]+\"|'[^']+')$/", $checkItem ) || preg_match ( "/^[^\(]+\(.*\)$/", $checkItem )) {
                      // function, do nothing
                    } else {
                      $configurations = $this->getConfigurationsForField ( $checkItem );
                      if (count ( $configurations ) > 0) {
                        $this->__configurations [] = $configurations;
                      } else if ($checkItem !== "score") {
                        $this->warnings [] = "documents - field '" . $checkItem . "' not found in any configuration";
                      }
                    }
                  }
                } else if (is_object ( $item )) {
                  $value [$i] = $this->checkResponseDocumentsJoin ( $item );
                } else {
                  $this->errors [] = "documents - unrecognized field type";
                }
              }
            }
          } else {
            $this->errors [] = "documents - fields should be array";
          }
        } else if ($key == "start") {
          if ($value === null || ! is_int ( $value )) {
            $this->errors [] = "documents - start should be integer";
          }
        } else if ($key == "rows") {
          if ($value === null || ! is_int ( $value )) {
            $this->errors [] = "documents - rows should be integer";
          }
        } else {
          $this->warnings [] = "documents - {$key} not expected";
        }
      }
      return $object;
    } else {
      $this->warnings [] = "documents - unexpected type";
      return null;
    }
  }
  /**
   * Check documents join in response
   *
   * @param object $object          
   * @return object
   */
  private function checkResponseDocumentsJoin($object) {
    if (is_object ( $object ) && isset ( $object->type ) && is_string ( $object->type ) && $object->type == "join") {
      if (! isset ( $object->name ) || ! is_string ( $object->name )) {
        $this->errors [] = "documents - join - no (valid) name provided for join";
      }
      if (! isset ( $object->to ) || ! is_string ( $object->to )) {
        $this->errors [] = "documents - join - no (valid) to field provided for join";
      }
      if (! isset ( $object->from ) || ! is_string ( $object->from )) {
        $this->errors [] = "documents - join - no (valid) from field provided for join";
      }
      if (! isset ( $object->fields ) || ! is_array ( $object->fields ) || count ( $object->fields ) == 0) {
        $this->errors [] = "documents - join - no (valid) fields provided for join";
      } else {
        foreach ( $object->fields as $fieldsItem ) {
          if (! is_string ( $fieldsItem ) && ! is_object ( $fieldsItem )) {
            $this->errors [] = "documents - join - invalid field provided for join";
          }
        }
      }
      foreach ( $object as $key => $value ) {
        if ($key == "type" || $key == "name" || $key == "to" || $key == "from" || $key == "fields") {
          // ignore
        } else if ($key == "configuration") {
          if (! is_string ( $value )) {
            $this->errors [] = "documents - join - invalid configuration provided for join";
          }
        } else if ($key == "filter" || $key == "condition") {
          if (! is_object ( $value )) {
            $this->errors [] = "documents - join - invalid {$key} provided for join";
          }
        } else {
          $this->warnings [] = "documents - join - {$key} not expected in join";
        }
      }
      return $object;
    } else {
      return null;
    }
  }
  /**
   * Check facets in response
   *
   * @param object $object          
   * @return object
   */
  private function checkResponseFacets($object) {
    if (($object && is_object ( $object ))) {
      $facetFieldKeyList = array ();
      $facetQueryKeyList = array ();
      $facetRangeKeyList = array ();
      $facetPivotKeyList = array ();
      $facetHeatmapKeyList = array ();
      foreach ( $object as $key => $value ) {
        if ($key == "facetfields") {
          if ($value != null && is_array ( $value )) {
            list ( $object->facetfields, $facetFieldKeyList ) = $this->checkResponseFacetFields ( $object->facetfields, $facetFieldKeyList );
          } else {
            $this->errors [] = "facets - facetfields should be array";
            unset ( $object->{$key} );
          }
        } else if ($key == "facetqueries") {
          if ($value != null && is_array ( $value )) {
            list ( $object->facetqueries, $facetQueryKeyList ) = $this->checkResponseFacetQueries ( $object->facetqueries, $facetQueryKeyList );
          } else {
            $this->errors [] = "facets - facetqueries should be array";
            unset ( $object->{$key} );
          }
        } else if ($key == "facetranges") {
          if ($value != null && is_array ( $value )) {
            list ( $object->facetranges, $facetRangeKeyList ) = $this->checkResponseFacetRanges ( $object->facetranges, $facetRangeKeyList );
          } else {
            $this->errors [] = "facets - facetranges should be array";
            unset ( $object->{$key} );
          }
        } else if ($key == "facetpivots") {
          if ($value != null && is_array ( $value )) {
            list ( $object->facetpivots, $facetPivotKeyList ) = $this->checkResponseFacetPivots ( $object->facetpivots, $facetPivotKeyList );
          } else {
            $this->errors [] = "facets - facetpivots should be array";
            unset ( $object->{$key} );
          }
        } else if ($key == "facetheatmaps") {
          if ($value != null && is_array ( $value )) {
            list ( $object->facetheatmaps, $facetHeatmapKeyList ) = $this->checkResponseFacetHeatmaps ( $object->facetheatmaps, $facetHeatmapKeyList );
          } else {
            $this->errors [] = "facets - facetheatmaps should be array";
            unset ( $object->{$key} );
          }
        } else if ($key == "prefix" || $key == "sort" || $key == "method" || $key == "contains") {
          if ($value != null && is_string ( $value )) {
            if ($key == "sort" && ($value != "index" && $value != "count")) {
              $this->warnings [] = "facets - sort \"" . $value . "\" should be \"index\" or \"count\"";
            } else if ($key == "method" && ($value != "enum" && $value != "fc" && $value != "fcs")) {
              $this->warnings [] = "facets - method \"" . $value . "\" should be \"enum\", \"fc\" or \"fcs\"";
            }
          } else {
            $this->warnings [] = "facets - {$key} should be a string";
            unset ( $object->{$key} );
          }
        } else if ($key == "limit" || $key == "offset" || $key == "mincount") {
          if ($value == null || ! is_integer ( $value )) {
            $this->warnings [] = "facets - {$key} should be an integer";
            unset ( $object->{$key} );
          }
        } else if ($key == "missing") {
          if ($value == null || ! is_bool ( $value )) {
            $this->warnings [] = "facets - {$key} should be a boolean";
            unset ( $object->{$key} );
          }
        } else if ($key == "excludeTerms") {
          if ($value == null || ! is_array ( $value ) || count ( $value ) == 0) {
            $this->warnings [] = "facets - {$key} should be an  non-empty array";
            unset ( $object->{$key} );
          } else {
            foreach ( $value as $valueItem ) {
              if (! is_string ( $valueItem )) {
                $this->warnings [] = "facets - {$key} - items in array should be string";
                unset ( $object->{$key} );
                break;
              }
            }
          }
        } else {
          $this->warnings [] = "facets - {$key} not expected";
        }
      }
      $object->__facetqueriesKeylist = $facetQueryKeyList;
      return $object;
    } else {
      $this->warnings [] = "facets - unexpected type";
      return null;
    }
  }
  /**
   * Check facet fields in response
   *
   * @param array $facetfields          
   * @param array $keyList          
   */
  private function checkResponseFacetFields($facetfields, $keyList) {
    if (count ( $facetfields ) > 0) {
      for($i = 0; $i < count ( $facetfields ); $i ++) {
        list ( $facetfields [$i], $keyList ) = $this->checkResponseFacetField ( $facetfields [$i], $keyList );
      }
    }
    return array (
        $facetfields,
        $keyList 
    );
  }
  /**
   * Check facet field
   *
   * @param object $object          
   * @param array $keyList          
   * @return array
   */
  private function checkResponseFacetField($object, $keyList) {
    if ($object && is_object ( $object )) {
      if (isset ( $object->field ) && is_string ( $object->field )) {
        $configurations = $this->getConfigurationsForField ( $object->field );
        if (count ( $configurations ) > 0) {
          $this->__configurations [] = $configurations;
          if (isset ( $object->__options )) {
            $this->warnings [] = "facets - facetfields - __options not expected";
          }
          $object->__options = array ();
          if (isset ( $object->key )) {
            if (is_string ( $object->key )) {
              $counter = 0;
              if (in_array ( $object->key, $keyList )) {
                $this->warnings [] = "facets - facetfields - key " . $object->key . " already exists";
                $counter = 0;
                $originalKey = $object->key;
                while ( in_array ( $object->key, $keyList ) ) {
                  $counter ++;
                  $object->key = $originalKey . " (" . $counter . ")";
                }
              }
            } else {
              unset ( $object->key );
              $this->warnings [] = "facets - facetfields - key should be a string";
            }
          }
          if (! isset ( $object->key )) {
            $counter = 0;
            $object->key = $object->field;
            $originalKey = $object->key;
            while ( in_array ( $object->key, $keyList ) ) {
              $counter ++;
              $object->key = $originalKey . " (" . $counter . ")";
            }
          }
          $object->__options [] = "key=\"" . str_replace ( "\"", "\\\"", $object->key ) . "\"";
          $keyList [] = $object->key;
          if (isset ( $object->ex )) {
            if (is_string ( $object->ex )) {
              $object->__options [] = "ex=\"" . implode ( ",", array_map ( "base64_encode", explode ( ",", $object->ex ) ) ) . "\"";
            } else {
              $this->warnings [] = "facets - facetfields - ex should be a string";
            }
          }
          foreach ( $object as $key => $value ) {
            if ($key == "field" || $key == "key" || $key == "ex" || $key == "__options") {
              // ignore
            } else if ($key == "prefix" || $key == "sort" || $key == "method" || $key == "contains") {
              if (is_string ( $value )) {
                if ($key == "sort" && ($value != "index" && $value != "count")) {
                  $this->warnings [] = "facets - facetfields - sort \"" . $value . "\" should be \"index\" or \"count\"";
                } else if ($key == "method" && ($value != "enum" && $value != "fc" && $value != "fcs")) {
                  $this->warnings [] = "facets - facetfields - method \"" . $value . "\" should be \"enum\", \"fc\" or \"fcs\"";
                } else {
                  $object->__options [] = "facet." . $key . "=\"" . str_replace ( "\"", "\\\"", $value ) . "\"";
                }
              } else {
                $this->warnings [] = "facets - facetfields - {$key} should be a string";
              }
            } else if ($key == "limit" || $key == "offset" || $key == "mincount") {
              if (! is_integer ( $value )) {
                $this->warnings [] = "facets - facetfields - {$key} should be an integer";
              }
            } else if ($key == "missing") {
              if (! is_bool ( $value )) {
                $this->warnings [] = "facets - facetfields - {$key} should be a boolean";
              } else {
                $object->__options [] = "facet." . $key . "=\"" . ($value ? "true" : "false") . "\"";
              }
            } else if ($key == "excludeTerms") {
              if ($value == null || ! is_array ( $value ) || count ( $value ) == 0) {
                $this->warnings [] = "facets - facetfields - {$key} should be an  non-empty array";
              } else {
                $validArrayOfStrings = true;
                foreach ( $value as $valueItem ) {
                  if (! is_string ( $valueItem )) {
                    $this->warnings [] = "facets - {$key} - items in array should be string";
                    $validArrayOfStrings = false;
                    break;
                  }
                }
                if ($validArrayOfStrings) {
                  $object->__options [] = "facet." . $key . "=\"" . str_replace ( "\"", "\\\"", implode ( ",", $value ) ) . "\"";
                }
              }
            } else if ($key == "join") {
              if (! is_object ( $value )) {
                $this->warnings [] = "facets - facetfields - {$key} should be an object";
              } else {
                $object->join = $this->checkResponseFacetFieldJoin ( $value );
              }
            } else {
              $this->warnings [] = "facets - facetfields - {$key} not expected";
            }
          }
          return array (
              $object,
              $keyList 
          );
        } else {
          $this->errors [] = "facets - facetfields - field '" . $object->field . "' not found in any configuration";
          return array (
              null,
              $keyList 
          );
        }
      } else {
        $this->errors [] = "facets - facetfields - no (valid) field provided";
        return array (
            null,
            $keyList 
        );
      }
    } else {
      $this->warnings [] = "facets - facetfields - unexpected type";
      return array (
          null,
          $keyList 
      );
    }
  }
  /**
   * Check join facet field
   *
   * @param object $object          
   * @return object
   */
  private function checkResponseFacetFieldJoin($object) {
    if (is_object ( $object )) {
      if (! isset ( $object->to ) || ! is_string ( $object->to )) {
        $this->errors [] = "facets - facetfields - no (valid) to field provided for join";
        return null;
      } else if (! isset ( $object->fields ) || ! is_array ( $object->fields ) || count ( $object->fields ) == 0) {
        $this->errors [] = "facets - facetfields - no (valid) fields provided for join";
        return null;
      } else {
        foreach ( $object->fields as $fieldsItem ) {
          if (! is_string ( $fieldsItem ) && ! is_object ( $fieldsItem )) {
            $this->errors [] = "facets - facetfields - invalid field provided for join";
          }
        }
      }
      foreach ( $object as $key => $value ) {
        if ($key == "to" || $key == "fields") {
          // ignore
        } else if ($key == "configuration") {
          if (! is_string ( $value )) {
            $this->errors [] = "facets - facetfields - invalid configuration provided for join";
          }
        } else {
          $this->warnings [] = "facets - facetfields - {$key} not expected in join";
        }
      }
      return $object;
    } else {
      return null;
    }
  }
  /**
   * Check facet queries
   *
   * @param array $facetqueries          
   * @param array $keyList          
   * @return array
   */
  private function checkResponseFacetQueries($facetqueries, $keyList) {
    if (count ( $facetqueries ) > 0) {
      for($i = 0; $i < count ( $facetqueries ); $i ++) {
        list ( $facetqueries [$i], $keyList ) = $this->checkResponseFacetQuery ( $facetqueries [$i], $keyList );
      }
    }
    return array (
        $facetqueries,
        $keyList 
    );
  }
  /**
   * Check facet query
   *
   * @param object $object          
   * @param array $keyList          
   * @return array
   */
  private function checkResponseFacetQuery($object, $keyList) {
    if ($object && is_object ( $object )) {
      // generated
      if (isset ( $object->__query ) && $object->__query && is_string ( $object->__query )) {
        foreach ( $object as $key => $value ) {
          if ($key == "key") {
            if ($value == null || ! is_string ( $value )) {
              $this->warnings [] = "facets - facetquery (generated) - key should be a string";
            } else {
              if (in_array ( $value, $keyList )) {
                $this->errors [] = "facets - facetquery (generated) - key '" . $value . "' already used";
              } else {
                $keyList [] = $value;
              }
            }
          } else if ($key == "ex") {
            if ($value == null || ! is_string ( $value )) {
              $this->warnings [] = "facets - facetquery (generated) - ex should be a string";
            }
          } else if ($key == "tag") {
            if ($value == null || ! is_string ( $value )) {
              $this->warnings [] = "facets - facetquery (generated) - tag should be a string";
            }
          } else if ($key == "__query") {
            if ($value == null || ! is_string ( $value )) {
              $this->errors [] = "facets - facetquery (generated) - no query";
            }
          }
        }
        if (! isset ( $object->__query )) {
          $this->errors [] = "facets - facetqueries (generated) - no (valid) query provided";
          return array (
              null,
              $keyList 
          );
        } else {
          return array (
              $object,
              $keyList 
          );
        }
        // provided
      } else {
        foreach ( $object as $key => $value ) {
          if ($key == "key") {
            if ($value == null || ! is_string ( $value )) {
              $this->warnings [] = "facets - facetquery - key should be a string";
            } else {
              if (in_array ( $value, $keyList )) {
                $this->errors [] = "facets - facetquery - key '" . $value . "' already used";
              } else {
                $keyList [] = $value;
              }
            }
          } else if ($key == "ex") {
            if ($value == null || ! is_string ( $value )) {
              $this->warnings [] = "facets - facetquery - ex should be a string";
            }
          } else if ($key == "tag") {
            if ($value == null || ! is_string ( $value )) {
              $this->warnings [] = "facets - facetquery - tag should be a string";
            }
          } else if ($key == "condition") {
            $object->condition = $this->checkCondition ( $object->condition );
          }
        }
        if (! isset ( $object->condition ) || $object->condition == null) {
          $this->errors [] = "facets - facetqueries - no (valid) condition provided" . var_export ( $object, true );
          return array (
              null,
              $keyList 
          );
        } else {
          return array (
              $object,
              $keyList 
          );
        }
      }
    } else {
      $this->warnings [] = "facets - facetqueries - unexpected type";
      return array (
          null,
          $keyList 
      );
    }
  }
  /**
   * Check facet ranges
   *
   * @param array $facetranges          
   * @param array $keyList          
   * @return array
   */
  private function checkResponseFacetRanges($facetranges, $keyList) {
    if (count ( $facetranges ) > 0) {
      for($i = 0; $i < count ( $facetranges ); $i ++) {
        list ( $facetranges [$i], $keyList ) = $this->checkResponseFacetRange ( $facetranges [$i], $keyList );
      }
    }
    return array (
        $facetranges,
        $keyList 
    );
  }
  /**
   * Check facet range
   *
   * @param object $object          
   * @param array $keyList          
   * @return array
   */
  private function checkResponseFacetRange($object, $keyList) {
    if ($object && is_object ( $object )) {
      if (isset ( $object->field ) && is_string ( $object->field )) {
        $configurations = $this->getConfigurationsForField ( $object->field );
        if (count ( $configurations ) > 0) {
          $this->__configurations [] = $configurations;
          if (isset ( $object->__options )) {
            $this->warnings [] = "facets - facetranges - __options not expected";
          }
          $object->__options = array ();
          $ignoreList = array (
              "__options",
              "field",
              "key" 
          );
          $optionalItems = array (
              "include",
              "other",
              "tag" 
          );
          foreach ( $optionalItems as $optionalItem ) {
            if (isset ( $object->{$optionalItem} )) {
              $ignoreList [] = $optionalItem;
              if (is_string ( $object->{$optionalItem} )) {
                $object->__options [] = $optionalItem . "=\"" . str_replace ( "\"", "\\\"", $object->{$optionalItem} ) . "\"";
              } else {
                $this->warnings [] = "facets - facetranges - " . $optionalItem . " should be a string";
              }
            }
          }
          if (isset ( $object->ex )) {
            $ignoreList [] = "ex";
            if (is_string ( $object->ex )) {
              $object->__options [] = "ex=\"" . implode ( ",", array_map ( "base64_encode", explode ( ",", $object->ex ) ) ) . "\"";
            } else {
              $this->warnings [] = "facets - facetranges - ex should be a string";
            }
          }
          if (isset ( $object->other )) {
            $ignoreList [] = "other";
            if (is_string ( $object->other )) {
              $object->__options [] = "facet.range.other=\"" . str_replace ( "\"", "\\\"", $object->other) . "\"";
            } else {
              $this->warnings [] = "facets - facetranges - ex should be a string";
            }
          }
// disabled: solr-problem with sharding
//           if (isset ( $object->mincount )) {
//             $ignoreList [] = "mincount";
//             if (is_numeric ( $object->mincount )) {
//               $object->__options [] = "facet.mincount=".$object->mincount;
//             } else {
//               $this->warnings [] = "facets - facetranges - mincount should be numeric";
//             }
//           }
          if (isset ( $object->key )) {
            if (is_string ( $object->key )) {
              $counter = 0;
              if (in_array ( $object->key, $keyList )) {
                $this->warnings [] = "facets - facetranges - key " . $object->key . " already exists";
                $counter = 0;
                $originalKey = $object->key;
                while ( in_array ( $object->key, $keyList ) ) {
                  $counter ++;
                  $object->key = $originalKey . " (" . $counter . ")";
                }
              }
            } else {
              unset ( $object->key );
              $this->warnings [] = "facets - facetranges - key should be a string";
            }
          }
          if (! isset ( $object->key )) {
            $counter = 0;
            $object->key = $object->field;
            $originalKey = $object->key;
            while ( in_array ( $object->key, $keyList ) ) {
              $counter ++;
              $object->key = $originalKey . " (" . $counter . ")";
            }
          }
          $object->__options [] = "key=\"" . str_replace ( "\"", "\\\"", $object->key ) . "\"";
          $keyList [] = $object->key;
          $obligatoryOptions = array (
              "start",
              "end",
              "gap" 
          );
          foreach ( $obligatoryOptions as $obligatoryOption ) {
            $ignoreList [] = $obligatoryOption;
            if (isset ( $object->{$obligatoryOption} )) {
              if (is_string ( $object->{$obligatoryOption} ) || is_int ( $object->{$obligatoryOption} )) {
                $object->__options [] = "facet.range." . $obligatoryOption . "=\"" . str_replace ( "\"", "\\\"", $object->{$obligatoryOption} ) . "\"";
              } else {
                $this->errors [] = "facets - facetranges - " . $obligatoryOption . " should be a string or an integer";
              }
            } else {
              $this->errors [] = "facets - facetranges - " . $obligatoryOption . " not set";
            }
          }
          foreach ( $object as $key => $value ) {
            if (in_array ( $key, $ignoreList )) {
              // ignore
            } else {
              $this->warnings [] = "facets - facetranges - {$key} not expected";
            }
          }
          return array (
              $object,
              $keyList 
          );
        } else {
          $this->warnings [] = "facets - facetranges - unexpected type";
          return array (
              null,
              $keyList 
          );
        }
      } else {
        $this->errors [] = "facets - facetranges - no (valid) field provided";
        return array (
            null,
            $keyList 
        );
      }
    } else {
      $this->warnings [] = "facets - facetranges - unexpected type";
      return array (
          null,
          $keyList 
      );
    }
  }
  /**
   * Check facet pivots
   *
   * @param array $facetpivots          
   * @param array $keyList          
   * @return array
   */
  private function checkResponseFacetPivots($facetpivots, $keyList) {
    if (count ( $facetpivots ) > 0) {
      for($i = 0; $i < count ( $facetpivots ); $i ++) {
        list ( $facetpivots [$i], $keyList ) = $this->checkResponseFacetPivot ( $facetpivots [$i], $keyList );
      }
    }
    return array (
        $facetpivots,
        $keyList 
    );
  }
  /**
   * Check facet pivot
   *
   * @param object $object          
   * @param array $keyList          
   * @return array
   */
  private function checkResponseFacetPivot($object, $keyList) {
    if ($object && is_object ( $object )) {
      if (isset ( $object->pivot ) && is_array ( $object->pivot )) {
        if (isset ( $object->__options )) {
          $this->warnings [] = "facets - facetpivots - __options not expected";
        }
        $object->__options = array ();
        foreach ( $object->pivot as $pivot ) {
          if (is_string ( $pivot )) {
            $configurations = $this->getConfigurationsForField ( $pivot );
            if (count ( $configurations ) > 0) {
              $this->__configurations [] = $configurations;
            }
          } else {
            $this->errors [] = "facets - facetpivots - pivot should be a string";
          }
        }
        $ignoreList = array (
            "__options",
            "pivot",
            "key" 
        );
        if (isset ( $object->ex )) {
          $ignoreList [] = "ex";
          if (is_string ( $object->ex )) {
            $object->__options [] = "ex=\"" . implode ( ",", array_map ( "base64_encode", explode ( ",", $object->ex ) ) ) . "\"";
          } else {
            $this->warnings [] = "facets - facetpivots - ex should be a string";
          }
        }
        $stringSpecialList = array (
            "query",
            "range",
            "stats" 
        );
        $integerList = array (
            "limit",
            "offset" 
        );
        foreach ( $stringSpecialList as $stringItem ) {
          if (isset ( $object->{$stringItem} )) {
            $ignoreList [] = $stringItem;
            if (is_string ( $object->{$stringItem} )) {
              $object->__options [] = $stringItem . "=\"" . str_replace ( "\"", "\\\"", $object->{$stringItem} ) . "\"";
            } else {
              unset ( $object->{$stringItem} );
            }
          }
        }
        foreach ( $integerList as $integerItem ) {
          if (isset ( $object->{$integerItem} )) {
            $ignoreList [] = $integerItem;
            if (is_int ( $object->{$integerItem} )) {
              $object->__options [] = "facet." . $integerItem . "=" . $object->{$integerItem};
            } else {
              unset ( $object->{$stringItem} );
            }
          }
        }
        if (isset ( $object->key )) {
          if (is_string ( $object->key )) {
            $counter = 0;
            if (in_array ( $object->key, $keyList )) {
              $this->warnings [] = "facets - facetpivots - key " . $object->key . " already exists";
              $counter = 0;
              $originalKey = $object->key;
              while ( in_array ( $object->key, $keyList ) ) {
                $counter ++;
                $object->key = $originalKey . " (" . $counter . ")";
              }
            }
          } else {
            unset ( $object->key );
            $this->warnings [] = "facets - facetpivots - key should be a string";
          }
        }
        if (! isset ( $object->key )) {
          $counter = 0;
          $object->key = implode ( ",", $object->pivot );
          $originalKey = $object->key;
          while ( in_array ( $object->key, $keyList ) ) {
            $counter ++;
            $object->key = $originalKey . " (" . $counter . ")";
          }
        }
        $object->__options [] = "key=\"" . str_replace ( "\"", "\\\"", $object->key ) . "\"";
        $keyList [] = $object->key;
        foreach ( $object as $key => $value ) {
          if (in_array ( $key, $ignoreList )) {
            // ignore
          } else if ($key == "sort") {
            if (is_string ( $value )) {
              if ($key == "sort" && ($value != "index" && $value != "count")) {
                $this->warnings [] = "facets - facetpivots - sort \"" . $value . "\" should be \"index\" or \"count\"";
              } else {
                $object->__options [] = "facet." . $key . "=\"" . str_replace ( "\"", "\\\"", $value ) . "\"";
              }
            } else {
              unset ( $object->key );
              $this->warnings [] = "facets - facetpivots - {$key} should be a string";
            }
          } else {
            $this->warnings [] = "facets - facetpivots - {$key} not expected";
          }
        }
        return array (
            $object,
            $keyList 
        );
      } else {
        $this->warnings [] = "facets - facetpivots - unexpected type";
        return array (
            null,
            $keyList 
        );
      }
    } else {
      $this->errors [] = "facets - facetpivots - no (valid) pivot provided";
      return array (
          null,
          $keyList 
      );
    }
  }
  /**
   * Check facet heatmaps
   *
   * @param array $facetheatmaps
   * @param array $keyList
   * @return array
   */
  private function checkResponseFacetHeatmaps($facetheatmaps, $keyList) {
    if (count ( $facetheatmaps ) > 0) {
      for($i = 0; $i < count ( $facetheatmaps ); $i ++) {
        list ( $facetheatmaps [$i], $keyList ) = $this->checkResponseFacetHeatmap ( $facetheatmaps [$i], $keyList );
      }
    }
    return array (
        $facetheatmaps,
        $keyList
    );
  }
  /**
   * Check facet pivot
   *
   * @param object $object
   * @param array $keyList
   * @return array
   */
  private function checkResponseFacetHeatmap($object, $keyList) {
    if ($object && is_object ( $object )) {
      if (isset ( $object->field ) && is_string ( $object->field )) {
        $configurations = $this->getConfigurationsForField ( $object->field );
        if (count ( $configurations ) > 0) {
          $this->__configurations [] = $configurations;
          if (isset ( $object->__options )) {
            $this->warnings [] = "facets - facetheatmaps - __options not expected";
          }
          $object->__options = array ();
          $ignoreList = array (
              "__options",
              "field",
              "key"
          );
          if (isset ( $object->geom )) {
            $ignoreList [] = "geom";
            if (is_string ( $object->geom )) {
              $object->__options [] = "facet.heatmap.geom=\"" . str_replace ( "\"", "\\\"", $object->geom ) . "\"";
            } else {
              $this->warnings [] = "facets - facetranges - geom should be a string";
            }
          }
          if (isset ( $object->gridLevel )) {
            $ignoreList [] = "gridLevel";
            if (is_int ( $object->gridLevel )) {
              $object->__options [] = "facet.heatmap.gridLevel=".$object->gridLevel;
            } else {
              $this->warnings [] = "facets - facetheatmaps - gridLevel should be an integer";
            }
          }
          if (isset ( $object->distErrPct )) {
            $ignoreList [] = "distErrPct";
            if (is_numeric ( $object->distErrPct )) {
              $object->__options [] = "facet.heatmap.distErrPct=".$object->distErrPct;
            } else {
              $this->warnings [] = "facets - facetranges - distErrPct should be numeric";
            }
          }
          if (isset ( $object->distErr )) {
            $ignoreList [] = "distErr";
            if (is_numeric ( $object->distErr )) {
              $object->__options [] = "facet.heatmap.distErr=".$object->distErr;
            } else {
              $this->warnings [] = "facets - facetheatmaps - distErr should be numeric";
            }
          }
          if (isset ( $object->ex )) {
            $ignoreList [] = "ex";
            if (is_string ( $object->ex )) {
              $object->__options [] = "ex=\"" . implode ( ",", array_map ( "base64_encode", explode ( ",", $object->ex ) ) ) . "\"";
            } else {
              $this->warnings [] = "facets - facetheatmaps - ex should be a string";
            }
          }
          if (isset ( $object->key )) {
            if (is_string ( $object->key )) {
              $counter = 0;
              if (in_array ( $object->key, $keyList )) {
                $this->warnings [] = "facets - facetheatmaps - key " . $object->key . " already exists";
                $counter = 0;
                $originalKey = $object->key;
                while ( in_array ( $object->key, $keyList ) ) {
                  $counter ++;
                  $object->key = $originalKey . " (" . $counter . ")";
                }
              }
            } else {
              unset ( $object->key );
              $this->warnings [] = "facets - facetheatmaps - key should be a string";
            }
          }
          if (! isset ( $object->key )) {
            $counter = 0;
            $object->key = $object->field;
            $originalKey = $object->key;
            while ( in_array ( $object->key, $keyList ) ) {
              $counter ++;
              $object->key = $originalKey . " (" . $counter . ")";
            }
          }
          $object->__options [] = "key=\"" . str_replace ( "\"", "\\\"", $object->key ) . "\"";
          $keyList [] = $object->key;
          foreach ( $object as $key => $value ) {
            if (in_array ( $key, $ignoreList )) {
              // ignore
            } else {
              $this->warnings [] = "facets - facetheatmaps - {$key} not expected";
            }
          }
          return array (
              $object,
              $keyList
          );
        } else {
          $this->warnings [] = "facets - facetheatmaps - unexpected field (type)";
          return array (
              null,
              $keyList
          );
        }
      } else {
        $this->errors [] = "facets - facetheatmaps - no (valid) heatmap provided";
        return array (
            null,
            $keyList
        );
      }
    } else {
      $this->warnings [] = "facets - facetheatmaps - unexpected type";
      return array (
          null,
          $keyList
      );
    }
  }
  
  /**
   * Check stats in response
   *
   * @param object $object          
   * @return object
   */
  private function checkResponseStats($object) {
    if ($object && is_object ( $object )) {
      $statsFieldKeyList = array ();
      foreach ( $object as $key => $value ) {
        if ($key == "statsfields") {
          if ($value != null && is_array ( $value )) {
            list ( $object->statsfields, $statsFieldKeyList ) = $this->checkResponseStatsFields ( $object->statsfields, $statsFieldKeyList );
          } else {
            $this->errors [] = "stats - statsfields should be array";
            unset ( $object->{$key} );
          }
        } else {
          $this->warnings [] = "stats - {$key} not expected";
        }
      }
      return $object;
    } else {
      $this->warnings [] = "stats - unexpected type";
      return null;
    }
  }
  /**
   * Check stats fields in response
   *
   * @param array $statsfields          
   * @param array $keyList          
   * @return array
   */
  private function checkResponseStatsFields($statsfields, $keyList) {
    if (count ( $statsfields ) > 0) {
      for($i = 0; $i < count ( $statsfields ); $i ++) {
        list ( $statsfields [$i], $keyList ) = $this->checkResponseStatsField ( $statsfields [$i], $keyList );
      }
    }
    return array (
        $statsfields,
        $keyList 
    );
  }
  /**
   * Check stats field
   *
   * @param object $object          
   * @param array $keyList          
   * @return array
   */
  private function checkResponseStatsField($object, $keyList) {
    if ($object && is_object ( $object )) {
      if (isset ( $object->field ) && is_string ( $object->field )) {
        if (isset ( $object->__options )) {
          $this->warnings [] = "stats - statsfields - __options not expected";
        }
        $object->__options = array ();
        // check field
        $validField = false;
        if (preg_match ( "/^(\"[^\"]+\"|'[^']+')$/", $object->field ) || preg_match ( "/^[^\(]+\(.*\)$/", $object->field )) {
          // function
          $validField = true;
          $object->__options [] = "func";
        } else {
          $configurations = $this->getConfigurationsForField ( $object->field );
          if (count ( $configurations ) > 0) {
            $this->__configurations [] = $configurations;
            $validField = true;
          }
        }
        if ($validField) {
          if (isset ( $object->key )) {
            if (is_string ( $object->key )) {
              $counter = 0;
              if (in_array ( $object->key, $keyList )) {
                $this->warnings [] = "stats - statsfields - key " . $object->key . " already exists";
                $counter = 0;
                $originalKey = $object->key;
                while ( in_array ( $object->key, $keyList ) ) {
                  $counter ++;
                  $object->key = $originalKey . " (" . $counter . ")";
                }
              }
            } else {
              unset ( $object->key );
              $this->warnings [] = "stats - statsfields - key should be a string";
            }
          }
          if (! isset ( $object->key )) {
            $counter = 0;
            $object->key = $object->field;
            $originalKey = $object->key;
            while ( in_array ( $object->key, $keyList ) ) {
              $counter ++;
              $object->key = $originalKey . " (" . $counter . ")";
            }
          }
          $object->__options [] = "key=\"" . str_replace ( "\"", "\\\"", $object->key ) . "\"";
          $keyList [] = $object->key;
          foreach ( $object as $key => $value ) {
            if ($key == "field" || $key == "key" || $key == "__options") {
              // ignore
            } else if ($key == "countDistinct") {
              if (is_bool ( $value )) {
                $object->__options [] = $key . "=" . ($value ? "true" : "false");
              } else {
                $this->warnings [] = "stats - statsfields - {$key} should be a boolean";
              }
            } else if ($key == "tag") {
              if (is_string ( $value )) {
                $object->__options [] = $key . "=\"" . str_replace ( "\"", "\\\"", $value ) . "\"";
              } else {
                $this->warnings [] = "stats - statsfields - {$key} should be a string";
              }
            } else if ($key == "ex") {
              if (is_string ( $value )) {
                $object->__options [] = "ex=\"" . implode ( ",", array_map ( "base64_encode", explode ( ",", $object->ex ) ) ) . "\"";
              } else {
                $this->warnings [] = "stats - statsfields - {$key} should be a string";
              }
            } else {
              $this->warnings [] = "stats - statsfields - {$key} not expected";
            }
          }
          return array (
              $object,
              $keyList 
          );
        } else {
          $this->errors [] = "stats - statsfields - field '" . $object->field . "' not found in any configuration";
          return array (
              null,
              $keyList 
          );
        }
      } else {
        $this->errors [] = "stats - statsfields - no (valid) field provided";
        return array (
            null,
            $keyList 
        );
      }
    } else {
      $this->warnings [] = "stats - statsfields - unexpected type";
      return array (
          null,
          $keyList 
      );
    }
  }
  /**
   * Check mtas in response
   *
   * @param object $object          
   * @return object
   */
  private function checkResponseMtas($object) {
    if (($object && is_object ( $object ))) {
      foreach ( $object as $key => $value ) {
        if ($key == "stats") {
          if (!is_null($value) && is_object ( $value )) {
            $object->{$key} = $this->checkResponseMtasStats ( $value );
          } else {
            $this->errors [] = "mtas - {$key} should be object";
            unset ( $object->{$key} );
          }
        } else if ($key == "document") {
          if (!is_null($value) && is_array ( $value )) {
            for($i = 0; $i < count ( $value ); $i ++) {
              $object->{$key} [$i] = $this->checkResponseMtasDocument ( $value [$i] );
            }
          } else {
            $this->errors [] = "mtas - {$key} should be array";
            unset ( $object->{$key} );
          }
        } else if ($key == "kwic" || $key == "list") {          
          if (!is_null($value) && is_array ( $value ))  { 
            for($i = 0; $i < count ( $value ); $i ++) {
              $object->{$key} [$i] = $this->checkResponseMtasKwicAndList ( $key, $value [$i] );
            }
          } else {
            $this->errors [] = "mtas - {$key} should be array";
            unset ( $object->{$key} );
          }
        } else if ($key == "termvector") { 
          if (!is_null($value) && is_array ( $value )) { 
            for($i = 0; $i < count ( $value ); $i ++) {
              $object->{$key} [$i] = $this->checkResponseMtasTermvector ( $value [$i] );
            }
          } else {
            $this->errors [] = "mtas - {$key} should be array";
            unset ( $object->{$key} );
          }
        } else if ($key == "facet") {
          if (!is_null($value) && is_array ( $value )) {
            for($i = 0; $i < count ( $value ); $i ++) {
              $object->{$key} [$i] = $this->checkResponseMtasFacet ( $value [$i] );
            }
          } else {
            $this->errors [] = "mtas - {$key} should be array";
            unset ( $object->{$key} );
          }
        } else if ($key == "group") {
          if (!is_null($value) && is_array ( $value )) {
            for($i = 0; $i < count ( $value ); $i ++) {
              $object->{$key} [$i] = $this->checkResponseMtasGroup ( $value [$i] );
            }
          } else {
            $this->errors [] = "mtas - {$key} should be array";
            unset ( $object->{$key} );
          }
        } else if ($key == "prefix") {
          if (!is_null($value) && is_array ( $value )) {
            for($i = 0; $i < count ( $value ); $i ++) {
              $object->{$key} [$i] = $this->checkResponseMtasPrefix ( $value [$i] );
            }
          } else {
            $this->errors [] = "mtas - {$key} should be array";
            unset ( $object->{$key} );
          }
        } else if ($key == "collection") {
          if (!is_null($value) && is_array ( $value )) {
            for($i = 0; $i < count ( $value ); $i ++) {
              $object->collection [$i] = $this->checkResponseMtasCollection ( $value [$i] );
            }
          } else {
            $this->errors [] = "mtas - {$key} should be array";
            unset ( $object->{$key} );
          }
        } else if ($key == "version") {
          if (!is_null($value) && is_bool ( $value )) {
            $object->version = $this->checkResponseMtasVersion ( $value );
          } else {
            $this->errors [] = "mtas - {$key} should be boolean";
            unset ( $object->{$key} );
          }
        } else {
          $this->warnings [] = "mtas - {$key} not expected";
        }
      }
      return $object;
    } else {
      $this->warnings [] = "mtas - unexpected type";
      return null;
    }
  }
  /**
   * Check mtas stats
   *
   * @param object $object          
   * @return object
   */
  private function checkResponseMtasStats($object) {
    if ($object && is_object ( $object )) {
      foreach ( $object as $key => $value ) {
        if ($key == "positions") {
          if ($value != null && is_array ( $value )) {
            for($i = 0; $i < count ( $value ); $i ++) {
              $object->{$key} [$i] = $this->checkResponseMtasStatsPositions ( $value [$i], $i );
            }
          } else {
            $this->errors [] = "mtas - stats - {$key} should be array";
            unset ( $object->{$key} );
          }
        } else if ($key == "tokens") {
          if ($value != null && is_array ( $value )) {
            for($i = 0; $i < count ( $value ); $i ++) {
              $object->{$key} [$i] = $this->checkResponseMtasStatsTokens ( $value [$i], $i );
            }
          } else {
            $this->errors [] = "mtas - stats - {$key} should be array";
            unset ( $object->{$key} );
          }
        } else if ($key == "spans") {
          if ($value != null && is_array ( $value )) {
            for($i = 0; $i < count ( $value ); $i ++) {
              $object->{$key} [$i] = $this->checkResponseMtasStatsSpans ( $value [$i], $i );
            }
          } else {
            $this->errors [] = "mtas - stats - {$key} should be array";
            unset ( $object->{$key} );
          }
        } else {
          $this->warnings [] = "mtas - stats - {$key} not expected";
        }
      }
      return $object;
    } else {
      $this->warnings [] = "mtas - stats - unexpected type";
      return null;
    }
  }
  /**
   * Check mtas stats positions
   *
   * @param object $object          
   * @return object
   */
  private function checkResponseMtasStatsPositions($object) {
    if ($object && is_object ( $object )) {
      foreach ( $object as $key => $value ) {
        if ($key == "field") {
          if (! is_string ( $value )) {
            $this->errors [] = "mtas - stats - positions - {$key} should be string";
          } else {
            $configurations = $this->getConfigurationsForField ( $value );
            if (count ( $configurations ) > 0) {
              $this->__configurations [] = $configurations;
            } else {
              $this->errors [] = "mtas - stats - positions - {$key} :  '" . $value . "' not found in any configuration";
            }
          }
        } else if ($key == "key" || $key == "type") {
          if (! is_string ( $value )) {
            $this->errors [] = "mtas - stats - positions - {$key} should be string";
          }
        } else if ($key == "minimum" || $key == "maximum") {
          if (! is_int ( $value )) {
            $this->errors [] = "mtas - stats - positions - {$key} should be integer";
          }
        } else {
          $this->warnings [] = "mtas - stats - positions - {$key} not expected";
        }
      }
      if (count ( $this->errors ) == 0) {
        if (! isset ( $object->field )) {
          $this->errors [] = "mtas - stats - positions - field is obligatory";
        }
      }
      return $object;
    } else {
      $this->warnings [] = "mtas - stats - positions unexpected type";
      return null;
    }
  }
  /**
   * Check mtas stats tokens
   *
   * @param object $object          
   * @return object
   */
  private function checkResponseMtasStatsTokens($object) {
    if ($object && is_object ( $object )) {
      foreach ( $object as $key => $value ) {
        if ($key == "field") {
          if (! is_string ( $value )) {
            $this->errors [] = "mtas - stats - tokens - {$key} should be string";
          } else {
            $configurations = $this->getConfigurationsForField ( $value );
            if (count ( $configurations ) > 0) {
              $this->__configurations [] = $configurations;
            } else {
              $this->errors [] = "mtas - stats - tokens - {$key} :  '" . $value . "' not found in any configuration";
            }
          }
        } else if ($key == "key" || $key == "type") {
          if (! is_string ( $value )) {
            $this->errors [] = "mtas - stats - tokens - {$key} should be string";
          }
        } else if ($key == "minimum" || $key == "maximum") {
          if (! is_int ( $value )) {
            $this->errors [] = "mtas - stats - tokens - {$key} should be integer";
          }
        } else {
          $this->warnings [] = "mtas - stats - tokens - {$key} not expected";
        }
      }
      if (count ( $this->errors ) == 0) {
        if (! isset ( $object->field )) {
          $this->errors [] = "mtas - stats - tokens - field is obligatory";
        }
      }
      return $object;
    } else {
      $this->warnings [] = "mtas - stats - tokens unexpected type";
      return null;
    }
  }
  /**
   * Check mtas stats spans
   *
   * @param object $object          
   * @return object
   */
  private function checkResponseMtasStatsSpans($object) {
    if ($object && is_object ( $object )) {
      foreach ( $object as $key => $value ) {
        if ($key == "field") {
          if (! is_string ( $value )) {
            $this->errors [] = "mtas - stats - spans - {$key} should be string";
          } else {
            $configurations = $this->getConfigurationsForField ( $value );
            if (count ( $configurations ) > 0) {
              $this->__configurations [] = $configurations;
            } else {
              $this->errors [] = "mtas - stats - spans - {$key} :  '" . $value . "' not found in any configuration";
            }
          }
        } else if ($key == "key" || $key == "type") {
          if (! is_string ( $value )) {
            $this->errors [] = "mtas - stats - spans - {$key} should be string";
          }
        } else if ($key == "queries") {
          if (! is_array ( $value ) || count ( $object->queries ) == 0) {
            $this->errors [] = "mtas - stats - spans - {$key} should be array";
          } else {
            for($i = 0; $i < count ( $value ); $i ++) {
              $object->{$key} [$i] = $this->checkResponseMtasQuery ( $value [$i], "mtas - stats - spans - query - " );
            }
          }
        } else if ($key == "functions") {
          if (! is_array ( $value )) {
            $this->errors [] = "mtas - stats - spans - {$key} should be array";
          } else {
            for($i = 0; $i < count ( $value ); $i ++) {
              $object->{$key} [$i] = $this->checkMtasStatsFunction ( $value [$i], "mtas - stats - spans - query - " );
            }
          }
        } else if ($key == "minimum" || $key == "maximum") {
          if (! is_int ( $value )) {
            $this->errors [] = "mtas - stats - spans - {$key} should be integer";
          }
        } else {
          $this->warnings [] = "mtas - stats - spans - {$key} not expected";
        }
      }
      if (count ( $this->errors ) == 0) {
        if (! isset ( $object->field )) {
          $this->errors [] = "mtas - stats - spans - field is obligatory";
        }
        if (! isset ( $object->queries )) {
          $this->errors [] = "mtas - stats - spans - queries is obligatory";
        }
      }
      return $object;
    } else {
      $this->warnings [] = "mtas - stats - tokens unexpected type";
      return null;
    }
  }
  /**
   * Check mtas documents
   *
   * @param object $object          
   * @return object
   */
  private function checkResponseMtasDocument($object) {
    if ($object && is_object ( $object )) {
      foreach ( $object as $key => $value ) {
        if ($key == "field") {
          if (! is_string ( $value )) {
            $this->errors [] = "mtas - document - {$key} should be string";
          } else {
            $configurations = $this->getConfigurationsForField ( $value );
            if (count ( $configurations ) > 0) {
              $this->__configurations [] = $configurations;
            } else {
              $this->errors [] = "mtas - document - {$key} :  '" . $value . "' not found in any configuration";
            }
          }
        } else if ($key == "prefix" || $key == "key" || $key == "type" || $key == "regexp" || $key == "ignoreRegexp") {
          if (! is_string ( $value )) {
            $this->errors [] = "mtas - document - {$key} should be string";
          }
        } else if ($key == "number" || $key == "listExpandNumber") {
          if (! is_int ( $value )) {
            $this->errors [] = "mtas - document - {$key} should be integer";
          }
        } else if ($key == "listRegexp" || $key == "listExpand" || $key == "ignoreListRegexp") {
          if (! is_bool ( $value )) {
            $this->errors [] = "mtas - document - {$key} should be boolean";
          }
        } else if ($key == "list" || $key == "ignoreList") {
          if (! is_array ( $value )) {
            $this->errors [] = "mtas - document - {$key} should be non empty array of strings";
          } else {
            foreach ( $value as $valueItem ) {
              if (! is_string ( $valueItem )) {
                $this->errors [] = "mtas - document - {$key} array should contain only strings";
                break;
              }
            }
          }
        } else {
          $this->warnings [] = "mtas - document - {$key} not expected";
        }
      }
      if (count ( $this->errors ) == 0) {
        if (! isset ( $object->field )) {
          $this->errors [] = "mtas - document - field is obligatory";
        }
        if (! isset ( $object->prefix )) {
          $this->errors [] = "mtas - document - prefix is obligatory";
        }
      }
      return $object;
    } else {
      $this->warnings [] = "mtas - document - unexpected type";
      return null;
    }
  }
  /**
   * Check mtas kwic and list
   *
   * @param string $type          
   * @param object $object          
   * @return object
   */
  private function checkResponseMtasKwicAndList($type, $object) {
    if ($type != "list" && $type != "kwic") {
      die ( "incorrect call" );
    }
    if ($object && is_object ( $object )) {
      foreach ( $object as $key => $value ) {
        if ($key == "field") {
          if (! is_string ( $value )) {
            $this->errors [] = "mtas - {$type} - {$key} should be string";
          } else {
            $configurations = $this->getConfigurationsForField ( $value );
            if (count ( $configurations ) > 0) {
              $this->__configurations [] = $configurations;
            } else {
              $this->errors [] = "mtas - {$type} - {$key} :  '" . $value . "' not found in any configuration";
            }
          }
        } else if ($key == "prefix" || $key == "key" || $key == "output") {
          if (! is_string ( $value )) {
            $this->errors [] = "mtas - {$type} - {$key} should be string";
          }
        } else if ($key == "number" || $key == "start" || $key == "left" || $key == "right") {
          if (! is_int ( $value )) {
            $this->errors [] = "mtas - {$type} - {$key} should be integer";
          }
        } else if ($key == "query") {
          if (! is_object ( $value )) {
            $this->errors [] = "mtas - {$type} - {$key} should be object";
          } else {
            $object->{$key} = $this->checkResponseMtasQuery ( $value, "mtas - {$type} - {$key} - " );
          }
        } else {
          $this->warnings [] = "mtas - {$type} - {$key} not expected";
        }
      }
      if (count ( $this->errors ) == 0) {
        if (! isset ( $object->field )) {
          $this->errors [] = "mtas - {$type} - field is obligatory";
        }
        if (! isset ( $object->query )) {
          $this->errors [] = "mtas - {$type} - query is obligatory";
        }
      }
      return $object;
    } else {
      $this->warnings [] = "mtas - {$type} unexpected type";
      return null;
    }
  }
  /**
   * Check mtas termvector
   *
   * @param object $object          
   * @return object
   */
  private function checkResponseMtasTermvector($object) {
    if ($object && is_object ( $object )) {
      foreach ( $object as $key => $value ) {
        if ($key == "field") {
          if (! is_string ( $value )) {
            $this->errors [] = "mtas - termvector - {$key} should be string";
          } else {
            $configurations = $this->getConfigurationsForField ( $value );
            if (count ( $configurations ) > 0) {
              $this->__configurations [] = $configurations;
            } else {
              $this->errors [] = "mtas - termvector - {$key} :  '" . $value . "' not found in any configuration";
            }
          }
        } else if ($key == "prefix" || $key == "key" || $key == "type" || $key == "regexp" || $key == "ignoreRegexp") {
          if (! is_string ( $value )) {
            $this->errors [] = "mtas - termvector - {$key} should be string";
          }
        } else if ($key == "number" || $key == "start") {
          if (! is_int ( $value )) {
            $this->errors [] = "mtas - termvector - {$key} should be integer";
          }
        } else if ($key == "full" || $key == "listRegexp" || $key == "ignoreListRegexp") {
          if (! is_bool ( $value )) {
            $this->errors [] = "mtas - termvector - {$key} should be boolean";
          }
        } else if ($key == "sort") {
          if (! is_object ( $value )) {
            $this->errors [] = "mtas - termvector - {$key} should be object";
          } else {
            foreach ( $value as $subKey => $subValue ) {
              if ($subKey == "type" || $subKey == "direction") {
                if (! is_string ( $subValue )) {
                  $this->errors [] = "mtas - termvector - {$key} - {$subKey} should be string";
                }
              } else {
                $this->warnings [] = "mtas - termvector - {$key} - {$subKey} not expected";
              }
            }
          }
        } else if ($key == "list" || $key == "ignoreList") {
          if (! is_array ( $value )) {
            $this->errors [] = "mtas - termvector - {$key} should be non empty array of strings";
          } else {
            foreach ( $value as $subValue ) {
              if (! is_string ( $subValue )) {
                $this->errors [] = "mtas - termvector - {$key} should be array of only strings";
              }
            }
          }
        } else if ($key == "functions") {
          if (! is_array ( $value )) {
            $this->errors [] = "mtas - termvector - {$key} should be array";
          } else {
            for($i = 0; $i < count ( $value ); $i ++) {
              $value [$i] = $this->checkResponseMtasFunction ( $value [$i], "mtas - termvector - function - " );
            }
          }
        } else if ($key == "distances") {
          if (! is_array ( $value )) {
            $this->errors [] = "mtas - termvector - {$key} should be array";
          } else {
            for($i = 0; $i < count ( $value ); $i ++) {
              $value [$i] = $this->checkResponseMtasDistance ( $value [$i], "mtas - termvector - distance - " );
            }
          }
        } else {
          $this->warnings [] = "mtas - termvector - {$key} not expected";
        }
      }
      if (count ( $this->errors ) == 0) {
        if (! isset ( $object->field )) {
          $this->errors [] = "mtas - termvector - field is obligatory";
        }
        if (! isset ( $object->prefix )) {
          $this->errors [] = "mtas - termvector - prefix is obligatory";
        }
      }
      return $object;
    } else {
      $this->warnings [] = "mtas - termvector - unexpected type";
      return null;
    }
  }
  /**
   * Check mtas facet
   *
   * @param object $object          
   * @return object
   */
  private function checkResponseMtasFacet($object) {
    if ($object && is_object ( $object )) {
      foreach ( $object as $key => $value ) {
        if ($key == "field") {
          if (! is_string ( $value )) {
            $this->errors [] = "mtas - facet - {$key} should be string";
          } else {
            $configurations = $this->getConfigurationsForField ( $value );
            if (count ( $configurations ) > 0) {
              $this->__configurations [] = $configurations;
            } else {
              $this->errors [] = "mtas - facet - {$key} :  '" . $value . "' not found in any configuration";
            }
          }
        } else if ($key == "key") {
          if (! is_string ( $value )) {
            $this->errors [] = "mtas - facet - {$key} should be string";
          }
        } else if ($key == "queries") {
          if (! is_array ( $value )) {
            $this->errors [] = "mtas - facet - {$key} should be array";
          } else {
            for($i = 0; $i < count ( $value ); $i ++) {
              $object->{$key} [$i] = $this->checkResponseMtasQuery ( $value [$i], "mtas - facet - {$key} - " );
            }
          }
        } else if ($key == "base") {
          if (! is_array ( $value )) {
            $this->errors [] = "mtas - facet - {$key} should be array";
          } else {
            for($i = 0; $i < count ( $value ); $i ++) {
              $object->{$key} [$i] = $this->checkResponseMtasBase ( $value [$i], "mtas - facet - {$key} - " );
            }
          }
        } else {
          $this->warnings [] = "mtas - facet - {$key} not expected";
        }
      }
      if (count ( $this->errors ) == 0) {
        if (! isset ( $object->field )) {
          $this->errors [] = "mtas - facet - field is obligatory";
        }
        if (! isset ( $object->queries )) {
          $this->errors [] = "mtas - facet - queries is obligatory";
        }
        if (! isset ( $object->base )) {
          $this->errors [] = "mtas - facet - base is obligatory";
        }
      }
      return $object;
    } else {
      $this->warnings [] = "mtas - facet - unexpected type";
      return null;
    }
  }
  /**
   * Check mtas group
   *
   * @param object $object          
   * @return object
   */
  private function checkResponseMtasGroup($object) {
    if ($object && is_object ( $object )) {
      foreach ( $object as $key => $value ) {
        if ($key == "field") {
          if (! is_string ( $value )) {
            $this->errors [] = "mtas - group - {$key} should be string";
          } else {
            $configurations = $this->getConfigurationsForField ( $value );
            if (count ( $configurations ) > 0) {
              $this->__configurations [] = $configurations;
            } else {
              $this->errors [] = "mtas - group - {$key} :  '" . $value . "' not found in any configuration";
            }
          }
        } else if ($key == "key") {
          if (! is_string ( $value )) {
            $this->errors [] = "mtas - group - {$key} should be string";
          }
        } else if ($key == "number" || $key == "start") {
          if (! is_int ( $value )) {
            $this->errors [] = "mtas - group - {$key} should be integer";
          }
        } else if ($key == "query") {
          $object->{$key} = $this->checkResponseMtasQuery ( $value, "mtas - group - {$key} - " );
        } else if ($key == "grouping") {
          if (! is_object ( $value )) {
            $this->errors [] = "mtas - group - {$key} should be object";
          } else {
            $items = 0;
            foreach ( $value as $subKey => $subValue ) {
              if ($subKey == "hit") {
                if (! is_object ( $subValue )) {
                  $this->warnings [] = "mtas - group - {$key} - {$subKey} should be object";
                } else {
                  foreach ( $subValue as $subSubKey => $subSubValue ) {
                    if ($subSubKey == "inside") {
                      if (! is_string ( $subSubValue )) {
                        $this->errors [] = "mtas - group - {$key} - {$subKey} - {$subSubKey} should be string";
                      } else {
                        $items ++;
                      }
                    } else if ($subSubKey == "insideLeft" || $subSubKey == "insideRight" || $subSubKey == "left" || $subSubKey == "right") {
                      if (! is_array ( $subSubValue )) {
                        $this->errors [] = "mtas - group - {$key} - {$subKey} - {$subSubKey} should be non empty array";
                      } else {
                        for($i = 0; $i < count ( $subSubValue ); $i ++) {
                          if (! is_object ( $subSubValue [$i] )) {
                            $this->errors [] = "mtas - group - {$key} - {$subKey} - {$subSubKey}[{$i}] should be an object";
                          } else if (! isset ( $subSubValue [$i]->prefixes ) || ! is_string ( $subSubValue [$i]->prefixes )) {
                            $this->errors [] = "mtas - group - {$key} - {$subKey} - {$subSubKey}[{$i}] - prefixes should be defined (as string)";
                          } else if (! isset ( $subSubValue [$i]->position ) || (! is_string ( $subSubValue [$i]->position ) && ! is_int ( $subSubValue [$i]->position ))) {
                            $this->errors [] = "mtas - group - {$key} - {$subKey} - {$subSubKey}[{$i}] - position should be defined (as string or integer)";
                          } else {
                            $items ++;
                          }
                        }
                      }
                    } else {
                      $this->warnings [] = "mtas - group - {$key} - {$subKey} - {$subSubKey} not expected";
                    }
                  }
                }
              } else if ($subKey == "left" || $subKey == "right") {
                if (! is_array ( $subValue )) {
                  $this->errors [] = "mtas - group - {$key} - {$subKey} should be non empty array";
                } else {
                  for($i = 0; $i < count ( $subValue ); $i ++) {
                    if (! is_object ( $subValue [$i] )) {
                      $this->errors [] = "mtas - group - {$key} - {$subKey} [{$i}] should be an object";
                    } else if (! isset ( $subValue [$i]->prefixes ) || ! is_string ( $subValue [$i]->prefixes )) {
                      $this->errors [] = "mtas - group - {$key} - {$subKey} [{$i}] - prefixes should be defined (as string)";
                    } else if (! isset ( $subValue [$i]->position ) || (! is_string ( $subValue [$i]->position ) && ! is_int ( $subValue [$i]->position ))) {
                      $this->errors [] = "mtas - group - {$key} - {$subKey} [{$i}] - position should be defined (as string or integer)";
                    } else {
                      $items ++;
                    }
                  }
                }
              } else {
                $this->warnings [] = "mtas - group - {$key} - {$subKey} not expected";
              }
            }
            if (! $items) {
              $this->errors [] = "mtas - group - {$key} - no (valid) groupings defined";
            }
          }
        } else {
          $this->warnings [] = "mtas - group - {$key} not expected";
        }
      }
      if (count ( $this->errors ) == 0) {
        if (! isset ( $object->field )) {
          $this->errors [] = "mtas - group - field is obligatory";
        }
        if (! isset ( $object->query )) {
          $this->errors [] = "mtas - group - query is obligatory";
        }
        if (! isset ( $object->grouping )) {
          $this->errors [] = "mtas - group - grouping is obligatory";
        }
      }
      return $object;
    } else {
      $this->warnings [] = "mtas - group - unexpected type";
      return null;
    }
  }
  /**
   * Check mtas prefix
   *
   * @param object $object          
   * @return object
   */
  private function checkResponseMtasPrefix($object) {
    if ($object && is_object ( $object )) {
      foreach ( $object as $key => $value ) {
        if ($key == "field") {
          if (! is_string ( $value )) {
            $this->errors [] = "mtas - prefix - {$key} should be string";
          } else {
            $configurations = $this->getConfigurationsForField ( $value );
            if (count ( $configurations ) > 0) {
              $this->__configurations [] = $configurations;
            } else {
              $this->errors [] = "mtas - prefix - {$key} :  '" . $value . "' not found in any configuration";
            }
          }
        } else if ($key == "key") {
          if (! is_string ( $value )) {
            $this->errors [] = "mtas - prefix - {$key} should be string";
          }
        } else {
          $this->warnings [] = "mtas - prefix - {$key} not expected";
        }
      }
      if (count ( $this->errors ) == 0) {
        if (! isset ( $object->field )) {
          $this->errors [] = "mtas - prefix - field is obligatory";
        }
      }
      return $object;
    } else {
      $this->warnings [] = "mtas - prefix - unexpected type";
      return null;
    }
  }
  /**
   * Check mtas collection
   *
   * @param object $object          
   * @return object
   */
  private function checkResponseMtasCollection($object) {
    if ($object && is_object ( $object )) {
      if (isset ( $object->action ) && is_string ( $object->action )) {
        if ($object->action == "create" || $object->action == "post" || $object->action == "list" || $object->action == "check" || $object->action == "empty" || $object->action == "get" || $object->action == "import" || $object->action == "delete") {
          foreach ( $object as $key => $value ) {
            if ($key == "action") {
              // ignore
            } else if ($key == "key") {
              if (! is_string ( $value )) {
                $this->errors [] = "mtas - collection - {$key} should be string";
              }
            } else if ($key == "field") {
              if ($object->action != "create") {
                $this->warnings [] = "mtas - collection - {$key} not expected for " . $object->action;
              } else if (! is_string ( $value )) {
                $this->errors [] = "mtas - collection - {$key} should be string";
              } else {
                $fields = explode ( ",", $value );
                foreach ( $fields as $field ) {
                  $configurations = $this->getConfigurationsForField ( $field );
                  if (count ( $configurations ) > 0) {
                    $this->__configurations [] = $configurations;
                  } else {
                    $this->errors [] = "mtas - collection - {$key} :  '" . $field . "' not found in any configuration";
                  }
                }
              }
            } else if ($key == "post") {
              if ($object->action != "post") {
                $this->warnings [] = "mtas - collection - {$key} not expected for " . $object->action;
              } else if (! is_array ( $value ) || count ( $value ) == 0) {
                $this->errors [] = "mtas - collection - {$key} should be array of strings or integers";
              } else {
                foreach ( $value as $valueItem ) {
                  if (! is_string ( $valueItem ) && ! is_int ( $valueItem )) {
                    $this->errors [] = "mtas - collection - {$key} should be array of strings or integers";
                  }
                }
              }
            } else if ($key == "id") {
              if ($object->action != "create" && $object->action != "post" && $object->action != "delete" && $object->action != "check" && $object->action != "import" && $object->action != "get") {
                $this->warnings [] = "mtas - collection - {$key} not expected for " . $object->action;
              } else if (! is_string ( $value )) {
                $this->errors [] = "mtas - collection - {$key} should be string";
              }
            } else if ($key == "configuration" || $key == "collection" || $key == "url") {
              if ($object->action != "import") {
                $this->warnings [] = "mtas - collection - {$key} not expected for " . $object->action;
              } else if (! is_string ( $value )) {
                $this->errors [] = "mtas - collection - {$key} should be string";
              }
            } else {
              $this->warnings [] = "mtas - collection - {$key} not expected";
            }
          }
          if ($object->action == "create") {
            if (! isset ( $object->field )) {
              $this->errors [] = "mtas - collection - field is obligatory for action {$object->action}";
            }
          } else if ($object->action == "post") {
            if (! isset ( $object->post )) {
              $this->errors [] = "mtas - collection - post is obligatory for action {$object->action}";
            }
          } else if ($object->action == "get" || $object->action == "delete" || $object->action == "check") {
            if (! isset ( $object->id )) {
              $this->errors [] = "mtas - collection - id is obligatory for action {$object->action}";
            }
          }
          return $object;
        } else {
          $this->errors [] = "mtas - collection - no (valid) action provided";
        }
      } else {
        $this->errors [] = "mtas - collection - no (valid) action provided";
        return null;
      }
    } else {
      $this->warnings [] = "mtas - collection - unexpected type";
      return null;
    }
  }
  /**
   * Check mtas collection
   *
   * @param object $object          
   * @return object
   */
  private function checkResponseMtasVersion($object) {
    return $object;
  }
  /**
   * Check query mtas
   *
   * @param object $object          
   * @param string $prefix          
   * @return object
   */
  private function checkResponseMtasQuery($object, $prefix) {
    if ($object && is_object ( $object )) {
      foreach ( $object as $key => $value ) {
        if ($key == "type" || $key == "value" || $key == "prefix" || $key == "ignore") {
          if (! is_string ( $value )) {
            $this->errors [] = $prefix . "{$key} should be string";
          }
        } else if ($key == "maximumIgnoreLength") {
          if (! is_int ( $value )) {
            $this->errors [] = $prefix . "{$key} should be integer";
          }
        } else if ($key == "variables") {
          $object->{$key} = $this->checkVariables ( $value, false, $prefix );
        } else {
          $this->warnings [] = $prefix . "{$key} not expected";
        }
      }
      if (! isset ( $object->type )) {
        $this->errors [] = $prefix . "type is obligatory";
      }
      if (! isset ( $object->value )) {
        $this->errors [] = $prefix . "value is obligatory";
      }
      return $object;
    } else {
      $this->warnings [] = $prefix . "unexpected type";
      return null;
    }
  }
  /**
   * Check mtas base (facets)
   *
   * @param object $object          
   * @param string $prefix          
   * @return object
   */
  private function checkResponseMtasBase($object, $prefix) {
    if ($object && is_object ( $object )) {
      foreach ( $object as $key => $value ) {
        if ($key == "field" || $key == "type") {
          if (! is_string ( $value )) {
            $this->errors [] = $prefix . "{$key} should be string";
          }
        } else if ($key == "number" || $key == "maximum" || $key == "minimum") {
          if (! is_int ( $value )) {
            $this->errors [] = $prefix . "{$key} should be integer";
          }
        } else if ($key == "sort") {
          if (! is_object ( $value )) {
            $this->errors [] = $prefix . "{$key} should be object";
          } else {
            foreach ( $value as $subKey => $subValue ) {
              if ($subKey == "type" || $subKey == "direction") {
                if (! is_string ( $subValue )) {
                  $this->errors [] = $prefix . "{$key} - {$subKey} should be string";
                }
              } else {
                $this->warnings [] = $prefix . "{$key} - {$subkey} not expected";
              }
            }
          }
        } else if ($key == "range") {
          if (! is_object ( $value )) {
            $this->errors [] = $prefix . "{$key} should be object";
          } else {
            foreach ( $value as $subKey => $subValue ) {
              if ($subKey == "size" || $subKey == "base") {
                if (! is_int ( $subValue )) {
                  $this->errors [] = $prefix . "{$key} - {$subKey} should be integer";
                }
              } else {
                $this->warnings [] = $prefix . "{$key} - {$subkey} not expected";
              }
            }
          }
        } else if ($key == "functions") {
          if (! is_array ( $value )) {
            $this->errors [] = "mtas - facet - {$key} should be array";
          } else {
            for($i = 0; $i < count ( $value ); $i ++) {
              $object->{$key} [$i] = $this->checkResponseMtasFunction ( $value [$i], "mtas - facet - {$key} - " );
            }
          }
        } else {
          $this->warnings [] = $prefix . "{$key} not expected";
        }
      }
      if (! isset ( $object->field )) {
        $this->errors [] = $prefix . "value is obligatory";
      }
      return $object;
    } else {
      $this->warnings [] = $prefix . "unexpected type";
      return null;
    }
  }
  /**
   * Check mtas function
   *
   * @param object $object          
   * @param string $prefix          
   * @return object
   */
  private function checkResponseMtasFunction($object, $prefix) {
    if ($object && is_object ( $object )) {
      foreach ( $object as $key => $value ) {
        if ($key == "expression" || $key == "key" || $key == "type") {
          if (! is_string ( $value )) {
            $this->errors [] = $prefix . "{$key} should be string";
          }
        } else {
          $this->warnings [] = $prefix . "{$key} not expected";
        }
      }
      if (! isset ( $object->expression )) {
        $this->errors [] = $prefix . "expression is obligatory";
      }
      return $object;
    } else {
      $this->warnings [] = $prefix . "unexpected type";
      return null;
    }
  }
  /**
   * Check mtas distance
   *
   * @param object $object          
   * @param string $prefix          
   * @return object
   */
  private function checkResponseMtasDistance($object, $prefix) {
    if ($object && is_object ( $object )) {
      foreach ( $object as $key => $value ) {
        if ($key == "type" || $key == "key" || $key == "base") {
          if (! is_string ( $value )) {
            $this->errors [] = $prefix . "{$key} should be string";
          }
        } else if ($key == "minimum" || $key == "maximum") {
          if (! is_numeric ( $value )) {
            $this->errors [] = $prefix . "{$key} should be numeric";
          }
        } else if ($key == "parameter") {
          if (! is_object ( $value )) {
            $this->errors [] = $prefix . "{$key} should be an object";
          } else {
            foreach ( $value as $subKey => $subValue ) {
              if (! preg_match ( "/^[a-z0-9]+$/i", $subKey )) {
                $this->errors [] = $prefix . "parameter - {$subKey} not allowed";
              } else if (! is_string ( $subValue ) && ! is_numeric ( $subValue )) {
                $this->errors [] = $prefix . "parameter - {$subKey} should be string or numeric";
              }
            }
          }
        } else {
          $this->warnings [] = $prefix . "{$key} not expected";
        }
      }
      if (! isset ( $object->type )) {
        $this->errors [] = $prefix . "type is obligatory";
      }
      if (! isset ( $object->base )) {
        $this->errors [] = $prefix . "base is obligatory";
      }
      return $object;
    } else {
      $this->warnings [] = $prefix . "unexpected type";
      return null;
    }
  }
  /**
   * Check condition
   *
   * @param object $object          
   * @return object
   */
  private function checkCondition($object) {
    static $availableTypes = array (
        "and",
        "or",
        "collection",
        "equals",
        "phrase",
        "wildcard",
        "regexp",
        "cql",
        "geojson",
        "range",
        "join" 
    );
    static $basicTypes = array (
        "equals",
        "phrase",
        "wildcard",
        "regexp",
        "cql",
        "range",
        "geojson"
    );
    static $valueTypes = array (
        "equals",
        "phrase",
        "wildcard",
        "regexp",
        "cql"
    );
    if ($object && is_object ( $object )) {
      $keys = array ();
      // first checks key/values
      foreach ( $object as $key => $value ) {
        $keys [] = $key;
        if ($key == "type") {
          if (! is_string ( $value )) {
            $this->errors [] = "condition - {$key} should be string";
          } else if (! in_array ( $value, $availableTypes )) {
            $this->errors [] = "condition - unknown {$key} '" . $value . "'";
          }
        } else if (isset ( $object->type ) && is_string ( $object->type )) {
          if ($key == "field") {
            if (in_array ( $object->type, $basicTypes )) {
              if (! is_string ( $value )) {
                $this->errors [] = "condition - {$key} should be string";
              } else {
                $configurations = $this->getConfigurationsForField ( $value );
                if (count ( $configurations ) > 0) {
                  $this->__configurations [] = $configurations;
                } else {
                  $this->errors [] = "condition - {$key} '" . $value . "' not found in any configuration";
                }
              }
            } else {
              $this->warnings [] = "condition - {$key} not expected for type '{$object->type}'";
            }
          } else if ($key == "value") {
            if (in_array ( $object->type, $valueTypes )) {
              if (is_array ( $value )) {
                if (count ( $value ) == 0) {
                  $this->errors [] = "condition - array {$key} should not be empty";
                }
                foreach ( $value as $valueItem ) {
                  if (! is_string ( $valueItem ) && ! (is_bool ( $value ) && $object->type == "equals")) {
                    $this->errors [] = "condition - item in array {$key} should be string" . (($object->type == "equals") ? " or boolean" : "");
                    break;
                  }
                }
              } else if (! is_string ( $value ) && ! (is_bool ( $value ) && $object->type == "equals")) {
                $this->errors [] = "condition - {$key} should be string" . (($object->type == "equals") ? " or boolean" : "") . ", or list of them";
              }
            } else {
              $this->warnings [] = "condition - {$key} not expected for type '{$object->type}'";
            }
          } else if ($key == "not" || $key == "facetquery") {
            if (in_array ( $object->type, $basicTypes )) {
              if (! is_bool ( $value )) {
                $this->errors [] = "condition - {$key} should be boolean";
              }
            } else {
              $this->warnings [] = "condition - {$key} not expected for type '{$object->type}'";
            }
          } else if ($key == "expansion") {
            if ($object->type == "equals" || $object->type == "phrase") {
              if (! is_object ( $value )) {
                $this->errors [] = "condition - {$key} should be an object";
              } else if (! isset ( $value->type )) {
                $this->errors [] = "condition - {$key} should have a type defined";
              } else if (! is_string ( $value->type )) {
                $this->errors [] = "condition - {$key} - type should be a string";
              }
            } else {
              $this->warnings [] = "condition - {$key} not expected for type '{$object->type}'";
            }
          } else if ($key == "start" || $key == "end") {
            if ($object->type == "range") {
              if (! is_string ( $value )) {
                $this->errors [] = "condition - {$key} should be string";
              }
            } else {
              $this->warnings [] = "condition - {$key} not expected for type '{$object->type}'";
            }
          } else if ($key == "geometry") {
            if ($object->type == "geojson") {
              if (! is_string ( $value )) {
                $this->errors [] = "condition - {$key} should be string";
              }
            } else {
              $this->warnings [] = "condition - {$key} not expected for type '{$object->type}'";
            }
          } else if ($key == "predicate") {
            if ($object->type == "geojson") {
              if (! is_string ( $value )) {
                $this->errors [] = "condition - {$key} should be string";
              } else if(!in_array($value, array("intersects", "iswithin", "contains", "isdisjointto"))) {
                $this->errors [] = "condition - {$key} cannot be ".$value;
              }
            } else {
              $this->warnings [] = "condition - {$key} not expected for type '{$object->type}'";
            }
          } else if ($key == "list") {
            if ($object->type == "and" || $object->type == "or") {
              if (is_array ( $value )) {
                if (count ( $value ) == 0) {
                  $this->errors [] = "condition - list should not be empty";
                } else {
                  $newvalue = array ();
                  for($i = 0; $i < count ( $value ); $i ++) {
                    $newvalue [$i] = $this->checkCondition ( $value [$i] );                    
                    if (! $newvalue [$i] || ! is_object ( $newvalue [$i] )) {
                      $this->errors [] = "condition - could not check condition from {$object->type} list";
                    }
                  }
                  $object->{$key} = $newvalue;
                }
              } else {
                $this->errors [] = "condition - {$key} should be array";
              }
            } else {
              $this->warnings [] = "condition - {$key} not expected for type '{$object->type}'";
            }
          } else if ($key == "ignore" || $key == "prefix") {
            if ($object->type == "cql") {
              if (! is_string ( $value )) {
                $this->errors [] = "condition - {$key} should be string";
              }
            } else {
              $this->warnings [] = "condition - {$key} not expected for type '{$object->type}'";
            }
          } else if ($key == "maximumIgnoreLength") {
            if ($object->type == "cql") {
              if (! is_int ( $value )) {
                $this->errors [] = "condition - {$key} should be integer";
              }
            } else {
              $this->warnings [] = "condition - {$key} not expected for type '{$object->type}'";
            }
          } else if ($key == "variables") {
            if ($object->type == "cql") {
              if (! is_object ( $value )) {
                $this->errors [] = "condition - {$key} should be an object";
              } else {
                $object->{$key} = $this->checkVariables ( $value, true, "condition - " );
              }
            } else {
              $this->warnings [] = "condition - {$key} not expected for type '{$object->type}'";
            }
          } else if ($key == "from" || $key == "to") {
            if ($object->type == "join") {
              if (! is_string ( $value ) && (!is_array($value) || count($value)==0)) {
                $this->errors [] = "condition - {$key} should be a string or non-empty array of strings";
              } else {
                if(is_array($value)) {
                  foreach($value AS $valueItem) {
                    if (! is_string ( $valueItem )) {
                      $this->errors [] = "condition - {$key} should be a string or array of strings";
                    }
                  }
                }
                if ($key == "to") {
                  if(is_array($value) && count($this->errors)==0) {
                    foreach($value AS $valueItem) {
                      $configurations = $this->getConfigurationsForField ( $valueItem );
                      if (count ( $configurations ) > 0) {
                        $this->__configurations [] = $configurations;
                      } else {
                        $this->errors [] = "condition - {$key} '" . $valueItem . "' not found in any configuration";
                      }
                    }
                  } else if(is_string($value)) {
                    $configurations = $this->getConfigurationsForField ( $value );
                    if (count ( $configurations ) > 0) {
                      $this->__configurations [] = $configurations;
                    } else {
                      $this->errors [] = "condition - {$key} '" . $value . "' not found in any configuration";
                    }
                  }
                }
              }
            } else {
              $this->warnings [] = "condition - {$key} not expected for type '{$object->type}'";
            }
          } else if ($key == "configuration") {
            if ($object->type == "join") {
              if (! is_string ( $value )) {
                $this->errors [] = "condition - {$key} should be a string";
              } 
            } else {
              $this->warnings [] = "condition - {$key} not expected for type '{$object->type}'";
            }
          } else if ($key == "condition" || $key == "filter") {
            if ($object->type == "join") {
              if (! is_object ( $value )) {
                $this->errors [] = "condition - {$key} should be an object";
              }
            } else {
              $this->warnings [] = "condition - {$key} not expected for type '{$object->type}'";
            }
          } else if ($key == "key") {
            if (! isset ( $object->facetquery ) || ! $object->facetquery) {
              $this->warnings [] = "condition - {$key} not expected without 'facetquery'";
            }
          } else {
            $this->warnings [] = "condition - {$key} not expected";
          }
        }
      }
      // check obligatory keys
      if (! in_array ( "type", $keys )) {
        $this->errors [] = "condition - no type defined";
      } else {
        if (in_array ( $object->type, $basicTypes ) && ! in_array ( "field", $keys )) {
          $this->errors [] = "condition - no field defined";
        }
        if (in_array ( $object->type, $valueTypes ) && ! in_array ( "value", $keys )) {
          $this->errors [] = "condition - no value defined";
        }
        if ($object->type == "and" || $object->type == "or") {
          if (! in_array ( "list", $keys )) {
            $this->errors [] = "condition - no list defined";
          }
        } else if ($object->type == "geojson") {
          if (! in_array ( "predicate", $keys )) {
            $this->errors [] = "condition - no predicate defined";
          }
          if (! in_array ( "geometry", $keys )) {
            $this->errors [] = "condition - no geometry defined";
          }
        } else if ($object->type == "collection") {
          if (! in_array ( "url", $keys )) {
            $this->errors [] = "condition - no url defined";
          }
          if (! in_array ( "id", $keys )) {
            $this->errors [] = "condition - no id defined";
          }
        } else if ($object->type == "join") {
          if (! in_array ( "from", $keys )) {
            $this->errors [] = "condition - no from defined";
          }
          if (! in_array ( "to", $keys )) {
            $this->errors [] = "condition - no to defined";
          }
        }
      }
      return $object;
    } else {
      $this->warnings [] = "condition - unexpected type";
      return null;
    }
  }
  /**
   * Check filter
   *
   * @param object $object          
   * @return object
   */
  private function checkFilter($object) {
    if ($object && is_object ( $object )) {
      foreach ( $object as $key => $value ) {
        if ($key == "tag") {
          if (! is_string ( $value )) {
            $this->warnings [] = "filter - tag should be a string";
          }
        } else if ($key == "condition") {
          $this->checkCondition ( $value );
        } else {
          $this->warnings [] = "filter - key '{$key}' not expected";
        }
      }
      if (! isset ( $object->condition )) {
        $this->errors [] = "filter - no condition";
      }
      return $object;
    } else {
      $this->warnings [] = "filter - unexpected type";
      return null;
    }
  }
  /**
   * Check filters
   *
   * @param object $object          
   * @return object
   */
  private function checkFilters($object) {
    if ($object && is_object ( $object )) {
      return $this->checkFilter ( $object );
    } else if ($object && is_array ( $object ) && count ( $object ) > 0) {
      for($i = 0; $i < count ( $object ); $i ++) {
        $object [$i] = $this->checkFilter ( $object [$i] );
      }
      return $object;
    } else {
      $this->warnings [] = "filter - unexpected type";
      return null;
    }
  }
  /**
   * Check variables
   *
   * @param object $object          
   * @param object $fromCondition          
   * @param string $prefixMessage          
   * @return object
   */
  private function checkVariables($object, $fromCondition, $prefixMessage = "") {
    if ($object && is_object ( $object )) {
      $variables = array ();
      $stats = null;
      foreach ( $object as $subkey => $subvalue ) {
        if ($subkey == "definitions") {
          if (! is_array ( $subvalue )) {
            $this->errors [] = $prefixMessage . "definitions in variables should be an array";
          } else {
            foreach ( $subvalue as $subitem ) {
              if ($subitem && is_object ( $subitem )) {
                foreach ( $subitem as $subsubitem => $subsubvalue ) {
                  if ($subsubitem == "name") {
                    if (! is_string ( $subsubvalue )) {
                      $this->errors [] = $prefixMessage . "name in definition for variables should be a string";
                    } else if (! preg_match ( "/^[a-z0-9]+$/i", $subsubvalue )) {
                      $this->errors [] = $prefixMessage . "name '" . $subsubvalue . "' in definition for variables not valid";
                    }
                  } else if ($subsubitem == "value") {
                    if (! is_string ( $subsubvalue )) {
                      $this->errors [] = $prefixMessage . "value in definition for variables should be a string";
                    }
                  } else if ($subsubitem == "expansion") {
                    if (! is_object ( $subsubvalue )) {
                      $this->errors [] = $prefixMessage . "expansion in definition for variables should be an object";
                    }
                  } else {
                    $this->warnings [] = $prefixMessage . "unexpected " . $subsubitem . " in definition for variables";
                  }
                }
                if (! isset ( $subitem->name )) {
                  $this->errors [] = $prefixMessage . "no name defined in definition for variables";
                  $name = null;
                } else {
                  $name = $subitem->name;
                }
                if (! isset ( $subitem->value )) {
                  $this->errors [] = $prefixMessage . "no value defined in definition for variables";
                  $value = null;
                } else {
                  $value = $subitem->value;
                }
                if (! isset ( $subitem->expansion )) {
                  $this->errors [] = $prefixMessage . "no expansion defined in definition for variables";
                  $expansion = null;
                } else {
                  $expansion = $subitem->expansion;
                }
                if ($name != null && $value != null && $expansion != null) {
                  if (isset ( $variables [$name] )) {
                    $this->errors [] = $prefixMessage . "variable '" . $name . "' in definition for variables already defined";
                  } else {
                    $variables [$name] = $this->computeExpansionValues ( $value, $expansion, $prefixMessage );
                  }
                }
              } else {
                $this->errors [] = $prefixMessage . "definition for variables should be an object";
              }
            }
          }
        } else if ($subkey == "stats") {
          if (! is_object ( $subvalue )) {
            $this->errors [] = $prefixMessage . "stats in variables should be object";
          } else {
            $object->{$subkey} = $this->checkMtasStats ( $subvalue, $prefixMessage );
            if ($object->{$subkey} && count ( $this->errors ) == 0) {
              $stats = new \stdClass ();
              if (isset ( $object->{$subkey}->key )) {
                $stats->key = $object->{$subkey}->key;
              }
              if (isset ( $object->{$subkey}->type )) {
                $stats->type = $object->{$subkey}->type;
              }
              if (isset ( $object->{$subkey}->minimum )) {
                $stats->minimum = $object->{$subkey}->minimum;
              }
              if (isset ( $object->{$subkey}->maximum )) {
                $stats->maximum = $object->{$subkey}->maximum;
              }
              if (isset ( $object->{$subkey}->functions )) {
                $stats->functions = array ();
                for($i = 0; $i < count ( $object->{$subkey}->functions ); $i ++) {
                  $stats->functions [$i] = clone $object->{$subkey}->functions [$i];
                }
              }
            }
          }
        } else {
          $this->warnings [] = $prefixMessage . $subkey . " not expected in variables";
        }
      }
      $object->__stats = array ();
      $object->__variables = $variables;
      if ($stats) {
        if ($fromCondition) {
          $statsItem = clone $stats;
          $statsItem->__variables = $variables;
          $object->__stats [] = $statsItem;
        }
        $variableCombinations = $this->createVariableCombinations ( $variables, array () );
        foreach ( $variableCombinations as $variableCombination ) {
          $statsItem = clone $stats;
          $itemKeyList = array ();
          foreach ( $variableCombination as $combinationKey => $combinationValue ) {
            $itemKeyList [] = $combinationKey . ":" . implode ( ",", $combinationValue );
          }
          if (isset ( $statsItem->key )) {
            $statsItem->key .= " - " . implode ( ",", $itemKeyList );
          } else {
            $statsItem->key = implode ( ",", $itemKeyList );
          }
          $statsItem->__variables = $variableCombination;
          $object->__stats [] = $statsItem;
        }
      }
      return $object;
    } else {
      $this->errors [] = $prefixMessage . "variables should be an object";
      return null;
    }
  }
  /**
   * Check mtas stats
   *
   * @param object $object          
   * @param string $prefixMessage          
   * @return object
   */
  private function checkMtasStats($object, $prefixMessage = "") {
    if ($object && is_object ( $object )) {
      foreach ( $object as $key => $value ) {
        if ($key == "key" || $key == "type") {
          if (! is_string ( $value )) {
            $this->errors [] = $prefixMessage . "{$key} should be string";
          }
        } else if ($key == "functions") {
          if (! is_array ( $value )) {
            $this->errors [] = $prefixMessage . "{$key} should be array";
          } else {
            for($i = 0; $i < count ( $value ); $i ++) {
              $object->{$key} [$i] = $this->checkMtasStatsFunction ( $value [$i] );
            }
          }
        } else if ($key == "minimum" || $key == "maximum") {
          if (! is_int ( $value )) {
            $this->errors [] = $prefixMessage . "{$key} should be integer";
          }
        } else {
          $this->warnings [] = $prefixMessage . "{$key} not expected";
        }
      }
      return $object;
    } else {
      $this->warnings [] = $prefixMessage . "unexpected type";
      return null;
    }
  }
  /**
   * Check mtas stats functions
   *
   * @param object $object          
   * @param string $prefixMessage          
   * @return object
   */
  private function checkMtasStatsFunction($object, $prefixMessage = "") {
    if ($object && is_object ( $object )) {
      foreach ( $object as $key => $value ) {
        if ($key == "key" || $key == "expression" || $key == "type") {
          if (! is_string ( $value )) {
            $this->errors [] = $prefixMessage . "{$key} should be string";
          }
        } else {
          $this->warnings [] = $prefixMessage . "{$key} not expected";
        }
      }
      if (! isset ( $object->expression )) {
        $this->errors [] = $prefixMessage . "expression is obligatory";
      }
      return $object;
    } else {
      $this->warnings [] = $prefixMessage . "unexpected type";
      return null;
    }
  }
  /**
   * Parse cache
   *
   * @param object $object          
   * @return null
   */
  private function parseCache($object) {
    return null;
  }
  /**
   * Parse timeAllowed
   *
   * @param object $object          
   * @return null
   */
  private function parseTimeAllowed($object) {
    if ($object && is_numeric ( $object )) {
      return "timeAllowed=" . urlencode ( $object );
    }
    return null;
  }
  /**
   * Parse debug
   *
   * @param object $object          
   * @return string|NULL
   */
  private function parseDebug($object) {
    if ($object && is_string ( $object )) {
      return "debug=" . urlencode ( $object );
    }
    return null;
  }
  /**
   * parse Sort
   *
   * @param object $object          
   * @return string|NULL
   */
  private function parseSort($object) {
    if ($object && is_array ( $object )) {
      $sortList = array ();
      foreach ( $object as $sortItem ) {
        if (isset ( $sortItem->direction ) && ($sortItem->direction == "asc")) {
          $sortList [] = $sortItem->field . " ASC";
        } else if (isset ( $sortItem->direction ) && ($sortItem->direction == "desc")) {
          $sortList [] = $sortItem->field . " DESC";
        } else if ($sortItem->field == "sort") {
          $sortList [] = $sortItem->field . " DESC";
        } else {
          $sortList [] = $sortItem->field . " ASC";
        }
      }
      if (count ( $sortList ) > 0) {
        return "sort=" . urlencode ( implode ( ",", $sortList ) );
      }
    }
    return null;
  }
  /**
   * Parse response
   *
   * @param object $object          
   * @param array $facetQueries          
   * @param array $mtasStats          
   * @return object
   */
  private function parseResponse($object, $facetQueries, $mtasStats) {
    if ($object && is_object ( $object )) {
      $requestList = array ();
      $requestAdditionalList = array ();
      if (! isset ( $object->documents )) {
        $requestList [] = "rows=0";
      } else {
        $object->documents = $this->parseResponseDocuments ( $object->documents );
        if ($object->documents != null && isset ( $object->documents->__requestList )) {
          $requestList = array_merge ( $requestList, $object->documents->__requestList );
        }
      }
      if (isset ( $object->facets ) || count ( $facetQueries ) > 0) {
        if (! isset ( $object->facets )) {
          $object->facets = new \stdClass ();
        }
        $object->facets = $this->parseResponseFacets ( $object->facets, $facetQueries );
        if ($object->facets != null && isset ( $object->facets->__requestList )) {
          $requestList = array_merge ( $requestList, $object->facets->__requestList );
        }
      }
      if (isset ( $object->stats )) {
        $object->stats = $this->parseResponseStats ( $object->stats );
        if ($object->stats != null && isset ( $object->stats->__requestList )) {
          $requestList = array_merge ( $requestList, $object->stats->__requestList );
        }
      }
      if (isset ( $object->mtas ) || count ( $mtasStats ) > 0 || $this->statusKey != null) {
        if(isset ( $object->mtas ) || count ( $mtasStats ) > 0) {
          if(!isset ( $object->mtas )) {
            $object->mtas = new \stdClass();
          }
          $object->mtas = $this->parseResponseMtas ( $object->mtas, $mtasStats);
        } else {
          $object->mtas = $this->parseResponseMtas ( null, $mtasStats);          
        }
        if ($object->mtas != null) {
          if (isset ( $object->mtas->__requestList )) {
            $requestList = array_merge ( $requestList, $object->mtas->__requestList );
          }
          if (isset ( $object->mtas->__requestAdditionalList )) {
            $requestAdditionalList = array_merge ( $requestAdditionalList, $object->mtas->__requestAdditionalList );
          }
        }
      }
      $object->__requestList = $requestList;
      $object->__requestAdditionalList = $requestAdditionalList;
      return $object;
    } else if ($this->statusKey != null) {
      $object = new \stdClass ();
      $object->mtas = new \stdClass ();
      $object->mtas = $this->parseResponseMtas ( $object->mtas, $mtasStats);
      if ($object->mtas != null) {
        if (isset ( $object->mtas->__requestList )) {
          $object->__requestList = $object->mtas->__requestList;
        }
        if (isset ( $object->mtas->__requestAdditionalList )) {
          $object->__requestAdditionalList = $object->mtas->__requestAdditionalList;
        }        
      }
      return $object;
    } else {
      return null;
    }
  }
  /**
   * Parse documents in response
   *
   * @param object $object          
   * @return object
   */
  private function parseResponseDocuments($object) {
    if ($object && is_object ( $object )) {
      foreach ( $object as $key => $value ) {
        if ($key == "fields") {
          $fields = array ();
          foreach ( $value as $fieldItem ) {
            if (is_string ( $fieldItem )) {
              $fields [] = $fieldItem;
            } else if (is_object ( $fieldItem ) && isset ( $fieldItem->type ) && is_string ( $fieldItem->type )) {
              if ($fieldItem->type == "join") {
                $fields [] = $fieldItem->from;
                $object->{$key} = $this->parseResponseDocumentsJoin ( $fieldItem );
              }
            }
          }
          $requestList [] = "fl=" . urlencode ( implode ( ",", array_unique ( $fields ) ) );
        } else if ($key == "start") {
          $requestList [] = "start=" . $value;
        } else if ($key == "rows") {
          $requestList [] = "rows=" . $value;
        }
      }
      $object->__requestList = $requestList;
      return $object;
    } else {
      return null;
    }
  }
  /**
   * Parse join in documents
   *
   * @param object $object          
   * @return object
   */
  private function parseResponseDocumentsJoin($object) {
    if ($object && is_object ( $object )) {
      if (! isset ( $this->responseJoins->documents )) {
        $this->responseJoins->documents = array ();
      }
      $responseJoinItem = new \stdClass ();
      $responseJoinItem->to = $object->to;
      $responseJoinItem->from = $object->from;
      $responseJoinItem->fields = $object->fields;
      $responseJoinItem->name = $object->name;
      $responseJoinItem->configuration = isset ( $object->configuration ) ? $object->configuration : $this->solrConfiguration;
      if (isset ( $object->condition ) && is_object ( $object->condition )) {
        $responseJoinItem->condition = clone $object->condition;
      }
      if (isset ( $object->filter ) && is_object ( $object->filter )) {
        $responseJoinItem->filter = clone $object->filter;
      }
      $this->responseJoins->documents [] = $responseJoinItem;
    }
    return $object;
  }
  /**
   * Parse facets in response
   *
   * @param object $object          
   * @param object $facetqueries          
   * @return object
   */
  private function parseResponseFacets($object, $facetqueries) {
    if (($object && is_object ( $object ))) {
      $requestList = array ();
      $requestList [] = "facet=true";
      $keyListFacetQueries = array ();
      // create defined facetqueries
      foreach ( $object as $key => $value ) {
        if ($key == "facetfields") {
          $requestList = $this->parseResponseFacetFields ( $value, $requestList );
        } else if ($key == "facetqueries") {
          list ( $requestList, $keyListFacetQueries ) = $this->parseResponseFacetQueries ( $value, $requestList, $keyListFacetQueries );
        } else if ($key == "facetranges") {
          $requestList = $this->parseResponseFacetRanges ( $value, $requestList );
        } else if ($key == "facetpivots") {
          $requestList = $this->parseResponseFacetPivots ( $value, $requestList );
        } else if ($key == "facetheatmaps") {
          $requestList = $this->parseResponseFacetHeatmaps ( $value, $requestList );
        } else if ($key == "prefix" || $key == "sort" || $key == "method" || $key == "contains") {
          $requestList [] = "facet.{$key}=" . urlencode ( $value );
        } else if ($key == "limit" || $key == "offset" || $key == "mincount") {
          $requestList [] = "facet.{$key}=" . intval ( $value );
        } else if ($key == "missing") {
          $requestList [] = "facet.{$key}=" . ($value ? "true" : "false");
        } else if ($key == "excludeTerms") {
          $requestList [] = "facet.{$key}=" . urlencode ( implode ( ",", $value ) );
        }
      }
      // add automatically generated facetqueries
      if ($facetqueries && is_array ( $facetqueries ) && count ( $facetqueries ) > 0) {
        list ( $requestList, $keyListFacetQueries ) = $this->parseResponseFacetQueries ( $facetqueries, $requestList, $keyListFacetQueries );
      }
      // return results
      $object->__requestList = $requestList;
      return $object;
    } else {
      $this->warnings [] = "facets - unexpected type";
      return null;
    }
  }
  /**
   * Parse facet fields
   *
   * @param object $object          
   * @param array $requestList          
   * @return array
   */
  private function parseResponseFacetFields($object, array $requestList) {
    if ($object != null && is_array ( $object )) {
      for($i = 0; $i < count ( $object ); $i ++) {
        $object [$i] = $this->parseResponseFacetField ( $object [$i], $i );
        if ($object [$i] && is_object ( $object [$i] ) && isset ( $object [$i]->__requestList )) {
          $requestList = array_merge ( $requestList, $object [$i]->__requestList );
        }
      }
    }
    return $requestList;
  }
  /**
   * Parse facet field
   *
   * @param object $object          
   * @param number $i          
   * @return object
   */
  private function parseResponseFacetField($object, $i) {
    if ($object && is_object ( $object )) {
      $requestList = array ();
      if (isset ( $object->__options ) && is_array ( $object->__options ) && count ( $object->__options ) > 0) {
        $requestList [] = "facet.field=" . urlencode ( "{!" . implode ( " ", $object->__options ) . "}" . $object->field );
      } else {
        $requestList [] = "facet.field=" . urlencode ( $object->field );
      }
      $ignoreList = array (
          "field",
          "ex",
          "key",
          "prefix",
          "sort",
          "method",
          "contains",
          "missing" 
      );
      foreach ( $object as $key => $value ) {
        if (in_array ( $key, $ignoreList )) {
          // ignore
        } else if ($key == "limit" || $key == "offset" || $key == "mincount") {
          if (is_integer ( $value )) {
            $requestList [] = "f." . urlencode ( $object->field ) . ".facet.{$key}=" . $value;
          }
        } else if ($key == "join") {
          $object->join = $this->parseResponseFacetFieldJoin ( $value, isset ( $object->key ) ? $object->key : $object->field, $i );
        }
      }
      $object->__requestList = $requestList;
      return $object;
    } else {
      return null;
    }
  }
  /**
   * Parse join facet field
   *
   * @param object $object          
   * @param string $key          
   * @param number $i          
   * @return object
   */
  private function parseResponseFacetFieldJoin($object, $key, $i) {
    if ($object && is_object ( $object )) {
      if (! isset ( $this->responseJoins->facetfield )) {
        $this->responseJoins->facetfield = array ();
      }
      $responseJoinItem = new \stdClass ();
      $responseJoinItem->to = $object->to;
      $responseJoinItem->fields = $object->fields;
      $responseJoinItem->key = $key;
      $responseJoinItem->configuration = isset ( $object->configuration ) ? $object->configuration : $this->solrConfiguration;
      $this->responseJoins->facetfield [] = $responseJoinItem;
    }
    return $object;
  }
  /**
   * Parse facet queries
   *
   * @param object $object          
   * @param array $requestList          
   * @param array $keyListFacetQueries          
   * @return array
   */
  private function parseResponseFacetQueries($object, $requestList, $keyListFacetQueries) {
    if ($object != null && is_array ( $object )) {
      for($i = 0; $i < count ( $object ); $i ++) {
        list ( $object [$i], $keyItem ) = $this->parseResponseFacetQuery ( $object [$i], $keyListFacetQueries, $i );
        if ($object [$i] && is_object ( $object [$i] ) && isset ( $object [$i]->__requestList )) {
          $requestList = array_merge ( $requestList, $object [$i]->__requestList );
          $keyListFacetQueries [] = $keyItem;
        }
      }
    }
    return array (
        $requestList,
        $keyListFacetQueries 
    );
  }
  /**
   * Parse facet query
   *
   * @param object $object          
   * @param array $keyListFacetQueries          
   * @param number $i          
   * @return array
   */
  private function parseResponseFacetQuery($object, $keyListFacetQueries, $i) {
    if ($object && is_object ( $object )) {
      $requestList = array ();
      $key = null;
      if (isset ( $object->__query ) && $object->__query && is_string ( $object->__query )) {
        $options = array ();
        // check provided key
        if (isset ( $object->key ) && is_string ( $object->key )) {
          $key = $object->key;
        }
        // define key to be used
        $key = ($key == null) ? $object->__query : $key;
        // check uniqueness
        $counter = 0;
        if (in_array ( $key, $keyListFacetQueries )) {
          $counter ++;
          while ( in_array ( $key . " (" . $counter . ")", $keyListFacetQueries ) ) {
            $counter ++;
          }
          $key = $key . " (" . $counter . ")";
        }
        // decide to set key
        if ($counter > 0 || (isset ( $object->key ) && is_string ( $object->key ))) {
          $options [] = "key=\"" . str_replace ( "\"", "\\\"", $key ) . "\"";
        }
        // check provided exclusion of filters
        if (isset ( $object->ex ) && is_string ( $object->ex )) {
          $options [] = "ex=\"" . implode ( ",", array_map ( "base64_encode", explode ( ",", $object->ex ) ) ) . "\"";
        }
        if (isset ( $object->tag ) && is_string ( $object->tag )) {
          $options [] = "tag=\"" . str_replace ( "\"", "\\\"", $object->tag ) . "\"";
        }
        // create request
        if (count ( $options ) > 0) {
          $requestList [] = "facet.query=" . urlencode ( "{!" . implode ( " ", $options ) . "}" . $object->__query );
        } else {
          $requestList [] = "facet.query=" . urlencode ( $object->__query );
        }
      } else if (is_object ( $object ) && isset ( $object->condition ) && $object->condition && is_object ( $object->condition )) {
        $object->condition = $this->parseCondition ( $object->condition );
        if (isset ( $object->condition->__query )) {
          $options = array ();
          // check provided key
          if (isset ( $object->key ) && is_string ( $object->key )) {
            $key = $object->key;
          } else {
            $key = null;
          }
          // define key to be used
          $key = ($key == null) ? $object->condition->__query : $key;
          // check uniqueness
          $counter = 0;
          if (in_array ( $key, $keyListFacetQueries )) {
            $counter ++;
            while ( in_array ( $key . " (" . $counter . ")", $keyListFacetQueries ) ) {
              $counter ++;
            }
            $key = $key . " (" . $counter . ")";
          }
          $options [] = "key=\"" . str_replace ( "\"", "\\\"", $key ) . "\"";
          // check provided exclusion of filters
          if (isset ( $object->ex ) && is_string ( $object->ex )) {
            $options [] = "ex=\"" . implode ( ",", array_map ( "base64_encode", explode ( ",", $object->ex ) ) ) . "\"";
          }
          if (isset ( $object->tag ) && is_string ( $object->tag )) {
            $options [] = "tag=\"" . str_replace ( "\"", "\\\"", $object->tag ) . "\"";
          }
          // create request
          if (count ( $options ) > 0) {
            $requestList [] = "facet.query=" . urlencode ( "{!" . implode ( " ", $options ) . "}" . $object->condition->__query );
          } else {
            $requestList [] = "facet.query=" . urlencode ( $object->condition->__query );
          }
          if (count ( $object->condition->__facetQueries ) > 0) {
            $this->warnings [] = "facets - facetqueries - condition in facetquery should not produce facetqueries";
          }
          if (count ( $object->condition->__mtasStats ) > 0) {
            $this->warnings [] = "facets - facetqueries - condition in facetquery should not produce mtasStats";
          }
        }
      }
      if (is_object ( $object )) {
        $object->__requestList = $requestList;
      }
      return array (
          $object,
          $key 
      );
    } else {
      return array (
          null,
          null 
      );
    }
  }
  /**
   * Parse facet range
   *
   * @param object $object          
   * @param array $requestList          
   * @return array
   */
  private function parseResponseFacetRanges($object, $requestList) {
    if ($object != null && is_array ( $object )) {
      for($i = 0; $i < count ( $object ); $i ++) {
        $object [$i] = $this->parseResponseFacetRange ( $object [$i], $i );
        if ($object [$i] && is_object ( $object [$i] ) && isset ( $object [$i]->__requestList )) {
          $requestList = array_merge ( $requestList, $object [$i]->__requestList );
        }
      }
    }
    return $requestList;
  }
  /**
   * Parse facet range
   *
   * @param object $object          
   * @param number $i          
   * @return object
   */
  private function parseResponseFacetRange($object, $i) {
    if ($object && is_object ( $object )) {
      $requestList = array ();
      if (isset ( $object->__options ) && is_array ( $object->__options ) && count ( $object->__options ) > 0) {
        $requestList [] = "facet.range=" . urlencode ( "{!" . implode ( " ", $object->__options ) . "}" . $object->field );
      } else {
        $requestList [] = "facet.range=" . urlencode ( $object->field );
      }
      $object->__requestList = $requestList;
      return $object;
    } else {
      return null;
    }
  }
  /**
   * Parse facet pivots
   *
   * @param object $object          
   * @param array $requestList          
   * @return array
   */
  private function parseResponseFacetPivots($object, array $requestList) {
    if ($object != null && is_array ( $object )) {
      for($i = 0; $i < count ( $object ); $i ++) {
        $object [$i] = $this->parseResponseFacetPivot ( $object [$i], $i );
        if ($object [$i] && is_object ( $object [$i] ) && isset ( $object [$i]->__requestList )) {
          $requestList = array_merge ( $requestList, $object [$i]->__requestList );
        }
      }
    }
    return $requestList;
  }
  /**
   * Parse facet pivot
   *
   * @param object $object          
   * @param number $i          
   * @return object
   */
  private function parseResponseFacetPivot($object, $i) {
    if ($object && is_object ( $object )) {
      $requestList = array ();
      if (isset ( $object->__options ) && is_array ( $object->__options ) && count ( $object->__options ) > 0) {
        $requestList [] = "facet.pivot=" . urlencode ( "{!" . implode ( " ", $object->__options ) . "}" . implode ( ",", $object->pivot ) );
      } else {
        $requestList [] = "facet.pivot=" . urlencode ( implode ( ",", $object->pivot ) );
      }
      $object->__requestList = $requestList;
      return $object;
    } else {
      return null;
    }
  }
  /**
   * Parse facet heatmaps
   *
   * @param object $object
   * @param array $requestList
   * @return array
   */
  private function parseResponseFacetHeatmaps($object, array $requestList) {
    if ($object != null && is_array ( $object )) {
      for($i = 0; $i < count ( $object ); $i ++) {
        $object [$i] = $this->parseResponseFacetHeatmap ( $object [$i], $i );
        if ($object [$i] && is_object ( $object [$i] ) && isset ( $object [$i]->__requestList )) {
          $requestList = array_merge ( $requestList, $object [$i]->__requestList );
        }
      }
    }
    return $requestList;
  }
  /**
   * Parse facet heatmap
   *
   * @param object $object          
   * @param number $i          
   * @return object
   */
  private function parseResponseFacetHeatmap($object, $i) {
    if ($object && is_object ( $object )) {
      $requestList = array ();
      if (isset ( $object->__options ) && is_array ( $object->__options ) && count ( $object->__options ) > 0) {
        $requestList [] = "facet.heatmap=" . urlencode ( "{!" . implode ( " ", $object->__options ) . "}" . $object->field );
      } else {
        $requestList [] = "facet.heatmap=" . urlencode ( $object->field );
      }
      $object->__requestList = $requestList;
      return $object;
    } else {
      return null;
    }
  }
  /**
   * Parse stats in response
   *
   * @param object $object
   *          return unknown
   */
  private function parseResponseStats($object) {
    if (($object && is_object ( $object ))) {
      $requestList = array ();
      $requestList [] = "stats=true";
      foreach ( $object as $key => $value ) {
        if ($key == "statsfields") {
          $requestList = $this->parseResponseStatsFields ( $value, $requestList );
        }
      }
      // return results
      $object->__requestList = $requestList;
      return $object;
    } else {
      $this->warnings [] = "stats - unexpected type";
      return null;
    }
  }
  /**
   * Parse stats fields
   *
   * @param object $object          
   * @param array $requestList          
   * @return array
   */
  private function parseResponseStatsFields($object, array $requestList) {
    if ($object != null && is_array ( $object )) {
      for($i = 0; $i < count ( $object ); $i ++) {
        $object [$i] = $this->parseResponseStatsField ( $object [$i], $i );
        if ($object [$i] && is_object ( $object [$i] ) && isset ( $object [$i]->__requestList )) {
          $requestList = array_merge ( $requestList, $object [$i]->__requestList );
        }
      }
    }
    return $requestList;
  }
  /**
   * Parse stats field
   *
   * @param object $object          
   * @param number $i          
   * @return object
   */
  private function parseResponseStatsField($object, $i) {
    if ($object && is_object ( $object )) {
      $requestList = array ();
      if (isset ( $object->__options ) && is_array ( $object->__options ) && count ( $object->__options ) > 0) {
        $requestList [] = "stats.field=" . urlencode ( "{!" . implode ( " ", $object->__options ) . "}" . $object->field );
      } else {
        $requestList [] = "stats.field=" . urlencode ( $object->field );
      }
      $object->__requestList = $requestList;
      return $object;
    } else {
      return null;
    }
  }
  /**
   * Parse mtas in response
   *
   * @param object $object          
   * @param array $mtasStats          
   * @return object
   */
  private function parseResponseMtas($object, $mtasStats) {
    $localErrors = array ();
    $localWarnings = array ();
    if ($object && is_object ( $object )) {
      $requestList = array ();
      $requestAdditionalList = array ();
      $requestList [] = "mtas=true";
      // add status key
      if ($this->statusKey != null) {
        $requestAdditionalList [] = "mtas.status=true";
        $requestAdditionalList [] = "mtas.status.key=".urlencode($this->statusKey);
      }
      // check response
      foreach ( $object as $key => $value ) {
        if ($key == "stats") {
          if ($value && is_object ( $value )) {
            $object->{$key} = $this->parseResponseMtasStats ( $value, $mtasStats );
            $requestList = array_merge ( $requestList, $object->{$key}->__requestList );
          }
        } else if ($key == "document") {
          if ($value && is_array ( $value )) {
            $requestList [] = "mtas.{$key}=true";
            for($i = 0; $i < count ( $value ); $i ++) {
              $object->{$key} [$i] = $this->parseResponseMtasDocument ( $object->{$key} [$i], $i );
              if ($object->{$key} [$i] && is_object ( $object->{$key} [$i] ) && isset ( $object->{$key} [$i]->__requestList )) {
                $requestList = array_merge ( $requestList, $object->{$key} [$i]->__requestList );
              }
            }
          }
        } else if ($key == "kwic" || $key == "list") {
          if ($value && is_array ( $value )) {
            $requestList [] = "mtas.{$key}=true";
            for($i = 0; $i < count ( $value ); $i ++) {
              $object->{$key} [$i] = $this->parseResponseMtasKwicAndList ( $key, $object->{$key} [$i], $i );
              if ($object->{$key} [$i] && is_object ( $object->{$key} [$i] ) && isset ( $object->{$key} [$i]->__requestList )) {
                $requestList = array_merge ( $requestList, $object->{$key} [$i]->__requestList );
              }
            }
          }
        } else if ($key == "termvector") {
          if ($value && is_array ( $value )) {
            $requestList [] = "mtas.{$key}=true";
            for($i = 0; $i < count ( $value ); $i ++) {
              $object->{$key} [$i] = $this->parseResponseMtasTermvector ( $object->{$key} [$i], $i );
              if ($object->{$key} [$i] && is_object ( $object->{$key} [$i] ) && isset ( $object->{$key} [$i]->__requestList )) {
                $requestList = array_merge ( $requestList, $object->{$key} [$i]->__requestList );
              }
            }
          }
        } else if ($key == "facet") {
          if ($value && is_array ( $value )) {
            $requestList [] = "mtas.{$key}=true";
            for($i = 0; $i < count ( $value ); $i ++) {
              $object->{$key} [$i] = $this->parseResponseMtasFacet ( $object->{$key} [$i], $i );
              if ($object->{$key} [$i] && is_object ( $object->{$key} [$i] ) && isset ( $object->{$key} [$i]->__requestList )) {
                $requestList = array_merge ( $requestList, $object->{$key} [$i]->__requestList );
              }
            }
          }
        } else if ($key == "group") {
          if ($value && is_array ( $value )) {
            $requestList [] = "mtas.{$key}=true";
            for($i = 0; $i < count ( $value ); $i ++) {
              $object->{$key} [$i] = $this->parseResponseMtasGroup ( $object->{$key} [$i], $i );
              if ($object->{$key} [$i] && is_object ( $object->{$key} [$i] ) && isset ( $object->{$key} [$i]->__requestList )) {
                $requestList = array_merge ( $requestList, $object->{$key} [$i]->__requestList );
              }
            }
          }
        } else if ($key == "prefix") {
          if ($value && is_array ( $value )) {
            $requestList [] = "mtas.{$key}=true";
            for($i = 0; $i < count ( $value ); $i ++) {
              $object->{$key} [$i] = $this->parseResponseMtasPrefix ( $object->{$key} [$i], $i );
              if ($object->{$key} [$i] && is_object ( $object->{$key} [$i] ) && isset ( $object->{$key} [$i]->__requestList )) {
                $requestList = array_merge ( $requestList, $object->{$key} [$i]->__requestList );
              }
            }
          }
        } else if ($key == "collection") {
          if ($value && is_array ( $value )) {
            $requestList [] = "mtas.collection=true";
            for($i = 0; $i < count ( $value ); $i ++) {
              $value [$i] = $this->parseResponseMtasCollection ( $value [$i], $i );
              if ($value [$i] && is_object ( $value [$i] ) && isset ( $value [$i]->__requestList )) {
                $requestList = array_merge ( $requestList, $value [$i]->__requestList );
              }
            }
          }
        } else if ($key == "version") {
          if ($value && is_bool ( $value )) {
            $requestList [] = "mtas.version=".($value?"true":"false"); 
          }
        }
      }
      if (! isset ( $object->stats ) && $mtasStats && count ( $mtasStats ) > 0) {
        $object->stats = $this->parseResponseMtasStats ( new \stdClass (), $mtasStats );
        $requestList = array_merge ( $requestList, $object->stats->__requestList );
      }
      $object->__requestList = $requestList;
      $object->__requestAdditionalList = $requestAdditionalList;
      $this->errors = array_merge ( $this->errors, $localErrors );
      $this->warnings = array_merge ( $this->warnings, $localWarnings );
      return $object;
    } else if($this->statusKey!=null) {
      $object = new \stdClass();
      $requestAdditionalList = array();
      $requestAdditionalList [] = "mtas=true";
      $requestAdditionalList [] = "mtas.status=true";
      $requestAdditionalList [] = "mtas.status.key=".urlencode($this->statusKey);
      $object->__requestList = array();
      $object->__requestAdditionalList = $requestAdditionalList;
      return $object;
    } else {
      $localWarnings [] = "mtas - unexpected type";
      return null;
    }
  }
  /**
   * Parse mtas stats
   *
   * @param object $object          
   * @param array $mtasStats          
   * @return object
   */
  private function parseResponseMtasStats($object, $mtasStats) {
    if ($object && is_object ( $object )) {
      $requestList = array ();
      $requestList [] = "mtas.stats=true";
      if (isset ( $object->positions ) && is_array ( $object->positions )) {
        $requestList [] = "mtas.stats.positions=true";
        for($i = 0; $i < count ( $object->positions ); $i ++) {
          $object->positions [$i] = $this->parseResponseMtasStatsPositions ( $object->positions [$i], $i );
          if ($object->positions [$i] && is_object ( $object->positions [$i] ) && isset ( $object->positions [$i]->__requestList )) {
            $requestList = array_merge ( $requestList, $object->positions [$i]->__requestList );
          }
        }
      }
      if (isset ( $object->tokens ) && is_array ( $object->tokens )) {
        $requestList [] = "mtas.stats.tokens=true";
        for($i = 0; $i < count ( $object->tokens ); $i ++) {
          $object->tokens [$i] = $this->parseResponseMtasStatsTokens ( $object->tokens [$i], $i );
          if ($object->tokens [$i] && is_object ( $object->tokens [$i] ) && isset ( $object->tokens [$i]->__requestList )) {
            $requestList = array_merge ( $requestList, $object->tokens [$i]->__requestList );
          }
        }
      }
      if (isset ( $object->spans ) && is_array ( $object->spans ) || ($mtasStats && is_array ( $mtasStats ) && count ( $mtasStats ) > 0)) {
        $requestList [] = "mtas.stats.spans=true";
        if (isset ( $object->spans ) && is_array ( $object->spans )) {
          for($i = 0; $i < count ( $object->spans ); $i ++) {
            $object->spans [$i] = $this->parseResponseMtasStatsSpans ( $object->spans [$i], $i );
            if ($object->spans [$i] && is_object ( $object->spans [$i] ) && isset ( $object->spans [$i]->__requestList )) {
              $requestList = array_merge ( $requestList, $object->spans [$i]->__requestList );
            }
          }
        } else {
          $object->spans = array ();
        }
        if ($mtasStats && is_array ( $mtasStats )) {
          $base = count ( $object->spans );
          for($i = 0; $i < count ( $mtasStats ); $i ++) {
            $object->spans [($i + $base)] = $this->parseResponseMtasStatsSpans ( $mtasStats [$i], ($i + $base) );
            if ($object->spans [($i + $base)] && is_object ( $object->spans [($i + $base)] ) && isset ( $object->spans [($i + $base)]->__requestList )) {
              $requestList = array_merge ( $requestList, $object->spans [($i + $base)]->__requestList );
            }
          }
        }
      }
      $object->__requestList = $requestList;
      return $object;
    } else {
      return null;
    }
  }
  /**
   * Parse mtas stats positions
   *
   * @param object $object          
   * @param number $i          
   * @return object
   */
  private function parseResponseMtasStatsPositions($object, $i) {
    if ($object && is_object ( $object )) {
      $requestList = array ();
      if (isset ( $object->key ) && is_string ( $object->key )) {
        $requestList [] = "mtas.stats.positions." . $i . ".key=" . urlencode ( $object->key );
      }
      if (isset ( $object->field ) && is_string ( $object->field )) {
        $requestList [] = "mtas.stats.positions." . $i . ".field=" . urlencode ( $object->field );
      }
      if (isset ( $object->type ) && is_string ( $object->type )) {
        $requestList [] = "mtas.stats.positions." . $i . ".type=" . urlencode ( $object->type );
      }
      if (isset ( $object->minimum ) && is_int ( $object->minimum )) {
        $requestList [] = "mtas.stats.positions." . $i . ".minimum=" . urlencode ( $object->minimum );
      }
      if (isset ( $object->maximum ) && is_int ( $object->maximum )) {
        $requestList [] = "mtas.stats.positions." . $i . ".maximum=" . urlencode ( $object->maximum );
      }
      $object->__requestList = $requestList;
      return $object;
    } else {
      return null;
    }
  }
  /**
   * Parse mtas stats tokens
   *
   * @param object $object          
   * @param number $i          
   * @return object
   */
  private function parseResponseMtasStatsTokens($object, $i) {
    if ($object && is_object ( $object )) {
      $requestList = array ();
      if (isset ( $object->key ) && is_string ( $object->key )) {
        $requestList [] = "mtas.stats.tokens." . $i . ".key=" . urlencode ( $object->key );
      }
      if (isset ( $object->field ) && is_string ( $object->field )) {
        $requestList [] = "mtas.stats.tokens." . $i . ".field=" . urlencode ( $object->field );
      }
      if (isset ( $object->type ) && is_string ( $object->type )) {
        $requestList [] = "mtas.stats.tokens." . $i . ".type=" . urlencode ( $object->type );
      }
      if (isset ( $object->minimum ) && is_int ( $object->minimum )) {
        $requestList [] = "mtas.stats.tokens." . $i . ".minimum=" . urlencode ( $object->minimum );
      }
      if (isset ( $object->maximum ) && is_int ( $object->maximum )) {
        $requestList [] = "mtas.stats.tokens." . $i . ".maximum=" . urlencode ( $object->maximum );
      }
      $object->__requestList = $requestList;
      return $object;
    } else {
      return null;
    }
  }
  /**
   * Parse mtas stats spans
   *
   * @param object $object          
   * @param number $i          
   * @return object
   */
  private function parseResponseMtasStatsSpans($object, $i) {
    if ($object && is_object ( $object )) {
      $requestList = array ();
      if (isset ( $object->key ) && is_string ( $object->key )) {
        $requestList [] = "mtas.stats.spans." . $i . ".key=" . urlencode ( $object->key );
      }
      if (isset ( $object->field ) && is_string ( $object->field )) {
        $requestList [] = "mtas.stats.spans." . $i . ".field=" . urlencode ( $object->field );
      }
      if (isset ( $object->type ) && is_string ( $object->type )) {
        $requestList [] = "mtas.stats.spans." . $i . ".type=" . urlencode ( $object->type );
      }
      if (isset ( $object->minimum ) && is_int ( $object->minimum )) {
        $requestList [] = "mtas.stats.spans." . $i . ".minimum=" . urlencode ( $object->minimum );
      }
      if (isset ( $object->maximum ) && is_int ( $object->maximum )) {
        $requestList [] = "mtas.stats.spans." . $i . ".maximum=" . urlencode ( $object->maximum );
      }
      if (isset ( $object->queries ) && is_array ( $object->queries )) {
        for($j = 0; $j < count ( $object->queries ); $j ++) {
          if (isset ( $object->queries [$j]->type ) && is_string ( $object->queries [$j]->type )) {
            $requestList [] = "mtas.stats.spans." . $i . ".query." . $j . ".type=" . urlencode ( $object->queries [$j]->type );
          }
          if (isset ( $object->queries [$j]->value ) && is_string ( $object->queries [$j]->value )) {
            $requestList [] = "mtas.stats.spans." . $i . ".query." . $j . ".value=" . urlencode ( $object->queries [$j]->value );
          }
          if (isset ( $object->queries [$j]->prefix ) && is_string ( $object->queries [$j]->prefix )) {
            $requestList [] = "mtas.stats.spans." . $i . ".query." . $j . ".prefix=" . urlencode ( $object->queries [$j]->prefix );
          }
          if (isset ( $object->queries [$j]->ignore ) && is_string ( $object->queries [$j]->ignore )) {
            $requestList [] = "mtas.stats.spans." . $i . ".query." . $j . ".ignore=" . urlencode ( $object->queries [$j]->ignore );
          }
          if (isset ( $object->queries [$j]->maximumIgnoreLength ) && is_int ( $object->queries [$j]->maximumIgnoreLength )) {
            $requestList [] = "mtas.stats.spans." . $i . ".query." . $j . ".maximumIgnoreLength=" . urlencode ( $object->queries [$j]->maximumIgnoreLength );
          }
          if (isset ( $object->queries [$j]->variables )) {
            $counter = 0;
            foreach ( $object->queries [$j]->variables->__variables as $key => $value ) {
              $values = array ();
              foreach ( $value as $valueItem ) {
                $values [] = str_replace ( ",", "\\,", str_replace ( "\\", "\\\\", $valueItem ) );
              }
              $requestList [] = "mtas.stats.spans." . $i . ".query." . $j . ".variable." . $counter . ".name=" . urlencode ( $key );
              $requestList [] = "mtas.stats.spans." . $i . ".query." . $j . ".variable." . $counter . ".value=" . urlencode ( implode ( ",", $values ) );
              $counter ++;
            }
            for($k = 0; $k < count ( $object->queries [$j]->variables->__stats ); $k ++) {
              $stats = $object->queries [$j]->variables->__stats [$k];
              if (isset ( $object->field ) && is_string ( $object->field )) {
                $stats->field = $object->field;
              }
              $stats->queries = array ();
              $stats->queries [0] = new \stdClass ();
              if (isset ( $stats->__variables )) {
                $stats->queries [0]->variables = new \stdClass ();
                $stats->queries [0]->variables->__variables = $stats->__variables;
                unset ( $stats->__variables );
                $stats->queries [0]->variables->__stats = array ();
              }
              if (isset ( $object->queries [$j]->type ) && is_string ( $object->queries [$j]->type )) {
                $stats->queries [0]->type = $object->queries [$j]->type;
              }
              if (isset ( $object->queries [$j]->value ) && is_string ( $object->queries [$j]->value )) {
                $stats->queries [0]->value = $object->queries [$j]->value;
              }
              if (isset ( $object->queries [$j]->prefix ) && is_string ( $object->queries [$j]->prefix )) {
                $stats->queries [0]->prefix = $object->queries [$j]->prefix;
              }
              if (isset ( $object->queries [$j]->ignore ) && is_string ( $object->queries [$j]->ignore )) {
                $stats->queries [0]->ignore = $object->queries [$j]->ignore;
              }
              if (isset ( $object->queries [$j]->maximumIgnoreLength ) && is_int ( $object->queries [$j]->maximumIgnoreLength )) {
                $stats->queries [0]->maximumIgnoreLength = $object->queries [$j]->maximumIgnoreLength;
              }
              if ($stats = $this->parseResponseMtasStatsSpans ( $stats, $i . "_" . $j . "_" . $k )) {
                $requestList = array_merge ( $requestList, $stats->__requestList );
              }
            }
          }
        }
      }
      if (isset ( $object->functions ) && is_array ( $object->functions )) {
        for($j = 0; $j < count ( $object->functions ); $j ++) {
          if (isset ( $object->functions [$j]->key ) && is_string ( $object->functions [$j]->key )) {
            $requestList [] = "mtas.stats.spans." . $i . ".function." . $j . ".key=" . urlencode ( $object->functions [$j]->key );
          }
          if (isset ( $object->functions [$j]->expression ) && is_string ( $object->functions [$j]->expression )) {
            $requestList [] = "mtas.stats.spans." . $i . ".function." . $j . ".expression=" . urlencode ( $object->functions [$j]->expression );
          }
          if (isset ( $object->functions [$j]->type ) && is_string ( $object->functions [$j]->type )) {
            $requestList [] = "mtas.stats.spans." . $i . ".function." . $j . ".type=" . urlencode ( $object->functions [$j]->type );
          }
        }
      }
      $object->__requestList = $requestList;
      return $object;
    } else {
      return null;
    }
  }
  /**
   * Parse mtas documents
   *
   * @param object $object          
   * @param number $i          
   * @return object
   */
  private function parseResponseMtasDocument($object, $i) {
    if ($object && is_object ( $object )) {
      $requestList = array ();
      if (isset ( $object->key ) && is_string ( $object->key )) {
        $requestList [] = "mtas.document." . $i . ".key=" . urlencode ( $object->key );
      }
      if (isset ( $object->field ) && is_string ( $object->field )) {
        $requestList [] = "mtas.document." . $i . ".field=" . urlencode ( $object->field );
      }
      if (isset ( $object->prefix ) && is_string ( $object->prefix )) {
        $requestList [] = "mtas.document." . $i . ".prefix=" . urlencode ( $object->prefix );
      }
      if (isset ( $object->type ) && is_string ( $object->type )) {
        $requestList [] = "mtas.document." . $i . ".type=" . urlencode ( $object->type );
      }
      if (isset ( $object->regexp ) && is_string ( $object->regexp )) {
        $requestList [] = "mtas.document." . $i . ".regexp=" . urlencode ( $object->regexp );
      }
      if (isset ( $object->ignoreRegexp ) && is_string ( $object->ignoreRegexp )) {
        $requestList [] = "mtas.document." . $i . ".ignoreRegexp=" . urlencode ( $object->ignoreRegexp );
      }
      if (isset ( $object->list ) && is_array ( $object->list ) && count($object->list)>0) {
        $requestList [] = "mtas.document." . $i . ".list=" . urlencode ( implode ( ",", str_replace ( ",", "\\,", str_replace ( "\\", "\\\\", $object->list ) ) ) );
      }
      if (isset ( $object->listRegexp ) && is_bool ( $object->listRegexp )) {
        $requestList [] = "mtas.document." . $i . ".listRegexp=" . urlencode ( $object->listRegexp ? "true" : "false" );
      }
      if (isset ( $object->listExpand ) && is_bool ( $object->listExpand )) {
        $requestList [] = "mtas.document." . $i . ".listExpand=" . urlencode ( $object->listExpand ? "true" : "false" );
      }
      if (isset ( $object->listExpandNumber ) && is_int ( $object->listExpandNumber )) {
        $requestList [] = "mtas.document." . $i . ".listExpandNumber=" . urlencode ( $object->listExpandNumber );
      }
      if (isset ( $object->ignoreList ) && is_array ( $object->ignoreList ) && count($object->ignoreList)>0) {
        $requestList [] = "mtas.document." . $i . ".ignoreList=" . urlencode ( implode ( ",", $object->ignoreList ) );
      }
      if (isset ( $object->ignoreListRegexp ) && is_bool ( $object->ignoreListRegexp )) {
        $requestList [] = "mtas.document." . $i . ".ignoreListRegexp=" . urlencode ( $object->ignoreListRegexp ? "true" : "false" );
      }
      if (isset ( $object->number ) && is_int ( $object->number )) {
        $requestList [] = "mtas.document." . $i . ".number=" . urlencode ( $object->number );
      }
      $object->__requestList = $requestList;
      return $object;
    } else {
      return null;
    }
  }
  /**
   * Parse mtas kwic and list
   *
   * @param string $type          
   * @param object $object          
   * @param number $i          
   * @return object
   */
  private function parseResponseMtasKwicAndList($type, $object, $i) {
    if ($type != "list" && $type != "kwic") {
      die ( "incorrect call" );
    }
    if ($object && is_object ( $object )) {
      $requestList = array ();
      if (isset ( $object->key ) && is_string ( $object->key )) {
        $requestList [] = "mtas.{$type}." . $i . ".key=" . urlencode ( $object->key );
      }
      if (isset ( $object->field ) && is_string ( $object->field )) {
        $requestList [] = "mtas.{$type}." . $i . ".field=" . urlencode ( $object->field );
      }
      if (isset ( $object->prefix ) && is_string ( $object->prefix )) {
        $requestList [] = "mtas.{$type}." . $i . ".prefix=" . urlencode ( $object->prefix );
      }
      if (isset ( $object->output ) && is_string ( $object->output )) {
        $requestList [] = "mtas.{$type}." . $i . ".output=" . urlencode ( $object->output );
      }
      if (isset ( $object->number ) && is_int ( $object->number )) {
        $requestList [] = "mtas.{$type}." . $i . ".number=" . urlencode ( $object->number );
      }
      if (isset ( $object->start ) && is_int ( $object->start )) {
        $requestList [] = "mtas.{$type}." . $i . ".start=" . urlencode ( $object->start );
      }
      if (isset ( $object->left ) && is_int ( $object->left )) {
        $requestList [] = "mtas.{$type}." . $i . ".left=" . urlencode ( $object->left );
      }
      if (isset ( $object->right ) && is_int ( $object->right )) {
        $requestList [] = "mtas.{$type}." . $i . ".right=" . urlencode ( $object->right );
      }
      if (isset ( $object->query ) && is_object ( $object->query )) {
        if (isset ( $object->query->type ) && is_string ( $object->query->type )) {
          $requestList [] = "mtas.{$type}." . $i . ".query.type=" . urlencode ( $object->query->type );
        }
        if (isset ( $object->query->value ) && is_string ( $object->query->value )) {
          $requestList [] = "mtas.{$type}." . $i . ".query.value=" . urlencode ( $object->query->value );
        }
        if (isset ( $object->query->prefix ) && is_string ( $object->query->prefix )) {
          $requestList [] = "mtas.{$type}." . $i . ".query.prefix=" . urlencode ( $object->query->prefix );
        }
        if (isset ( $object->query->ignore ) && is_string ( $object->query->ignore )) {
          $requestList [] = "mtas.{$type}." . $i . ".query.ignore=" . urlencode ( $object->query->ignore );
        }
        if (isset ( $object->query->maximumIgnoreLength ) && is_int ( $object->query->maximumIgnoreLength )) {
          $requestList [] = "mtas.{$type}." . $i . ".query.maximumIgnoreLength=" . urlencode ( $object->query->maximumIgnoreLength );
        }
        if (isset ( $object->query->variables )) {
          $counter = 0;
          foreach ( $object->query->variables->__variables as $key => $value ) {
            $values = array ();
            foreach ( $value as $valueItem ) {
              $values [] = str_replace ( ",", "\\,", str_replace ( "\\", "\\\\", $valueItem ) );
            }
            $requestList [] = "mtas.{$type}." . $i . ".query.variable." . $counter . ".name=" . urlencode ( $key );
            $requestList [] = "mtas.{$type}." . $i . ".query.variable." . $counter . ".value=" . urlencode ( implode ( ",", $values ) );
            $counter ++;
          }
        }
      }
      $object->__requestList = $requestList;
      return $object;
    } else {
      return null;
    }
  }
  /**
   * Parse mtas termvector
   *
   * @param object $object          
   * @param number $i          
   * @return object
   */
  private function parseResponseMtasTermvector($object, $i) {
    if ($object && is_object ( $object )) {
      $requestList = array ();
      if (isset ( $object->key ) && is_string ( $object->key )) {
        $requestList [] = "mtas.termvector." . $i . ".key=" . urlencode ( $object->key );
      }
      if (isset ( $object->field ) && is_string ( $object->field )) {
        $requestList [] = "mtas.termvector." . $i . ".field=" . urlencode ( $object->field );
      }
      if (isset ( $object->prefix ) && is_string ( $object->prefix )) {
        $requestList [] = "mtas.termvector." . $i . ".prefix=" . urlencode ( $object->prefix );
      }
      if (isset ( $object->type ) && is_string ( $object->type )) {
        $requestList [] = "mtas.termvector." . $i . ".type=" . urlencode ( $object->type );
      }
      if (isset ( $object->full ) && is_bool ( $object->full )) {
        $requestList [] = "mtas.termvector." . $i . ".full=" . urlencode ( $object->full ? "true" : "false" );
      }
      if (isset ( $object->regexp ) && is_string ( $object->regexp )) {
        $requestList [] = "mtas.termvector." . $i . ".regexp=" . urlencode ( $object->regexp );
      }
      if (isset ( $object->ignorRegexp ) && is_string ( $object->ignoreRegexp )) {
        $requestList [] = "mtas.termvector." . $i . ".ignoreRegexp=" . urlencode ( $object->ignoreRegexp );
      }
      if (isset ( $object->list ) && is_array ( $object->list ) && count($object->list)>0) {
        $requestList [] = "mtas.termvector." . $i . ".list=" . urlencode ( implode ( ",", str_replace ( ",", "\\,", str_replace ( "\\", "\\\\", $object->list ) ) ) );
      }
      if (isset ( $object->listRegexp ) && is_bool ( $object->listRegexp )) {
        $requestList [] = "mtas.termvector." . $i . ".listRegexp=" . urlencode ( $object->listRegexp ? "true" : "false" );
      }
      if (isset ( $object->ignoreList ) && is_array ( $object->ignoreList ) && count($object->ignoreList)>0) {
        $requestList [] = "mtas.termvector." . $i . ".ignoreList=" . urlencode ( implode ( ",", $object->ignoreList ) );
      }
      if (isset ( $object->ignoreListRegexp ) && is_bool ( $object->ignoreListRegexp )) {
        $requestList [] = "mtas.termvector." . $i . ".ignoreListRegexp=" . urlencode ( $object->ignoreListRegexp ? "true" : "false" );
      }
      if (isset ( $object->start ) && is_int ( $object->start )) {
        $requestList [] = "mtas.termvector." . $i . ".start=" . urlencode ( $object->start );
      }
      if (isset ( $object->number ) && is_int ( $object->number )) {
        $requestList [] = "mtas.termvector." . $i . ".number=" . urlencode ( $object->number );
      }
      if (isset ( $object->distances ) && is_array ( $object->distances )) {
        for($j = 0; $j < count ( $object->distances ); $j ++) {
          if (isset ( $object->distances [$j]->key ) && is_string ( $object->distances [$j]->key )) {
            $requestList [] = "mtas.termvector." . $i . ".distance." . $j . ".key=" . urlencode ( $object->distances [$j]->key );
          }
          if (isset ( $object->distances [$j]->type ) && is_string ( $object->distances [$j]->type )) {
            $requestList [] = "mtas.termvector." . $i . ".distance." . $j . ".type=" . urlencode ( $object->distances [$j]->type );
          }
          if (isset ( $object->distances [$j]->base ) && is_string ( $object->distances [$j]->base )) {
            $requestList [] = "mtas.termvector." . $i . ".distance." . $j . ".base=" . urlencode ( $object->distances [$j]->base );
          }
          if (isset ( $object->distances [$j]->minimum ) && is_numeric ( $object->distances [$j]->minimum )) {
            $requestList [] = "mtas.termvector." . $i . ".distance." . $j . ".minimum=" . urlencode ( $object->distances [$j]->minimum );
          }
          if (isset ( $object->distances [$j]->maximum ) && is_numeric ( $object->distances [$j]->maximum )) {
            $requestList [] = "mtas.termvector." . $i . ".distance." . $j . ".maximum=" . urlencode ( $object->distances [$j]->maximum );
          }
          if (isset ( $object->distances [$j]->parameter ) && is_object ( $object->distances [$j]->parameter )) {
            foreach ( $object->distances [$j]->parameter as $parameterKey => $parameterValue ) {
              $requestList [] = "mtas.termvector." . $i . ".distance." . $j . ".parameter." . $parameterKey . "=" . urlencode ( $parameterValue );
            }
          }
        }
      }
      if (isset ( $object->functions ) && is_array ( $object->functions )) {
        for($j = 0; $j < count ( $object->functions ); $j ++) {
          if (isset ( $object->functions [$j]->key ) && is_string ( $object->functions [$j]->key )) {
            $requestList [] = "mtas.termvector." . $i . ".function." . $j . ".key=" . urlencode ( $object->functions [$j]->key );
          }
          if (isset ( $object->functions [$j]->expression ) && is_string ( $object->functions [$j]->expression )) {
            $requestList [] = "mtas.termvector." . $i . ".function." . $j . ".expression=" . urlencode ( $object->functions [$j]->expression );
          }
          if (isset ( $object->functions [$j]->type ) && is_string ( $object->functions [$j]->type )) {
            $requestList [] = "mtas.termvector." . $i . ".function." . $j . ".type=" . urlencode ( $object->functions [$j]->type );
          }
        }
      }
      if (isset ( $object->sort ) && is_object ( $object->sort )) {
        if (isset ( $object->sort->type ) && is_string ( $object->sort->type )) {
          $requestList [] = "mtas.termvector." . $i . ".sort.type=" . urlencode ( $object->sort->type );
        }
        if (isset ( $object->sort->direction ) && is_string ( $object->sort->direction )) {
          $requestList [] = "mtas.termvector." . $i . ".sort.direction=" . urlencode ( $object->sort->direction );
        }
      }
      $object->__requestList = $requestList;
      return $object;
    } else {
      return null;
    }
  }
  /**
   * Parse mtas facet
   *
   * @param object $object          
   * @param number $i          
   * @return object
   */
  private function parseResponseMtasFacet($object, $i) {
    if ($object && is_object ( $object )) {
      $requestList = array ();
      if (isset ( $object->key ) && is_string ( $object->key )) {
        $requestList [] = "mtas.facet." . $i . ".key=" . urlencode ( $object->key );
      }
      if (isset ( $object->field ) && is_string ( $object->field )) {
        $requestList [] = "mtas.facet." . $i . ".field=" . urlencode ( $object->field );
      }
      if (isset ( $object->queries ) && is_array ( $object->queries )) {
        for($j = 0; $j < count ( $object->queries ); $j ++) {
          if (isset ( $object->queries [$j]->type ) && is_string ( $object->queries [$j]->type )) {
            $requestList [] = "mtas.facet." . $i . ".query." . $j . ".type=" . urlencode ( $object->queries [$j]->type );
          }
          if (isset ( $object->queries [$j]->value ) && is_string ( $object->queries [$j]->value )) {
            $requestList [] = "mtas.facet." . $i . ".query." . $j . ".value=" . urlencode ( $object->queries [$j]->value );
          }
          if (isset ( $object->queries [$j]->prefix ) && is_string ( $object->queries [$j]->prefix )) {
            $requestList [] = "mtas.facet." . $i . ".query." . $j . ".prefix=" . urlencode ( $object->queries [$j]->prefix );
          }
          if (isset ( $object->queries [$j]->ignore ) && is_string ( $object->queries [$j]->ignore )) {
            $requestList [] = "mtas.facet." . $i . ".query." . $j . ".ignore=" . urlencode ( $object->queries [$j]->ignore );
          }
          if (isset ( $object->queries [$j]->maximumIgnoreLength ) && is_int ( $object->queries [$j]->maximumIgnoreLength )) {
            $requestList [] = "mtas.facet." . $i . ".query." . $j . ".maximumIgnoreLength=" . urlencode ( $object->queries [$j]->maximumIgnoreLength );
          }
          if (isset ( $object->queries [$j]->variables ) && is_object ( $object->queries [$j]->variables )) {
            $counter = 0;
            foreach ( $object->queries [$j]->variables->__variables as $key => $value ) {
              $values = array ();
              foreach ( $value as $valueItem ) {
                $values [] = str_replace ( ",", "\\,", str_replace ( "\\", "\\\\", $valueItem ) );
              }
              $requestList [] = "mtas.facet." . $i . ".query." . $j . ".variable." . $counter . ".name=" . urlencode ( $key );
              $requestList [] = "mtas.facet." . $i . ".query." . $j . ".variable." . $counter . ".value=" . urlencode ( implode ( ",", $values ) );
              $counter ++;
            }
          }
        }
      }
      if (isset ( $object->base ) && is_array ( $object->base )) {
        for($j = 0; $j < count ( $object->base ); $j ++) {
          if (isset ( $object->base [$j]->field ) && is_string ( $object->base [$j]->field )) {
            $requestList [] = "mtas.facet." . $i . ".base." . $j . ".field=" . urlencode ( $object->base [$j]->field );
          }
          if (isset ( $object->base [$j]->type ) && is_string ( $object->base [$j]->type )) {
            $requestList [] = "mtas.facet." . $i . ".base." . $j . ".type=" . urlencode ( $object->base [$j]->type );
          }
          if (isset ( $object->base [$j]->sort ) && is_object ( $object->base [$j]->sort )) {
            if (isset ( $object->base [$j]->sort->type ) && is_string ( $object->base [$j]->sort->type )) {
              $requestList [] = "mtas.facet." . $i . ".base." . $j . ".sort.type=" . urlencode ( $object->base [$j]->sort->type );
            }
            if (isset ( $object->base [$j]->sort->direction ) && is_string ( $object->base [$j]->sort->direction )) {
              $requestList [] = "mtas.facet." . $i . ".base." . $j . ".sort.direction=" . urlencode ( $object->base [$j]->sort->direction );
            }
          }
          if (isset ( $object->base [$j]->number ) && is_int ( $object->base [$j]->number )) {
            $requestList [] = "mtas.facet." . $i . ".base." . $j . ".number=" . urlencode ( $object->base [$j]->number );
          }
          if (isset ( $object->base [$j]->minimum ) && is_int ( $object->base [$j]->minimum )) {
            $requestList [] = "mtas.facet." . $i . ".base." . $j . ".minimum=" . urlencode ( $object->base [$j]->minimum );
          }
          if (isset ( $object->base [$j]->maximum ) && is_int ( $object->base [$j]->maximum )) {
            $requestList [] = "mtas.facet." . $i . ".base." . $j . ".maximum=" . urlencode ( $object->base [$j]->maximum );
          }
          if (isset ( $object->base [$j]->range ) && is_object ( $object->base [$j]->range )) {
            if (isset ( $object->base [$j]->range->size ) && is_int ( $object->base [$j]->range->size )) {
              $requestList [] = "mtas.facet." . $i . ".base." . $j . ".range.size=" . urlencode ( $object->base [$j]->range->size );
            }
            if (isset ( $object->base [$j]->range->base ) && is_int ( $object->base [$j]->range->base )) {
              $requestList [] = "mtas.facet." . $i . ".base." . $j . ".range.base=" . urlencode ( $object->base [$j]->range->base );
            }
          }
          if (isset ( $object->base [$j]->functions ) && is_array ( $object->base [$j]->functions )) {
            for($k = 0; $k < count ( $object->base [$j]->functions ); $k ++) {
              if (is_object ( $object->base [$j]->functions [$k] )) {
                if (isset ( $object->base [$j]->functions [$k]->key ) && is_string ( $object->base [$j]->functions [$k]->key )) {
                  $requestList [] = "mtas.facet." . $i . ".base." . $j . ".function." . $k . ".key=" . urlencode ( $object->base [$j]->functions [$k]->key );
                }
                if (isset ( $object->base [$j]->functions [$k]->expression ) && is_string ( $object->base [$j]->functions [$k]->expression )) {
                  $requestList [] = "mtas.facet." . $i . ".base." . $j . ".function." . $k . ".expression=" . urlencode ( $object->base [$j]->functions [$k]->expression );
                }
                if (isset ( $object->base [$j]->functions [$k]->type ) && is_string ( $object->base [$j]->functions [$k]->type )) {
                  $requestList [] = "mtas.facet." . $i . ".base." . $j . ".function." . $k . ".type=" . urlencode ( $object->base [$j]->functions [$k]->type );
                }
              }
            }
          }
        }
      }
      $object->__requestList = $requestList;
      return $object;
    } else {
      return null;
    }
  }
  /**
   * Parse mtas group
   *
   * @param object $object          
   * @param number $i          
   * @return object
   */
  private function parseResponseMtasGroup($object, $i) {
    if ($object && is_object ( $object )) {
      $requestList = array ();
      if (isset ( $object->key ) && is_string ( $object->key )) {
        $requestList [] = "mtas.group." . $i . ".key=" . urlencode ( $object->key );
      }
      if (isset ( $object->field ) && is_string ( $object->field )) {
        $requestList [] = "mtas.group." . $i . ".field=" . urlencode ( $object->field );
      }
      if (isset ( $object->number ) && is_int ( $object->number )) {
        $requestList [] = "mtas.group." . $i . ".number=" . urlencode ( $object->number );
      }
      if (isset ( $object->start ) && is_int ( $object->start )) {
        $requestList [] = "mtas.group." . $i . ".start=" . urlencode ( $object->start );
      }
      if (isset ( $object->query ) && is_object ( $object->query )) {
        if (isset ( $object->query->type ) && is_string ( $object->query->type )) {
          $requestList [] = "mtas.group." . $i . ".query.type=" . urlencode ( $object->query->type );
        }
        if (isset ( $object->query->value ) && is_string ( $object->query->value )) {
          $requestList [] = "mtas.group." . $i . ".query.value=" . urlencode ( $object->query->value );
        }
        if (isset ( $object->query->prefix ) && is_string ( $object->query->prefix )) {
          $requestList [] = "mtas.group." . $i . ".query.prefix=" . urlencode ( $object->query->prefix );
        }
        if (isset ( $object->query->ignore ) && is_string ( $object->query->ignore )) {
          $requestList [] = "mtas.group." . $i . ".query.ignore=" . urlencode ( $object->query->ignore );
        }
        if (isset ( $object->query->maximumIgnoreLength ) && is_int ( $object->query->maximumIgnoreLength )) {
          $requestList [] = "mtas.group." . $i . ".query.maximumIgnoreLength=" . urlencode ( $object->query->maximumIgnoreLength );
        }
        if (isset ( $object->query->variables ) && is_object ( $object->query->variables )) {
          $counter = 0;
          foreach ( $object->query->variables->__variables as $key => $value ) {
            $values = array ();
            foreach ( $value as $valueItem ) {
              $values [] = str_replace ( ",", "\\,", str_replace ( "\\", "\\\\", $valueItem ) );
            }
            $requestList [] = "mtas.group." . $i . ".query.variable." . $counter . ".name=" . urlencode ( $key );
            $requestList [] = "mtas.group." . $i . ".query.variable." . $counter . ".value=" . urlencode ( implode ( ",", $values ) );
            $counter ++;
          }
        }
      }
      if (isset ( $object->grouping ) && is_object ( $object->grouping )) {
        if (isset ( $object->grouping->hit ) && is_object ( $object->grouping->hit )) {
          if (isset ( $object->grouping->hit->inside ) && is_string ( $object->grouping->hit->inside )) {
            $requestList [] = "mtas.group." . $i . ".grouping.hit.inside.prefixes=" . urlencode ( $object->grouping->hit->inside );
          }
          if (isset ( $object->grouping->hit->insideLeft ) && is_array ( $object->grouping->hit->insideLeft )) {
            for($j = 0; $j < count ( $object->grouping->hit->insideLeft ); $j ++) {
              if (isset ( $object->grouping->hit->insideLeft [$j]->prefixes ) && is_string ( $object->grouping->hit->insideLeft [$j]->prefixes )) {
                $requestList [] = "mtas.group." . $i . ".grouping.hit.insideLeft." . $j . ".prefixes=" . urlencode ( $object->grouping->hit->insideLeft [$j]->prefixes );
              }
              if (isset ( $object->grouping->hit->insideLeft [$j]->position ) && (is_string ( $object->grouping->hit->insideLeft [$j]->position ) || is_int ( $object->grouping->hit->insideLeft [$j]->position ))) {
                $requestList [] = "mtas.group." . $i . ".grouping.hit.insideLeft." . $j . ".position=" . urlencode ( $object->grouping->hit->insideLeft [$j]->position );
              }
            }
          }
          if (isset ( $object->grouping->hit->insideRight ) && is_array ( $object->grouping->hit->insideRight )) {
            for($j = 0; $j < count ( $object->grouping->hit->insideRight ); $j ++) {
              if (isset ( $object->grouping->hit->insideRight [$j]->prefixes ) && is_string ( $object->grouping->hit->insideRight [$j]->prefixes )) {
                $requestList [] = "mtas.group." . $i . ".grouping.hit.insideRight." . $j . ".prefixes=" . urlencode ( $object->grouping->hit->insideRight [$j]->prefixes );
              }
              if (isset ( $object->grouping->hit->insideRight [$j]->position ) && (is_string ( $object->grouping->hit->insideRight [$j]->position ) || is_int ( $object->grouping->hit->insideRight [$j]->position ))) {
                $requestList [] = "mtas.group." . $i . ".grouping.hit.insideRight." . $j . ".position=" . urlencode ( $object->grouping->hit->insideRight [$j]->position );
              }
            }
          }
        }
        if (isset ( $object->grouping->right ) && is_array ( $object->grouping->right )) {
          for($j = 0; $j < count ( $object->grouping->right ); $j ++) {
            if (isset ( $object->grouping->right [$j]->prefixes ) && is_string ( $object->grouping->right [$j]->prefixes )) {
              $requestList [] = "mtas.group." . $i . ".grouping.right." . $j . ".prefixes=" . urlencode ( $object->grouping->right [$j]->prefixes );
            }
            if (isset ( $object->grouping->right [$j]->position ) && (is_string ( $object->grouping->right [$j]->position ) || is_int ( $object->grouping->right [$j]->position ))) {
              $requestList [] = "mtas.group." . $i . ".grouping.right." . $j . ".position=" . urlencode ( $object->grouping->right [$j]->position );
            }
          }
        }
        if (isset ( $object->grouping->left ) && is_array ( $object->grouping->left )) {
          for($j = 0; $j < count ( $object->grouping->left ); $j ++) {
            if (isset ( $object->grouping->left [$j]->prefixes ) && is_string ( $object->grouping->left [$j]->prefixes )) {
              $requestList [] = "mtas.group." . $i . ".grouping.left." . $j . ".prefixes=" . urlencode ( $object->grouping->left [$j]->prefixes );
            }
            if (isset ( $object->grouping->left [$j]->position ) && (is_string ( $object->grouping->left [$j]->position ) || is_int ( $object->grouping->left [$j]->position ))) {
              $requestList [] = "mtas.group." . $i . ".grouping.left." . $j . ".position=" . urlencode ( $object->grouping->left [$j]->position );
            }
          }
        }
      }
      $object->__requestList = $requestList;
      return $object;
    } else {
      return null;
    }
  }
  /**
   * Parse mtas prefix
   *
   * @param object $object          
   * @param number $i          
   * @return object
   */
  private function parseResponseMtasPrefix($object, $i) {
    if ($object && is_object ( $object )) {
      $requestList = array ();
      if (isset ( $object->key ) && is_string ( $object->key )) {
        $requestList [] = "mtas.prefix." . $i . ".key=" . urlencode ( $object->key );
      }
      if (isset ( $object->field ) && is_string ( $object->field )) {
        $requestList [] = "mtas.prefix." . $i . ".field=" . urlencode ( $object->field );
      }
      $object->__requestList = $requestList;
      return $object;
    } else {
      return null;
    }
  }
  /**
   * Parse mtas collection
   *
   * @param object $object          
   * @param number $i          
   * @return object
   */
  private function parseResponseMtasCollection($object, $i) {
    if ($object && is_object ( $object )) {
      $requestList = array ();
      if (isset ( $object->key ) && is_string ( $object->key )) {
        $requestList [] = "mtas.collection." . $i . ".key=" . urlencode ( $object->key );
      }
      if (isset ( $object->action ) && is_string ( $object->action )) {
        $requestList [] = "mtas.collection." . $i . ".action=" . urlencode ( $object->action );
      }
      if (isset ( $object->id ) && is_string ( $object->id )) {
        $requestList [] = "mtas.collection." . $i . ".id=" . urlencode ( $object->id );
      }
      if (isset ( $object->field ) && is_string ( $object->field )) {
        $requestList [] = "mtas.collection." . $i . ".field=" . urlencode ( $object->field );
      }
      if (isset ( $object->post ) && is_array ( $object->post )) {
        $requestList [] = "mtas.collection." . $i . ".post=" . urlencode ( json_encode ( $object->post ) );
      }
      if (isset ( $object->collection ) && is_string ( $object->collection )) {
        $requestList [] = "mtas.collection." . $i . ".collection=" . urlencode ( $object->collection );
      }
      if (isset ( $object->configuration ) && is_string ( $object->configuration )) {
        if (isset ( $this->configuration->config ["solr"] [$object->configuration] ) && isset ( $this->configuration->config ["solr"] [$object->configuration] ["url"] )) {
          $requestList [] = "mtas.collection." . $i . ".url=" . urlencode ( $this->configuration->config ["solr"] [$object->configuration] ["url"] );
        }
      }
      if (isset ( $object->url ) && is_string ( $object->url )) {
        $requestList [] = "mtas.collection." . $i . ".url=" . urlencode ( $object->url );
      }
      $object->__requestList = $requestList;
      return $object;
    } else {
      return null;
    }
  }
  /**
   * Parse condition
   *
   * @param object $object          
   * @return object
   */
  private function parseCondition($object) {
    if ($object && is_object ( $object )) {
      // initialise
      $object->__query = "*:* NOT *:*";
      $object->__facetQueries = array ();
      $object->__mtasStats = array ();
      // and + or
      if ($object->type == "and" || $object->type == "or") {
        $subqueries = array ();
        for($i = 0; $i < count ( $object->list ); $i ++) {
          $object->list [$i] = $this->parseCondition($object->list [$i]);
          $subquery = $object->list [$i]->__query;
          $object->__facetQueries = array_merge ( $object->__facetQueries, $object->list [$i]->__facetQueries );
          $object->__mtasStats = array_merge ( $object->__mtasStats, $object->list [$i]->__mtasStats );
          $subqueries [] = $subquery;
        }
        if ($object->type == "and") {
          if (count ( $subqueries ) > 1) {
            $object->__query = "(" . implode ( ") AND (", $subqueries ) . ")";
          } else {
            $object->__query = $subqueries [0];
          }
        } else {
          if (count ( $subqueries ) > 1) {
            $object->__query = "(" . implode ( ") OR (", $subqueries ) . ")";
          } else {
            $object->__query = $subqueries [0];
          }
        }
        // equals/phrase + expansion
      } else if (($object->type == "equals" || $object->type == "phrase") && isset ( $object->expansion )) {
        $values = $this->computeExpansionValues ( $object->value, $object->expansion, "condition - " );
        if (count ( $values ) > 0) {
          $queries = array ();
          foreach ( $values as $value ) {
            $subquery = $object->field . ":" . $this->solrEncode ( $value, $object->type );
            if (isset ( $object->facetquery ) && $object->facetquery) {
              $facetQuery = new \stdClass ();
              if (isset ( $object->not ) && $object->not) {
                $facetQuery->__query = "(*:* NOT {$subquery})";
              } else {
                $facetQuery->__query = $subquery;
              }
              if (isset ( $object->key ) && is_string ( $object->key )) {
                $facetQuery->key = $object->key . " - '" . $value . "'";
              }
              $object->__facetQueries [] = $facetQuery;
            }
            $queries [] = $subquery;
          }
          if (count ( $queries ) > 1) {
            if (isset ( $object->not ) && $object->not) {
              $object->__query = "(*:* NOT (" . implode ( ") AND (", $queries ) . "))";
            } else {
              $object->__query = "(" . implode ( ") OR (", $queries ) . ")";
            }
          } else {
            if (isset ( $object->not ) && $object->not) {
              $object->__query = "(*:* NOT {$queries [0]})";
            } else {
              $object->__query = $queries [0];
            }
          }
        } else {
          if (isset ( $object->not ) && $object->not) {
            $object->__query = "*:*";
          } else {
            $object->__query = "(*:* NOT *:*)";
          }
        }
      } else if (($object->type == "equals" || $object->type == "phrase" || $object->type == "wildcard" || $object->type == "regexp") && is_array ( $object->value )) {
        $subqueries = array ();
        foreach ( $object->value as $valueItem ) {
          $subquery = $object->field . ":" . $this->solrEncode ( $valueItem, $object->type );
          $subqueries [] = $subquery;
          // create facetQuery
          if (isset ( $object->facetquery ) && $object->facetquery) {
            $facetQuery = new \stdClass ();
            if (isset ( $object->not ) && $object->not) {
              $facetQuery->__query = "(*:* NOT {$subquery})";
            } else {
              $facetQuery->__query = $subquery;
            }
            if (isset ( $object->key ) && is_string ( $object->key )) {
              $facetQuery->key = $object->key . " - '" . $valueItem . "'";
            }
            $object->__facetQueries [] = $facetQuery;
          }
        }
        if (isset ( $object->not ) && $object->not) {
          if (count ( $subqueries ) > 1) {
            $object->__query = "(*:* NOT (" . implode ( ") AND (", $subqueries ) . "))";
          } else {
            $object->__query = "(*:* NOT {$subqueries [0]})";
          }
        } else {
          if (count ( $subqueries ) > 1) {
            $object->__query = "(" . implode ( ") OR (", $subqueries ) . ")";
          } else {
            $object->__query = $subqueries [0];
          }
        }
        // equals, phrase, wildcard or regexp
      } else if ($object->type == "equals" || $object->type == "phrase" || $object->type == "wildcard" || $object->type == "regexp") {
        $object->__query = $object->field . ":" . $this->solrEncode ( $object->value, $object->type );
        if (isset ( $object->not ) && $object->not) {
          $object->__query = "(*:* NOT {$object->__query})";
        }
      } else if ($object->type == "range") {
        $start = isset ( $object->start ) ? $object->start : "*";
        $end = isset ( $object->end ) ? $object->end : "*";
        $object->__query = $object->field . ":[" . $this->solrEncode ( $start, $object->type ) . " TO " . $this->solrEncode ( $end, $object->type ) . "]";
        if (isset ( $object->not ) && $object->not) {
          $object->__query = "(*:* NOT " . $object->__query . ")";
        }
      } else if ($object->type == "geojson") {
        if($object->predicate=="intersects") {
          $value  = "Intersects(".$object->geometry.")"; 
        } else if($object->predicate=="iswithin") {
          $value  = "IsWithin(".$object->geometry.")";
        } else if($object->predicate=="contains") {
          $value  = "Contains(".$object->geometry.")";
        } else if($object->predicate=="isdisjointto") {
          $value  = "IsDisjointTo(".$object->geometry.")";
        }
        $object->__query = $object->field . ":".$this->solrEncode ( $value, $object->type );
        if (isset ( $object->not ) && $object->not) {
          $object->__query = "(*:* NOT " . $object->__query . ")";
        }
      } else if ($object->type == "cql") {
        if (isset ( $object->ignore ) && (trim ( $object->ignore ) != "")) {
          $ignore = $object->ignore;
          if (isset ( $object->maximumIgnoreLength ) && is_int ( $object->maximumIgnoreLength )) {
            $maximumIgnoreLength = $object->maximumIgnoreLength;
          } else {
            $maximumIgnoreLength = null;
          }
        } else {
          $ignore = null;
          $maximumIgnoreLength = null;
        }
        if (isset ( $object->prefix ) && (trim ( $object->prefix ) != "")) {
          $prefix = $object->prefix;
        } else {
          $prefix = null;
        }
        if (isset ( $object->variables )) {
          $variables = $object->variables->__variables;
          $stats = $object->variables->__stats;
        } else {
          $variables = null;
          $stats = null;
        }
        $object->__query = "{!" . $this->configuration->solr [$this->solrConfiguration] ["queryParserCql"] . " field=\"" . $object->field . "\" query=" . $this->solrEncode ( $object->value, $object->type ) . " " . ($prefix != null ? "prefix=" . $this->solrEncode ( $prefix, "equals" ) : "") . " " . ($ignore != null ? ("ignore=" . $this->solrEncode ( $ignore, $object->type )) : "") . " " . ($maximumIgnoreLength != null ? ("maximumIgnoreLength=" . $maximumIgnoreLength) : "") . " " . $this->createVariablesString ( $variables ) . "}";
        if (isset ( $object->not ) && $object->not) {
          $object->__query = "(*:* NOT " . $object->__query . ")";
        }
        if ($stats) {
          foreach ( $stats as $statsItem ) {
            $statsItem->queries = array ();
            $statsItem->queries [0] = new \stdClass ();
            if (isset ( $object->field ) && is_string ( $object->field )) {
              $statsItem->field = $object->field;
            }
            if (isset ( $object->type ) && is_string ( $object->type )) {
              $statsItem->queries [0]->type = $object->type;
            }
            if (isset ( $object->value ) && is_string ( $object->value )) {
              $statsItem->queries [0]->value = $object->value;
            }
            if (isset ( $object->prefix ) && is_string ( $object->prefix )) {
              $statsItem->queries [0]->prefix = $object->prefix;
            }
            if (isset ( $object->ignore ) && is_string ( $object->ignore )) {
              $statsItem->queries [0]->ignore = $object->ignore;
            }
            if (isset ( $object->maximumIgnoreLength ) && is_int ( $object->maximumIgnoreLength )) {
              $statsItem->queries [0]->maximumIgnoreLength = $object->maximumIgnoreLength;
            }
            if (isset ( $statsItem->__variables )) {
              $statsItem->queries [0]->variables = new \stdClass ();
              $statsItem->queries [0]->variables->__variables = $statsItem->__variables;
              $statsItem->queries [0]->variables->__stats = array ();
              unset ( $statsItem->__variables );
            }
            $object->__mtasStats [] = $statsItem;
          }
        }
      } else if ($object->type == "join") {
        $object->_collectionId = $this->createCollectionIdFromJoin ( $object, $this->solrConfiguration );
        $this->collectionIds [] = $object->_collectionId;
        if(is_string($object->to)) {
          $queryToPart = "field=\"".$object->to."\""; 
        } else if(is_array($object->to)) {
          $queryToPart = array();
          foreach($object->to AS $toItem) {
            $queryToPart[] = "field=\"".$toItem."\""; 
          }          
          $queryToPart = implode(" ", $queryToPart);
        } else {
          die("unexpected to field in join");
        }
        $object->__query = "{!" . $this->configuration->solr [$this->solrConfiguration] ["queryParserJoin"] . " ".$queryToPart." collection=\"" . $object->_collectionId . "\"}";
      } else {
        // should not happen
        die ( "unknown type: " . $object->type );
      }
      // create (main) facetquery
      if (isset ( $object->facetquery ) && $object->facetquery) {
        $facetQuery = new \stdClass ();
        $facetQuery->__query = $object->__query;
        if (isset ( $object->key ) && is_string ( $object->key )) {
          $facetQuery->key = $object->key;
        }
        $object->__facetQueries [] = $facetQuery;
      }
    }
    return $object;
  }
  /**
   * Parse filters
   *
   * @param object $object          
   * @return array
   */
  private function parseFilters($object) {
    $localErrors = array ();
    $localWarnings = array ();
    $requestList = array ();
    $facetQueries = array ();
    $mtasStats = array ();
    if ($object && is_object ( $object )) {
      list ( $object, $requestList, $facetQueries, $mtasStats ) = $this->parseFilter ( $object, $requestList, $facetQueries, $mtasStats );
    } else if ($object && is_array ( $object ) && count ( $object ) > 0) {
      for($i = 0; $i < count ( $object ); $i ++) {
        list ( $object [$i], $requestList, $facetQueries, $mtasStats ) = $this->parseFilter ( $object [$i], $requestList, $facetQueries, $mtasStats );
      }
    } else {
      $localWarnings [] = "filter - unexpected type";
    }
    $this->errors = array_merge ( $this->errors, $localErrors );
    $this->warnings = array_merge ( $this->warnings, $localWarnings );
    return array (
        $object,
        $requestList,
        $facetQueries,
        $mtasStats 
    );
  }
  /**
   * Parse filter
   *
   * @param object $object          
   * @param array $requestList          
   * @param array $facetQueries          
   * @param array $mtasStats          
   * @return array
   */
  private function parseFilter($object, array $requestList, array $facetQueries, array $mtasStats) {
    $localErrors = array ();
    $localWarnings = array ();
    if ($object && is_object ( $object )) {
      $options = array ();
      if (isset ( $object->tag ) && is_string ( $object->tag )) {
        $options [] = "tag=\"" . base64_encode ( $object->tag ) . "\"";
      }
      if ($object->condition && is_object ( $object->condition )) {
        $object->condition = $this->parseCondition ( $object->condition );
        if ($object->condition && is_object ( $object->condition ) && isset ( $object->condition->__query )) {
          $requestList [] = "fq=" . urlencode ( (count ( $options ) > 0 ? "{!" . implode ( " ", $options ) . "}" : "") . $object->condition->__query );
          $facetQueries = array_merge ( $facetQueries, $object->condition->__facetQueries );
          $mtasStats = array_merge ( $mtasStats, $object->condition->__mtasStats );
        }
      } else {
        $localErrors [] = "filter - condition should be an object";
      }
    } else {
      $localWarnings [] = "filter - unexpected type";
    }
    $this->errors = array_merge ( $this->errors, $localErrors );
    $this->warnings = array_merge ( $this->warnings, $localWarnings );
    return array (
        $object,
        $requestList,
        $facetQueries,
        $mtasStats 
    );
  }
  /**
   * Create collectionId from join
   *
   * @param object $object          
   * @param object $configuration          
   * @return string
   */
  private function createCollectionIdFromJoin($object, $configuration) {
    $collectionId = null;
    if (count ( $this->errors ) == 0) {
      if (isset ( $object->configuration )) {
        $subConfiguration = $object->configuration;
      } else {
        $subConfiguration = null;
      }
      if (isset ( $object->filter )) {
        $subFilter = clone $object->filter;
      } else {
        $subFilter = null;
      }
      if (isset ( $object->condition )) {
        $subCondition = clone $object->condition;
      } else {
        $subCondition = null;
      }
      if ($this->collection == null) {
        $this->getCollection ();
      }
      $collectionId = $this->collection->create ( $subConfiguration, $subFilter, $subCondition, $object->from );
      return $this->finishCollectionIdFromJoin ( $collectionId, $configuration );
    }
    return $collectionId;
  }
  /**
   * Finish collectionId from join
   *
   * @param object $collectionId          
   * @param object $configuration          
   * @return string
   */
  private function finishCollectionIdFromJoin($collectionId, $configuration) {
    if ($collectionId) {
      $checkInfo = $this->collection->check ( $collectionId );
      if (! $checkInfo || $checkInfo ["key"] != $collectionId) {
        $this->errors [] = "collection - couldn't check collection " . $collectionId;
      } else {
        // initialise if necessary
        if (! $checkInfo ["initialised"]) {
          list ( $localWarnings, $localErrors ) = $this->collection->doInitialise ( $collectionId );
          foreach ( $localErrors as $localError ) {
            $this->errors [] = "collection " . $collectionId . " - " . $localError;
          }
          foreach ( $localWarnings as $localWarning ) {
            $this->warnings [] = "collection " . $collectionId . " - " . $localWarning;
          }
          $checkInfo = $this->collection->check ( $collectionId );
          if (! $checkInfo || ! $checkInfo ["initialised"]) {
            $this->errors [] = "collection - couldn't initialise collection " . $collectionId;
          }
        }
        // check if necessary
        if ($checkInfo && $checkInfo ["check"] && ! $this->collection->check ( $collectionId )) {
          $this->errors [] = "collection - collection " . $collectionId . " couldn't be checked";
        }
        if ($checkInfo ["configuration"] != $configuration) {
          return $this->finishCollectionIdFromJoin ( $this->collection->createFromCollection ( $configuration, $collectionId ), $checkInfo ["configuration"] );
        }
      }
    }
    return $collectionId;
  }
  /**
   * Create variables string
   *
   * @param array $variables          
   * @return string
   */
  private function createVariablesString($variables) {
    $result = "";
    if ($variables && is_array ( $variables )) {
      foreach ( $variables as $key => $item ) {
        $value = "";
        if (is_array ( $item ) && count ( $item ) > 0) {
          for($i = 0; $i < count ( $item ); $i ++) {
            if ($i > 0) {
              $value .= ",";
            }
            $value .= str_replace ( ",", "\\,", str_replace ( "\\", "\\\\", $item [$i] ) );
          }
        }
        $result .= "variable_{$key}=" . $this->solrEncode ( $value, "query" ) . " ";
      }
    }
    return $result;
  }
  /**
   * Create variable combinations
   *
   * @param array $variables          
   * @param array $combinations          
   * @return array
   */
  private function createVariableCombinations($variables, $combinations) {
    $list = array ();
    $combinationKeys = array_keys ( $combinations );
    if (count ( $combinationKeys ) == count ( $variables )) {
      $list [] = $combinations;
      return $list;
    } else {
      foreach ( $variables as $key => $value ) {
        if (! in_array ( $key, $combinationKeys )) {
          foreach ( $value as $valueItem ) {
            $combinations [$key] = array (
                $valueItem 
            );
            $list = array_merge ( $list, $this->createVariableCombinations ( $variables, $combinations ) );
          }
          return $list;
        }
      }
    }
  }
  /**
   * Get configurations for field
   *
   * @param object $field          
   * @return array
   */
  private function getConfigurationsForField($field) {
    $list = array ();
    if ($this->configuration->getConfig ( "solr" ) != null) {
      $configurations = array_keys ( $this->configuration->getConfig ( "solr" ) );
      foreach ( $configurations as $configuration ) {
        $data = $this->configuration->getSolrConfig ( $configuration );
        if ($data != null) {
          if (in_array ( $field, $data ["fields"] )) {
            $list [] = $configuration;
          } else {
            // do nothing
          }
        }
      }
    }
    return array_unique ( $list );
  }
  /**
   * Compute expansions values
   *
   * @param array|string $value          
   * @param object $expansion          
   * @param string $prefixMessage          
   * @return array
   */
  private function computeExpansionValues($value, $expansion, $prefixMessage = "") {    
    $values = array ();
    if ($expansion && is_object ( $expansion )) {
      foreach ( $expansion as $key => $item ) {
        if ($key != "type" && $key != "parameters") {
          $this->warnings [] = $prefixMessage . "expansion - unexpected key '{$key}'";
        }
      }
      if (! isset ( $expansion->type ) || ! is_string ( $expansion->type )) {
        $this->errors [] = $prefixMessage . "expansion - no (valid) type provided";
      } else if (isset ( $expansion->parameters ) && ! is_object ( $expansion->parameters )) {
        $this->errors [] = $prefixMessage . "expansion - invalid parameters provided";
      } else {
        $expansionObjectClass = "\\BrokerExpansion\\" . ucfirst ( $expansion->type ) . "Expansion";
        if (! class_exists ( $expansionObjectClass, true )) {
          $this->errors [] = $prefixMessage . "expansion - could not find expansion module '" . $expansion->type . "'";
        } else if (! in_array ( "Broker\\Expansion", class_implements ( $expansionObjectClass, true ) )) {
          $this->errors [] = $prefixMessage . "expansion - expansion module '" . $expansion->type . "' does not implement Extension interface";
        } else {
          if (isset ( $expansion->parameters )) {
            $parameters = $expansionObjectClass::parameters ();
            foreach ( $expansion->parameters as $parameter => $parameterValue ) {
              if (! isset ( $parameters [$parameter] )) {
                $this->warnings [] = $prefixMessage . "expansion - unexpected parameter '{$parameter}'";
              }
            }
          }
          if (is_array ( $value )) {
            foreach ( $value as $valueItem ) {
              $expansionObject = new $expansionObjectClass ( $valueItem, $expansion , $this->configuration, $this->solrConfiguration);
              $values = array_merge ( $values, $expansionObject->getValues () );
            }
          } else {
            $expansionObject = new $expansionObjectClass ( $value, $expansion, $this->configuration, $this->solrConfiguration );
            // check cache
            if ($expansionObject->cached ()) {
              if (! $this->expansionCache) {
                $this->expansionCache = new \Broker\ExpansionCache ( SITE_CACHE_DATABASE_DIR );
              }
              list ( $id, $cachedValues ) = $this->expansionCache->check ( $expansion->type, $value, isset ( $expansion->parameters ) ? array($this->solrConfiguration, $expansion->parameters) : $this->solrConfiguration );
              if ($id) {
                $values = $cachedValues;
              } else {
                $values = $expansionObject->getValues ();
                if (! ($expansionErrors = $expansionObject->getErrors ())) {
                  $this->expansionCache->create ( $expansion->type, $value, isset ( $expansion->parameters ) ? array($this->solrConfiguration, $expansion->parameters) : $this->solrConfiguration, $values );
                } else {
                  foreach ( $expansionErrors as $expansionError ) {
                    $this->warnings [] = $prefixMessage . "expansion - " . $expansionError;
                  }
                }
              }
            } else {
              $values = $expansionObject->getValues ();
              if ($expansionErrors = $expansionObject->getErrors ()) {
                foreach ( $expansionErrors as $expansionError ) {
                  $this->warnings [] = $prefixMessage . "expansion - " . $expansionError;
                }
              }
            }
          }
        }
      }
    }
    return $values;
  }
  /**
   * Compute configuration
   *
   * @param string $config          
   * @return object
   */
  private function computeConfiguration($config) {
    $availableConfigs = array_keys ( $this->configuration->getConfig ( "solr" ) );
    if ($config != null) {
      if (! in_array ( $config, $availableConfigs )) {
        $this->errors [] = "configuration - configuration '{$config}' unknown";
        return null;
      }
    } else if (count ( $this->__configurations ) == 0) {
      if (count ( $availableConfigs ) > 0) {
        return $availableConfigs [0];
      } else {
        // continue
      }
    }
    if (count ( $this->__configurations ) == 0) {
      if ($config != null) {
        return $config;
      } else {
        return null;
      }
    } else {
      $candidates = $this->__configurations [0];
      for($i = 1; $i < count ( $this->__configurations ); $i ++) {
        $candidates = array_intersect ( $candidates, $this->__configurations [$i] );
        if (count ( $candidates ) == 0) {
          return null;
        }
      }
      if (count ( $candidates ) == 0) {
        return null;
      } else {
        if ($config == null) {
          return $candidates [0];
        } else if (in_array ( $config, $candidates )) {
          return $config;
        } else {
          return null;
        }
      }
    }
  }
  /**
   * Solr encode
   *
   * @param object $value          
   * @param string $type          
   * @return string
   */
  private function solrEncode($value, $type = null) {
    if (($type == "equals") && is_bool ( $value )) {
      if ($value) {
        $string = "true";
      } else {
        $string = "false";
      }
    } else if ($type == "query") {
      $match = array (
          '\\' 
      );
      $replace = array (
          '\\\\' 
      );
      $string = "\"" . str_replace ( $match, $replace, $value ) . "\"";
    } else if ($type == "range") {
      $match = array (
          '\\',
          '+',
          '-',
          '&',
          '|',
          '!',
          '(',
          ')',
          '{',
          '}',
          '[',
          ']',
          '^',
          '~',
          '?',
          ':',
          '"',
          ';',
          ' ' 
      );
      $replace = array (
          '\\\\',
          '\\+',
          '\\-',
          '\\&',
          '\\|',
          '\\!',
          '\\(',
          '\\)',
          '\\{',
          '\\}',
          '\\[',
          '\\]',
          '\\^',
          '\\~',
          '\\?',
          '\\:',
          '\\"',
          '\\;',
          '\\ ' 
      );
      $string = str_replace ( $match, $replace, $value );
    } else if ($type == "wildcard") {
      $match = array (
          '\\',
          '+',
          '-',
          '&',
          '|',
          '!',
          '(',
          ')',
          '{',
          '}',
          '[',
          ']',
          '^',
          '~',
          ':',
          '"',
          ';',
          ' ' 
      );
      $replace = array (
          '\\\\',
          '\\+',
          '\\-',
          '\\&',
          '\\|',
          '\\!',
          '\\(',
          '\\)',
          '\\{',
          '\\}',
          '\\[',
          '\\]',
          '\\^',
          '\\~',
          '\\:',
          '\\"',
          '\\;',
          '\\ ' 
      );
      $string = str_replace ( $match, $replace, $value );
    } else if ($type == "phrase") {
      $match = array (
          '\\',
          '+',
          '-',
          '&',
          '|',
          '!',
          '(',
          ')',
          '{',
          '}',
          '[',
          ']',
          '^',
          '~',
          '*',
          '?',
          ':',
          '"',
          ';',
          ' ' 
      );
      $replace = array (
          '\\\\',
          '\\+',
          '\\-',
          '\\&',
          '\\|',
          '\\!',
          '\\(',
          '\\)',
          '\\{',
          '\\}',
          '\\[',
          '\\]',
          '\\^',
          '\\~',
          '\\*',
          '\\?',
          '\\:',
          '\\"',
          '\\;',
          '\\ ' 
      );
      $string = str_replace ( $match, $replace, $value );
      $string = "\"" . $string . "\"";
    } else if ($type == "regexp") {
      $match = array (
          '/' 
      );
      $replace = array (
          '\\/' 
      );
      $string = str_replace ( $match, $replace, $value );
      $string = "/" . $string . "/";
    } else if ($type == "cql") {
      $match = array (
          '\\',
          '"' 
      );
      $replace = array (
          '\\\\',
          '\\"' 
      );
      $string = str_replace ( $match, $replace, $value );
      $string = "\"" . $string . "\"";
    } else {
      $match = array (
          '\\',
          '+',
          '-',
          '&',
          '|',
          '!',
          '(',
          ')',
          '{',
          '}',
          '[',
          ']',
          '^',
          '~',
          '*',
          '?',
          ':',
          '"',
          ';',
          ' ' 
      );
      $replace = array (
          '\\\\',
          '\\+',
          '\\-',
          '\\&',
          '\\|',
          '\\!',
          '\\(',
          '\\)',
          '\\{',
          '\\}',
          '\\[',
          '\\]',
          '\\^',
          '\\~',
          '\\*',
          '\\?',
          '\\:',
          '\\"',
          '\\;',
          '\\ ' 
      );
      $string = str_replace ( $match, $replace, $value );
      if (! preg_match ( "/ /", $string )) {
        $string = "\"" . $string . "\"";
      }
    }
    return $string;
  }
}

?>