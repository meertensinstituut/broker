<?php

namespace Broker;

class Status {
  private $database;
  private $configuration;
  private $filename;
  private $collection;
  private $cache;
  private $timeout = 10;
  public function __construct($directory, $configuration, $cache) {
    if (file_exists ( $directory ) && is_file ( $directory )) {
      $this->filename = $directory;
      if (! is_writeable ( $this->filename )) {
        $this->filename = tempnam ( sys_get_temp_dir (), "status" );
      }
    } else if (is_dir ( $directory )) {
      $this->filename = $directory . "status";
      if (! is_writable ( $directory ) || (file_exists ( $this->filename ) && ! is_writable ( $this->filename ))) {
        $this->filename = tempnam ( sys_get_temp_dir (), "status" );
      }
    }
    $this->configuration = $configuration;
    $this->cache = $cache;
    $this->init();
  }
  private function init() {
    $this->database = new \PDO ( "sqlite:" . $this->filename );
    $this->database->setAttribute(\PDO::ATTR_TIMEOUT, 5000);
    $this->database->setAttribute ( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
    $sql = "CREATE TABLE IF NOT EXISTS \"status\" (
          \"id\" INTEGER PRIMARY KEY ASC,
          \"key\" TEXT NOT NULL,
          \"brokerRequest\" TEXT NOT NULL,
          \"collectionIds\" TEXT,
          \"cache\" INTEGER,
          \"configuration\" TEXT,
          \"solrUrl\" TEXT,
          \"solrRequest\" TEXT,
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
    unset($query);
  }
  public function create(string $brokerRequest): array {
    $this->clean ();
    $response = array ();
    $response ["status"] = "ERROR";
    if ($brokerRequest && trim ( $brokerRequest != "" )) {
      try {
        $parser = new \Broker\Parser ( $brokerRequest, $this->configuration, $this->cache, null, null );        
        $key = $this->generateKey ();
        $collectionIds = $parser->getCollectionIds();
        $parserConfiguration = $parser->getConfiguration();
        $solrUrl = $parser->getUrl ();
        $solrRequest = $parser->getRequest ();
        $solrShards = $parser->getShards ();
        $cache = $parser->getCache();
        $responseJoins = $parser->getResponseJoins();
        if ($solrRequest != null) {
          $response ["solrRequest"] = array (
              "description" => null,
              "data" => array () 
          );  
          $dependencies = $parser->getCollection()->getWithDependencies($collectionIds);
          for($i=0;$i<count($dependencies["missing"]); $i++) {
            $response ["solrRequest"]["data"]["collection".$i] = "collection ".$dependencies["missing"][$i]." is not available";
          }
          for($i=0;$i<count($dependencies["data"]); $i++) {
            $response ["solrRequest"]["data"]["collection".($i+count($dependencies["missing"]))] = "collection ".$dependencies["data"][$i]["key"]." on ".$dependencies["data"][$i]["configuration"].": ".$dependencies["data"][$i]["solrCreateRequest"];           
          }    
          $response ["solrRequest"]["data"]["main"] = "main request on ".$parserConfiguration.": ".$solrRequest;
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
          unset($query);
          // create status
          $sql = "INSERT INTO status (key, brokerRequest, collectionIds, cache, configuration, solrUrl, solrRequest, solrShards, responseJoins, created, expires)
                                             VALUES (:key, :brokerRequest, :collectionIds, :cache, 
                                             :configuration, :solrUrl, :solrRequest, :solrShards, 
                                             :responseJoins, datetime('now'), datetime('now', '+".intval($this->timeout)." minutes'))";
          $query = $this->database->prepare ( $sql );
          $query->bindValue ( ":key", $key );
          $query->bindValue ( ":brokerRequest", $brokerRequest );
          $query->bindValue ( ":collectionIds", count($collectionIds)>0?json_encode($collectionIds):"" );
          $query->bindValue ( ":cache", $cache!=null ? 1 : 0 );
          $query->bindValue ( ":configuration", $parserConfiguration );
          $query->bindValue ( ":solrUrl", $solrUrl );
          $query->bindValue ( ":solrRequest", $solrRequest );
          $query->bindValue ( ":solrShards", $solrShards ? implode ( ",", $solrShards ) : null );
          $query->bindValue ( ":responseJoins", count($responseJoins)>0?json_encode($responseJoins):null );
          if ($query->execute ()) {
            $response ["id"] = $this->database->lastInsertId();
            $response ["key"] = $key;
            $response ["status"] = "OK";
          } else {
            $response ["error"] = "couldn't create status";
          }
          unset($query);
        }
      } catch ( \Exception $e ) {
        $response ["error"] = $e->getMessage ();
      }
    } else {
      $response ["error"] = "no request";
    }
    return $response;
  }
  public function get(string $key) {
    // update expiration
    $sql = "UPDATE \"status\" SET
        expires = datetime('now', '+".intval($this->timeout)." minutes')
    WHERE key IS :key;";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":key", $key );
    $query->execute ();
    unset($query);
    // get info
    $sql = "SELECT
        key, 
        brokerRequest,
        collectionIds,
        cache,
        solrUrl,
        solrRequest,
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
      unset($query);
      if ($result) {
        return ( array ) $result;
      } else {
        return null;
      }
    } else {
      return null;
    }
  }
  public function start(string $key): array {
    $response = array ();
    $response ["status"] = "ERROR";
    $sql = "SELECT * FROM status 
            WHERE key = :key AND started IS NULL 
            AND expires > datetime('now')";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":key", $key );
    if ($query->execute ()) {
      $status = $query->fetch ( \PDO::FETCH_ASSOC );
      unset($query);
      if ($status) {
        $sql = "UPDATE status SET started = datetime('now'), 
                expires = datetime('now', '+".intval($this->timeout)." minutes')
                WHERE key = :key AND started IS NULL";
        $query = $this->database->prepare ( $sql );
        $query->bindValue ( ":key", $key );
        if ($query->execute ()) {
          unset($query);
          if($status["collectionIds"]) {
            $collectionIds = json_decode($status["collectionIds"], true);
            $this->getCollection();
            foreach($collectionIds AS $collectionId) {
              $checkInfo = $this->collection->check($collectionId);
              if(!$checkInfo) {
                $response ["error"] = "collection ".$collectionId." not found";
                return $response;
              } else if(!$checkInfo["initialised"]) {
                $response ["error"] = "collection ".$collectionId." not initialised";
                return $response;
              } else if($checkInfo["check"]) {
                if(!$this->collection->doCheck($collectionId)) {
                  $response ["error"] = "collection ".$collectionId." couldn't be checked";
                  return $response;
                }                
              }
            }
          }
          try {
            $solr = new \Broker\Solr ( $status["configuration"], $status ["solrUrl"], "select", $status ["solrRequest"], $status ["solrShards"], $status["cache"]?$this->getCache():null );
            $solrResponse = $solr->getResponse ();
            if ($solrResponse && is_object ( $solrResponse )) {
              if (isset ( $solrResponse->error )) {
                $response ["error"] = $solrResponse->error;
              } else if (isset ( $solrResponse->response )) {
                $response ["status"] = "OK";
                $response ["response"] = clone $solrResponse;
                $responseJoins = $status["responseJoins"]?json_decode($status["responseJoins"]):null;
                $response = (new \Broker\Response($response, $responseJoins, $this->configuration, $status["cache"]?$this->getCache():null, $this->collection))->process();                
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
                expires = datetime('now', '+".intval($this->timeout)." minutes')
                WHERE key = :key AND finished IS NULL";
          $query = $this->database->prepare ( $sql );
          $query->bindValue ( ":key", $key );
          $query->execute ();
          unset($query);
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
  public function update(string $key): array {
    $response = array ();
    $response ["status"] = "ERROR";
    //select
    $sql = "SELECT id,updated,started,solrStatus FROM status
            WHERE key = :key 
            AND expires > datetime('now')";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":key", $key );
    if ($query->execute ()) {
      $status = $query->fetch ( \PDO::FETCH_ASSOC );
      unset($query);
      if ($status && $status ["id"]) {
        //update
        $sql = "UPDATE status SET updated = datetime('now'),
                expires = datetime('now', '+".intval($this->timeout)." minutes')
                WHERE key = :key";
        $query = $this->database->prepare ( $sql );
        $query->bindValue ( ":key", $key );
        $query->execute ();
        unset($query);
        //create response;
        $response ["status"] = "OK";
        $response ["updated"] = $status ["updated"];
        $response ["started"] = $status ["started"];
        $response ["solrStatus"] = array (
            "description" => "sent to solr",
            "data" => array (
                "solr" => $status["solrStatus"], 
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
  public function delete(string $key) {
    $sql = "DELETE FROM \"status\" WHERE key IS :key;";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":key", $key );
    $query->execute ();
    unset($query);
  }
  public function number():int {
    $sql = "SELECT COUNT(*) AS number
    FROM \"status\";";
    $query = $this->database->prepare ( $sql );
    if ($query->execute ()) {
      $result = $query->fetch ( \PDO::FETCH_ASSOC );
      unset($query);
      if ($result) {
        return intval($result["number"]);
      } else {
        return 0;
      }
    } else {
      return 0;
    }
  }
  public function list(int $start, int $number) {
    $sql = "SELECT
        key, 
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
      $result = $query->fetchAll( \PDO::FETCH_ASSOC );
      unset($query);
      if ($result) {
        return ( array ) $result;
      } else {
        return null;
      }
    } else {
      return null;
    }
  }
  public function clean() {
    $sql = "DELETE FROM status WHERE expires < datetime('now');";
    $query = $this->database->prepare ( $sql );
    $query->execute ();
    unset($query);
  }
  public function reset() {
    $sql = "DROP TABLE IF EXISTS \"status\";";
    $query = $this->database->prepare ( $sql );
    $query->execute ();
    unset($query);
    $this->init ();
  }
  public function getCollection() {
    if ($this->collection == null) {
      $this->collection = new \Broker\Collection ( SITE_CACHE_DATABASE_DIR, $this->configuration );
    }
    return $this->collection;
  }
  private function getCache() {
    if(!$this->cache) {
      $this->cache = new \Broker\Cache(SITE_CACHE_DATABASE_DIR, $this->configuration);
    }
    return $this->cache;
  }
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