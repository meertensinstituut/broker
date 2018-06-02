<div class="title">Documentation</div>

<div class="info">
  The broker translates JSON into Solr request(s).
  <ul>
    <li>Support for sharding can be set in the configuration</li>
    <li>Multiple configurations can be used</li>
    <li>Mtas queries are supported</li>
    <li>Additional expansion modules can be used (query expansion)</li>
    <li>Support for joins based on Mtas collections</li>
    <li>Caching mechanism</li>
    <li>Authentication based on login/password, ip and/or key</li>
  </ul>  
</div>

<div class="info">
  See the <a href="{$_configuration->url("search",null)}">examples</a>, 
  Solr documention and also <a href="https://textexploration.github.io/mtas/" target="_blank">Mtas documentation</a> for more information.
</div>

<div class="info">
  <b>Basic usage</b><br/>
  Post JSON request to {$_configuration->url("search",null)} and wait for the Solr response.
  The <em>X-Broker-...</em> headers in the response contain some additional information:
  <ul>
    <li>X-Broker-errors: number of errors while the broker parses the JSON request</li> 
    <li>X-Broker-warnings: number of warnings while the broker parses the JSON request</li> 
    <li>X-Broker-shards: number of participating shards</li> 
    <li>X-Broker-configuration: configuration used for request</li>  
  </ul>
  More detailed information is available with the method described below.
</div>

<div class="info">
  <b>Advanced usage</b><br/>
  To get more information about parsing and processing of the JSON request:
  <ul>
    <li>Post JSON request to {$_configuration->url("status","create")} to create the request. The response
      will contain parsing information from the Broker, and also an <em>id</em> and <em>key</em>.</li>
    <li>Post these to {$_configuration->url("status","start")} to initiate the request, and wait for the Solr response.</li>  
    <li>While waiting, a post to {$_configuration->url("status","update")} will return status information
      on this request (to be implemented).</li>
  </ul>    
</div>


<div class="info">
  <b>Access</b><br/>
  Access may be provided for users and services:
  <ul>
    <li>User access can be provided based on IP or by authentication with username/password.</li>
    <li>Access for services can be provided based on IP or by a authentication key <em>X-Broker-key</em> that has to be sent with each request.</li>
  </ul>
  Configuration is set in the file <em>{$_SITE_ROOT_DIR|escape:html}config/config.inc.php</em>.  
</div>

<br />