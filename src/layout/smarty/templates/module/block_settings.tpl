<div class="title">Settings</div>


<div class="info">
  <b>Configuration</b><br />
  Last update configuration: {$_configurationDate|date_format:"Y-m-d H:i:s"|escape:html}<br />
</div>  

<div class="info">
  <b>Databases and caches</b><br />
  Content databases and caches<br />
  <table>
    <tr class="title">
      <td width="20%">Cache</td>      
      <td width="20%">Status</td>
      <td width="20%">Collection</td>
      <td width="20%">ExpansionCache</td>    
      <td width="20%">Session</td>              
    </tr> 
    <tr>
      <td>{$_cacheNumber|intval}x</td>
      <td>{$_statusNumber|intval}x</td>
      <td>{$_collectionNumber|intval}x</td>
      <td>{$_expansionCacheNumber|intval}x</td>
      <td>{$_sessionNumber|intval}x</td>
    </tr>
  </table>  
  <br />
  Last update automaticly parsing Solr schema: {$_solrDate|date_format:"Y-m-d H:i:s"|escape:html}<br />
  {if $_authentication->accessWithAdminPrivileges()}   
  <br />
  Click for more info about <a href="{$_configuration->url("status",null)|escape:javascript}">status</a>,
  <a href="{$_configuration->url("collections",null)|escape:javascript}">collections</a>,
  <a href="{$_configuration->url("cache",null)|escape:javascript}">cache</a> or
  <a href="{$_configuration->url("expansion",null)|escape:javascript}">expansionCache</a>.<br />
  <br />
  To reset all databases and caches:<br />
  <br />
  <form action="" method="post">
    <button type="submit" name="reset">Reset all</button>
  </form>
  {/if}
</div>


<div class="info">

  {if $_configuration->config && $_configuration->config["solr"]} 
    <div class="info">      
      <b>Configurations</b><br />
      List of available configurations<br />              
      <table>
        <tr class="title">
          <td width="25%">Configuration</td>
          <td width="25%">Main</td>
          <td width="25%">Shards</td>
          <td width="25%">Fields</td>
        </tr>  
        {foreach $_configuration->config["solr"] AS $configuration=>$settings}
          <tr>
            <td>{$configuration|escape:html}</td>   
            <td>{if isset($settings.url) && is_string($settings.url)}{$settings.url|escape:html}{else}---{/if}</td>
            <td>{if isset($settings.shards) && is_array($settings.shards)}{foreach $settings.shards as $shard}{if is_string($shard)}{$shard|escape:html}{else}?{/if}<br/>{/foreach}{else}---{/if}</td>
            <td>
              exampleFieldText: {if isset($settings.exampleFieldText) && is_string($settings.exampleFieldText)}{$settings.exampleFieldText|escape:html}{else}---{/if}<br />
              exampleFieldInteger: {if isset($settings.exampleFieldInteger) && is_string($settings.exampleFieldInteger)}{$settings.exampleFieldInteger|escape:html}{else}---{/if}<br />
              exampleFieldString: {if isset($settings.exampleFieldString) && is_string($settings.exampleFieldString)}{$settings.exampleFieldString|escape:html}{else}---{/if}<br />
              exampleFieldMtas: {if isset($settings.exampleFieldMtas) && is_string($settings.exampleFieldMtas)}{$settings.exampleFieldMtas|escape:html}{else}---{/if}<br />
              exampleMtasPrefixWord: {if isset($settings.exampleMtasPrefixWord) && is_string($settings.exampleMtasPrefixWord)}{$settings.exampleMtasPrefixWord|escape:html}{else}---{/if}<br />
              exampleMtasPrefixLemma: {if isset($settings.exampleMtasPrefixLemma) && is_string($settings.exampleMtasPrefixLemma)}{$settings.exampleMtasPrefixLemma|escape:html}{else}---{/if}<br />
              exampleMtasPrefixPos: {if isset($settings.exampleMtasPrefixPos) && is_string($settings.exampleMtasPrefixPos)}{$settings.exampleMtasPrefixPos|escape:html}{else}---{/if}<br />
            </td>          
          </tr>
        {/foreach}
      </table>               
    </div>
    <br />     
  {/if}

  {if $_authentication->accessWithAdminPrivileges()}     
    {if $_configuration->config && $_configuration->config["authentication"]} 
      <div class="info">      
        <b>Users</b><br />
        List of users with access<br />        
        {if $_configuration->config["authentication"]["login"]}
          <table>
            <tr class="title">
              <td width="25%">Name</td>
              <td width="25%">Login</td>
              <td width="25%">Password</td>
              <td width="25%">Admin</td>
            </tr>  
          {foreach $_configuration->config["authentication"]["login"] AS $login}
            <tr>
              <td>{if isset($login.name) && is_string($login.name)}{$login.name|escape:html}{else}---{/if}</td>
              <td>{if isset($login.login) && is_string($login.login)}{$login.login|escape:html}{else}---{/if}</td>
              <td>{if isset($login.password) && is_string($login.password) && $login.password}yes{else}---{/if}</td>
              <td>{if isset($login.admin) && $login.admin}yes{else}no{/if}</td>
            </tr>
          {/foreach}
          </table>
        {else}
          <em>none found</em>  
        {/if}         
      </div> 
    
      <br />
    
      <div class="block">
        <b>IP-ranges</b><br />
        List of IP-ranges with access<br />        
        {if $_configuration->config["authentication"]["ip"]}
          <table>
            <tr class="title">
              <td width="50%">Name</td>
              <td width="50%">IP-range</td>
            </tr>  
          {foreach $_configuration->config["authentication"]["ip"] AS $ip}
            <tr>
              <td>{if isset($ip.name) && is_string($ip.name)}{$ip.name|escape:html}{else}---{/if}</td>
              <td>{if isset($ip.ip) && is_string($ip.ip)}{$ip.ip|escape:html}{else}---{/if}</td>
            </tr>
          {/foreach}
          </table>
        {else}
          <em>none found</em>  
        {/if}           
      </div>
    
      <br />
    
      <div class="info">
        <b>Key</b><br />
        List of keys with access<br />        
        {if $_configuration->config["authentication"]["key"]}
          <table>
            <tr class="title">
              <td width="50%">Name</td>
              <td width="50%">Key</td>
            </tr>  
          {foreach $_configuration->config["authentication"]["key"] AS $key}
            <tr>
              <td>{if isset($key.name) && is_string($key.name)}{$key.name|escape:html}{else}---{/if}</td>
              <td>{if isset($key.key) && is_string($key.key)}{$key.key|escape:html}{else}---{/if}</td>
            </tr>
          {/foreach}
          </table>
        {else}
          <em>none found</em>  
        {/if}           
      </div>      
    {/if}
    <br />                        
  {/if}
     
    
</div>

  
