{$this->Html->script("form", false)}
{$this->Html->script("jquery/jquery.form", false)}
{$this->Html->script("jquery/jquery.changealert", false)}

{*$this->Html->script("jquery/jquery.MultiFile.pack", false)*}

<script type="text/javascript">
	
var urlGetObj		= '{$this->Html->url("/streams/get_item_form_by_id")}' ;
var containerItem = "#multimediaItems";

$(document).ready(function() {  
	var optionsForm = {
		beforeSubmit:	resetError,
		success:		showResponse,  // post-submit callback  
		dataType:		'json',        // 'xml', 'script', or 'json' (expected server response type)
		url: "{$this->Html->url('/files/uploadAjax')}"
	};

	$("#uploadForm").click(function() {
		$('#uploadAjaxMedia').ajaxSubmit(optionsForm);
		return false;
	});
});
			
function commitUploadItem(IDs) {

	for(var i=0 ; i < IDs.length ; i++)
	{
		var id = escape(IDs[i]) ;
		var emptyDiv = "<div id='item_" + id + "' class='multimediaitem itemBox gold'><\/div>";
		$(emptyDiv).load(
			urlGetObj, { 'id': id, 'relation':'attach', 'template':'/elements/file_item'}, function (responseText, textStatus, XMLHttpRequest)
			{
				$("#loading").hide();
				$(containerItem).append(this); 
			}
		)
	}	
}

function showResponse(data) {
	if (data.UploadErrorMsg) {
		$("#loading").hide();
		$("#ajaxUploadContainer").append("<label class='error'>"+data.UploadErrorMsg+"<\/label>").addClass("error");
	} else {
		var tmp = new Array() ;
		var countFile = 0; 
		$.each(data, function(entryIndex, entry) {
			tmp[countFile++] = entry['fileId'];
		});

		commitUploadItem(tmp);
	}
	
	$("#ajaxUploadContainer").find("input[type=text]").attr("value", "");
	$("#ajaxUploadContainer").find("input[type=file]").attr("value", "");
	$("#ajaxUploadContainer").find("textarea").attr("value", "");
}

function resetError() {
	$("#ajaxUploadContainer").find("label").remove();
	$("#loading").show();
}

</script>

{$view->element('modulesmenu')}

{include file="inc/menuleft.tpl"}

{include file="inc/menucommands.tpl" fixed=true}

{$view->element('toolbar')}

<div class="mainfull">

	{include file="./inc/list_streams.tpl" streamTitle="multimedia"}
	
	{bedev}
	{*
	{include file="./inc/list_streams_table.tpl" streamTitle="multimedia"}
	 *}
	{/bedev}
	
	<div class="tab"><h2>{t}Add multiple items{/t}</h2></div>
	
	<div class="htabcontent">
		<div style="clear:both; margin:-20px 0px 20px -20px">
		{* form needed for ajax upload *}
		<form id="uploadAjaxMedia" action="#" method="post" enctype="multipart/form-data">
		{$view->element('form_upload_multi')}
		</form>
		</div>		 

		<div id="loading" style="clear:both" class="multimediaitem itemBox small">&nbsp;</div>
		<div id="multimediaItems"></div>
			

	</div>
	
</div>

