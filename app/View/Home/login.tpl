{$this->Html->script("form", false)}
{$this->Html->script("jquery/jquery.form", false)}
{$this->Html->script("jquery/jquery.cmxforms", false)}
{$this->Html->script("jquery/jquery.metadata", false)}
{$this->Html->script("jquery/jquery.validate", false)}

<script type="text/javascript">
<!--
$.validator.setDefaults({ 
	/*submitHandler: function() { alert("submitted!"); },*/
	success: function(label) { label.html("&nbsp;").addClass("checked");}
});
$().ready(function() { 
	$("#loginform").validate();
	$("#userid").focus();
});
//-->
</script>

	
<div class="primacolonna">
	 <div class="modules"><label class="bedita" rel="{$this->Html->url('/')}">{$conf->projectName|default:$conf->userVersion}</label></div>
	 
	 
	<div class="insidecol colophon">	
	
		{$view->element('colophon')}
	
	</div>
	 
</div>


<div class="secondacolonna">

	<div class="modules">
	   <label class="admin">{t}Login{/t}</label>
	</div> 

</div>


<div style="width:180px; margin-left:310px; padding-top:25px;">
<form action="{$this->Html->url('/authentications/login')}" method="post" name="loginForm" id="loginForm" class="cmxform" style="padding-left:5px;">
	<fieldset>
		<input type="hidden" name="data[login][URLOK]" value="{$this->Beurl->here()}" id="loginURLOK" />
		
		<label class="block" id="luserid" for="userid">{t}Username{/t}</label>
		<input class="big" tabindex="1" style="width:103px" type="text" name="data[login][userid]" id="userid" class="{literal}{ required:true}{/literal}" title="{t}Username is required{/t}"/>
		<label class="block" id="lpasswd" for="passwd">{t}Password{/t}</label>
		<input class="big" tabindex="2" style="width:103px; margin-bottom:10px;" type="password" name="data[login][passwd]" id="passwd" class="{literal}{ required:true}{/literal}" title="{t}Password is required{/t}"/>
		
		<input class="bemaincommands" tabindex="2" type="submit" value="{t}Enter{/t}"/>
	</fieldset>
	</form>
</div>

<div class="quartacolonna" style="border-left:1px solid gray; padding:120px 0px 0px 10px; width:420px; left:440px; top:20px;">

	<label class="block"><a href='javascript:void(0)' onClick="$('#pswforget').toggle('fast')">{t}Forgotten username or password?{/t}</a></label>
	<div id="pswforget" style="display:none">
		<form method="post" action="{$this->Html->url('/authentications/recoverPassword')}">
		{t}Write your email here{/t}:&nbsp;
		<br />
		<input class="big" style="width:153px" type="text" name="data[email]"/>
		<input class="bemaincommands" type="submit" value="{t}Send{/t}"/>

		<hr />
		{if isset($conf->projectAdmin)}
		{t}or{/t} <label><a href="mailto:{$conf->projectAdmin}">{t}contact the project admin{/t}</a></label>{/if}
	</div>

</div>


