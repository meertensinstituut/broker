<?php

/**
 * Broker
 * @package Broker
 */
namespace Broker;

/**
 * Session handler
 */
class Session extends Database implements \SessionHandlerInterface {
  /**
   * Constructor
   *
   * @param string $directory          
   */
  public function __construct($directory) {
    parent::__construct($directory, null, "session");
    @unlink($directory."sessions");
  }
  /**
   * Initialize
   */
  public function init() {
    $sql = "CREATE TABLE IF NOT EXISTS session(
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
    //session_write_close ( );
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
    $sql = "DELETE FROM session WHERE sid = :sid";
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
    $sql = "DELETE FROM session WHERE updated < datetime('now', '-" . intval ( $maxlifetime ) . " seconds')";
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
    $sql = "SELECT data FROM session WHERE sid = :sid";
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
    $sql = "INSERT OR REPLACE INTO session (sid, data, created, updated) 
                                         VALUES (:sid, :data, COALESCE((SELECT created FROM session WHERE sid = :sid), datetime('now')), datetime('now'))";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":sid", $session_id );
    $query->bindValue ( ":data", $session_data );
    return $query->execute ();
  }
}

?>