<div class="menu">
  <div class="item{if !isset($_smartyIncludeModule) || !$_smartyIncludeModule} selected{/if}" onclick="location.href='{$_configuration->url(null,null)|escape:javascript}';">home</div>
  <div class="item{if isset($_smartyIncludeModule) && $_smartyIncludeModule=="search"} selected{/if}" onclick="location.href='{$_configuration->url("search",null)|escape:javascript}';">search</div>
  {if $_authentication->accessWithAdminPrivileges()}
  <div class="item{if isset($_smartyIncludeModule) && $_smartyIncludeModule=="status"} selected{/if}" onclick="location.href='{$_configuration->url("status",null)|escape:javascript}';">status</div>        
  <div class="item{if isset($_smartyIncludeModule) && $_smartyIncludeModule=="collections"} selected{/if}" onclick="location.href='{$_configuration->url("collections",null)|escape:javascript}';">collections</div>        
  <div class="item{if isset($_smartyIncludeModule) && $_smartyIncludeModule=="expansion"} selected{/if}" onclick="location.href='{$_configuration->url("expansion",null)|escape:javascript}';">expansion</div>        
  <div class="item{if isset($_smartyIncludeModule) && $_smartyIncludeModule=="cache"} selected{/if}" onclick="location.href='{$_configuration->url("cache",null)|escape:javascript}';">cache</div>
  <div class="item{if isset($_smartyIncludeModule) && $_smartyIncludeModule=="test"} selected{/if}" onclick="location.href='{$_configuration->url("test",null)|escape:javascript}';">test</div> 
  {/if}        
  <div class="item{if isset($_smartyIncludeModule) && $_smartyIncludeModule=="documentation"} selected{/if}" onclick="location.href='{$_configuration->url("documentation",null)|escape:javascript}';">documentation</div>        
  <div class="item{if isset($_smartyIncludeModule) && $_smartyIncludeModule=="settings"} selected{/if}" onclick="location.href='{$_configuration->url("settings",null)|escape:javascript}';">settings</div> 
  {if $_authentication->accessBasedOnLogin()}
    <div class="item right" onclick="location.href='{$_configuration->url("logout",null)|escape:javascript}';">{$_authentication->getName()|escape}</div> 
  {/if}       
</div>