<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Broker</title>
    {if $_authentication->access()}<script src="{$_SITE_LOCATION}vendor/components/jquery/jquery.min.js"></script>{/if}
    {if $_authentication->access()}<script src="{$_SITE_LOCATION}{$_LAYOUT_DIR}/javascript/search.js"></script>{/if}
    <link rel="stylesheet" media="all" href="{$_SITE_LOCATION}{$_LAYOUT_DIR}/style.css" />    
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  </head>
  <body>
    <div id="header">
      <div class="title">        
        <div class="logo">Broker</div>
      </div>
      {if $_authentication->access()}
        {include file="menu.tpl"}
      {/if}  
    </div>      
    <div id="content">
      {if isset($_smartyIncludeBlock) && $_smartyIncludeBlock}
        {include file="$_smartyIncludeBlock"}
      {/if}
    </div>       
  </body>
</html>