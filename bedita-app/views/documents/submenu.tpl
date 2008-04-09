{assign var='method' value=$method|default:'index'}
<div id="menuLeftPage">
	<div class="menuLeft">
		<h1 onClick="window.location='{$html->url('/documents')}'" class="documenti"><a href="{$html->url('/documents')}">{t}Documents{/t}</a></h1>
		<div class="inside">
			<ul class="simpleMenuList" style="margin: 15px 0;">
				<li {if $method eq 'index'}class="on"{/if}> <b>&#8250;</b> {$tr->link('Documents', '/documents')}</li>
				{if $module_modify eq '1'}
				<li {if $method eq 'viewArea'}class="on"{/if}> <b>&#8250;</b> {$tr->link('New Document', '/documents/view')}</li>
				{/if}
			</ul>
			<hr/>
			{include file="../pages/user_module_perms.tpl"}
			<hr/>
		</div>
	</div>
	<br/>
	<div id="handlerChangeAlert"></div>
	<br/>
</div>
