<?php 

namespace BrokerExpansion;

class IdentityExpansion implements \Broker\Expansion {
  
  private $value;
  private $configuration;
    
  public function __construct($value, $configuration) {
    $this->value = $value;
    $this->configuration = $configuration;
  }
  
  public static function cached() : bool {
    return false;
  }
  
  public static function description() : string {
    return "expansion returning only the provided value for test purposes";
  }

  public static function parameters() : array {
    return array();
  }
  
  public function getValues(): array {
    return array($this->value);      
  }
  
  public function getErrors() {
    return null;
  }
}

?>