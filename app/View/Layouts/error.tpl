{agent var="agent"}
<!DOCTYPE html>
<html lang="it">
<head>
	<title>BEdita</title>


	{$this->Html->css('bedita.css?v=01')}
	
	<!--[if lte IE 6]>
		{$this->Html->css('IE6fix')}
	<![endif]-->

	{$this->Html->script("jquery/jquery")}
	{$this->Html->script("jquery/jquery.cookie")}
	{$this->Html->script("jquery/jquery.autogrow")}
	{$this->Html->script("jquery/jquery.dimensions")}
	{$this->Html->script("beditaUI")}

	{$this->Beurl->addModuleScripts()}

	{* collect linked scripts around *}
	{$scripts_for_layout}

	{$this->Html->script("jquery/ui/jquery-ui-1.8rc3.custom")}
	{$this->Html->script("jquery/ui/jquery.ui.draggable")}

	
</head>
<body{if !empty($bodyClass)} class="{$bodyClass}"{/if}>

{$view->element('messages')}

{$view->element('modulesmenu')}

<div class="primacolonna">
	<div class="modules"><label class="bedita" rel="{$this->Html->url('/')}">{$conf->projectName|default:$conf->userVersion}</label></div>	
</div>

<div id="messagesDiv" style="margin-top:140px">
	<div class="message error">
		{$content_for_layout}	
	</div>
</div>

	
{*
** Help container
*}

{$view->element('help')}


{*
** Modal container
*}

{$view->element('modal')}


{*
** Page Footer
*}

{if empty($noFooter)}

{$view->element('footer')}

{/if}

{$view->element('sql_dump')}

{* HTML document's end *}
</body>
</html>

