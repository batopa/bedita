{$javascript->link("jquery/jquery.selectboxes.pack", false)}

{$html->css('tree')}
{$javascript->link("jquery/jquery.treeview", false)}

{$javascript->link("form", false)}
{$javascript->link("jquery/jquery.changealert", false)}
{$javascript->link("jquery/jquery.form", false)}
{$javascript->link("jquery/jquery.selectboxes.pack", false)}
{$javascript->link("jquery/jquery.cmxforms", false)}
{$javascript->link("jquery/jquery.metadata", false)}


<script language="JavaScript">
	{literal}
	$(document).ready( function ()
	{
		var openAtStart ="#properties";
		$(openAtStart).prev(".tab").BEtabstoggle();	
	});
	{/literal}
</script>


</head>

<body>

{include file="../common_inc/modulesmenu.tpl"}

{include file="inc/menuleft.tpl" method="viewSection"}

<div class="head">
<h1>
	{if (empty($object))}
	
		{t}Create new section{/t}
	
	{else}
	
		{$object.title|default:"no title"}
	
	{/if}
</h1>	
</div> 

{include file="inc/menucommands.tpl" method="viewSection"}

<div class="main">
	<form action="{$html->url('/areas/saveSection')}" method="post" name="updateForm" id="updateForm" class="cmxform">
	
	<div class="tab"><h2>{t}Properties{/t}</h2></div>
		
		{include file="inc/form_section.tpl"  method="viewSection"}
	
	</form>
</div>

{include file="../common_inc/menuright.tpl"}
