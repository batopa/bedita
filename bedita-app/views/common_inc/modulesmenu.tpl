<div class="modulesmenucaption"> 
	go to: &nbsp;<a>be</a>
</div>

<ul class="modulesmenu">

{foreach from=$moduleList key=k item=mod}

	{assign_concat var='link' 0=$html->url('/') 1=$mod.path}
	<li href="{$link}" title="{t}{$mod.label}{/t}" class="{$mod.path} {if (stripos($html->here, $mod.path) !== false)} on{/if}"></li>
   
{/foreach}

    <li href="{$html->url('/')}" title="{t}Bedita3 main dashboard{/t}" class="bedita"></li>

</ul>