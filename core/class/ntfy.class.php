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
		curl_setopt($request_http, CURLOPT_POSTFIELDS, $_options['message']);
		$data= array();
		if (isset($_options['title'])) {
			if (strpos($_options['title'], ';') === false) {
				$data[] = $_options['title'];
			} else {
				$values = explode(";", $_options['title']);
				foreach ($values as $value) {
					$data[] = $value;
				}
			}
		}
		if ($this->getEqlogic()->getConfiguration('user','') != '') {
			log::add('ntfy', 'debug', 'Using auth : ' . $this->getEqlogic()->getConfiguration('user') . ':' . $this->getEqlogic()->getConfiguration('password'));
			curl_setopt($request_http, CURLOPT_USERPWD, $this->getEqlogic()->getConfiguration('user') . ':' . $this->getEqlogic()->getConfiguration('password'));
			//$data[] = 'Authorisation: Basic '. base64_encode($this->getEqlogic()->getConfiguration('user') . ':' . $this->getEqlogic()->getConfiguration('password'));
		}
		if (count($data) > 0) {
			curl_setopt($request_http, CURLOPT_HTTPHEADER, $data);
		}
		log::add('ntfy', 'debug', 'Send notify ' . $_options['message'] . ' with option ' . print_r($data, true));
		$output = curl_exec($request_http);
		curl_close($request_http);
		log::add('ntfy', 'debug', 'Result : ' . $output);
	}

}
