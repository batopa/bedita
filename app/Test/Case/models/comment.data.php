<?php
/**
 * 
 *
 * @version			$Revision$
 * @modifiedby 		$LastChangedBy$
 * @lastmodified	$LastChangedDate$
 * 
 * $Id$
 */
class CommentTestData extends BeditaTestData {
	var $data =  array(
			
		'document' => array(
			'title' => "document to comment",
			'description' => "bla bla bla bla",
		),
	
		'comment' => array(
			'description' => "what a fraking document.....",
			'author' => "Admiral Adama",
			'email' => "wadama@bsg.com",
			'url' => "www.fightthecylons.com",
		),

		'editor_note' => array(
			'title' => "the final five",
			'description' => "you are one of them!",
		),
		
	);
}
?>