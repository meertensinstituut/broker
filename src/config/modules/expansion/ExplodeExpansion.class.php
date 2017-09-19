<?php

/**
 * Broker expansion
 * @package Broker
 * @subpackage Expansion
 */
namespace BrokerExpansion;

/**
 * ExplodeExpansion
 */
class ExplodeExpansion implements \Broker\Expansion {
  /**
   * Value
   * @var array|string
   */
  private $value;
  /**
   * Split string
   * @var string
   */
  private $split = ",";
  /**
   * Trim
   * @var boolean
   */
  private $trim = true;
  
  /**
   * Explode expansion
   * 
   * @param string|array $value          
   * @param unknown $configuration          
   */
  public function __construct($value, $configuration) {
    $this->value = $value;
    if ($configuration && is_object ( $configuration )) {
      if (isset ( $configuration->parameters ) && is_object ( $configuration->parameters )) {
        if (isset ( $configuration->parameters->split ) && is_string ( $configuration->parameters->split )) {
          $this->split = $configuration->parameters->split;
        }
        if (isset ( $configuration->parameters->trim )) {
          if ($configuration->parameters->trim) {
            $this->trim = true;
          } else {
            $this->trim = false;
          }
        }
      }
    }
  }
  /**
   * {@inheritDoc}
   * @see \Broker\Expansion::cached()
   */
  public static function cached() {
    return false;
  }
  
  /**
   * {@inheritDoc}
   * @see \Broker\Expansion::description()
   */
  public static function description() {
    return "split value";
  }
  /**
   * {@inheritDoc}
   * @see \Broker\Expansion::parameters()
   */
  public static function parameters() {
    return array (
        "split" => "optional, default using \",\"",
        "trim" => "optional, default true" 
    );
  }
  /**
   * {@inheritDoc}
   * @see \Broker\Expansion::getValues()
   */
  public function getValues() {
    $list = explode ( $this->split, $this->value );
    if ($this->trim) {
      $list = array_map ( "trim", $list );
    }
    return $list;
  }
  /**
   * 
   * {@inheritDoc}
   * @see \Broker\Expansion::getErrors()
   */
  public function getErrors() {
    return null;
  }
}

?>