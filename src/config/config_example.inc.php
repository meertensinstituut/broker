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
        "exampleFieldTextValues"=>array("jan","piet","kees","els","miep","greet"),
        "exampleFieldInteger"=> "year",
        "exampleFieldIntegerValues"=>null, //autofill
        "exampleFieldString"=> "genre",
        "exampleFieldStringValues"=>null, //autofill
        "exampleFieldMtas"=> "mtas",
        "exampleMtasPrefixWord"=>"t_lc",
        "exampleMtasPrefixWordValues"=>array("koe","paard","schaap","geit","kip","ezel","konijn","cavia","muis","rat"),
        "exampleMtasPrefixLemma"=>"lemma", 
        "exampleMtasPrefixLemmaValues"=>array("boom","struik","gras","plant","bloem","aarde","wortel","blad","hout","mest"),
        "exampleMtasPrefixPos"=>"pos",
        "exampleMtasPrefixPosValues"=>null,
    ),         
);
?>