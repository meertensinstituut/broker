<?php

namespace Broker;

class Collection {
  private $database;
  private $configuration;
  private $filename;
  public function __construct($directory, $configuration) {
    if (file_exists ( $directory ) && is_file ( $directory )) {
      $this->filename = $directory;
      if (! is_writeable ( $this->filename )) {
        $this->filename = tempnam ( sys_get_temp_dir (), "collection" );
      }
    } else if (is_dir ( $directory )) {
      $this->filename = $directory . "collection";
      if (! is_writable ( $directory ) || (file_exists ( $this->filename ) && ! is_writable ( $this->filename ))) {
        $this->filename = tempnam ( sys_get_temp_dir (), "collection" );
      }
    }
    $this->configuration = $configuration;
    $this->init ();
  }
  private function init() {
    $this->database = new \PDO ( "sqlite:" . $this->filename );
    $this->database->beginTransaction();
    $this->database->setAttribute ( \PDO::ATTR_TIMEOUT, 5000 );
    $this->database->setAttribute ( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
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
    $this->database->commit();
    unset ( $query );
  }
  public function create($configuration, $filter, $condition, $field): string {
    return $this->_create ( $configuration, $filter, $condition, $field, null );
  }
  public function createFromCollection($configuration, $collectionId): string {
    return $this->_create ( $configuration, null, null, null, $collectionId );
  }
  private function _create($configuration, $filter, $condition, $field, $collectionId): string {
    $this->clean ();
    // create strings
    list ( $hash, $brokerConfiguration, $brokerFilter, $brokerCondition, $brokerField, $sourceCollectionId ) = $this->createHash ( $configuration, $filter, $condition, $field, $collectionId );
    $this->database->beginTransaction();
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
    $this->database->commit();
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
  public function delete(string $key) {
    $this->clean ();
    $sql = "DELETE FROM \"collection\" WHERE key IS :key;";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":key", $key );
    $query->execute ();
    unset ( $query );
  }
  public function check(string $key, int $recheckTime = 60) {
    // update expiration
    $sql = "UPDATE \"collection\" SET
        expires = datetime('now', '+60 minutes')
    WHERE key IS :key
    AND initialised = 1;";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":key", $key );
    $query->execute ();
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
  public function get(string $key) {
    $this->clean ();
    // update expiration
    $sql = "UPDATE \"collection\" SET
        expires = datetime('now', '+60 minutes')
    WHERE key IS :key;";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":key", $key );
    $query->execute ();
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
  public function setInitialised(string $key, $configuration, string $solrUrl, string $solrCreateRequest, string $solrCheckRequest, string $solrShards, string $collectionIds) {
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
    unset ( $query );
  }
  public function setUninitialised(string $key) {
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
    unset ( $query );
  }
  public function setCreated(string $key, string $solrCreateStatus) {
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
    unset ( $query );
  }
  public function setUncreated(string $key, string $solrCreateStatus) {
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
    unset ( $query );
  }
  public function setChecked(string $key, string $solrCheckStatus) {
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
    unset ( $query );
  }
  public function setUnchecked(string $key, string $solrCheckStatus = null) {
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
    unset ( $query );
  }
  public function doInitialise(string $key) {
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
              if ($sourceCollectionInfo ["configuration"] && isset($this->configuration->config["solr"][$sourceCollectionInfo ["configuration"]])) {                
                $url = $this->configuration->config["solr"][$sourceCollectionInfo ["configuration"]]["url"];
                array_unshift($localCollectionIds, $result ["sourceCollectionId"]);
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
              $subRequestCreate->configuration = json_decode ($result ["brokerConfiguration"]);
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
          if($subRequestCreate) {
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
  public function doCheck(string $key, bool $dontReintialise = false): bool {
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
            $solr = new \Broker\Solr ( $result["configuration"], $result ["solrUrl"], "select", $result ["solrCheckRequest"], $result ["solrShards"], null );
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
            $solr = new \Broker\Solr ( $result["configuration"], $result ["solrUrl"], "select", $result ["solrCreateRequest"], $result ["solrShards"], null );
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
  public function number(): int {
    $sql = "SELECT COUNT(*) AS number
    FROM \"collection\";";
    $query = $this->database->prepare ( $sql );
    if ($query->execute ()) {
      $result = $query->fetch ( \PDO::FETCH_ASSOC );
      unset ( $query );
      if ($result) {
        return intval ( $result ["number"] );
      } else {
        return 0;
      }
    } else {
      return 0;
    }
  }
  public function list(int $start, int $number) {
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
  public function clean() {
    $sql = "DELETE FROM \"collection\" WHERE expires < datetime('now');";
    $query = $this->database->prepare ( $sql );
    $query->execute ();
    unset ( $query );
  }
  public function reset() {    
    $sql = "DROP TABLE IF EXISTS \"collection\";";
    $query = $this->database->prepare ( $sql );
    $query->execute ();
    unset ( $query );
    $this->init ();
  }
  private function generateKey(int $length = 20): string {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen ( $characters );
    $randomString = '';
    for($i = 0; $i < $length; $i ++) {
      $randomString .= $characters [rand ( 0, $charactersLength - 1 )];
    }
    return $randomString;
  }
  private static function createHash($configuration, $filter, $condition, $field, $collectionId): array {
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