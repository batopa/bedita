<script type="text/javascript">
<!--
var urlDelete = "{$html->url('deleteSelected/')}" ;
var message = "{t}Are you sure that you want to delete the item?{/t}" ;
var messageSelected = "{t}Are you sure that you want to delete selected items?{/t}" ;
var URLBase = "{$html->url('index/')}" ;
var urlChangeStatus = "{$html->url('changeStatusObjects/')}";
{literal}
$(document).ready(function(){
	
	$("#deleteSelected").click(function() {
		if(!confirm(message)) 
			return false ;
		$("#formObject").attr("action", urlDelete) ;
		$("#formObject").submit() ;
	});
	
	$("#changestatusSelected").click( function() {
		$("#formObject").attr("action", urlChangeStatus) ;
		$("#formObject").submit() ;
	});
});

{/literal}
//-->
</script>	

<style>
	{literal}
	.vlist {display:none}
	{/literal}
</style>


<form method="post" action="" id="formObject">

	<div id="viewthumb">
	<table class="indexlist">
	{capture name="theader"}
		<tr>
			<th colspan="2" nowrap>
				{* 
				<img class="multimediaitemToolbar viewlist" src="{$html->webroot}img/iconML-list.png" />
				<img class="multimediaitemToolbar viewsmall" src="{$html->webroot}img/iconML-small.png" />
				<img class="multimediaitemToolbar viewthumb" src="{$html->webroot}img/iconML-thumb.png" />
				*}
				 {t}order by{/t}:
			</th>
			<th>{$beToolbar->order('id','id')}</th>
			<th>{$beToolbar->order('title','Title')}</th>
			<th>{$beToolbar->order('name','Name')}</th>
			<th>{$beToolbar->order('mediatype','type')}</th>
			<th>{t}size{/t}</th>
			<th>{$beToolbar->order('status','Status')}</th>
			<th>{$beToolbar->order('modified','modified')}</th>
		</tr>
	{/capture}
		
		{$smarty.capture.theader}

	</table>

	<br style="clear:both" />
	{strip}	
		{foreach from=$objects item="item"}
			<div class="multimediaitem itemBox{if $item.status != "on"} off{/if}">
				
				{include file="../common_inc/file_item.tpl"}
				
				<table border=0 padding="0" spacing="0" style="width:100%">
					<tr>
					<td colspan=2 class="vlist">{$item.id}</td>
					<td colspan=2 class="vlist"><a href="{$html->url('view/')}{$item.id}">{$item.title}</a></td>
					<td colspan=2 class="vlist">{$item.name}</td>
					<td colspan=2 class="vlist">{$item.mediatype}</td>
					<td colspan=2 class="vlist">{math equation="x/y" x=$item.size|default:0 y=1024 format="%d"|default:""} KB</td>
					<td colspan=2 class="vlist">{$item.status}</td>
					<td colspan=2 class="vlist">{$item.created|date_format:'%b %e, %Y'}</td>
					{if (empty($item.fixed))}	
					<td style="text-align:left;">
					<input type="checkbox" style="width:15px" name="objects_selected[]" class="objectCheck" title="{$item.id}" value="{$item.id}" />
					</td>
					{/if}			
					<td style="text-align:right;"><a href="{$html->url('view/')}{$item.id}" class="BEbutton">+</a></td>
				</tr>	
				</table>
				
			</div>
		{/foreach}
	</div>
	
	{/strip}
	<br style="margin:0px; line-height:0px; clear:both" />


{if !empty($objects)}

<div style="border-top: 1px solid gray; padding-top:10px; margin-top:10px; white-space:nowrap">
	
	{t}Go to page{/t}: {$beToolbar->changePageSelect('pagSelectBottom')} 
	&nbsp;&nbsp;&nbsp;
	{t}Dimensions{/t}: {$beToolbar->changeDimSelect('selectTop')} &nbsp;
	&nbsp;&nbsp;&nbsp
	<label for="selectAll"><input type="checkbox" class="selectAll" id="selectAll"/> {t}(un)select all{/t}</label>

	
</div>

<br />

<div class="tab"><h2>{t}Bulk actions on{/t} <span class="selecteditems evidence"></span> {t}selected records{/t}</h2></div>
<div class="htabcontent" style="width:620px">

{t}change status to{/t}: 	<select style="width:75px" id="newStatus" name="newStatus">
								<option value=""> -- </option>
								{html_options options=$conf->statusOptions}
							</select>
			<input id="changestatusSelected" type="button" value=" ok " />
	<hr />

	
	<input id="deleteSelected" type="button" value="X {t}Delete selected items{/t}"/>
	
</div>

{/if}

</form>


