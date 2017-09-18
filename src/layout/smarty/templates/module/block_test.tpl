{if $_testType eq "examples"}
  <div class="title">Test examples</div>
  <div class="test" 
    data-examplesurl="{$_configuration->url ("javascript","examples")|escape:javascript}"
    data-searchurl="{$_configuration->url ("search",null)|escape:javascript}"></div>
{else}
  <div class="title">Test</div>
  <div class="info">
    All examples that from the <a href="{$_configuration->url ("search",null)|escape:javascript}">search</a> section can be used 
    to test parsing and processing.<br />  
    <br />
    <button onclick="location.href='{$_configuration->url ("test","examples")|escape:javascript}';">Test examples</button>
  </div>
{/if}      