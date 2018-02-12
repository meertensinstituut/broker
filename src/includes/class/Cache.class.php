<?php

/**
 * Broker
 * @package Broker
 */
namespace Broker;

/**
 * Cache
 */
class Cache extends Database {
  /**
   * Lifetime
   *
   * @var number
   */
  private $lifetime = 3000;
  /**
   * Constructor
   *
   * @param string $directory          
   * @param unknown $configuration          
   */
  public function __construct($directory, $configuration) {
    parent::__construct($directory, $configuration, "cache");
  }
  /**
   * Init
   */
  public function init() {
    $sql = "CREATE TABLE IF NOT EXISTS \"cache\" (
          \"id\" INTEGER PRIMARY KEY ASC,
          \"hash\" TEXT NOT NULL,
          \"configuration\" TEXT NULL,
          \"url\" TEXT NOT NULL,
          \"request\" TEXT NOT NULL,
          \"requestAddition\" TEXT,
          \"response\" TEXT NOT NULL,          
          \"numberOfChecks\" INTEGER,
          \"created\" TEXT NOT NULL,
          \"used\" TEXT NOT NULL,          
          \"expires\" TEXT NOT NULL,
          UNIQUE(\"hash\"));";
    $query = $this->database->prepare ( $sql );
    $query->execute ();
    unset ( $query );
  }
  /**
   * Create
   *
   * @param string $configuration          
   * @param string $url          
   * @param string $request          
   * @param unknown $response          
   */
  public function create($configuration, $url, $request, $requestAddition, $response) {
    $this->clean ();
    // hash
    $hash = $this->createHash ( $configuration, $url, $request );
    // delete
    $sql = "DELETE FROM \"cache\" WHERE hash IS :hash;";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":hash", $hash );
    $query->execute ();
    unset ( $query );
    // insert
    $sql = "INSERT OR IGNORE INTO \"cache\"
    (hash, configuration, url, request, requestAddition, response, numberOfChecks, created, used, expires)
    VALUES (:hash, :configuration, :url, :request, :requestAddition, :response, 1, datetime('now'), datetime('now'), datetime('now', '+" . intval ( $this->lifetime ) . " minutes'))";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":hash", $hash );
    $query->bindValue ( ":configuration", $configuration );
    $query->bindValue ( ":url", $url );
    $query->bindValue ( ":request", $request );
    $query->bindValue ( ":requestAddition", $requestAddition );
    $query->bindValue ( ":response", $response );
    $query->execute ();
    unset ( $query );
  }
  /**
   * Get
   *
   * @param string $hash          
   * @return array
   */
  public function get($hash) {
    $sql = "SELECT    
    id, hash, configuration, url, request, requestAddition, response, numberOfChecks,
    datetime(created, 'localtime') as created,
    datetime(used, 'localtime') as used,
    datetime(expires, 'localtime') as expires
    FROM \"cache\"
    WHERE hash IS :hash";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":hash", $hash );
    if ($query->execute ()) {
      $result = $query->fetch ( \PDO::FETCH_ASSOC );
      unset ( $query );
      return $result;
    } else {
      return null;
    }
  }
  /**
   * Check
   *
   * @param string $configuration          
   * @param string $url          
   * @param string $request          
   * @return array
   */
  public function check($configuration, $url, $request) {
    $this->clean ();
    // hash
    $hash = $this->createHash ( $configuration, $url, $request );
    // get info
    $sql = "SELECT
        id, response
    FROM \"cache\"
    WHERE hash IS :hash
    AND configuration IS :configuration
    AND url IS :url
    AND request IS :request;";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":hash", $hash );
    $query->bindValue ( ":configuration", $configuration );
    $query->bindValue ( ":url", $url );
    $query->bindValue ( ":request", $request );
    if ($query->execute ()) {
      $result = $query->fetch ( \PDO::FETCH_ASSOC );
      unset ( $query );
      if ($result && $result ["id"]) {
        // update
        $sql = "UPDATE \"cache\" SET
          numberOfChecks = numberOfChecks + 1,    
          used = datetime('now'),   
          expires = datetime('now', '+" . intval ( $this->lifetime ) . " minutes')          
        WHERE id IS :id;";
        $query = $this->database->prepare ( $sql );
        $query->bindValue ( ":id", $result ["id"] );
        $query->execute ();
        unset ( $query );
        // return response
        return array (
            $result ["id"],
            $result ["response"] 
        );
      } else {
        return array (
            null,
            null 
        );
      }
    } else {
      return array (
          null,
          null 
      );
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
        id, hash, configuration, numberOfChecks,
        datetime(created, 'localtime') as created,
        datetime(used, 'localtime') as used,
        datetime(expires, 'localtime') as expires
    FROM \"cache\"
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
   * Clean
   */
  public function clean() {
    $sql = "DELETE FROM \"cache\" WHERE expires < datetime('now');";
    $query = $this->database->prepare ( $sql );
    $query->execute ();
    unset ( $query );
  }  
  /**
   * Create hash
   *
   * @param string $configuration          
   * @param string $url          
   * @param string $request          
   * @return string
   */
  private static function createHash($configuration, $url, $request) {
    $base = trim ( $configuration ) . "\n" . trim ( $url ) . "\n" . trim ( $request );
    return hash ( "md5", $base );
  }
}

?>