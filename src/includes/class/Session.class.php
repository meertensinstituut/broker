<?php

namespace Broker;

class Session implements \SessionHandlerInterface {
  private $database;
  private $filename;
  public function __construct($directory) {
    if (file_exists ( $directory ) && is_file ( $directory )) {
      $this->filename = $directory;
      if (! is_writeable ( $this->filename )) {
        $this->filename = tempnam ( sys_get_temp_dir (), "session" );
      }
    } else if (is_dir ( $directory )) {
      $this->filename = $directory . "session";
      if (! is_writable ( $directory ) || (file_exists ( $this->filename ) && ! is_writable ( $this->filename ))) {
        $this->filename = tempnam ( sys_get_temp_dir (), "session" );
      }
    }
    $this->init();
  }
  private function init() {
    $this->database = new \PDO ( "sqlite:" . $this->filename );
    $this->database->setAttribute ( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
    $sql = "CREATE TABLE IF NOT EXISTS sessions(
          id INTEGER PRIMARY KEY ASC,
          sid TEXT NOT NULL UNIQUE,
          data TEXT,
          created TEXT NOT NULL,
          updated TEXT NOT NULL);";
    $query = $this->database->prepare ( $sql );
    $query->execute ();
    unset($query);
  }
  public function __destruct() {
    session_write_close ( true );
  }
  public function close() {
    $this->database = null;
    return true;
  }
  public function destroy($session_id) {
    $sql = "DELETE FROM sessions WHERE sid = :sid";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":sid", $session_id );
    return $query->execute ();
  }
  public function gc($maxlifetime) {
    $sql = "DELETE FROM sessions WHERE updated < datetime('now', '-" . intval ( $maxlifetime ) . " seconds')";
    $query = $this->database->prepare ( $sql );
    return $query->execute ();
  }
  public function open($save_path, $session_name) {
    return $this->database != null;
  }
  public function read($session_id) {
    $sql = "SELECT data FROM sessions WHERE sid = :sid";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":sid", $session_id );
    if ($query->execute ()) {
      $result = $query->fetch ( \PDO::FETCH_ASSOC );
      if ($result) {
        return ( string ) $result ["data"];
      } else {
        return "";
      }
    } else {
      return "";
    }
  }
  public function write($session_id, $session_data) {
    $sql = "INSERT OR REPLACE INTO sessions (sid, data, created, updated) 
                                         VALUES (:sid, :data, COALESCE((SELECT created FROM sessions WHERE sid = :sid), datetime('now')), datetime('now'))";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":sid", $session_id );
    $query->bindValue ( ":data", $session_data );
    return $query->execute ();
  } 
  public function number(): int {
    $sql = "SELECT COUNT(*) AS number
    FROM \"sessions\";";
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
  public function reset() {
    $sql = "DROP TABLE IF EXISTS \"sessions\";";
    $query = $this->database->prepare ( $sql );
    $query->execute ();
    unset ( $query );
    $this->init ();
  }
}

?>