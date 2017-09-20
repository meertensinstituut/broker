<?php
/**
 * Example configuration
 * @package Broker
 */
$authentication = array (
    // access based on ip 
    "ip" => array (
        array(
          "ip" => "127.0.0.1/24",
           "name" => "Access from localhost",
        ),
    ),
    // access based on login/password
    "login" => array (
        array (
            "name" => "Matthijs Brouwer",
            "login" => "matthijs",
            "password" => null, // crypt("testpassword", "\$6\$rounds=5000\$saltstring\$");
            "admin" => true,
        ), 
    ),
    // access based on key
    "key" => array (
        array(
          "name" => "Test Service",
          "key" => null,
        ),
    )  
);

$solr = array ( 
    "config1" => array (
        "url" => "http://localhost:8983/solr/core1/",
        "shards" => array ( 
            "http://localhost:8983/solr/core2/",
            "http://localhost:8983/solr/core3/",
            "http://localhost:8983/solr/core4/" 
        ),
        "exampleFieldText"=> "title",  
        "exampleFieldInteger"=> "year",
        "exampleFieldString"=> "genre",
        "exampleMtasPrefixWord"=>"t_lc",
        "exampleMtasPrefixLemma"=>"lemma",
        "exampleMtasPrefixPos"=>"pos"
    ),         
);
?>