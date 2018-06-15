<?php

/**
 * Broker
 * @package Broker
 */
namespace Broker;

/**
 * Database
 */
abstract class Database {
  
  /**
   * Database
   *
   * @var \PDO
   */
  public $database;
  /**
   * Configuration
   *
   * @var object
   */
  public $configuration;
  /**
   * Filename
   *
   * @var string
   */
  public $filename;
  /**
   * Classname
   *
   * @var string
   */
  private $classname;  
  /**
   * Constructor
   *
   * @param string $directory
   * @param object $configuration
   * @param string $classname
   */  
  public function __construct($directory, $configuration, $classname) {
    $this->classname = $classname;
    if (file_exists ( $directory ) && is_file ( $directory )) {
      $this->filename = $directory;
      if (! is_writeable ( $this->filename )) {
        $this->filename = tempnam ( sys_get_temp_dir (), $this->classname );
      }
    } else if (is_dir ( $directory )) {
      $this->filename = $directory . $this->classname;
      if (! is_writable ( $directory ) || (file_exists ( $this->filename ) && ! is_writable ( $this->filename ))) {
        $this->filename = tempnam ( sys_get_temp_dir (), $this->classname );
      }
    }
    $this->configuration = $configuration;
    $this->database = new \PDO ( "sqlite:" . $this->filename );
    $this->database->setAttribute ( \PDO::ATTR_TIMEOUT, 5000 );
    // $this->database->setAttribute ( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
    $this->init ( false );
  }
  
  /**
   * Init 
   */
  abstract function init();
  
  /**
   * Get number
   *
   * @return number
   */
  public function number() {
    $sql = "SELECT COUNT(*) AS number
    FROM \"".$this->classname."\";";
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
    $sql = "DROP TABLE IF EXISTS \"".$this->classname."\";";
    $query = $this->database->prepare ( $sql );
    $query->execute ();
    $this->errorCheck("reset", $query, true);
    unset ( $query );
    $this->init();
  }
  /**
   * Check for errors
   * @param string $source
   * @param \PDOStatement $query
   * @param boolean $removeDatabase
   */
  public function errorCheck($source, $query, $removeDatabase) {
    if($query->errorCode()!="00000") {
      echo "Problem ".$source." ".$this->classname.": ";
      var_dump($query->errorInfo());
      if($removeDatabase) {
        @unlink($this->filename);
        //if unlink somehow didn't work
        @chmod($this->filename, 0777);
      }
      exit();
    }
  }
  
}