<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class ntfy extends eqLogic {

	public function postSave() {
		$cmd = $this->getCmd(null, 'notify');
		if (!is_object($cmd)) {
			$cmd = new ntfyCmd();
			$cmd->setLogicalId('notify');
			$cmd->setIsVisible(1);
			$cmd->setName(__('Envoi Notification', __FILE__));
			$cmd->setType('action');
			$cmd->setSubType('message');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setDisplay('title_placeholder', __('Options', __FILE__));
			$cmd->setDisplay('message_placeholder', __('Message', __FILE__));
			$cmd->save();
		}
	}
}

class ntfyCmd extends cmd {
	public function execute($_options = array()) {
		$request_http = curl_init();
		curl_setopt($request_http, CURLOPT_URL, $this->getEqlogic()->getConfiguration('url'));
		curl_setopt($request_http, CURLOPT_POST, 1);
		curl_setopt($request_http, CURLOPT_RETURNTRANSFER, true);
		$data= array();
		if (isset($_options['title'])) {
			if (strpos($_options['title'], ';') === false) {
				$data[] = trim($_options['title']);
			} else {
				$values = explode(";", $_options['title']);
				foreach ($values as $value) {
					$data[] = trim($value);
					$test = explode(":", $value);
					if (trim($test[0]) == 'Filename') {
						$_options['files'][] = trim($test[1]);
					}
				}
			}
		}
		if(isset($_options['answer'])){
			  $action = 'Actions:';
			  foreach($_options['answer'] as $answer){
			    	$action .= 'http, '.$answer.', '.$this->generateAskResponseLink($answer).', method=POST, clear=true;';
			  }
			  $data[] = $action;
			  $data[] = 'Tags: question';
		}
		if(count($data) != 0){
			curl_setopt($request_http, CURLOPT_HTTPHEADER, $data); 
		}
		if ($this->getEqlogic()->getConfiguration('user','') != '') {
			log::add('ntfy', 'debug', 'Using auth : ' . $this->getEqlogic()->getConfiguration('user') . ':' . $this->getEqlogic()->getConfiguration('password'));
			curl_setopt($request_http, CURLOPT_USERPWD, $this->getEqlogic()->getConfiguration('user') . ':' . $this->getEqlogic()->getConfiguration('password'));
			//$data[] = 'Authorisation: Basic '. base64_encode($this->getEqlogic()->getConfiguration('user') . ':' . $this->getEqlogic()->getConfiguration('password'));
		}
		if ($this->getEqlogic()->getConfiguration('user','') == '' && $this->getEqlogic()->getConfiguration('password','') != '') {
			curl_setopt($request_http, CURLOPT_USERPWD, ':' . $this->getEqlogic()->getConfiguration('password'));
		}
		if ($_options['message'] != '') {
			curl_setopt($request_http, CURLOPT_POSTFIELDS, $_options['message']);
			log::add('ntfy', 'debug', 'Send notify ' . $_options['message'] . ' with option ' . print_r($data, true));
			$output = curl_exec($request_http);
			log::add('ntfy', 'debug', 'Result : ' . $output);
			curl_close($request_http);
		}
		if (isset($_options['files']) && is_array($_options['files'])) {
			foreach ($_options['files'] as $file) {
				$file = trim($file);
				if ($file == '') {
					continue;
				}
				if (!file_exists(realpath($file))) {
					continue;
				}
				$data[] = 'Filename: ' . $file;
				$data[] = 'Content-Type: ' . mime_content_type(realpath($file));
				$data[] = 'Authorization: Basic ' . base64_encode($this->getEqlogic()->getConfiguration('user') . ':' . $this->getEqlogic()->getConfiguration('password'));
				file_get_contents($this->getEqlogic()->getConfiguration('url'), false, stream_context_create([
					'http' => [
						'method' => 'PUT',
						'header' => $data,
						'content' => file_get_contents(realpath($file))
					]
				]));

				/*curl_setopt($request_http, CURLOPT_CUSTOMREQUEST, "PUT");
				curl_setopt($request_http, CURLOPT_RETURNTRANSFER, 1);
				$post = new CurlFile(realpath($file),mime_content_type(realpath($file)));
				curl_setopt($request_http, CURLOPT_POSTFIELDS, $post);
				$data[] = 'Filename: ' . $file;
				$data[] = 'Content-Type: ' . mime_content_type(realpath($file));
				curl_setopt($request_http, CURLOPT_HTTPHEADER, $data);
				$output = curl_exec($request_http);*/
				log::add('ntfy', 'debug', 'Send file ' . realpath($file) . ' ' . mime_content_type(realpath($file)));
				//log::add('ntfy', 'debug', 'Result : ' . $output);
			}
		}
	}

}
