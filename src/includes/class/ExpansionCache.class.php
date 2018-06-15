<?php

/**
 * Broker
 * @package Broker
 */
namespace Broker;

/**
 * Cache for expansion modules
 */
class ExpansionCache extends Database {
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
   */
  public function __construct($directory) {
    parent::__construct($directory, null, "expansion");
  }
  /**
   * Initialize
   */
  public function init() {
    $sql = "CREATE TABLE IF NOT EXISTS \"expansion\" (
          \"id\" INTEGER PRIMARY KEY ASC,
          \"hash\" TEXT NOT NULL,
          \"module\" TEXT NOT NULL,
          \"value\" TEXT NOT NULL,
          \"parameters\" TEXT NOT NULL,
          \"result\" TEXT NOT NULL,
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
   * @param string $module          
   * @param string|array $value          
   * @param object $parameters          
   * @param object $result          
   */
  public function create($module, $value, $parameters, $result) {
    $this->clean ();
    $value = is_null ( $value ) ? "" : serialize ( $value );
    $parameters = is_null ( $parameters ) ? "" : serialize ( $parameters );
    $result = is_null ( $result ) ? "" : serialize ( $result );
    // hash
    $hash = $this->createHash ( $module, $value, $parameters );
    // delete
    $sql = "DELETE FROM \"expansion\" WHERE hash IS :hash;";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":hash", $hash );
    $query->execute ();
    unset ( $query );
    // insert
    $sql = "INSERT OR IGNORE INTO \"expansion\"
    (hash, module, value, parameters, result, numberOfChecks, created, used, expires)
    VALUES (:hash, :module, :value, :parameters, :result, 1, datetime('now'), datetime('now'), datetime('now', '+" . intval ( $this->lifetime ) . " minutes'))";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":hash", $hash );
    $query->bindValue ( ":module", $module );
    $query->bindValue ( ":value", $value );
    $query->bindValue ( ":parameters", $parameters );
    $query->bindValue ( ":result", $result );
    $query->execute ();
    unset ( $query );
  }
  /**
   * Get
   *
   * @param string $hash          
   * @return object
   */
  public function get($hash) {
    $sql = "SELECT
    id, hash, module, value, parameters, result, numberOfChecks,
    datetime(created, 'localtime') as created,
    datetime(used, 'localtime') as used,
    datetime(expires, 'localtime') as expires
    FROM \"expansion\"
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
   * Delete
   *
   * @param string $hash          
   */
  public function delete($hash) {
    $sql = "DELETE FROM \"expansion\" WHERE hash IS :hash;";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":hash", $hash );
    $query->execute ();
    unset ( $query );
  }
  /**
   * Check
   *
   * @param string $module          
   * @param string|array $value          
   * @param object $parameters          
   * @return array
   */
  public function check($module, $value, $parameters) {
    $this->clean ();
    $value = is_null ( $value ) ? "" : serialize ( $value );
    $parameters = is_null ( $parameters ) ? "" : serialize ( $parameters );
    // hash
    $hash = $this->createHash ( $module, $value, $parameters );
    // get info
    $sql = "SELECT
        id, result
    FROM \"expansion\"
    WHERE hash IS :hash
    AND module IS :module
    AND value IS :value
    AND parameters IS :parameters;";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":hash", $hash );
    $query->bindValue ( ":module", $module );
    $query->bindValue ( ":value", $value );
    $query->bindValue ( ":parameters", $parameters );
    if ($query->execute ()) {
      $result = $query->fetch ( \PDO::FETCH_ASSOC );
      unset ( $query );
      if ($result && $result ["id"]) {
        // update
        $sql = "UPDATE \"expansion\" SET
          numberOfChecks = numberOfChecks + 1,    
          used = datetime('now'),   
          expires = datetime('now', '+" . intval ( $this->lifetime ) . " minutes')          
        WHERE id IS :id;";
        $query = $this->database->prepare ( $sql );
        $query->bindValue ( ":id", $result ["id"] );
        $query->execute ();
        unset ( $query );
        // return response
        if ($result ["result"]) {
          $result ["result"] = unserialize ( $result ["result"] );
        } else {
          $result ["result"] = null;
        }
        return array (
            $result ["id"],
            $result ["result"] 
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
   * @param int $start          
   * @param int $number          
   * @return array
   */
  public function getList($start, $number) {
    $sql = "SELECT
        id, hash, module, value, parameters, numberOfChecks,
        datetime(created, 'localtime') as created,
        datetime(used, 'localtime') as used,
        datetime(expires, 'localtime') as expires
    FROM \"expansion\"
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
    $sql = "DELETE FROM \"expansion\" WHERE expires < datetime('now');";
    $query = $this->database->prepare ( $sql );
    $query->execute ();
    unset ( $query );
  }  
  /**
   * Create hash
   *
   * @param string $module          
   * @param string $value          
   * @param string $parameters          
   * @return string
   */
  private static function createHash($module, $value, $parameters) {
    $base = trim ( $module ) . "\n" . trim ( $value ) . "\n" . trim ( $parameters );
    return hash ( "md5", $base );
  }
}

?>