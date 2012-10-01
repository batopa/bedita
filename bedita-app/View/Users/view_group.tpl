{$this->Html->script("form", false)}
{$this->Html->script("jquery/jquery.form", false)}
{$this->Html->script("jquery/jquery.cmxforms", false)}
{$this->Html->script("jquery/jquery.metadata", false)}

{$view->element('modulesmenu')}

{include file="inc/menuleft.tpl" method="viewGroup"}

<div class="head">
    <h1>{if !empty($group)}{t}Group{/t} "{$group.Group.name}"{else}<i>[{t}New group{/t}]</i>{/if}</h1>
</div>

{include file="inc/menucommands.tpl" method="viewGroup" fixed=true}

<div class="main">

	{include file="inc/form_group.tpl"}

</div>

{$view->element('menuright')}