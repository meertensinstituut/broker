<?php 
/**
 * Broker expansion
 * @package Broker
 * @subpackage Expansion
 */
namespace BrokerExpansion;

/**
 * IdentityExpansion
 */
class IdentityExpansion implements \Broker\Expansion {
  
  /**
   * Value
   * @var string|array
   */
  private $value;
  /**
   * Configuration
   * @var unknown
   */
  private $configuration;
  
  /**
   * IdentityExpansion
   * 
   * @param string|array $value
   * @param unknown $expansionConfiguration
   * @param unknown $brokerConfiguration
   * @param unknown $solrConfiguration
   */
  public function __construct($value, $expansionConfiguration, $brokerConfiguration, $solrConfiguration) {
    $this->value = $value;
    $this->configuration = $expansionConfiguration;
  }
  /**
   * {@inheritDoc}
   * @see \Broker\Expansion::cached()
   */
  public static function cached()  {
    return false;
  }
  /**
   * {@inheritDoc}
   * @see \Broker\Expansion::description()
   */
  public static function description() {
    return "expansion returning only the provided value for test purposes";
  }
  /**
   * {@inheritDoc}
   * @see \Broker\Expansion::parameters()
   */
  public static function parameters() {
    return array();
  }
  /**
   * 
   * {@inheritDoc}
   * @see \Broker\Expansion::getValues()
   */
  public function getValues() {
    return array($this->value);      
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