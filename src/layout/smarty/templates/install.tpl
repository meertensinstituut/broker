<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Broker</title>
    <script src="{$_SITE_LOCATION}vendor/components/jquery/jquery.min.js"></script>
    <script src="{$_SITE_LOCATION}{$_LAYOUT_DIR}/javascript/install.js"></script>
    <link rel="stylesheet" media="all" href="{$_SITE_LOCATION}{$_LAYOUT_DIR}/style.css" />    
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  </head>
  <body>
    <div id="header">
      <div class="title">        
        <div class="logo">Broker</div>
      </div>       
    </div>      
    <div id="content">
    
      <h1>Installation instructions</h1>
      
      {if isset($smarty.post.solrUrl)}      
      
      <div class="info">
        Create a file <em>config.inc.php</em> in <em>{$_SITE_ROOT_DIR|escape:html}config/</em> of the form
      </div>
      
      <div class="info">
        <button onclick="location.href='{$_SITE_LOCATION|escape:javascript}';">Reset</button>
      </div>
      
      <hr noshade/>
      
      <div class="configuration">
        <pre>
&lt;?php

  // ================
  //  AUTHENTICATION
  // ================
  $authentication = array (
    // =================================================
    //  access based on ip as listed in example:
    // =================================================
    "ip" => array(
      //array(
      //  "name" => "localhost",
      //  "ip" => "127.0.0.1/24",
      //),
      array(
        "name" => "My machine",
        "ip" => "{$smarty.server.REMOTE_ADDR|escape:html}",
      ),  
    ), 
    // =================================================
    //  access based on login as listed in example:
    // =================================================
    "login" => array (
      array (
        "name" => {if isset($smarty.post.adminName) && is_string($smarty.post.adminName)}"{$smarty.post.adminName|replace:"\"":"\\\""|escape:html}"{else}"John Doe"{/if},
        "login" => {if isset($smarty.post.login) && is_string($smarty.post.login)}"{$smarty.post.login|replace:"\"":"\\\""|escape:html}"{else}"john"{/if},
        "password" => {if isset($smarty.post.password) && is_string($smarty.post.password)}"{assign var="random" value=(time()+rand(100000,999999))|md5}{assign var="salt" value="\$6\$rounds=5000\$"|cat:$random|cat:"\$"}{$smarty.post.password|crypt:$salt|replace:"\$":"\\\$"|replace:"\"":"\\\""}"{else}"[encrypted password]"{/if},
        "admin" => true,
      ), 
    ), 
    // =================================================
    //  access based on key as listed in example:
    // =================================================
    "key" => array (
      //array(
      //  "name" => "test key",
      //  "key" => "1234567890",
      //),                  
    ),  
  );
  
  // ================
  //  SOLR
  // ================
  $solr = array (
    // ==========================================
    //  example configuration named 'demoConfig'
    // ==========================================
    "config1" => array (
      // obligatory: url solr core
      "url" => {if isset($smarty.post.solrUrl) && is_string($smarty.post.solrUrl)}"{$smarty.post.solrUrl|replace:"\"":"\\\""|escape:html}"{else}"http://localhost:8983/solr/core1/"{/if},
      //"shards" => array (                 // optional: shards
      //  "http://localhost:8983/solr/demoCore1/",
      //  "http://localhost:8983/solr/demoCore2/",
      //  "http://localhost:8983/solr/demoCore3/", 
      //),
      //"exampleFieldText"=> "title",       // optional: preferred field examples
      //"exampleFieldInteger"=> "year",     // optional: preferred field examples
      //"exampleFieldString"=> "genre",     // optional: preferred field examples                            
      //"exampleFieldMtas" => "mtas",       // optional: preferred field examples
      //"exampleMtasPrefixWord"=> "t_lc",   // optional: preferred prefix examples
      //"exampleMtasPrefixLemma"=> "lemma", // optional: preferred prefix examples
      //"exampleMtasPrefixPos"=> "pos",     // optional: preferred prefix examples
    ), 
    //"config2" => array ( 
    //  "url" => "http://localhost:8983/solr/core1/" // obligatory: url solr core
    //),
  );
  
?&gt;
        </pre>
      </div>
      
      {else}
      
      <div class="info">
        No <em>config.inc.php</em> found in <em>{$_SITE_ROOT_DIR|escape:html}config/</em>
      </div>
      
      <br />
      
      <form action="" method="post">
      
      <b>Solr</b>
      <div class="info">  
        <table>
          <tr>
            <td><input class="text" name="solrUrl" placeholder="url solr" type="url" required="true" /></td>
          </tr>          
        </table>
      </div>
      
      <br />
      
      <b>Administrator</b>
      <div class="info">  
        <table>
          <tr>
            <td><input class="text" name="adminName" placeholder="name" required="true" /></td>
          </tr>
          <tr>
            <td><input class="text" name="login" placeholder="login" required="true" /></td>
          </tr>
          <tr>
            <td><input class="text" name="password" placeholder="password" required="true" /></td>
          </tr>
        </table>
      </div>         
      
      <div class="info">
        <button type="submit">Create 'config.inc.php'</button>
      </div>
      
      </form>
      {/if}
    
    </div>       
  </body>
</html>