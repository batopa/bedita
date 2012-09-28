<?php
/*-----8<--------------------------------------------------------------------
 *
 * BEdita - a semantic content management framework
 *
 * Copyright 2008, 2010 ChannelWeb Srl, Chialab Srl
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
 * Administration: system info, eventlogs, plug/unplug module, addons, utility....
 *
 *
 * @version			$Revision$
 * @modifiedby 		$LastChangedBy$
 * @lastmodified	$LastChangedDate$
 *
 * $Id$
 */
class AdminController extends ModulesController {

	public $uses = array('MailJob','MailLog','MailMessage') ;
	public $components = array('BeSystem','BeMail');
	public $helpers = array('Paginator');
	public $paginate = array(
		'EventLog' => array('limit' => 20, 'page' => 1, 'order'=>array('created'=>'desc')),
		'MailJob' => array('limit' => 10, 'page' => 1, 'order'=>array('created'=>'desc'))
	);
	protected $moduleName = 'admin';

	public function index() {
		$this->request->action = "systemEvents";
		$this->systemEvents();
	}

	public function importData() {
		// TODO
	}

	/**
	 * http request load utility page
	 * ajax request try to execute the utility operation defined in $this->request->params["form"]["operation"]
	 *
	 * @throws BeditaAjaxException
	 */
	public function utility() {
		if ($this->request->params["isAjax"]) {
			if (empty($this->request->params["form"]["operation"])) {
				throw new BeditaAjaxException(__("Error: utility operation undefined"), array("output" => "json"));
			}
			try {
				$data = ClassRegistry::init("Utility")->call($this->request->params["form"]["operation"], array('log' => true));
				// render info/warn message
				if (!empty($data['log'])) {
					$this->set('detail', nl2br($data['log']));
					$data['msgType'] = 'warn';
				} else {
					$data['msgType'] = 'info';
				}
				$this->set('message', $data['message']);
				$this->set('class', $data['msgType']);
				$data['htmlMsg'] = $this->render(null, null, APP . 'View' . DS . 'Elements' . DS . 'message.tpl');
				$this->output = "";
			} catch (BeditaException $ex) {
				$details = $ex->getDetails();
				if (!is_array($details)) {
					$details = array($details);
				}
				$details["output"] = "json";
				throw new BeditaAjaxException($ex->getMessage(), $details);
			}

			$this->view = "View";
			header("Content-Type: application/json");
			$this->set("data", $data);
			$this->eventInfo("utility [". $this->request->params["form"]["operation"] ."] executed");
			$this->render(null, "ajax", APP . 'View' . DS . "/pages/json.ctp");
		}
	}

	/**
	 * list core modules to choose which switch on/off
	 *
	 * @return void
	 */
	public function coreModules() {
		$modules = ClassRegistry::init("Module")->find("all", array(
			"conditions" => array("module_type" => "core"),
			"order" => "priority ASC"
		));
		$modules = Set::classicExtract($modules,'{n}.Module');
		$this->set("moduleList", $modules);
	}

	/**
	 * list all modules and allow to sort them
	 *
	 * @return void
	 */
	public function sortModules() {
		$this->checkWriteModulePermission();
		if (!empty($this->request->data["Modules"])) {
			$Module = ClassRegistry::init("Module");
			$this->Transaction->begin();
			foreach ($this->request->data["Modules"] as $id => $priority) {
				$Module->id = $id;
				if (!$Module->saveField("priority", $priority)) {
					$name = $Module->findField("name", array("id" => $id));
					$details = array_merge($Module->validationErrors, array("id" => $id));
					throw new BeditaException(__("Error sorting module") . " " . $name, $Module->validationErrors);
				}
			}
			$this->Transaction->commit();
			$this->userInfoMessage(__("Modules sorted succesfully"));
		}
		$modules = ClassRegistry::init("Module")->find("all", array(
			"conditions" => array("status" => "on"),
			"order" => "priority ASC"
		));
		$modules = Set::classicExtract($modules,'{n}.Module');
		$this->set("moduleList", $modules);
	}

	public function systemInfo() {
		$this->beditaVersion();
		$this->set('sys', $this->BeSystem->systemInfo());
	}

	public function systemEvents() {
		$this->set('events', $this->paginate('EventLog'));
	}

	public function systemLogs($maxRows = 10) {
		$this->set('backendLogs', $this->BeSystem->backendSystemLogs($maxRows));
		$this->set('frontendLogs', $this->BeSystem->frontendSystemLogs($maxRows));
		$this->set('maxRows',$maxRows);
	}

	public function emptyFile() {
		$this->BeSystem->emptyFile($this->request->data["fileToEmpty"]);
		$this->set('logs', $this->BeSystem->systemLogs(10));
		$this->set('maxRows',10);
	}

	public function emptySystemLog() {
		$logFiles = $this->BeSystem->logFiles();
		foreach($logFiles as $fileName) {
			$this->BeSystem->emptyFile($fileName);
		}
		$this->set('logs', $this->BeSystem->systemLogs(10));
		$this->set('maxRows',10);
	}

	public function deleteMailJob($id) {
		$this->checkWriteModulePermission();
		$this->MailJob->delete($id);
		$this->loadMailData();
		$this->userInfoMessage(__("MailJob deleted") . " -  " . $id);
		$this->eventInfo("mail job $id deleted");
	}

	public function deleteMailLog($id) {
		$this->checkWriteModulePermission();
		$this->MailLog->delete($id);
		$this->loadMailLogData();
		$this->userInfoMessage(__("MailLog deleted") . " -  " . $id);
		$this->eventInfo("mail log $id deleted");
	}

	public function deleteAllMailUnsent() {
		$this->checkWriteModulePermission();
		$this->MailJob->deleteAll("mail_message_id IS NULL");
		$this->loadMailData();
		$this->userInfoMessage(__("MailJob deleted"));
		$this->eventInfo("all mail job deleted");
	}

	public function deleteAllMailLogs() {
		$this->checkWriteModulePermission();
		$this->MailLog->deleteAll("id > 0");
		$this->loadMailLogData();
		$this->userInfoMessage(__("MailLog deleted"));
		$this->eventInfo("all mail log deleted");
	}

	public function emailLogs() {
		$this->loadMailLogData();
	}

	public function emailInfo() {
		$this->loadMailData();
	}

	public function testSmtp($to) {
		$this->checkWriteModulePermission();
		$mailOptions = Configure::read("mailOptions");
		$mailData = array();
		$mailData['sender'] = $mailOptions["sender"];
		$mailData['from'] = $mailOptions["sender"];
		$mailData['to'] = $to;
		$mailData['subject'] = "Test mail BEdita";
		$mailData['body'] = "Test mail BEdita" . "\n\n--\n" . $mailOptions["signature"];
		$this->BeMail->Email->smtpOptions['port'] = $this->request->params['form']['sys']['smtpOptions']['port'];
		$this->BeMail->Email->smtpOptions['timeout'] = $this->request->params['form']['sys']['smtpOptions']['timeout'];
		$this->BeMail->Email->smtpOptions['host'] = $this->request->params['form']['sys']['smtpOptions']['host'];
		$this->BeMail->Email->smtpOptions['username'] = $this->request->params['form']['sys']['smtpOptions']['username'];
		if(!empty($this->request->params['form']['sys']['smtpOptions']['password'])) {
			$this->BeMail->Email->smtpOptions['password'] = $this->request->params['form']['sys']['smtpOptions']['password'];
		}
		$this->BeMail->sendMail($mailData);
		$this->userInfoMessage(__("Test mail sent to ") . $to);
		$this->eventInfo("test mail [". $mailData["title"]."] sent");
	}

	private function loadMailData() {
		$mailJob = ClassRegistry::init("MailJob");
		$this->passedArgs["sort"] = "id";
		$this->passedArgs["direction"] = "desc";
		$this->set('jobs',$this->paginate('MailJob'));
		$this->set('totalJobs',  $mailJob->find("count", array("conditions" => array())));
		$this->set('jobsFailed', $mailJob->find("count", array("conditions" => array("status" => array("failed")))));
		$this->set('jobsSent',   $mailJob->find("count", array("conditions" => array("status" => array("sent")))));
		$this->set('jobsPending',$mailJob->find("count", array("conditions" => array("status" => array("pending")))));
		$this->set('jobsUnsent', $mailJob->find("count", array("conditions" => array("status" => array("unsent")))));
	}

	private function loadMailLogData() {
		$mailLog = ClassRegistry::init("MailLog");
		$this->passedArgs["sort"] = "id";
		$this->passedArgs["direction"] = "desc";
		$this->set('logs',$this->paginate('MailLog'));
	}

	private function beditaVersion() {
		$c = Configure::getInstance();
		if (!isset($c->Bedita['version'])) {
			$versionFile = APP . 'config' . DS . 'bedita.version.php';
			if(file_exists($versionFile)) {
				require($versionFile);
			} else {
				$config['Bedita.version'] = "--";
			}
			$c->write('Bedita.version', $config['Bedita.version']);
		}
	}

	public function deleteEventLog() {
		$this->checkWriteModulePermission();
		$this->beditaVersion();
		$this->EventLog->deleteAll("id > 0");
		$this->set('events', $this->paginate('EventLog'));
		$this->set('sys', $this->BeSystem->systemInfo());
	}

	public function customproperties() {
		$properties = ClassRegistry::init("Property")->find("all", array(
			"contain" => "PropertyOption"
		));
		$this->set("properties", $properties);
	}

	public function saveCustomProperties() {
		$this->checkWriteModulePermission();
		if (empty($this->request->data["Property"]))
	 		throw new BeditaException(__("Empty data"));

	 	$propertyModel = ClassRegistry::init("Property");

	 	$objTypeId = $this->request->data["Property"]["object_type_id"];
	 	if(empty($objTypeId)){
	 		$objTypeId = null;
	 	}

	 	$conditions = array(
 					"name" => $this->request->data["Property"]["name"],
	 				"object_type_id" => $this->request->data["Property"]["object_type_id"]
 				);

 		if (!empty($this->request->data["Property"]["id"])) {
 			$conditions[] = "id <> '" . $this->request->data["Property"]["id"] . "'";
		}

	 	$countProperties = $propertyModel->find("count", array(
 				"conditions" => $conditions
 		));

 		if ($countProperties > 0) {
 			throw new BeditaException(__("Duplicate property name for the same type"));
		}

	 	if (empty($this->request->data["Property"]["multiple_choice"]) || $this->request->data["Property"]["property_type"] != "options") {
	 		$this->request->data["Property"]["multiple_choice"] = 0;
		}

	 	$this->Transaction->begin();
	 	if (!$propertyModel->save($this->request->data)) {
	 		throw new BeditaException(__("Error saving custom property"), $propertyModel->validationErrors);
	 	}

	 	// save options
	 	$propertyModel->PropertyOption->deleteAll("property_id='" . $propertyModel->id . "'");
	 	if ($this->request->data["Property"]["property_type"] == "options") {
	 		if (empty($this->request->data["options"])) {
	 			throw new BeditaException(__("Missing options"));
			}

	 		$optionArr = explode(",", trim($this->request->data["options"],","));
	 		foreach ($optionArr as $opt) {
	 			$propOpt[] = array("property_id" => $propertyModel->id, "property_option" => trim($opt));
	 		}
	 		if (!$propertyModel->PropertyOption->saveAll($propOpt)) {
	 			throw new BeditaException(__("Error saving options"));
	 		}
	 	}

	 	$this->Transaction->commit();

	 	$this->eventInfo("property ".$this->request->data['Property']['name']." saved");
		$this->userInfoMessage(__("Custom property saved"));
	}

	function deleteCustomProperties() {
	 	$this->checkWriteModulePermission();
	 	if (!empty($this->request->data["Property"]["id"])) {
	 		if (!ClassRegistry::init("Property")->delete($this->request->data["Property"]["id"])) {
	 			throw new BeditaException(__("Error deleting custom property " . $this->request->data["Property"]["name"]));
	 		}
	 	}
	 }

	/**
	 * list all plugged/unplugged plugin modules
	 * @return void
	 */
	public function pluginModules() {
	 	$moduleModel = ClassRegistry::init("Module");
		$pluginModules = $moduleModel->getPluginModules();
		$this->set("pluginModules", $pluginModules);
		$this->set("pluginDir", BEDITA_MODULES_PATH);
	}

	/**
	 * plug in a module
	 *
	 * @return void
	 */
	public function plugModule() {
		$this->checkWriteModulePermission();
		$moduleModel = ClassRegistry::init("Module");
	 	$pluginName = $this->request->params["form"]["pluginName"];
		$filename = BEDITA_MODULES_PATH . DS . $pluginName . DS . "config" . DS . "bedita_module_setup.php";
		if (!file_exists($filename)) {
			throw new BeditaException(__("Something seems wrong. bedita_module_setup.php didn't found"));
		}
		include($filename);
		$this->Transaction->begin();
	 	$moduleModel->plugModule($pluginName, $moduleSetup);
	 	$this->Transaction->commit();
	 	$this->eventInfo("module ".$pluginName." plugged succesfully");
		$this->userInfoMessage($pluginName . " " . __("plugged succesfully"));
	}

	/**
	 * switch off => on and back a plugin module
	 * @return void
	 */
	public function toggleModule() {
		$this->checkWriteModulePermission();
		if (empty($this->request->data)) {
			throw new BeditaException(__("Missing data"));
		}
		$moduleModel = ClassRegistry::init("Module");
		$this->Transaction->begin();
		if (!$moduleModel->save($this->request->data)) {
			throw new BeditaException(__("Error saving module data"));
		}
		$this->Transaction->commit();
		BeLib::getObject("BeConfigure")->cacheConfig();
		$this->eventInfo("module ".$this->request->params["form"]["pluginName"]." turned " . $this->request->data["status"]);
		$msg = ($this->request->data["status"] == "on")? __("turned on") : __("turned off");;
		$this->userInfoMessage($this->request->params["form"]["pluginName"]." " .$msg);
	}

	/**
	 * plug out a module
	 * @return void
	 */
	public function unplugModule() {
		$this->checkWriteModulePermission();
		if (empty($this->request->data["id"])) {
			throw new BeditaException(__("Missing data"));
		}
		$moduleModel = ClassRegistry::init("Module");
		$pluginName = $this->request->params["form"]["pluginName"];
		$filename = BEDITA_MODULES_PATH . DS . $pluginName . DS . "config" . DS . "bedita_module_setup.php";
		if (!file_exists($filename)) {
			throw new BeditaException(__("Something seems wrong. bedita_module_setup.php didn't found"));
		}
		include($filename);
		$this->Transaction->begin();
	 	$moduleModel->unplugModule($this->request->data["id"], $moduleSetup);
	 	$this->Transaction->commit();
	 	$this->eventInfo("module ".$this->request->params["form"]["pluginName"]." unplugged succesfully");
		$this->userInfoMessage($this->request->params["form"]["pluginName"] . " " . __("unplugged succesfully"));
	}

	/**
	 * list all available addons
	 * @return void
	 */
	public function addons() {
		$this->set("addons", ClassRegistry::init("Addon")->getAddons());
	}

	/**
	 * Enable addon copying the addon file in the related enabled folder.
	 * If addon is a BEdita object type create also a row on object_types table
	 */
	public function enableAddon() {
	 	if (empty($this->request->params["form"])) {
	 		throw new BeditaException(__("Missing form data"));
	 	}
	 	$filePath = $this->request->params["form"]["path"] . DS . $this->request->params["form"]["file"];
		$enabledPath = $this->request->params["form"]["path"] . DS . "enabled" .  DS . $this->request->params["form"]["file"];
	 	$beLib = BeLib::getInstance();
	 	if ($beLib->isFileNameUsed($this->request->params["form"]["file"], $this->request->params["form"]["type"], array($this->request->params["form"]["path"] . DS))) {
	 		throw new BeditaException(__($this->request->params["form"]["file"] . " model is already present in the system. Can't create a new object type"));
	 	}

		if (!BeLib::getObject("BeSystem")->checkWritable($this->request->params["form"]["path"] . DS . "enabled")) {
			throw new BeditaException(__("enabled folder isn't writable"), $this->request->params["form"]["path"] . DS . "enabled");
		}

		$this->Transaction->begin();
		ClassRegistry::init("Addon")->enable($this->request->params["form"]["file"],  $this->request->params["form"]["type"]);
		$this->Transaction->commit();

		$msg = $this->request->params["form"]["name"] . " " . __("addon plugged succesfully");
		$this->userInfoMessage($msg);
		$this->eventInfo($msg);
	}

	/**
	 * Disable addon deleting the addon file from the related enabled folder.
	 * If addon is a BEdita object type remove also the row on object_types table
	 */
	public function disableAddon() {
	 	if (empty($this->request->params["form"])) {
	 		throw new BeditaException(__("Missing form data"));
	 	}

		if (!BeLib::getObject("BeSystem")->checkWritable($this->request->params["form"]["path"] . DS . "enabled")) {
			throw new BeditaException(__("enabled folder isn't writable"), $this->request->params["form"]["path"] . DS . "enabled");
		}

		$this->Transaction->begin();
		ClassRegistry::init("Addon")->disable($this->request->params["form"]["file"], $this->request->params["form"]["type"]);
		$this->Transaction->commit();

		// BEdita object type
		if (!empty($this->request->params["form"]["objectType"])) {
			$this->userInfoMessage($this->request->params["form"]["name"] . " " . __("disable succesfully, all related objects are been deleted"));
		} else {
			$this->userInfoMessage($this->request->params["form"]["name"] . " " . __("disable succesfully"));
		}

		$this->eventInfo("addon ". $this->request->params["form"]["model"]." disable succesfully");
	}

	public function updateAddon() {
		if (empty($this->request->params["form"])) {
	 		throw new BeditaException(__("Missing form data"));
	 	}

		if (!BeLib::getObject("BeSystem")->checkWritable($this->request->params["form"]["path"] . DS . "enabled")) {
			throw new BeditaException(__("enabled folder isn't writable"), $this->request->params["form"]["path"] . DS . "enabled");
		}

		ClassRegistry::init("Addon")->update($this->request->params["form"]["file"], $this->request->params["form"]["type"]);
		$this->userInfoMessage($this->request->params["form"]["name"] . " " . __("updated succesfully"));
		$this->eventInfo("addon ". $this->request->params["form"]["model"]." updated succesfully");
	}

	public function diffAddon() {
		$Addon = ClassRegistry::init("Addon");
		$addonPath = $Addon->getFolderByType($this->request->params["named"]["type"]) . DS . $this->request->params["named"]["filename"];
		$addonEnabledPath = $Addon->getEnabledFolderByType($this->request->params["named"]["type"]) . DS . $this->request->params["named"]["filename"];

		$addon = file_get_contents($addonPath);
		$addonEnabled = file_get_contents($addonEnabledPath);

		App::import("Vendor", "finediff");
		$opcodes = FineDiff::getDiffOpcodes($addonEnabled, $addon, FineDiff::$paragraphGranularity);
		$diff = FineDiff::renderDiffToHTMLFromOpcodes($addonEnabled, $opcodes);
		$this->set("diff", $diff);
	}


	public function viewConfig() {
		include APP . 'Config' . DS . 'langs.iso.php';
		$this->set('langs_iso',$config['langsIso']);

		$besys = BeLib::getObject("BeSystem");

		// check bedita.cfg.php
		$beditaCfgPath = APP . 'Config' . DS . "bedita.cfg.php";
		if (!file_exists($beditaCfgPath)){
			$this->set("bedita_cfg_err", __("Path not found") . ": " . $beditaCfgPath);
		}
		if (!$besys->checkWritable($beditaCfgPath)) {
			$this->set("bedita_cfg_err", __("File not writable, update properly file permits for") . " " . $beditaCfgPath);
		}

		$conf = Configure::getInstance();
		$mediaRoot = $conf->mediaRoot;
		if(empty($mediaRoot)) {
			$this->set("media_root_err",__("media root not set"));
		}
		if (!$besys->checkAppDirPresence($mediaRoot)) {
			$this->set("media_root_err",__("media root folder not found"));
		} else if (!$besys->checkWritable($mediaRoot)) {
			$this->set("media_root_err",__("media root folder is not writable: update folder permits properly"));
		}

		$mediaUrl = $conf->mediaUrl;
		if(empty($mediaUrl)) {
			$this->set("media_url_err",__("media url not set"));
		}
		$headerResponse = @get_headers($mediaUrl);
		if(empty($headerResponse) || !$headerResponse) {
			$this->set("media_url_err",__("media url is unreachable"));
		} else if (stristr($headerResponse[0],'HTTP/1.1 4') || stristr($headerResponse[0],'HTTP/1.1 5')) {
			$this->set("media_url_err",__("media url is unreachable"));
		}

		$beditaUrl = $conf->beditaUrl;
		if(empty($beditaUrl)) {
			$this->set("bedita_url_err",__("bedita url not set"));
		}
		$headerResponse = @get_headers($beditaUrl);
		if(empty($headerResponse) || !$headerResponse) {
			$this->set("bedita_url_err",__("bedita url is unreachable"));
		} else if (stristr($headerResponse[0],'HTTP/1.1 4') || stristr($headerResponse[0],'HTTP/1.1 5')) {
			$this->set("bedita_url_err",__("bedita url is unreachable"));
		}

		// .po
		$poLangs = array();
		$localePath = APP."locale".DS;
		$folder = new Folder($localePath);
		$ls = $folder->read();
		foreach ($ls[0] as $loc) {
			if($loc[0] != '.') { // only "regular" dirs...
				$poLangs[] = $loc;
			}
		}
		$this->set("po_langs",$poLangs);
	}

	public function saveConfig() {
		// sys and cfg array
		$sys = $this->request->params["form"]["sys"];

		$warnMsg = array();
		if (empty($sys["mediaRoot"])) {
			$warnMsg[] = __("media root can't be empty");
		}
		if (empty($sys["mediaUrl"])) {
			$warnMsg[] = __("media url can't be empty");
		}

		$sys["mediaRoot"] = rtrim($sys["mediaRoot"], DS);
		$sys["mediaUrl"] = rtrim($sys["mediaUrl"], "/");

		$besys = BeLib::getObject("BeSystem");
		if (!$besys->checkAppDirPresence($sys["mediaRoot"])) {
			$warnMsg[] = __("media root folder doesn't exist") . " - " . $sys["mediaRoot"];
		}

		if (!$besys->checkWritable($sys["mediaRoot"])) {
			$warnMsg[] = __("media root folder is not writable") . " - " . $sys["mediaRoot"];
		}

		$headerResponse = @get_headers($sys["mediaUrl"]);
		if(empty($headerResponse) || !$headerResponse) {
			$warnMsg[] = __("media url is unreachable") . " - " . $sys["mediaUrl"];
		}

		if (stristr($headerResponse[0],'HTTP/1.1 4') || stristr($headerResponse[0],'HTTP/1.1 5')) {
			$warnMsg[] = __("media url is unreachable") . ": " . $headerResponse[0] . " - " . $sys["mediaUrl"];
		}

		$headerResponse = @get_headers($sys["beditaUrl"]);
		if(empty($headerResponse) || !$headerResponse) {
			$warnMsg[] = __("bedita url is unreachable") . " - " . $sys["beditaUrl"];
		}

		if (stristr($headerResponse[0],'HTTP/1.1 4') || stristr($headerResponse[0],'HTTP/1.1 5')) {
			$warnMsg[] = __("bedita url is unreachable") . ": " . $headerResponse[0] . " - " . $sys["beditaUrl"];
		}

		// smtpOptions password
		$conf = Configure::getInstance();
		if(!empty($conf->smtpOptions['password']) && !empty($sys['smtpOptions']) && empty($sys['smtpOptions']['password'])) {
			$sys['smtpOptions']['password'] = $conf->smtpOptions['password'];
		}

		// prepare cfg array
		$cfg = array_merge($this->request->params["form"]["cfg"], $sys);

		// from string to boolean - $cfg["langOptionsIso"]
		$cfg["langOptionsIso"] = ($cfg["langOptionsIso"] === "true") ? true : false;

		if($cfg["langOptionsIso"]) {
			$cfg["langOptions"] = $conf->langOptionsDefault;
		}

		// order langs
		if(!empty($cfg["langOptions"])) {
			ksort($cfg["langOptions"]);
		}

		// check if configs already set
		foreach ($cfg as $k => $v) {
			if(!empty($conf->$k) && ($conf->$k === $v)) {
				unset($cfg[$k]);
			}
		}

		// write bedita.cfg.php
		$beditaCfgPath = APP . 'Config' . DS . "bedita.cfg.php";
		$besys->writeConfigFile($beditaCfgPath, $cfg, true);

		foreach ($warnMsg as $w) {
			$this->userWarnMessage($w);
			$this->eventWarn($w);
		}
		if(!empty($warnMsg)) {
			$this->log("Warnings saving configuration, params " . var_export($warnMsg, true));
		} else {
			$this->userInfoMessage(__("Configuration saved"));
		}
	}

	protected function forward($action, $esito) {
			$REDIRECT = array(
				"deleteAllMailUnsent" => 	array(
								"OK"	=> self::VIEW_FWD.'emailInfo',
								"ERROR"	=> self::VIEW_FWD.'emailInfo'
							),
				"deleteAllMailLogs" => 	array(
								"OK"	=> self::VIEW_FWD.'emailLogs',
								"ERROR"	=> self::VIEW_FWD.'emailLogs'
							),
				"deleteMailJob" => 	array(
								"OK"	=> self::VIEW_FWD.'emailInfo',
								"ERROR"	=> self::VIEW_FWD.'emailInfo'
							),
				"deleteMailLog" => 	array(
								"OK"	=> self::VIEW_FWD.'emailLogs',
								"ERROR"	=> self::VIEW_FWD.'emailLogs'
							),
				"emptyFile" => 	array(
								"OK"	=> self::VIEW_FWD.'systemLogs',
								"ERROR"	=> self::VIEW_FWD.'systemLogs'
							),
				"emptySystemLog" => 	array(
								"OK"	=> self::VIEW_FWD.'systemLogs',
								"ERROR"	=> self::VIEW_FWD.'systemLogs'
							),
	 	 		"deleteEventLog" => 	array(
 								"OK"	=> self::VIEW_FWD.'systemEvents',
	 							"ERROR"	=> self::VIEW_FWD.'systemEvents'
	 						),
				"saveCustomProperties" =>	array(
					 			"OK"	=> '/admin/customproperties',
								"ERROR"	=> '/admin/customproperties'
							),
				"deleteCustomProperties" =>	array(
					 			"OK"	=> '/admin/customproperties',
								"ERROR"	=> '/admin/customproperties'
							),
				"plugModule" => array(
								"OK" => "/admin/pluginModules",
								"ERROR" => "/admin/pluginModules",
							),
				"toggleModule" => array(
								"OK" => $this->referer(),
								"ERROR" => $this->referer(),
							),
				"unplugModule" => array(
								"OK" => "/admin/pluginModules",
								"ERROR" => "/admin/pluginModules",
							),
				"enableAddon" => array(
								"OK" => "/admin/addons",
								"ERROR" => "/admin/addons",
							),
				"disableAddon" => array(
								"OK" => "/admin/addons",
								"ERROR" => "/admin/addons",
							),
				"updateAddon" => array(
								"OK" => "/admin/addons",
								"ERROR" => "/admin/addons",
							),
				"saveConfig" => 	array(
	 							"OK"	=> "/admin/viewConfig",
	 							"ERROR"	=> "/admin/viewConfig"
	 						),
	 			"testSmtp" => 	array(
	 							"OK"	=> "/admin/viewConfig",
	 							"ERROR"	=> "/admin/viewConfig"
	 						)
	 			);
	 	if(isset($REDIRECT[$action][$esito])) return $REDIRECT[$action][$esito] ;
	 	return false;
	}

}

?>