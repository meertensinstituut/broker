<?php

/**
 * Broker
 * @package Broker
 */
namespace Broker;

/**
 * Collection
 */
class Collection extends Database {
  
  /**
   * Constructor
   *
   * @param string $directory          
   * @param unknown $configuration          
   */
  public function __construct($directory, $configuration) {
    parent::__construct($directory, $configuration, "collection");
  }
  
  /**
   * Initialize
   */
  public function init() {
    $sql = "CREATE TABLE IF NOT EXISTS \"collection\" (
          \"id\" INTEGER PRIMARY KEY ASC,
          \"key\" TEXT NOT NULL,
          \"hash\" TEXT NOT NULL,
          \"initialised\" INTEGER,
          \"brokerConfiguration\" TEXT NULL,
          \"brokerFilter\" TEXT NULL,
          \"brokerCondition\" TEXT NULL,
          \"brokerField\" TEXT NULL,
          \"sourceCollectionId\" TEXT NULL,
          \"configuration\" TEXT,
          \"collectionIds\" TEXT,
          \"solrUrl\" TEXT,
          \"solrCreateRequest\" TEXT,
          \"solrCheckRequest\" TEXT,
          \"solrShards\" TEXT,
          \"solrCreateStatus\" TEXT,
          \"solrCheckStatus\" TEXT,
          \"numberOfCreates\" INTEGER,
          \"numberOfChecks\" INTEGER,
          \"created\" TEXT NOT NULL,
          \"checked\" TEXT NULL,          
          \"expires\" TEXT NOT NULL,
          UNIQUE(\"key\"),
          UNIQUE(\"hash\"));";
    $query = $this->database->prepare ( $sql );
    $query->execute ();
    $this->errorCheck("init", $query, false);
    unset ( $query );
  }
  /**
   * Create
   *
   * @param unknown $configuration          
   * @param unknown $filter          
   * @param unknown $condition          
   * @param string $field          
   * @return string
   */
  public function create($configuration, $filter, $condition, $field) {
    return $this->_create ( $configuration, $filter, $condition, $field, null );
  }
  /**
   * Create from collection
   *
   * @param unknown $configuration          
   * @param string $collectionId          
   * @return string
   */
  public function createFromCollection($configuration, $collectionId) {
    return $this->_create ( $configuration, null, null, null, $collectionId );
  }
  /**
   * Create
   *
   * @param unknown $configuration          
   * @param unknown $filter          
   * @param unknown $condition          
   * @param string $field          
   * @param string $collectionId          
   * @return string
   */
  private function _create($configuration, $filter, $condition, $field, $collectionId) {
    $this->clean ();
    // create strings
    list ( $hash, $brokerConfiguration, $brokerFilter, $brokerCondition, $brokerField, $sourceCollectionId ) = $this->createHash ( $configuration, $filter, $condition, $field, $collectionId );
    // insert
    $sql = "INSERT OR IGNORE INTO \"collection\" 
    (key, hash, initialised, brokerConfiguration, brokerFilter, brokerCondition, brokerField, sourceCollectionId,
    configuration, collectionIds, solrUrl, solrCreateRequest, solrCheckRequest, solrShards, solrCreateStatus, solrCheckStatus, 
    numberOfCreates, numberOfChecks, created, checked, expires)
    VALUES (:key, :hash, 0, :brokerConfiguration, :brokerFilter, :brokerCondition, :brokerField, :sourceCollectionId,
        null, null, null, null, null, null, null, null, 0, 0, datetime('now'), null, datetime('now', '+60 minutes'))";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":key", $this->generateKey () );
    $query->bindValue ( ":hash", $hash );
    $query->bindValue ( ":brokerConfiguration", $brokerConfiguration );
    $query->bindValue ( ":brokerFilter", $brokerFilter );
    $query->bindValue ( ":brokerCondition", $brokerCondition );
    $query->bindValue ( ":brokerField", $brokerField );
    $query->bindValue ( ":sourceCollectionId", $sourceCollectionId );
    $query->execute ();
    $this->errorCheck("create - insert", $query, false);
    unset ( $query );
    // update (if already existed)
    $sql = "UPDATE \"collection\" SET expires = datetime('now', '+60 minutes')
    WHERE hash IS :hash 
    AND brokerConfiguration IS :brokerConfiguration 
    AND brokerFilter IS :brokerFilter 
    AND brokerCondition IS :brokerCondition
    AND brokerField IS :brokerField
    AND sourceCollectionId IS :sourceCollectionId;";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":hash", $hash );
    $query->bindValue ( ":brokerConfiguration", $brokerConfiguration );
    $query->bindValue ( ":brokerFilter", $brokerFilter );
    $query->bindValue ( ":brokerCondition", $brokerCondition );
    $query->bindValue ( ":brokerField", $brokerField );
    $query->bindValue ( ":sourceCollectionId", $sourceCollectionId );
    $query->execute ();
    $this->errorCheck("create - update", $query, false);
    unset ( $query );
    // get key
    $sql = "SELECT key FROM \"collection\" 
    WHERE hash IS :hash 
    AND brokerConfiguration IS :brokerConfiguration 
    AND brokerFilter IS :brokerFilter 
    AND brokerCondition IS :brokerCondition
    AND brokerField IS :brokerField
    AND sourceCollectionId IS :sourceCollectionId;";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":hash", $hash );
    $query->bindValue ( ":brokerConfiguration", $brokerConfiguration );
    $query->bindValue ( ":brokerFilter", $brokerFilter );
    $query->bindValue ( ":brokerCondition", $brokerCondition );
    $query->bindValue ( ":brokerField", $brokerField );
    $query->bindValue ( ":sourceCollectionId", $sourceCollectionId );
    if ($query->execute ()) {      
      $result = $query->fetch ( \PDO::FETCH_ASSOC );
      unset ( $query );
      if ($result) {
        return ( string ) $result ["key"];
      } else {
        return "";
      }
    } else {
      return "";
    }
  }  
  /**
   * Check
   *
   * @param string $key          
   * @param number $recheckTime          
   * @return array
   */
  public function check($key, $recheckTime = 60) {
    // update expiration
    $sql = "UPDATE \"collection\" SET
        expires = datetime('now', '+60 minutes')
    WHERE key IS :key
    AND initialised = 1;";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":key", $key );
    $query->execute ();
    $this->errorCheck("check - update", $query, false);
    unset ( $query );
    // get info
    $sql = "SELECT key, configuration, initialised,
        (case when checked IS null then 1 else (case when checked < datetime('now','-" . $recheckTime . " seconds') then 1 else 0 end) end) as \"check\" 
            FROM \"collection\"
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
   * Get
   *
   * @param string $key          
   * @return array
   */
  public function get($key) {
    $this->clean ();
    // update expiration
    $sql = "UPDATE \"collection\" SET
        expires = datetime('now', '+60 minutes')
    WHERE key IS :key;";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":key", $key );
    $query->execute ();
    $this->errorCheck("get - update", $query, false);
    unset ( $query );
    // get info
    $sql = "SELECT 
        key, initialised, brokerConfiguration, brokerFilter, 
        brokerCondition, brokerField, sourceCollectionId, configuration, collectionIds,
        solrUrl, solrCreateRequest, solrCheckRequest, solrShards, 
        solrCreateStatus, solrCheckStatus, 
        numberOfCreates, numberOfChecks,
        datetime(created, 'localtime') as created, 
        datetime(checked, 'localtime') as checked, 
        datetime(expires, 'localtime') as expires
    FROM \"collection\" 
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
   * Get with dependencies
   *
   * @param array $keys          
   * @return array
   */
  public function getWithDependencies(array $keys) {
    $result = array ();
    $result ["data"] = array ();
    $result ["dependencies"] = array ();
    $result ["missing"] = array ();
    while ( $key = array_shift ( $keys ) ) {
      $subResult = array ();
      $subResult ["data"] = array ();
      $subResult ["dependencies"] = array ();
      $subResult ["missing"] = array ();
      $mainResult = $this->get ( $key );
      if (! $mainResult) {
        $subResult ["missing"] [] = $key;
      } else {
        if ($mainResult ["collectionIds"]) {
          $collectionIds = explode ( ",", $mainResult ["collectionIds"] );
          while ( $collectionId = array_shift ( $collectionIds ) ) {
            if (! in_array ( $collectionId, $result ["dependencies"] ) && ! in_array ( $collectionId, $subResult ["dependencies"] )) {
              $subCollectionIds = array ();
              $subCollectionIds [] = $collectionId;
              while ( $subCollectionId = array_shift ( $subCollectionIds ) ) {
                if (! in_array ( $subCollectionId, $result ["dependencies"] ) && ! in_array ( $subCollectionId, $subResult ["dependencies"] )) {
                  $dependencyResult = $this->get ( $subCollectionId );
                  if ($dependencyResult) {
                    array_unshift ( $result ["data"], $dependencyResult );
                    array_unshift ( $result ["dependencies"], $subCollectionId );
                    if ($dependencyResult ["collectionIds"]) {
                      $subSubCollectionIds = explode ( ",", $dependencyResult ["collectionIds"] );
                      foreach ( $subSubCollectionIds as $subSubCollectionId ) {
                        array_unshift ( $subCollectionIds, $subSubCollectionId );
                      }
                    }
                  } else {
                    array_unshift ( $subResult ["missing"], $subCollectionId );
                  }
                }
              }
            }
          }
        }
        $result ["data"] [] = $mainResult;
        $result ["dependencies"] [] = $key;
      }
      // merge
      foreach ( $subResult ["data"] as $subDataItem ) {
        $result ["data"] [] = $subDataItem;
      }
      foreach ( $subResult ["dependencies"] as $subDependencyItem ) {
        $result ["dependencies"] [] = $subDependencyItem;
      }
      foreach ( $subResult ["missing"] as $subMissingItem ) {
        $result ["missing"] [] = $subMissingItem;
      }
    }
    return $result;
  }
  /**
   * Set initialised
   *
   * @param string $key          
   * @param unknown $configuration          
   * @param string $solrUrl          
   * @param string $solrCreateRequest          
   * @param string $solrCheckRequest          
   * @param string $solrShards          
   * @param string $collectionIds          
   */
  public function setInitialised($key, $configuration, $solrUrl, $solrCreateRequest, $solrCheckRequest, $solrShards, $collectionIds) {
    $sql = "UPDATE \"collection\" SET
        initialised = 1,
        configuration = :configuration,
        collectionIds = :collectionIds,
        solrUrl = :solrUrl,
        solrCreateRequest = :solrCreateRequest,
        solrCheckRequest = :solrCheckRequest,
        solrShards = :solrShards,
        solrCreateStatus = null,
        solrCheckStatus = null,
        checked = null,
        expires = datetime('now', '+60 minutes')
    WHERE key IS :key;";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":key", $key );
    $query->bindValue ( ":configuration", $configuration );
    $query->bindValue ( ":collectionIds", $collectionIds );
    $query->bindValue ( ":solrUrl", $solrUrl );
    $query->bindValue ( ":solrCreateRequest", $solrCreateRequest );
    $query->bindValue ( ":solrCheckRequest", $solrCheckRequest );
    $query->bindValue ( ":solrShards", $solrShards );
    $query->execute ();
    $this->errorCheck("setInitialised", $query, false);
    unset ( $query );
  }
  /**
   * Set uninitialised
   *
   * @param string $key          
   */
  public function setUninitialised($key) {
    $sql = "UPDATE \"collection\" SET
        initialised = 0,
        configuration = null,
        collectionIds = null,
        solrUrl = null,
        solrCreateRequest = null,
        solrCheckRequest = null,
        solrShards = null,
        solrCreateStatus = null,
        solrCheckStatus = null,
        checked = null,
        expires = datetime('now', '+60 minutes')
    WHERE key IS :key;";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":key", $key );
    $query->execute ();
    $this->errorCheck("setUninitialised", $query, false);
    unset ( $query );
  }
  /**
   * Set created
   *
   * @param string $key          
   * @param string $solrCreateStatus          
   */
  public function setCreated($key, $solrCreateStatus) {
    $sql = "UPDATE \"collection\" SET
        solrCreateStatus = :solrCreateStatus,
        numberOfCreates = numberOfCreates + 1,
        numberOfChecks = 0,
        checked = datetime('now'),
        expires = datetime('now', '+60 minutes')
    WHERE key IS :key
    AND initialised = 1;";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":key", $key );
    $query->bindValue ( ":solrCreateStatus", $solrCreateStatus );
    $query->execute ();
    $this->errorCheck("setCreated", $query, false);
    unset ( $query );
  }
  /**
   * Set uncreated
   *
   * @param string $key          
   * @param string $solrCreateStatus          
   */
  public function setUncreated($key, $solrCreateStatus) {
    $sql = "UPDATE \"collection\" SET        
        solrCreateStatus = :solrCreateStatus,
        solrCheckStatus = null,
        checked = null,
        expires = datetime('now', '+60 minutes')
    WHERE key IS :key
    AND initialised = 1;";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":key", $key );
    $query->bindValue ( ":solrCreateStatus", $solrCreateStatus );
    $query->execute ();
    $this->errorCheck("setUncreated", $query, false);
    unset ( $query );
  }
  /**
   * Set checked
   *
   * @param string $key          
   * @param string $solrCheckStatus          
   */
  public function setChecked($key, $solrCheckStatus) {
    $sql = "UPDATE \"collection\" SET
        solrCheckStatus = :solrCheckStatus,
        numberOfChecks = numberOfChecks + 1,
        checked = datetime('now'),
        expires = datetime('now', '+60 minutes')
    WHERE key IS :key
    AND initialised = 1;";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":key", $key );
    $query->bindValue ( ":solrCheckStatus", $solrCheckStatus );
    $query->execute ();
    $this->errorCheck("setChecked", $query, false);
    unset ( $query );
  }
  /**
   * Set unchecked
   *
   * @param string $key          
   * @param string $solrCheckStatus          
   */
  public function setUnchecked($key, $solrCheckStatus = null) {
    $sql = "UPDATE \"collection\" SET
        solrCheckStatus = :solrCheckStatus,
        checked = null,
        expires = datetime('now', '+60 minutes')
    WHERE key IS :key
    AND initialised = 1;";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":key", $key );
    $query->bindValue ( ":solrCheckStatus", $solrCheckStatus );
    $query->execute ();
    $this->errorCheck("setUnchecked", $query, false);
    unset ( $query );
  }
  /**
   * Do initialise
   *
   * @param string $key          
   */
  public function doInitialise($key) {
    $localCollectionIds = array ();
    $localWarnings = array ();
    $localErrors = array ();
    // get info
    $sql = "SELECT * FROM \"collection\"
    WHERE key IS :key;";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":key", $key );
    if ($query->execute ()) {
      $result = $query->fetch ( \PDO::FETCH_ASSOC );
      unset ( $query );
      if ($result) {
        if (! $result ["initialised"]) {
          // create main request
          $subRequestCreate = new \stdClass ();
          if ($result ["sourceCollectionId"]) {
            $sourceCollectionInfo = $this->get ( $result ["sourceCollectionId"] );
            if ($sourceCollectionInfo && ($sourceCollectionInfo ["key"] == $result ["sourceCollectionId"])) {
              if ($sourceCollectionInfo ["configuration"] && isset ( $this->configuration->config ["solr"] [$sourceCollectionInfo ["configuration"]] )) {
                $url = $this->configuration->config ["solr"] [$sourceCollectionInfo ["configuration"]] ["url"];
                array_unshift ( $localCollectionIds, $result ["sourceCollectionId"] );
                $subRequestCreate->response = new \stdClass ();
                $subRequestCreate->response->mtas = new \stdClass ();
                $subRequestCreate->response->mtas->collection = array ();
                $subRequestCreate->response->mtas->collection [0] = new \stdClass ();
                $subRequestCreate->response->mtas->collection [0]->id = $result ["key"];
                $subRequestCreate->response->mtas->collection [0]->action = "import";
                $subRequestCreate->response->mtas->collection [0]->url = $url;
                $subRequestCreate->response->mtas->collection [0]->collection = $result ["sourceCollectionId"];
              } else {
                $subRequestCreate = null;
                $localErrors [] = "collection " . $key . " - couldn't find url for configuration " . $sourceCollectionInfo ["configuration"];
              }
            } else {
              $subRequestCreate = null;
              $localErrors [] = "collection " . $key . " - couldn't find source collection " . $result ["sourceCollectionId"];
            }
          } else {
            if ($result ["brokerConfiguration"] != null) {
              $subRequestCreate->configuration = json_decode ( $result ["brokerConfiguration"] );
            }
            if ($result ["brokerFilter"] != null) {
              $subRequestCreate->filter = json_decode ( $result ["brokerFilter"] );
            }
            if ($result ["brokerCondition"] != null) {
              $subRequestCreate->condition = json_decode ( $result ["brokerCondition"] );
            }
            $subRequestCreate->response = new \stdClass ();
            $subRequestCreate->response->mtas = new \stdClass ();
            $subRequestCreate->response->mtas->collection = array ();
            $subRequestCreate->response->mtas->collection [0] = new \stdClass ();
            $subRequestCreate->response->mtas->collection [0]->id = $result ["key"];
            $subRequestCreate->response->mtas->collection [0]->action = "create";
            $subRequestCreate->response->mtas->collection [0]->field = $result ["brokerField"];
          }
          // create parser for collection
          if ($subRequestCreate) {
            $subCreateParser = new \Broker\Parser ( $subRequestCreate, $this->configuration, null, $this, null );
            // register result from parsing collection request
            $subParserWarnings = $subCreateParser->getWarnings ();
            $subParserErrors = $subCreateParser->getErrors ();
            $subParserCollectionParsers = $subCreateParser->getCollectionIds ();
            foreach ( $subParserErrors as $subParserError ) {
              $localErrors [] = "collection " . $key . " - " . $subParserError;
            }
            foreach ( $subParserWarnings as $subParserWarning ) {
              $localWarnings [] = "collection " . $key . " - " . $subParserWarning;
            }
            // success
            if (count ( $subParserErrors ) == 0) {
              foreach ( $subParserCollectionParsers as $subParserCollectionParser ) {
                $localCollectionIds [] = $subParserCollectionParser;
              }
              // create check request
              $subRequestCheck = new \stdClass ();
              $subRequestCheck->configuration = $subCreateParser->getConfiguration ();
              $subRequestCheck->response = new \stdClass ();
              $subRequestCheck->response->mtas = new \stdClass ();
              $subRequestCheck->response->mtas->collection = array ();
              $subRequestCheck->response->mtas->collection [0] = new \stdClass ();
              $subRequestCheck->response->mtas->collection [0]->id = $result ["key"];
              $subRequestCheck->response->mtas->collection [0]->action = "check";
              // create parser for collection
              $subCheckParser = new \Broker\Parser ( $subRequestCheck, $this->configuration, null, $this, null );
              // initialise
              $this->setInitialised ( $result ["key"], $subCreateParser->getConfiguration (), $subCreateParser->getUrl (), $subCreateParser->getRequest (), $subCheckParser->getRequest (), ($subCreateParser->getShards () != null) ? implode ( ",", $subCreateParser->getShards () ) : "", implode ( ",", $localCollectionIds ) );
            }
          }
        }
      }
    }
    return array (
        $localWarnings,
        $localErrors 
    );
  }
  /**
   * Do check
   *
   * @param string $key          
   * @param boolean $dontReintialise          
   * @return boolean
   */
  public function doCheck($key, $dontReintialise = false) {
    $sql = "SELECT * FROM \"collection\"
    WHERE key IS :key;";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":key", $key );
    if ($query->execute ()) {
      $result = $query->fetch ( \PDO::FETCH_ASSOC );
      unset ( $query );
      if ($result) {
        if (! $result ["initialised"]) {
          return false;
        } else {
          // dependency on other collections
          $deleteableCollectionIds = array ();
          if ($result ["collectionIds"]) {
            $collectionIds = explode ( ",", $result ["collectionIds"] );
            foreach ( $collectionIds as $collectionId ) {
              $checkInfo = $this->check ( $collectionId );
              if (! $checkInfo || $checkInfo ["key"] != $collectionId) {
                $deleteableCollectionIds [] = $collectionId;
                continue;
              } else {
                // check if intialised
                if (! $checkInfo ["initialised"]) {
                  $this->doInitialise ( $collectionId );
                  $checkInfo = $this->check ( $collectionId );
                  // can't initialise
                  if (! $checkInfo || ! $checkInfo ["initialised"]) {
                    $deleteableCollectionIds [] = $collectionId;
                    continue;
                  }
                }
                // check if checked
                if ($checkInfo ["check"] && ! $this->doCheck ( $collectionId )) {
                  $deleteableCollectionIds [] = $collectionId;
                  continue;
                }
              }
            }
            // remove collections that couldn't be checked
            foreach ( $deleteableCollectionIds as $deleteableCollectionId ) {
              $this->delete ( $deleteableCollectionId );
            }
            // reinitialise
            if (count ( $deleteableCollectionIds ) > 0) {
              // must initialise again
              $this->setUninitialised ( $key );
              if ($dontReintialise) {
                return false;
              } else {
                $this->doInitialise ( $key );
                return $this->doCheck ( $key );
              }
            }
          }
          // check
          try {
            $solr = new \Broker\Solr ( $result ["configuration"], $result ["solrUrl"], "select", $result ["solrCheckRequest"], $result ["solrShards"], null );
            $solrResponse = $solr->getResponse ();
            if ($solrResponse && is_object ( $solrResponse )) {
              if (isset ( $solrResponse->mtas ) && is_object ( $solrResponse->mtas )) {
                if (isset ( $solrResponse->mtas->collection ) && is_array ( $solrResponse->mtas->collection ) && count ( $solrResponse->mtas->collection ) == 1) {
                  $collectionResponse = $solrResponse->mtas->collection [0];
                  if (is_object ( $collectionResponse )) {
                    if (isset ( $collectionResponse->id ) && $collectionResponse->id == $key) {
                      $this->setChecked ( $key, json_encode ( $solrResponse ) );
                      return true;
                    }
                  }
                }
              }
            }
          } catch ( \Exception $e ) {
            // should not happen
          }
          try {
            $solr = new \Broker\Solr ( $result ["configuration"], $result ["solrUrl"], "select", $result ["solrCreateRequest"], $result ["solrShards"], null );
            $solrResponse = $solr->getResponse ();
            if ($solrResponse && is_object ( $solrResponse )) {
              if (isset ( $solrResponse->error )) {
                $this->setUncreated ( $key, json_encode ( $solrResponse->error ) );
              } else if (isset ( $solrResponse->responseHeader )) {
                $this->setCreated ( $key, json_encode ( $solrResponse ) );
                return true;
              } else {
                $this->setUncreated ( $key, json_encode ( $solrResponse ) );
              }
            } else {
              $this->setUncreated ( $key, json_encode ( $solrResponse ) );
            }
          } catch ( \Broker\SolrException $se ) {
            $this->setUncreated ( $key, $se->getMessage () );
          } catch ( \Exception $e ) {
            $this->setUncreated ( $key, $e->getMessage () );
          }
        }
        return false;
      } else {
        return false;
      }
    } else {
      return false;
    }
  }
  /**
   * Get list
   *
   * @param number $start          
   * @param number $number          
   * @return array
   */
  public function getList($start, $number) {
    $sql = "SELECT
        key, initialised, brokerConfiguration, brokerFilter, brokerCondition, brokerField,
        configuration, collectionIds, solrUrl, solrCreateRequest, solrCheckRequest, 
        solrShards, solrCreateStatus, solrCheckStatus,
        numberOfCreates, numberOfChecks,
        datetime(created, 'localtime') as created, 
        datetime(checked, 'localtime') as checked, 
        datetime(expires, 'localtime') as expires
    FROM \"collection\"
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
    $sql = "DELETE FROM \"collection\" WHERE key IS :key;";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":key", $key );
    $query->execute ();
    $this->errorCheck("delete", $query, false);
    unset ( $query );
  }
  /**
   * Clean
   */
  public function clean() {
    $sql = "DELETE FROM \"collection\" WHERE expires < datetime('now');";
    $query = $this->database->prepare ( $sql );
    $query->execute ();
    $this->errorCheck("clean", $query, false);
    unset ( $query );
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
  /**
   * Create hash
   *
   * @param unknown $configuration          
   * @param unknown $filter          
   * @param unknown $condition          
   * @param unknown $field          
   * @param unknown $collectionId          
   * @return array
   */
  private static function createHash($configuration, $filter, $condition, $field, $collectionId) {
    $base = "";
    if ($configuration != null) {
      $brokerConfiguration = json_encode ( $configuration );
      $base .= $brokerConfiguration;
    } else {
      $brokerConfiguration = null;
    }
    if ($collectionId != null) {
      $sourceCollectionId = $collectionId;
      $base .= $sourceCollectionId;
      $brokerFilter = null;
      $brokerCondition = null;
      $brokerField = null;
    } else {
      $sourceCollectionId = null;
      if ($filter != null) {
        $brokerFilter = json_encode ( $filter );
        $base .= $brokerFilter;
      } else {
        $brokerFilter = null;
      }
      if ($condition != null) {
        $brokerCondition = json_encode ( $condition );
        $base .= $brokerCondition;
      } else {
        $brokerCondition = null;
      }
      if ($field != null) {
        $brokerField = $field;
        $base .= $brokerField;
      } else {
        $brokerField = null;
      }
    }
    return array (
        hash ( "md5", $base ),
        $brokerConfiguration,
        $brokerFilter,
        $brokerCondition,
        $brokerField,
        $sourceCollectionId 
    );
  }
}

?>