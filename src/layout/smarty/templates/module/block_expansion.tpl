<div class="title">Expansion Cache</div>

<div class="info">

  {if $_expansionType eq "list"}
  
    {if $_expansionList|@count gt 0}
      <div class="subtitle">{math equation="x*y+1" x=$_expansionPage y=$_expansionNumber} - {math equation="x*y+z" x=$_expansionPage y=$_expansionNumber z=$_expansionList|@count} from {$_expansionTotal|intval} item(s)</div>
      
      <table>
        <tr class="title">
          <td>&nbsp;</td>
          <td>Module</td>
          <td>Value</td>
          <td>Parameters</td>
          <td>Created</td>
          <td>Used</td>
          <td>Last Used</td>
          <td>Expires</td>
          <td>Actions</td>
        </tr>   
        {foreach $_expansionList as $item}
          <tr>
            <td>{$item@key + ($_expansionPage*$_expansionNumber) + 1}</td>
            <td>{$item.module|escape:html}</td>
            <td>{$item.value|unserialize|var_export:true|escape:html}</td>
            <td>{if $item.parameters}yes{else}no{/if}</td>
            <td>{$item.created|escape:html}</td>
            <td>{$item.used|escape:html}</td>
            <td>{$item.numberOfChecks|escape:html}</td>
            <td>{$item.expires|escape:html}</td>
            <td><form action="{$_configuration->url ("expansion","list")|escape:javascript}" method="post"><input type="hidden" name="key" value="{$item.hash|escape:javascript}"/><button name="action" value="delete">delete</button>&nbsp;<button name="action" value="view">view</button></form></td>
          </tr>
        {/foreach}  
      </table>
      <br />
      {assign var="previousPage" value="`$_expansionPage-1`"}    
      {assign var="nextPage" value="`$_expansionPage+1`"} 
      {if $_expansionPage gt 0}         
        {if $previousPage eq 0}
          {assign var="previousUrl" value=$_configuration->url ("expansion","list")}
        {else}
          {assign var="listPreviousPage" value="list$previousPage"}
          {assign var="previousUrl" value=$_configuration->url ("expansion",{$listPreviousPage})}
        {/if}
        <button onclick="location.href='{$previousUrl|escape:javascript}';">previous {$_expansionNumber|intval}</button>      
      {/if} 
      {if $nextPage*$_expansionNumber lt $_expansionTotal}         
        {assign var="listNextPage" value="list$nextPage"}
        {assign var="nextUrl" value=$_configuration->url ("expansion",{$listNextPage})}
        <button onclick="location.href='{$nextUrl|escape:javascript}';">next {min($_expansionNumber,$_expansionTotal-($nextPage*$_expansionNumber))}</button> 
      {/if}  
    {elseif $_expansionTotal gt 0}
      <div class="subtitle">No items, but {$_expansionTotal|intval} item(s) available</div>
    {else}
      <div class="subtitle">No items available</div>
    {/if}
  {elseif $_expansionType eq "view"}
    <div class="subtitle">Details expansion '{$_expansionData.hash|escape:html}'</div>
    <div>Back to the <a href="{$_configuration->url ("expansion","list")|escape:javascript}">list of items</a></div>
    <br />
    <div>
      <table>
        {foreach $_expansionData as $item}
          <tr>
            <td>{$item@key|escape:html}</td>
            <td>{$item|escape:html}</td>
        {/foreach}
      </table>
    </div>  
  {else}  
    {if $_expansionTotal gt 0}        
      <div>
        <button onclick="location.href='{$_configuration->url ("expansion","list")|escape:javascript}';">view list with {$_expansionTotal|intval} item(s)</button>
      </div>  
      <br />
    {else} 
      <div class="subtitle">0 items</div> 
    {/if}
    <div>
      <form method="post" action="{$_configuration->url ("expansion","list")|escape:javascript}">
        <button name="action" value="reset">reset database</button>
      </form>
    </div>
  {/if}
  <br />
</div>

