{*
** books form template
*}
	
	{include file="./inc/form_book_detail.tpl"}
	
	{include file="./inc/form_properties.tpl" comments=true}	
	
	{include file="../common_inc/form_tree.tpl"}
	
	{include file="../common_inc/form_file_list.tpl" containerId='multimediaContainer' collection="true" relation='attach' title='Multimedia'}

	{include file="../common_inc/form_tags.tpl"}
	
	{include file="../common_inc/form_translations.tpl"}
	
	{include file="../common_inc/form_assoc_objects.tpl" object_type_id=$conf->objectTypes.book.id}

	{include file="./inc/form_advanced_properties.tpl" el=$object}




