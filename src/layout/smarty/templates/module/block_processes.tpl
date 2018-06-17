

  {if !$_processesType}  
    {if $_configuration->config && $_configuration->config["solr"]} 
      <div class="title">Processes</div>
      <div class="info">      
        <table>
          <tr class="title">
            <td width="25%">Configuration</td>
            <td width="25%">&nbsp;</td>
            <td width="25%">Main</td>
            <td width="25%">Shards</td>
          </tr>  
          {foreach $_configuration->config["solr"] AS $configuration=>$settings}
            <tr>
              <td>{$configuration|escape:html}</td>   
              <td>
                <button onclick="location.href='{$_configuration->url ("processes","running",$configuration)|escape:javascript}';">Running</button>
                <button onclick="location.href='{$_configuration->url ("processes","history",$configuration)|escape:javascript}';">History</button>
                <button onclick="location.href='{$_configuration->url ("processes","error",$configuration)|escape:javascript}';">Errors</button>
              </td>   
              <td>{if isset($settings.url) && is_string($settings.url)}{$settings.url|escape:html}{else}---{/if}</td>
              <td>{if isset($settings.shards) && is_array($settings.shards)}{foreach $settings.shards as $shard}{if is_string($shard)}{$shard|escape:html}{else}?{/if}<br/>{/foreach}{else}---{/if}</td>                    
            </tr>
          {/foreach}
        </table>               
      </div>
      <br />     
    {/if}
  {else}
    {if $_processesType eq "running" || $_processesType eq "history" || $_processesType eq "error"}
      <div class="title">{if $_processesType eq "running"}Running{elseif $_processesType eq "history"}History{elseif $_processesType eq "error"}Error{/if} processes '{$_processesConfiguration|escape}'</div>
      <div class="info">
        <button {if $_processesType eq "running"}class="selected"{/if} onclick="location.href='{$_configuration->url ("processes","running",$_processesConfiguration)|escape:javascript}';">Running</button>
        <button {if $_processesType eq "history"}class="selected"{/if} onclick="location.href='{$_configuration->url ("processes","history",$_processesConfiguration)|escape:javascript}';">History</button>
        <button {if $_processesType eq "error"}class="selected"{/if} onclick="location.href='{$_configuration->url ("processes","error",$_processesConfiguration)|escape:javascript}';">Errors</button>
        &nbsp;
        <button onclick="location.href='{$_configuration->url ("processes")|escape:javascript}';">All processes</button>
      </div>
      <div class="processes" data-processesurl="{$_configuration->url ("processes","api",$_processesConfiguration)|escape:javascript}" data-type="{$_processesType|escape}" data-configuration="{$_processesConfiguration|escape}"></div>
    {/if}
  {/if}  

</div>
     