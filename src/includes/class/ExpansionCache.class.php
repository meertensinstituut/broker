<?php

namespace Broker;

class ExpansionCache {
  private $lifetime = 3000;
  public function __construct($directory) {
    if (file_exists ( $directory ) && is_file ( $directory )) {
      $this->filename = $directory;
      if (! is_writeable ( $this->filename )) {
        $this->filename = tempnam ( sys_get_temp_dir (), "expansion" );
      }
    } else if (is_dir ( $directory )) {
      $this->filename = $directory . "expansion";
      if (! is_writable ( $directory ) || (file_exists ( $this->filename ) && ! is_writable ( $this->filename ))) {
        $this->filename = tempnam ( sys_get_temp_dir (), "expansion" );
      }
    }
    $this->init ();
  }
  private function init() {
    $this->database = new \PDO ( "sqlite:" . $this->filename );
    $this->database->setAttribute ( \PDO::ATTR_TIMEOUT, 5000 );
    $this->database->setAttribute ( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
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
  public function create(string $module, $value, $parameters, $result) {
    $this->clean ();
    $value = is_null($value)?"":serialize($value);
    $parameters = is_null($parameters)?"":serialize($parameters);
    $result = is_null($result)?"":serialize($result);
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
    VALUES (:hash, :module, :value, :parameters, :result, 1, datetime('now'), datetime('now'), datetime('now', '+".intval($this->lifetime)." minutes'))";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":hash", $hash );
    $query->bindValue ( ":module", $module );
    $query->bindValue ( ":value", $value );
    $query->bindValue ( ":parameters", $parameters );
    $query->bindValue ( ":result", $result );
    $query->execute ();
    unset ( $query );
  }
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
  public function delete(string $hash) {
    $sql = "DELETE FROM \"expansion\" WHERE hash IS :hash;";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":hash", $hash );
    $query->execute ();
    unset($query);
  }
  public function check(string $module, $value, $parameters): array {
    $this->clean ();
    $value = is_null($value)?"":serialize($value);
    $parameters = is_null($parameters)?"":serialize($parameters);
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
      if ($result && $result["id"]) {
        // update
        $sql = "UPDATE \"expansion\" SET
          numberOfChecks = numberOfChecks + 1,    
          used = datetime('now'),   
          expires = datetime('now', '+".intval($this->lifetime)." minutes')          
        WHERE id IS :id;";
        $query = $this->database->prepare ( $sql );
        $query->bindValue ( ":id", $result["id"] );
        $query->execute ();
        unset ( $query );
        //return response        
        if($result["result"]) {
          $result["result"] = unserialize($result["result"]);
        } else {
          $result["result"] = null;
        }
        return array($result["id"], $result["result"]);
      } else {
        return array(null, null);
      }
    } else {
      return array(null, null);
    }
  }
  public function number(): int {
    $sql = "SELECT COUNT(*) AS number
    FROM \"expansion\";";
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
  public function clean() {
    $sql = "DELETE FROM \"expansion\" WHERE expires < datetime('now');";
    $query = $this->database->prepare ( $sql );
    $query->execute ();
    unset ( $query );        
  }
  public function reset() {
    //$sql = "DROP TABLE IF EXISTS \"expansion\";";
    //$query = $this->database->prepare ( $sql );
    //$query->execute ();
    //unset ( $query );
    @unlink($this->filename);
    $this->init ();
  }
  private static function createHash(string $module, string $value, string $parameters): string {
    $base = trim ( $module ) . "\n" . trim ( $value ) . "\n" . trim ( $parameters );
    return hash ( "md5", $base );
  }
}

?>