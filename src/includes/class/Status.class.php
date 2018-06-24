<?php

/**
 * Broker
 * @package Broker
 */
namespace Broker;

/**
 * Status
 */
class Status extends Database {
  /**
   * Collection
   *
   * @var \Broker\Collection
   */
  private $collection;
  /**
   * Cache
   *
   * @var \Broker\Cache
   */
  private $cache;
  /**
   * Timeout
   *
   * @var number
   */
  private $timeout = 10;
  /**
   * Constructor
   *
   * @param string $directory          
   * @param object $configuration          
   * @param \Broker\Cache $cache          
   */
  public function __construct($directory, $configuration, $cache) {
    parent::__construct ( $directory, $configuration, "status" );
    $this->cache = $cache;
  }
  /**
   * Init
   */
  public function init() {
    $sql = "CREATE TABLE IF NOT EXISTS \"status\" (
          \"id\" INTEGER PRIMARY KEY ASC,
          \"key\" TEXT NOT NULL,
          \"statusKey\" TEXT,
          \"brokerRequest\" TEXT NOT NULL,
          \"collectionIds\" TEXT,
          \"cache\" INTEGER,
          \"configuration\" TEXT,
          \"solrUrl\" TEXT,
          \"solrRequest\" TEXT,
          \"solrRequestAddition\" TEXT,
          \"solrShards\" TEXT,
          \"solrStatus\" TEXT,
          \"responseJoins\" TEXT,
          \"created\" TEXT NOT NULL,
          \"started\" TEXT NULL,
          \"updated\" TEXT NULL,
          \"finished\" TEXT NULL,
          \"expires\" TEXT NOT NULL,
          UNIQUE(\"key\"));";
    $query = $this->database->prepare ( $sql );
    $query->execute ();
    $this->errorCheck ( "init", $query, false );
    unset ( $query );
  }
  /**
   * Create status
   *
   * @param string $brokerRequest          
   * @param boolean $status          
   * @return array
   */
  public function create($brokerRequest, $status=false) {
    $this->clean ();
    $response = array ();
    $response ["status"] = "ERROR";
    if ($brokerRequest && trim ( $brokerRequest != "" )) {
      try {
        $key = $this->generateKey ();
        if($status) {
          $statusKey = $this->generateKey (32);
        } else {
          $statusKey = null;
        }
        $parser = new \Broker\Parser ( $brokerRequest, $this->configuration, $this->cache, null, null, $statusKey );
        $collectionIds = $parser->getCollectionIds ();
        $parserConfiguration = $parser->getConfiguration ();
        $solrUrl = $parser->getUrl ();
        $solrRequest = $parser->getRequest ();
        $solrRequestAddition = $parser->getRequestAddition ();
        $solrShards = $parser->getShards ();
        $cacheEnabled = $parser->getCache ()!=null;
        $responseJoins = $parser->getResponseJoins ();
        if ($solrRequest != null) {
          $response ["solrRequest"] = array (
              "description" => null,
              "data" => array (),
              "key" => $parser->getStatusKey ()
          );
          $collectionObject = $parser->getCollection ();
          $dependencies = $collectionObject->getWithDependencies ( $collectionIds );
          for($i = 0; $i < count ( $dependencies ["missing"] ); $i ++) {
            $response ["solrRequest"] ["data"] ["collection" . $i] = "collection " . $dependencies ["missing"] [$i] . " is not available";
          }
          for($i = 0; $i < count ( $dependencies ["data"] ); $i ++) {
            $response ["solrRequest"] ["data"] ["collection" . ($i + count ( $dependencies ["missing"] ))] = "collection " . $dependencies ["data"] [$i] ["key"] . " on " . $dependencies ["data"] [$i] ["configuration"] . ": " . $dependencies ["data"] [$i] ["solrCreateRequest"];
          }
          $response ["solrRequest"] ["data"] ["main"] = "main request on " . $parserConfiguration . ": " . $solrRequest;
          if($solrRequestAddition) {
            $response ["solrRequest"] ["data"] ["additional"] = "additional parameters for request on " . $parserConfiguration . ": " . $solrRequestAddition;
          }
        }
        if (count ( $parser->getWarnings () ) > 0) {
          $response ["brokerWarnings"] = array (
              "data" => $parser->getWarnings () 
          );
        }
        if (count ( $parser->getErrors () ) > 0) {
          $response ["brokerErrors"] = array (
              "data" => $parser->getErrors () 
          );
          $response ["solrStatus"] = array (
              "description" => "request couldn't be parsed by broker",
              "data" => array (
                  "broker" => "request couldn't be parsed by broker" 
              ) 
          );
        } else {
          $infoList = array ();
          $infoList ["broker_0"] = "request successfully parsed by broker";
          $infoList ["broker_1"] = "solr configuration '" . $parser->getConfiguration () . "' used";
          if (($shards = $parser->getShards ()) == null) {
            $infoList ["broker_2"] = "request for single core";
          } else {
            $infoList ["broker_2"] = "request for " . count ( $shards ) . " shard" . (count ( $shards ) > 1 ? "s" : "");
          }
          $response ["solrStatus"] = array (
              "description" => "request parsed by broker",
              "data" => $infoList 
          );
          // delete key (always insert)
          $sql = "DELETE FROM status WHERE key = :key";
          $query = $this->database->prepare ( $sql );
          $query->bindValue ( ":key", $key );
          $query->execute ();
          unset ( $query );
          // create status
          $sql = "INSERT INTO status (key, statusKey, brokerRequest, collectionIds, cache, configuration, solrUrl, solrRequest, solrRequestAddition, solrShards, responseJoins, created, expires)
                                             VALUES (:key, :statusKey, :brokerRequest, :collectionIds, :cache, 
                                             :configuration, :solrUrl, :solrRequest, :solrRequestAddition, :solrShards, 
                                             :responseJoins, datetime('now'), datetime('now', '+" . intval ( $this->timeout ) . " minutes'))";
          $query = $this->database->prepare ( $sql );
          $query->bindValue ( ":key", $key );
          $query->bindValue ( ":statusKey", $statusKey );
          $query->bindValue ( ":brokerRequest", $brokerRequest );
          $query->bindValue ( ":collectionIds", count ( $collectionIds ) > 0 ? json_encode ( $collectionIds ) : "" );
          $query->bindValue ( ":cache", $cacheEnabled ? 1 : 0 );
          $query->bindValue ( ":configuration", $parserConfiguration );
          $query->bindValue ( ":solrUrl", $solrUrl );
          $query->bindValue ( ":solrRequest", $solrRequest );
          $query->bindValue ( ":solrRequestAddition", $solrRequestAddition );
          $query->bindValue ( ":solrShards", $solrShards ? implode ( ",", $solrShards ) : null );
          $query->bindValue ( ":responseJoins", ($responseJoins && is_object($responseJoins)) ? json_encode ( $responseJoins ) : null );
          if ($query->execute ()) {
            $response ["id"] = $this->database->lastInsertId ();
            $response ["key"] = $key;
            if($statusKey!=null) {
              $response ["statusKey"] = $statusKey;
            }
            $response ["status"] = "OK";
          } else {
            $response ["error"] = "couldn't create status";
          }
          unset ( $query );
        }
      } catch ( \Exception $e ) {
        $response ["error"] = $e->getMessage ();
      }
    } else {
      $response ["error"] = "no request";
    }
    return $response;
  }
  /**
   * Get status
   *
   * @param string $key          
   * @return array
   */
  public function get($key) {
    // update expiration
    $sql = "UPDATE \"status\" SET
        expires = datetime('now', '+" . intval ( $this->timeout ) . " minutes')
    WHERE key IS :key;";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":key", $key );
    $query->execute ();
    unset ( $query );
    // get info
    $sql = "SELECT
        key, 
        statusKey, 
        brokerRequest,
        collectionIds,
        cache,
        solrUrl,
        solrRequest,
        solrRequestAddition,
        solrShards,
        solrStatus,        
        responseJoins,        
        datetime(created, 'localtime') as created, 
        datetime(started, 'localtime') as started, 
        datetime(updated, 'localtime') as updated, 
        datetime(finished, 'localtime') as finished, 
        datetime(expires, 'localtime') as expires
    FROM \"status\"
    WHERE key IS :key;";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":key", $key );
    if ($query->execute ()) {
      $result = $query->fetch ( \PDO::FETCH_ASSOC );
      unset ( $query );
      if ($result) {
        return ( array ) $result;
      } else {
        return null;
      }
    } else {
      return null;
    }
  }
  /**
   * Start
   *
   * @param string $key          
   * @return array
   */
  public function start($key) {
    $response = array ();
    $response ["status"] = "ERROR";
    $sql = "SELECT * FROM status 
            WHERE key = :key AND started IS NULL 
            AND expires > datetime('now')";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":key", $key );
    if ($query->execute ()) {
      $status = $query->fetch ( \PDO::FETCH_ASSOC );
      unset ( $query );
      if ($status) {
        $sql = "UPDATE status SET started = datetime('now'), 
                expires = datetime('now', '+" . intval ( $this->timeout ) . " minutes')
                WHERE key = :key AND started IS NULL";
        $query = $this->database->prepare ( $sql );
        $query->bindValue ( ":key", $key );
        if ($query->execute ()) {
          unset ( $query );
          if ($status ["collectionIds"]) {
            $collectionIds = json_decode ( $status ["collectionIds"], true );
            $this->getCollection ();
            foreach ( $collectionIds as $collectionId ) {
              $checkInfo = $this->collection->check ( $collectionId );
              if (! $checkInfo) {
                $response ["error"] = "collection " . $collectionId . " not found";
                return $response;
              } else if (! $checkInfo ["initialised"]) {
                $response ["error"] = "collection " . $collectionId . " not initialised";
                return $response;
              } else if ($checkInfo ["check"]) {
                if (! $this->collection->doCheck ( $collectionId )) {
                  $response ["error"] = "collection " . $collectionId . " couldn't be checked";
                  return $response;
                }
              }
            }
          }
          try {
            $solr = new \Broker\Solr ( $status ["configuration"], $status ["solrUrl"], "select", $status ["solrRequest"], $status ["solrRequestAddition"], $status ["solrShards"], $status ["cache"] ? $this->getCache () : null );
            $solrResponse = $solr->getResponse ();
            if ($solrResponse && is_object ( $solrResponse )) {
              if (isset ( $solrResponse->error )) {
                $response ["error"] = $solrResponse->error;
              } else if (isset ( $solrResponse->responseHeader ) && isset ( $solrResponse->responseHeader->partialResults ) && $solrResponse->responseHeader->partialResults) {
                $partialNumber = (isset($solrResponse->response) && isset($solrResponse->response->numFound))?intval($solrResponse->response->numFound):null;
                $partialTime = (isset ($solrResponse->responseHeader) && isset ($solrResponse->responseHeader->QTime))?intval($solrResponse->responseHeader->QTime):null;
                $partialError = "Only partial results";
                if($partialNumber!==null) {
                  $partialError.=": ".$partialNumber." documents";
                }
                if($partialTime!==null) {
                  $partialError.=" found in ".$partialTime." ms";
                }
                $response ["error"] = $partialError;
              } else if (isset ( $solrResponse->response )) {
                $response ["status"] = "OK";
                $response ["response"] = clone $solrResponse;
                $responseJoins = $status ["responseJoins"] ? json_decode ( $status ["responseJoins"] ) : null;
                $responseObject = new \Broker\Response ( $response, $responseJoins, $this->configuration, $status ["cache"] ? $this->getCache () : null, $this->collection );
                $response = $responseObject->process ();
              } else {
                $response ["error"] = clone $solrResponse;
              }
            } else {
              $response ["error"] = $solrResponse;
            }
          } catch ( \Broker\SolrException $se ) {
            $response ["error"] = $se->getMessage ();
          } catch ( \Exception $e ) {
            $response ["error"] = $solr->getResponse ();
          }
          // register finish
          $sql = "UPDATE status SET finished = datetime('now'),
                expires = datetime('now', '+" . intval ( $this->timeout ) . " minutes')
                WHERE key = :key AND finished IS NULL";
          $query = $this->database->prepare ( $sql );
          $query->bindValue ( ":key", $key );
          $query->execute ();
          unset ( $query );
        } else {
          $response ["error"] = "couldn't start from status";
        }
      } else {
        $response ["error"] = "status not found";
      }
    } else {
      $response ["error"] = "status not found";
    }
    return $response;
  }
  /**
   * Update
   *
   * @param string $key          
   * @return array
   */
  public function update($key) {
    $response = array ();
    $response ["status"] = "ERROR";
    // select
    $sql = "SELECT id,updated,started,solrStatus FROM status
            WHERE key = :key 
            AND expires > datetime('now')";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":key", $key );
    if ($query->execute ()) {
      $status = $query->fetch ( \PDO::FETCH_ASSOC );
      unset ( $query );
      if ($status && $status ["id"]) {
        // update
        $sql = "UPDATE status SET updated = datetime('now'),
                expires = datetime('now', '+" . intval ( $this->timeout ) . " minutes')
                WHERE key = :key";
        $query = $this->database->prepare ( $sql );
        $query->bindValue ( ":key", $key );
        $query->execute ();
        unset ( $query );
        // create response;
        $response ["status"] = "OK";
        $response ["updated"] = $status ["updated"];
        $response ["started"] = $status ["started"];
        $response ["solrStatus"] = array (
            "description" => "sent to solr",
            "data" => array (
                "solr" => $status ["solrStatus"] 
            ) 
        );
      } else {
        $response ["error"] = "status not found";
      }
    } else {
      $response ["error"] = "status not found";
    }
    return $response;
  }
  /**
   * List
   *
   * @param int $start          
   * @param int $number          
   * @return array
   */
  public function getList($start, $number) {
    $sql = "SELECT
        key, 
        statusKey, 
        cache, 
        datetime(created, 'localtime') as created, 
        datetime(started, 'localtime') as started, 
        datetime(updated, 'localtime') as updated, 
        datetime(finished, 'localtime') as finished, 
        datetime(expires, 'localtime') as expires
    FROM \"status\"
    ORDER BY expires DESC
    LIMIT :start,:number;";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":start", $start );
    $query->bindValue ( ":number", $number );
    if ($query->execute ()) {
      $result = $query->fetchAll ( \PDO::FETCH_ASSOC );
      unset ( $query );
      if ($result) {
        return ( array ) $result;
      } else {
        return null;
      }
    } else {
      return null;
    }
  }
  /**
   * Delete
   *
   * @param string $key          
   */
  public function delete($key) {
    $this->clean ();
    $sql = "DELETE FROM \"status\" WHERE key IS :key;";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":key", $key );
    $query->execute ();
    $this->errorCheck ( "delete", $query, false );
    unset ( $query );
  }
  /**
   * Clean
   */
  public function clean() {
    $sql = "DELETE FROM status WHERE expires < datetime('now');";
    $query = $this->database->prepare ( $sql );
    $query->execute ();
    unset ( $query );
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
   * Get (or create) cache
   *
   * @return \Broker\Cache
   */
  private function getCache() {
    if (! $this->cache) {
      $this->cache = new \Broker\Cache ( SITE_CACHE_DATABASE_DIR, $this->configuration );
    }
    return $this->cache;
  }
  /**
   * Generate key
   *
   * @param number $length          
   * @return string
   */
  private function generateKey($length = 20) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen ( $characters );
    $randomString = '';
    for($i = 0; $i < $length; $i ++) {
      $randomString .= $characters [rand ( 0, $charactersLength - 1 )];
    }
    return $randomString;
  }
}

?>