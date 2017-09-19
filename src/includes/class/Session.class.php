<?php

/**
 * Broker
 * @package Broker
 */
namespace Broker;

/**
 * Session handler
 */
class Session implements \SessionHandlerInterface {
  /**
   * Database
   *
   * @var \PDO
   */
  private $database;
  /**
   * Filename
   *
   * @var string
   */
  private $filename;
  /**
   * Constructor
   *
   * @param string $directory          
   */
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
    $this->database = new \PDO ( "sqlite:" . $this->filename );
    // $this->database->setAttribute ( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
    $this->init ();
  }
  /**
   * Initialize
   */
  private function init() {
    $sql = "CREATE TABLE IF NOT EXISTS sessions(
          id INTEGER PRIMARY KEY ASC,
          sid TEXT NOT NULL UNIQUE,
          data TEXT,
          created TEXT NOT NULL,
          updated TEXT NOT NULL);";
    $query = $this->database->prepare ( $sql );
    $query->execute ();
    unset ( $query );
  }
  /**
   * Destruct
   */
  public function __destruct() {
    session_write_close ( true );
  }
  /**
   *
   * {@inheritDoc}
   *
   * @see SessionHandlerInterface::close()
   */
  public function close() {
    $this->database = null;
    return true;
  }
  /**
   *
   * {@inheritDoc}
   *
   * @see SessionHandlerInterface::destroy()
   * @param string $session_id          
   */
  public function destroy($session_id) {
    $sql = "DELETE FROM sessions WHERE sid = :sid";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":sid", $session_id );
    return $query->execute ();
  }
  /**
   *
   * {@inheritDoc}
   *
   * @see SessionHandlerInterface::gc()
   * @param number $maxlifetime          
   */
  public function gc($maxlifetime) {
    $sql = "DELETE FROM sessions WHERE updated < datetime('now', '-" . intval ( $maxlifetime ) . " seconds')";
    $query = $this->database->prepare ( $sql );
    return $query->execute ();
  }
  /**
   *
   * {@inheritDoc}
   *
   * @see SessionHandlerInterface::open()
   * @param string $save_path          
   * @param string $session_name          
   */
  public function open($save_path, $session_name) {
    return $this->database != null;
  }
  /**
   *
   * {@inheritDoc}
   *
   * @see SessionHandlerInterface::read()
   * @param string $session_id          
   */
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
  /**
   *
   * {@inheritDoc}
   *
   * @see SessionHandlerInterface::write()
   * @param string $session_id          
   * @param unknown $session_data          
   */
  public function write($session_id, $session_data) {
    $sql = "INSERT OR REPLACE INTO sessions (sid, data, created, updated) 
                                         VALUES (:sid, :data, COALESCE((SELECT created FROM sessions WHERE sid = :sid), datetime('now')), datetime('now'))";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":sid", $session_id );
    $query->bindValue ( ":data", $session_data );
    return $query->execute ();
  }
  /**
   * Number
   *
   * @return number
   */
  public function number() {
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
  /**
   * Reset
   */
  public function reset() {
    $sql = "DROP TABLE IF EXISTS \"sessions\";";
    $query = $this->database->prepare ( $sql );
    $query->execute ();
    unset ( $query );
    $this->init ();
  }
}

?>