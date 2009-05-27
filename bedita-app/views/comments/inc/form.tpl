
<div class="tab"><h2>comment#{$object.id} on {$object.ReferenceObject.title}</h2></div>

<fieldset id="details">
<table class="bordered">
		
	<tr>

		<th>{t}Status{/t}:</th>
		<td colspan="3">
			{html_radios name="data[status]" options=$conf->statusOptions 
			selected=$object.status|default:$conf->defaultStatus separator="&nbsp;"}
		</td>

	</tr>
	
	<tr>
	
		<th>{t}Text{/t}:</th>
		<td colspan="3">
		<textarea style="height:120px; width:380px;" name="data[description]">{$object.description|default:''}</textarea>
		</td>
	
	</tr>
	
	<tr>
	
		<th>{t}Author{/t}:</th>
		<td colspan="3">
		<input type="text" name="data[author]" value="{$object.author|default:''}"/>
		</td>
	
	</tr>
	
	<tr>
		<th>{t}created on{/t}:</th>
		<td>{$object.created|date_format:$conf->dateTimePattern}</td>
		<th>{t}last modified on{/t}:</th>
		<td>{$object.modified|date_format:$conf->dateTimePattern}</td>
	</tr>
	
	<tr>
	
		<th>{t}email{/t}:</th>
		<td colspan="3">
		<input type="text" name="data[email]" value="{$object.email|default:''}"/>
		</td>
	
	</tr>
	
	<tr>
	
		<th>{t}web site{/t}:</th>
		<td colspan="3">
		<input type="text" name="data[url]" value="{$object.url|default:''}"/>
		</td>
	
	</tr>

	{if !empty($object.ip_created)}
	<tr>
	
		<th>{t}IP{/t}:</th>
		<td>
		{$object.ip_created}
		<input type="hidden" name="data[ip_to_ban]" value="{$object.ip_created}"/>
		</td>
		<td colspan="2">
			{if !isset($banned)}
				<input type="hidden" name="data[ban_status]" value="ban"/>
				<input type="button" class="delete" id="banIP" title="banIP" value="{t}ban this IP!{/t}"/>
			{else}
				<input type="hidden" name="data[ban_status]" value="accept"/>
				IP banned - <input type="button" class="delete" id="sbanIP" title="banIP" value="{t}accept this IP!{/t}"/>
			{/if}
		</td>		
	
	</tr>
	{/if}
	
</table>
</fieldset>

{include file="../common_inc/form_geotag.tpl"}

<div class="tab"><h2>{t}altro{/t}</h2></div>
<fieldset id="altro">
<ul>
	<li><a href="{$html->url('/comments/index')}/ip_created:{$object.ip_created|default:''}">{t}see comments from this IP{/t}</a></li>
	<li><a href="{$html->url('/comments/index')}/email:{$object.email|default:''}">{t}see comments from this email{/t}</a></li>
	<li><a href="{$html->url('/comments/index')}/comment_object_id:{$object.object_id|default:''}">{t}see comments on this content{/t}</a></li>
</ul>
</fieldset>

