<?php

/**
 * Broker
 * @package Broker
 */
namespace Broker;

/**
 * Interface for expansion modules
 */
interface Expansion {
  /**
   * Constructor
   *
   * @param string|array $value          
   * @param unknown $expansionConfiguration          
   * @param unknown $brokerConfiguration          
   * @param unknown $solrConfiguration          
   */
  public function __construct($value, $expansionConfiguration, $brokerConfiguration, $solrConfiguration);
  /**
   * Check cache status
   *
   * @return boolean
   */
  public static function cached();
  /**
   * Description
   *
   * @return string
   */
  public static function description();
  /**
   * Parameters
   *
   * @return array
   */
  public static function parameters();
  /**
   * Get values
   *
   * @return array
   */
  public function getValues();
  /**
   * Get errors
   *
   * @return array
   */
  public function getErrors();
}