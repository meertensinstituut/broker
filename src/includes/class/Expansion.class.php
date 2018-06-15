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
   * @param object $expansionConfiguration          
   * @param object $brokerConfiguration          
   * @param object $solrConfiguration          
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