<?php
/*-----8<--------------------------------------------------------------------
 * 
 * BEdita - a semantic content management framework
 * 
 * Copyright 2008 ChannelWeb Srl, Chialab Srl
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
 * Upload component: common file, multimedia file, third party multimedia url (bliptv, vimeo, youtube, etc.)
 *
 * @version			$Revision$
 * @modifiedby 		$LastChangedBy$
 * @lastmodified	$LastChangedDate$
 * 
 * $Id$
 */
class BeUploadToObjComponent extends Component {
	var $components	= array('BeFileHandler', 'BeBlip', 'BeVimeo', 'BeYoutube') ;

	function startup(&$controller) {
		$this->request->params = &$controller->params;
		$this->BeFileHandler->startup($controller) ;
	}

	/**
	 * Uploads a file to location and create stream object.
	 * 
	 * @param array $dataStream
	 * @param string $formFileName
	 * @return mixed int|boolean, object_id if upload was successful, false otherwise.
	 */
	public function upload($dataStream=null, $formFileName="Filedata") {
		$result = false ;
		if (empty($this->request->params["form"][$formFileName]["name"])) {
			throw new BEditaException(__("No file in the form"));
		}
		if ($this->request->params['form'][$formFileName]['error']) {
			throw new BEditaUploadPHPException($this->request->params['form'][$formFileName]['error']);
		}
		// Prepare data
		if (!empty($dataStream)) {
			$data = array_merge($dataStream, $this->request->params['form'][$formFileName]);
		} else {
			$data = $this->request->params['form'][$formFileName];
		}
		$data['original_name'] = $data['name'];
		$tmp = $this->BeFileHandler->splitFilename($data['name']);
		if(!empty($tmp[1])) {
			$data['name'] = BeLib::getInstance()->friendlyUrlString($tmp[0]) . '.' . $tmp[1];
		} else {
			$data['name'] = BeLib::getInstance()->friendlyUrlString($tmp[0]);
		}
		$data['mime_type'] = $this->BeFileHandler->getMimeType($data);
		unset($data['type']);
		$data['file_size'] = $this->request->params['form'][$formFileName]['size'];
		unset($data['size']);
		
		if (!empty($this->request->params['form']['mediatype'])) {
			$data['mediatype'] = $this->request->params['form']['mediatype'];
		}
		
		$forceupload = (isset($this->request->params['form']['forceupload'])) ? ((boolean)$this->request->params['form']['forceupload']) : false ;

		if (empty($data['title']))
			$data['title'] = $data['original_name'];

		$data['uri']	= $data['tmp_name'] ;

		if (empty($data["status"]))
			$data["status"] = "on";

		unset($data['tmp_name']) ;
		unset($data['error']) ;

		$result = $this->BeFileHandler->save($data, $forceupload) ;
		
		return $result;
	}

	/**
	 * Create obj stream from URL. Form must have: url, title, lang.
	 * 
	 * @param string $dataURL
	 * @param boolean $clone
	 * @return mixed boolean|int, false if upload was unsuccessful, int $id otherwise
	 * @throws BEditaMediaProviderException
	 */
	public function uploadFromURL($dataURL, $clone=false) {
		$result = false ;
		$getInfoURL = false;
		$mediaProvider = ClassRegistry::init("Stream")->getMediaProvider($dataURL['url']);
		if(empty($dataURL['title'])) {
			$link = ClassRegistry::init("Link");
			$dataURL['title'] = $link->readHtmlTitle($dataURL['url']);
		}
		if (!empty($mediaProvider)) {
			$dataURL['provider'] = $mediaProvider["provider"];
			$dataURL['video_uid'] = $mediaProvider["video_uid"];
			$dataURL['uri'] = $mediaProvider["uri"];
			$componentName = Inflector::camelize("be_" . $mediaProvider["provider"]);
			if (isset($this->{$componentName}) && method_exists($this->{$componentName}, "setInfoToSave")) {
				if (!$this->{$componentName}->setInfoToSave($dataURL)) {
					throw new BEditaMediaProviderException(__("Multimedia Provider not found or error preparing data to save")) ;
				}
			} else {
				throw new BEditaMediaProviderException(__("Multimedia provider is not managed")) ;
			}
		} else {
			$dataURL['provider'] = null;
			$dataURL['video_uid'] = null;
			$dataURL['uri'] = $dataURL["url"];
			$getInfoURL = true;
		}
		if (empty($dataURL["status"])) {
			$dataURL['status'] = "on";
		}
		if (!empty($this->request->params['form']['mediatype'])) {
			$dataURL['mediatype'] = $this->request->params['form']['mediatype'];
		}
		unset($dataURL["url"]);
		$id = $this->BeFileHandler->save($dataURL, $clone, $getInfoURL) ;
		return $id;
	}

	/**
	 * Clone data for media
	 * 
	 * @param array $data
	 * @return mixed boolean|int, false if cloning was unsuccessful, int $id otherwise
	 */
	public function cloneMediaObject($data) {
		if (!empty($data["id"])) {
			unset($data["id"]);
		}
		if(preg_match(Configure::read("validate_resource.URL"), $data["uri"])) {
			$data["url"] = $data["uri"];
			return $this->uploadFromURL($data, true);
		} else {
			$data['uri'] = Configure::read("mediaRoot") . $data["uri"];
			if (empty($data["file_size"])) {
				$data["file_size"] = filesize($data["uri"]);
			}
			if (!empty($this->request->params['form']['mediatype'])) {
				$data['mediatype'] = $this->request->params['form']['mediatype'];
			}
			return $this->BeFileHandler->save($data, true);
		}
	}

	/**
	 * Get thumbnail for media
	 * 
	 * @param array $data
	 * @return array
	 * @throws BEditaMediaProviderException
	 */
	public function getThumbnail($data) {
		if (!empty($data["thumbnail"]) && preg_match(Configure::read("validate_resource.URL"), $data["thumbnail"])) {
			$thumbnail = $data["thumbnail"]; 	
		} else {
			if (empty($data["provider"]) || empty($data["video_uid"])) {
				$url = (!empty($data['url']))? $data['url'] : $data['uri'];
				$mediaProvider = ClassRegistry::init("Stream")->getMediaProvider($data['url']);
				if (!empty($mediaProvider)) {
					$provider = $mediaProvider["provider"];
					$uid = $mediaProvider["video_uid"];
				}
			} else {
				$provider = $data["provider"];
				$uid = $data["video_uid"];
			}
			$thumbnail = null;
			if (!empty($provider)) {
				$componentName = Inflector::camelize("be_" . $provider);
				if (isset($this->{$componentName}) && method_exists($this->{$componentName}, "getThumbnail")) {
					if (!$thumbnail	= $this->{$componentName}->getThumbnail($uid)) {
						throw new BEditaMediaProviderException(__("Multimedia Provider not found or error getting thumbnail")) ;
					}
				} else {
					throw new BEditaMediaProviderException(__("Multimedia provider is not managed")) ;
				}
			}
		}
		return $thumbnail;
	}
}
?>