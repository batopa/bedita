{*
Template incluso.
Menu a SX valido per tutte le pagine del controller.
*}
{if !empty($object_master)}
{assign_concat var=back_url 1="/" 2=$object_master.ObjectType.module_name 3="/view/" 4=$object_master.id}
<script type="text/javascript">

$(document).ready(function(){
	$("#delLangText").submitConfirm({
		
		action: "{if !empty($delparam)}{$this->Html->url($delparam)}{else}{$this->Html->url('delete/')}{/if}",
		message: "{t}Are you sure that you want to delete the item?{/t}",
		formId: "updateForm"
		
	});

	
	var urlBack = '{$this->Html->url("$back_url")}';
	$("#backBEObject").click(function() {
		document.location = urlBack;
	});
});
</script>

{/if}

<div class="secondacolonna {if !empty($fixed)}fixed{/if}">
	<div class="modules">
		<label class="{$moduleName}" rel="{$this->Html->url('/translations')}">{t}{$currentModule.label}{/t}</label>
	</div>
	
	{assign var="user" value=$this->Session->read('BEAuthUser')}

	{if !empty($view->action) && $view->action != "index"} 
	<div class="insidecol">
		{if $module_modify eq '1'}		
			<input class="bemaincommands" type="button" value=" {t}Save{/t} " name="save" />
			<input class="bemaincommands" type="button" value="{t}Delete{/t}" name="delete" id="delLangText" {if !($object_translation.id|default:false)}disabled="1"{/if} />
		{/if}
		<input class="bemaincommands" type="button" value="{t}Back to {$object_master.ObjectType.name}{/t}" name="back" id="backBEObject"/>
	</div>
	{/if}

</div>
