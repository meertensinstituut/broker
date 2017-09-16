{if $_authentication->accessBasedOnLogin()}
  <div id="message">
    Succesfully logged in as {$_authentication->getName()|escape} 
    from {$_authentication->getIP()|escape}.
  </div>
{else}
  {if isset($smarty.post.login) || isset($smarty.post.password)}
    <div id="message">
      Could not authenticate user based on provided username and password.
    </div>
  {else}
    <div id="login">
      <form action="" method="POST">
        <input type="text" placeholder="Enter username" name="login" autofocus required>
        <input type="password" placeholder="Enter password" name="password" required>
        <button type="submit">Login</button>
      </form>
    </div>
  {/if}
{/if}