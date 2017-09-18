<?php

namespace Broker;

class Cache {
  private $configuration;
  private $lifetime = 3000;
  public function __construct($directory, $configuration) {
    if (file_exists ( $directory ) && is_file ( $directory )) {
      $this->filename = $directory;
      if (! is_writeable ( $this->filename )) {
        $this->filename = tempnam ( sys_get_temp_dir (), "cache" );
      }
    } else if (is_dir ( $directory )) {
      $this->filename = $directory . "collection";
      if (! is_writable ( $directory ) || (file_exists ( $this->filename ) && ! is_writable ( $this->filename ))) {
        $this->filename = tempnam ( sys_get_temp_dir (), "cache" );
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
    $sql = "CREATE TABLE IF NOT EXISTS \"cache\" (
          \"id\" INTEGER PRIMARY KEY ASC,
          \"hash\" TEXT NOT NULL,
          \"configuration\" TEXT NULL,
          \"url\" TEXT NOT NULL,
          \"request\" TEXT NOT NULL,
          \"response\" TEXT NOT NULL,
          \"numberOfChecks\" INTEGER,
          \"created\" TEXT NOT NULL,
          \"used\" TEXT NOT NULL,          
          \"expires\" TEXT NOT NULL,
          UNIQUE(\"hash\"));";
    $query = $this->database->prepare ( $sql );
    $query->execute ();
    $this->database->commit();
    unset ( $query );
  }
  public function create(string $configuration, string $url, string $request, $response) {
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
    (hash, configuration, url, request, response, numberOfChecks, created, used, expires)
    VALUES (:hash, :configuration, :url, :request, :response, 1, datetime('now'), datetime('now'), datetime('now', '+" . intval ( $this->lifetime ) . " minutes'))";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":hash", $hash );
    $query->bindValue ( ":configuration", $configuration );
    $query->bindValue ( ":url", $url );
    $query->bindValue ( ":request", $request );
    $query->bindValue ( ":response", $response );
    $query->execute ();
    unset ( $query );
  }
  public function get($hash) {
    $sql = "SELECT    
    id, hash, configuration, url, request, response, numberOfChecks,
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
  public function check(string $configuration, string $url, string $request): array {
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
  public function number(): int {
    $sql = "SELECT COUNT(*) AS number
      FROM \"cache\";";
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
  public function clean() {
    $this->database->beginTransaction();
    $sql = "DELETE FROM \"cache\" WHERE expires < datetime('now');";
    $query = $this->database->prepare ( $sql );
    $query->execute ();
    $this->database->commit();
    unset ( $query );
  }
  public function reset() {
    $sql = "DROP TABLE IF EXISTS \"cache\";";
    $query = $this->database->prepare ( $sql );
    $query->execute ();
    unset ( $query );
    $this->init ();
  }
  private static function createHash(string $configuration, string $url, string $request): string {
    $base = trim ( $configuration ) . "\n" . trim ( $url ) . "\n" . trim ( $request );
    return hash ( "md5", $base );
  }
}

?>