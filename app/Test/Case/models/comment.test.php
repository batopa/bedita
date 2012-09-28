<?php
/*-----8<--------------------------------------------------------------------
 * 
 * BEdita - a semantic content management framework
 * 
 * Copyright 2009 ChannelWeb Srl, Chialab Srl
 * 
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published 
 * by the Free Software Foundation, either version 3 of the License, or 
 * (at your option) any later version.
 * BEdita is distributed WITHOUT ANY WARRANTY; without even the implied 
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Lesser General Public License for more details.
 * You should have received a copy of the GNU Lesser General Public License 
 * version 3 along with BEdita (see LICENSE.LGPL).
 * If not, see <http://gnu.org/licenses/lgpl-3.0.html>.
 * 
 *------------------------------------------------------------------->8-----
 */

/**
 * 
 *
 * @version			$Revision$
 * @modifiedby 		$LastChangedBy$
 * @lastmodified	$LastChangedDate$
 * 
 * $Id$
 */
require_once ROOT . DS . APP_DIR. DS. 'tests'. DS . 'bedita_base.test.php';

class CommentTestCase extends BeditaTestCase {
	
	var $uses = array("Comment", "Document", "EditorNote");
	
	function testCommentAndNote() {
		$this->requiredData(array("document", "comment", "editor_note"));
		$result = $this->Document->save($this->data['document']) ;
		$this->assertNotEqual($result,false);		
		$idDoc = $this->Document->id;

		$this->data['comment']['object_id'] = $idDoc;
		$result = $this->Comment->save($this->data['comment']) ;
		$this->assertNotEqual($result,false);		
		$idComm = $this->Comment->id;

		$this->data['editor_note']['object_id'] = $idDoc;
		$result = $this->EditorNote->save($this->data['editor_note']) ;
		$this->assertNotEqual($result,false);		
		$idNote = $this->EditorNote->id;
		
		// load doc and check comments
		$this->Document->containLevel("detailed");
		$result = $this->Document->findById($idDoc);
		$this->assertNotEqual($result,false);		
		pr("Document: ");
		pr($result);
		$this->assertEqual(count($result['Annotation']),2);		
		$this->Comment->containLevel("detailed");
		$result = $this->Comment->findById($idComm);
		$this->assertNotEqual($result, false);		
		pr("Comment: ");
		pr($result);
		$this->assertEqual($result['description'], $this->data['comment']['description']);		
		$this->assertEqual($result['object_id'], $idDoc);		
		$this->assertEqual($result['ReferenceObject']['id'], $idDoc);		
		
		$this->EditorNote->containLevel("detailed");
		$result = $this->EditorNote->findById($idNote);
		$this->assertNotEqual($result, false);		
		pr("EditorNote: ");
		pr($result);
		$this->assertEqual($result['description'], $this->data['editor_note']['description']);		
		$this->assertEqual($result['object_id'], $idDoc);		
		$this->assertEqual($result['ReferenceObject']['id'], $idDoc);		
		
		// remove document
		$result = $this->Document->delete($idDoc);
		$this->assertEqual($result, true);		
		// check comment removed
		$result = $this->Comment->findById($idComm);
		$this->assertEqual($result, false);

		$beObject = ClassRegistry::init("BEObject");
		$resObj = $beObject->findById($idComm);
		$this->assertEqual($resObj, false);
					
		// check note removed
		$result = $this->EditorNote->findById($idNote);
		$this->assertEqual($result, false);

		$resObj = $beObject->findById($idNote);
		$this->assertEqual($resObj, false);
	}
	
 	public   function __construct () {
		parent::__construct('Comment', dirname(__FILE__)) ;
	}	
}
?>