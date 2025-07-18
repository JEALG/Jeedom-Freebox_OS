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

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/../../core/php/Freebox_OS.inc.php';

class Freebox_OS extends eqLogic
{
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */
	public static function deadCmd()
	{
		$return = array();
		foreach (eqLogic::byType('Freebox_OS') as $Freebox_OS) {
			foreach ($Freebox_OS->getCmd() as $cmd) {
				preg_match_all("/#([0-9]*)#/", $cmd->getConfiguration('infoName', ''), $matches);
				foreach ($matches[1] as $cmd_id) {
					if (!cmd::byId(str_replace('#', '', $cmd_id))) {
						$return[] = array('detail' => __('Freebox_OS', __FILE__) . ' ' . $Freebox_OS->getHumanName() . ' ' . __('dans la commande', __FILE__) . ' ' . $cmd->getName(), 'help' => __('Nom Information', __FILE__), 'who' => '#' . $cmd_id . '#');
					}
				}
				preg_match_all("/#([0-9]*)#/", $cmd->getConfiguration('calcul', ''), $matches);
				foreach ($matches[1] as $cmd_id) {
					if (!cmd::byId(str_replace('#', '', $cmd_id))) {
						$return[] = array('detail' => __('Freebox_OS', __FILE__) . ' ' . $Freebox_OS->getHumanName() . ' ' . __('dans la commande', __FILE__) . ' ' . $cmd->getName(), 'help' => __('Calcul', __FILE__), 'who' => '#' . $cmd_id . '#');
					}
				}
			}
		}
		return $return;
	}
	public static $_widgetPossibility = array('custom' => true);
	public static function pluginGenericTypes()
	{
		$generics = array(
			'SWITCH_STATE' => array( //capitalise without space
				'name' => __('Interrupteur Etat (Freebox_OS)', __FILE__),
				'familyid' => 'Switch', //No space here
				'family' => __('Interrupteur', __FILE__),
				'type' => 'Info',
				'subtype' => array('binary', 'numeric')
			),
			'SWITCH_ON' => array( //capitalise without space
				'name' => __('Interrupteur Bouton On (Freebox_OS)', __FILE__),
				'familyid' => 'Switch', //No space here
				'family' => __('Interrupteur', __FILE__),
				'type' => 'Action',
				'homebridge_type' => true
			),
			'SWITCH_OFF' => array( //capitalise without space
				'name' => __('Interrupteur Bouton Off (Freebox_OS)', __FILE__),
				'familyid' => 'Switch', //No space here
				'family' => __('Interrupteur', __FILE__),
				'type' => 'Action',
				'subtype' => array('other')
			)
		);
		return $generics;
	}
	public static function cron()
	{
		$eqLogics = eqLogic::byType('Freebox_OS');
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] == 'ok') {
			if ($deamon_info['state'] != 'ok' && config::byKey('deamonAutoMode', 'Freebox_OS') != 0) {
				log::add('Freebox_OS', 'debug', ':fg-info: ' . (__('Etat du Démon', __FILE__)) . ' ' . $deamon_info['state'] . ':/fg:');
				Freebox_OS::deamon_start();
				$Free_API = new Free_API();
				$Free_API->getFreeboxOpenSession();
				$deamon_info = self::deamon_info();
				log::add('Freebox_OS', 'debug', ':fg-info: ' . (__('Redémarrage du démon', __FILE__)) . ' : ' . $deamon_info['state'] . ':/fg:');
			}
			foreach ($eqLogics as $eqLogic) {
				$autorefresh = $eqLogic->getConfiguration('autorefresh', '*/5 * * * *');
				try {
					$c = new Cron\CronExpression($autorefresh, new Cron\FieldFactory);

					if ($c->isDue() && $deamon_info['state'] == 'ok') {
						if ($eqLogic->getIsEnable()) {
							if (($eqLogic->getConfiguration('eq_group') == 'nodes' || $eqLogic->getConfiguration('eq_group') == 'tiles') && (config::byKey('TYPE_FREEBOX_TILES', 'Freebox_OS') == 'OK' && config::byKey('FREEBOX_TILES_CRON', 'Freebox_OS') == 1)) {
							} else {
								log::add('Freebox_OS', 'debug', '──────────▶︎ :fg-info: CRON ' . (__('pour l\'actualisation de', __FILE__)) . ' : ' .  $eqLogic->getName() . ':/fg: ◀︎───────────');
								Free_Refresh::RefreshInformation($eqLogic->getId());
								log::add('Freebox_OS', 'debug', '───────────────────────────────────────────');
							}
						}
					}
					if ($deamon_info['state'] != 'ok' && config::byKey('deamonAutoMode', 'Freebox_OS') != 0) {
						log::add('Freebox_OS', 'debug', '[WARNING] - ' . (__('PAS DE CRON pour d\'actualisation', __FILE__)) . ' ' . $eqLogic->getName() . ' ' . (__('à cause du Démon', __FILE__)) . ' : ' . $deamon_info['state']);
					}
				} catch (Exception $exc) {
					log::add('Freebox_OS', 'debug', '[WARNING] - ' . __('L\'expression cron est non valide pour ', __FILE__) . $eqLogic->getHumanName() . ' : ' . $autorefresh . ' (' . (__('Il est conseillé d\'utiliser l\'assistant cron en cliquant sur "?"', __FILE__)) . ') ' . 'ou il y a problème dans le CRON');
				}
				if ($eqLogic->getLogicalId() == 'network' || $eqLogic->getLogicalId() == 'networkwifiguest' || $eqLogic->getLogicalId() == 'disk' || $eqLogic->getLogicalId() == 'homeadapters') {
					if ($eqLogic->getIsEnable()) {
						if ($deamon_info['state'] == 'ok') {
							Freebox_OS::cron_autorefresh_eqLogic($eqLogic, $deamon_info);
						}
					}
				}
			}
		}
	}
	// Fonction exécutée automatiquement tous les jours par Jeedom
	public static function cronDaily()
	{
		$API_version_OLD = config::byKey('FREEBOX_API', 'Freebox_OS');
		log::add('Freebox_OS', 'debug', '──────────▶︎ :fg-info: CRON DAILY' . (__('pour l\'actualisation de l\'API', __FILE__)) . ' : ' . ':/fg: ◀︎───────────');
		$API_version = Freebox_OS::FreeboxAPI($type_Log = 'Debug');
		if ($API_version_OLD != $API_version) {
			message::add('Freebox_OS', '{{L\'API de la Freebox a été mis à jour de la version }}' . $API_version_OLD . ' à la version ' . $API_version);
		}

		log::add('Freebox_OS', 'debug', '───────────────────────────────────────────');
	}

	public static function cron_autorefresh_eqLogic($eqLogic, $deamon_info)
	{
		$_crondailyEq = null;
		$_crondailyTil = null;
		$autorefresh_eqLogic = $eqLogic->getConfiguration('autorefresh_eqLogic');
		try {
			if ($autorefresh_eqLogic != null) {
				switch ($eqLogic->getLogicalId()) {
					case 'network':
					case 'networkwifiguest':
						if (config::byKey('TYPE_FREEBOX_MODE', 'Freebox_OS') == 'router') {
							$_crondailyEq = $eqLogic->getLogicalId();
						}
						break;
					case 'disk':
						$_crondailyEq = $eqLogic->getLogicalId();
						break;
					case 'homeadapters':
						$_crondailyTil = 'homeadapters_SP';
						break;
				}
				if ($_crondailyEq != null or $_crondailyTil != null) {
					try {
						$cron = new Cron\CronExpression($autorefresh_eqLogic, new Cron\FieldFactory);
						if ($cron->isDue()) {
							log::add('Freebox_OS', 'debug', ':fg-info: CRON ' . (__('Cron Actualisation pour l\'Ajout nouvelle commande pour l\'équipement', __FILE__)) . ' : ' . $eqLogic->getName() . ':/fg:');
							if ($_crondailyEq != null) {
								Free_CreateEq::createEq($_crondailyEq, false);
							}
							if ($_crondailyTil != null) {
								Free_CreateTil::createTil($_crondailyTil, false);
							}
						}
					} catch (Exception $e) {
						log::add('Freebox_OS', 'error', '[WARNING] - ' . __('L\'expression Cron pour l\'"Ajout nouvelle commande" est non valide pour l\'équipement', __FILE__) . ' ' . $eqLogic->getHumanName() . ' : ' . $autorefresh_eqLogic . ', ' . (__('Il est conseillé d\'utiliser l\'assistant cron en cliquant sur "?"', __FILE__)));
					}
				}
				$_crondailyEq = null;
				$_crondailyTil = null;
			}
		} catch (Exception $exc) {
			log::add('Freebox_OS', 'error', __('Erreur Cron Actualisation pour l\'Ajout nouvelle commande pour l\'équipement', __FILE__) . ' : ' . $eqLogic->getHumanName());
		}
	}

	public static function deamon_info()
	{
		$return = array();
		$return['log'] = 'Freebox_OS';
		if (trim(config::byKey('FREEBOX_SERVER_IP', 'Freebox_OS')) != '' && config::byKey('FREEBOX_SERVER_APP_TOKEN', 'Freebox_OS') != '' && trim(config::byKey('FREEBOX_SERVER_APP_ID', 'Freebox_OS')) != '') {
			$return['launchable'] = 'ok';
		} else {
			$return['launchable'] = 'nok';
		}
		$return['state'] = 'ok';
		$session_token = cache::byKey('Freebox_OS::SessionToken');
		if (!is_object($session_token) || $session_token->getValue('') == '') {
			$return['state'] = 'nok';
			return $return;
		}
		$cron = cron::byClassAndFunction('Freebox_OS', 'RefreshToken');
		if (!is_object($cron)) {
			$return['state'] = 'nok';
			return $return;
		}
		$cron = cron::byClassAndFunction('Freebox_OS', 'FreeboxPUT');
		if (!is_object($cron)) {
			$return['state'] = 'nok';
			return $return;
		}
		return $return;
	}
	public static function deamon_start($_debug = false)
	{
		//log::remove('Freebox_OS');
		$deamon_info = self::deamon_info();
		self::deamon_stop();
		if ($deamon_info['launchable'] != 'ok') {
			throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
		}
		if ($deamon_info['state'] == 'ok') return;
		$cron = cron::byClassAndFunction('Freebox_OS', 'RefreshToken');
		if (!is_object($cron)) {
			throw new Exception(__('Tache cron RefreshToken introuvable', __FILE__));
		}
		if (is_object($cron)) {
			$cron->run();
		}

		$cron = cron::byClassAndFunction('Freebox_OS', 'FreeboxPUT');
		if (!is_object($cron)) {
			throw new Exception(__('Tache cron FreeboxPUT introuvable', __FILE__));
		}
		if (is_object($cron)) {
			$cron->run();
		}
		if (config::byKey('TYPE_FREEBOX_TILES', 'Freebox_OS') == 'OK') {
			if (config::byKey('FREEBOX_TILES_CRON', 'Freebox_OS') == 1) {
				$cron = cron::byClassAndFunction('Freebox_OS', 'FreeboxGET');
				if (!is_object($cron)) {
					throw new Exception(__('Tache cron FreeboxGET introuvable', __FILE__));
				}
				if (is_object($cron)) {
					$cron->run();
				}
			}
		}
	}
	public static function deamon_stop()
	{
		$cron = cron::byClassAndFunction('Freebox_OS', 'RefreshToken');
		if (!is_object($cron)) {
			throw new Exception(__('Tache cron RefreshToken introuvable', __FILE__));
		}
		if (is_object($cron)) {
			$cron->halt();
		}
		$cron = cron::byClassAndFunction('Freebox_OS', 'FreeboxPUT');
		if (!is_object($cron)) {
			throw new Exception(__('Tache cron FreeboxPUT introuvable', __FILE__));
		}
		if (is_object($cron)) {
			$cron->stop();
			sleep(3);
			if ($cron->running()) {
				$cron->halt();
			}
		}
		cache::delete("Freebox_OS::actionlist");

		if (config::byKey('TYPE_FREEBOX_TILES', 'Freebox_OS') == 'OK') {
			if (config::byKey('FREEBOX_TILES_CRON', 'Freebox_OS') == '1') {
				$cron = cron::byClassAndFunction('Freebox_OS', 'FreeboxGET');
				if (!is_object($cron)) {
					throw new Exception(__('Tache cron FreeboxGET introuvable', __FILE__));
				}
				if (is_object($cron)) {
					$cron->stop();
					sleep(3);
					if ($cron->running()) {
						$cron->halt();
					}
				}
			}
		}

		$Free_API = new Free_API();
		$Free_API->close_session();
	}
	public static function FreeboxPUT()
	{
		$action = cache::byKey("Freebox_OS::actionlist")->getValue();
		if (!is_array($action)) {
			return;
		}
		if (!isset($action[0])) {
			return;
		}
		if ($action[0] == '') {
			return;
		}
		$value_log = ' ';
		if ($action[0]['LogicalIdEqLogic'] == 'management') {
			switch ($action[0]['LogicalId']) {
				case 'host':
				case 'host_type':
				case 'method':
				case 'host_type_info':
					//log::add('Freebox_OS', 'debug', '[testNotArray]' . ' ' .  $action[0]['Options']['select']);
					$value_log = ' : ' . $action[0]['Options']['select'] . ' - ';
					break;
				case 'primary_name_info':
					//log::add('Freebox_OS', 'debug', '[testNotArray] - MESSAGE' . ' ' . $action[0]['Options']['message']);
					$value_log = ' : ' . $action[0]['Options']['message'] . ' - ';
					break;
				case 'start':
					$value_log = ' ';
				default:
					//log::add('Freebox_OS', 'debug', '[testNotArray]' . ' ' . $action[0]['Options']['message']);
					$value_log = ' : ' . $action[0]['Options']['message'] . ' - ';
					break;
			}
		}


		log::add('Freebox_OS', 'debug', ':fg-info:Action pour l\'action : ' . ':/fg:' . $action[0]['Name'] . ' (' . $action[0]['LogicalId'] . ')' . $value_log . 'de l\'équipement : ' . $action[0]['NameEqLogic']);
		Free_Update::UpdateAction($action[0]['LogicalId'], $action[0]['SubType'], $action[0]['Name'], $action[0]['Value'], $action[0]['Config'], $action[0]['EqLogic'], $action[0]['Options'], $action[0]['This']);
		$action = cache::byKey("Freebox_OS::actionlist")->getValue();
		array_shift($action);
		cache::set("Freebox_OS::actionlist", $action);
	}
	public static function FreeboxGET()
	{
		try {
			Free_Refresh::RefreshInformation('Tiles_global');
			sleep(15);
		} catch (Exception $exc) {
			log::add('Freebox_OS', 'error', '[CRITICAL] - ' . __('ERREUR CRON UPDATE TILES/NODE ', __FILE__));
		}
	}
	public static function resetConfig()
	{
		log::add('Freebox_OS', 'debug', ':fg-info: ───▶︎ ' . (__('RESET DES PARAMETRES STANDARDS', __FILE__))  . ':/fg:');
		config::save('FREEBOX_SERVER_IP', "mafreebox.freebox.fr", 'Freebox_OS');
		config::save('FREEBOX_SERVER_APP_NAME', "Plugin Freebox OS", 'Freebox_OS');
		config::save('FREEBOX_SERVER_APP_ID', "plugin.freebox.jeedom", 'Freebox_OS');
		config::save('FREEBOX_SERVER_DEVICE_NAME', config::byKey("name"), 'Freebox_OS');
		$API_version = config::byKey('FREEBOX_API_DEFAUT', 'Freebox_OS');
		config::save('FREEBOX_API', $API_version, 'Freebox_OS');
		log::add('Freebox_OS', 'debug', 'RESET [  OK  ]');
		config::save('FREEBOX_REBOOT_DEAMON', FALSE, 'Freebox_OS');
		log::add('Freebox_OS', 'debug', ':fg-info: ───▶︎ ' . (__('RESET DU TYPE DE BOX', __FILE__)) . ':/fg:');
		config::save('TYPE_FREEBOX', '', 'Freebox_OS');
		config::save('TYPE_FREEBOX_NAME', "", 'Freebox_OS');
		config::save('TYPE_FREEBOX_TILES', "", 'Freebox_OS');
		log::add('Freebox_OS', 'debug', 'RESET [  OK  ]');
	}
	public static function EqLogic_ID($Name, $_logicalId)
	{
		$EqLogic = self::byLogicalId($_logicalId, 'Freebox_OS');
		log::add('Freebox_OS', 'debug', '───▶︎ Name : ' . $Name . ' -- LogicalID : ' . $_logicalId);
		return $EqLogic;
	}
	public static function DisableEqLogic($EqLogic, $TILES = false)
	{
		$logicalinfo = Freebox_OS::getlogicalinfo();
		if ($EqLogic != null) {
			log::add('Freebox_OS', 'debug', ':fg-info: ───▶︎ ' . (__('DESACTIVATION DE', __FILE__)) . ' : ' . $EqLogic->getname() . ':/fg:');
			if (!is_object($EqLogic->getLogicalId())) {
				$EqLogic->setIsEnable(0);
				$EqLogic->save(true);
				//log::add('Freebox_OS', 'debug', '[  OK  ] - FIN DE DESACTIVATION DE : ' . $EqLogic->getname());
			}
		}
		if ($TILES == true) {
			$cron = cron::byClassAndFunction('Freebox_OS', 'FreeboxGET');
			if (is_object($cron)) {
				log::add('Freebox_OS', 'debug', ':fg-info: ───▶︎ ' . (__('SUPPRESSION CRON GLOBAL TITLES', __FILE__))  . ':/fg:');
				$cron->stop();
				$cron->remove();
			}
		}
	}
	public static function AddEqLogic($Name, $_logicalId, $category = null, $tiles = false, $eq_type = null, $eq_action = null, $logicalID_equip = null, $_autorefresh = null, $_Room = null, $Player = null, $eq_group = 'system', $type_save = false, $Player_CONFIG = null)
	{
		$EqLogic = self::byLogicalId($_logicalId, 'Freebox_OS');
		log::add('Freebox_OS', 'debug', ':fg-info:| ' . (__('Création Équipement', __FILE__)) . ' : :/fg:' . $Name . ' ── LogicalID : ' . $_logicalId . ' ── ' . (__('Catégorie', __FILE__)) . ' : ' . $category . ' ── ' . (__('Équipement Type', __FILE__)) . ' : ' . $eq_type . ' ── Logical ID Equip : ' . $logicalID_equip . ' ── Cron : ' . $_autorefresh . ' ── ' . (__('Objet', __FILE__)) . ' : ' . $_Room . ' ── ' . (__('Regroupement', __FILE__)) . ' : ' . $eq_group);
		if (!is_object($EqLogic)) {
			$EqLogic = new Freebox_OS();
			$EqLogic->setLogicalId($_logicalId);
			if ($_Room == null) {
				$defaultRoom = intval(config::byKey('defaultParentObject', "Freebox_OS", '', true));
			} else {
				// Fonction NON désactiver A TRAITER => Pose des soucis chez certain utilisateurs (Voir Fil d'actualité du Plugin)
				$defaultRoom = intval($_Room);
			}

			if ($defaultRoom != null) {
				$EqLogic->setObject_id($defaultRoom);
			}
			$EqLogic->setEqType_name('Freebox_OS');
			$EqLogic->setIsEnable(1);
			$EqLogic->setIsVisible(0);
			$EqLogic->setName($Name);
			if ($category != null) {
				$EqLogic->setcategory($category, 1);
			}

			if ($_autorefresh != null) {
				$EqLogic->setConfiguration('autorefresh', $_autorefresh);
			} else {
				$EqLogic->setConfiguration('autorefresh', '*/5 * * * *');
			}
			if ($tiles == true) {
				$EqLogic->setConfiguration('type', $eq_type);
				$EqLogic->setConfiguration('action', $eq_action);
				if ($EqLogic->getConfiguration('type', $eq_type) == 'parental' || $EqLogic->getConfiguration('type', $eq_type) == 'player') {
					$EqLogic->setConfiguration('action', $logicalID_equip);
				}
			}
			if ($eq_group != null) {
				$EqLogic->setConfiguration('eq_group', $eq_group);
			}

			if ($_logicalId == 'network' || $_logicalId == 'networkwifiguest' || $_logicalId == 'disk' || $_logicalId == 'homeadapters') {
				$EqLogic->setConfiguration('autorefresh_eqLogic', '2 1 * * *');
			}
			try {
				$EqLogic->save();
			} catch (Exception $e) {
				$EqLogic->setName($EqLogic->getName() . ' doublon ' . rand(0, 9999));
				$EqLogic->save();
			}
		}
		$EqLogic->setConfiguration('logicalID', $_logicalId);
		if ($_autorefresh == null) {
			if ($tiles == true && ($EqLogic->getConfiguration('type', $eq_type) != 'parental' && $EqLogic->getConfiguration('type', $eq_type) != 'player' && $EqLogic->getConfiguration('type', $eq_type) != 'alarm_remote')) {
				$EqLogic->setConfiguration('autorefresh', '* * * * *');
			} elseif ($tiles == true && ($EqLogic->getConfiguration('type', $eq_type) == 'alarm_remote')) {
				$EqLogic->setConfiguration('autorefresh', '*/5 * * * *');
			} elseif ($EqLogic->getLogicalId() == 'disk') {
				$EqLogic->setConfiguration('autorefresh', '1 * * * *');
			} else {
				$EqLogic->setConfiguration('autorefresh', '*/5 * * * *');
			}
		}
		if ($tiles === true) {
			if ($eq_type != 'pir' && $eq_type != 'kfb' && $eq_type != 'dws' && $eq_type != 'alarm' && $eq_type != 'basic_shutter' && $eq_type != 'shutter'  && $eq_type != 'opener' && $eq_type != 'plug') {
				$EqLogic->setConfiguration('type', $eq_type);
			} else {
				$EqLogic->setConfiguration('type2', $eq_type);
				if ($eq_type === 'pir') {
					$EqLogic->setConfiguration('info', 'mouv_sensor');
				}
			}
			if ($eq_action != null) {
				$EqLogic->setConfiguration('action', $eq_action);
			}
			if ($EqLogic->getConfiguration('type', $eq_type) == 'parental' || $EqLogic->getConfiguration('type', $eq_type) == 'player' || $EqLogic->getConfiguration('type', $eq_type) == 'VM') {
				$EqLogic->setConfiguration('action', $logicalID_equip);
			}
		}
		if ($Player != null) {
			$EqLogic->setConfiguration('player', $Player);
			if ($Player_CONFIG != null) {
				if ($Player_CONFIG['player_ID_MAC'] != null) {
					$EqLogic->setConfiguration('player_MAC', $Player_CONFIG['player_ID_MAC']);
				}
				$EqLogic->setConfiguration('player_API_VERSION', $Player_CONFIG['player_API_VERSION']);
				if ($Player_CONFIG['player_ID_MAC'] !=  $eq_action) {
					if ($eq_action != null) {
						$EqLogic->setConfiguration('action', $eq_action);
					}
				}
				if ($Player_CONFIG['player_MAC_ADDRESS'] != null) {
					$EqLogic->setConfiguration('player_MAC_ADDRESS', $Player_CONFIG['player_MAC_ADDRESS']);
				}
				log::add('Freebox_OS', 'debug', ':fg-info:| ───▶︎ ' . (__('Configuration spécifique pour les players', __FILE__)) .  ' : :/fg:' . $Player_CONFIG['player_ID_MAC'] . ' / ' . $Player_CONFIG['player_API_VERSION']);
			}
		}
		if ($type_save == false) {
			$EqLogic->save();
		} else {
			$EqLogic->save(true);
		}
		return $EqLogic;
	}

	public static function templateWidget()
	{
		return Free_Template::getTemplate();
	}

	public function AddCommand($Name, $_logicalId, $Type = 'info', $SubType = 'binary', $Template = null, $unite = null, $generic_type = null, $IsVisible = 1, $link_I = 'default', $link_logicalId = 'default',  $invertBinary_display = '0', $icon = null, $forceLineB = '0', $valuemin = 'default', $valuemax = 'default', $_order = null, $IsHistorized = '0', $forceIcone_widget = false, $repeatevent = 'never', $_logicalId_slider = null, $_iconname = null, $_home_config_eq = null, $_calculValueOffset = null, $_historizeRound = null, $_noiconname = null, $invertSlide = null, $request = null, $_eq_type_home = null, $forceLineA = null, $listValue = null, $updatenetwork = false, $name_connectivity_type = null, $listValue_Update = null, $_display_parameters = null, $invertBinary_config = null, $PARATemplate = null)
	{
		if ($listValue_Update == true) {
			log::add('Freebox_OS', 'debug', ':fg-info:| ' . (__('Création Commande', __FILE__)) . ' : :/fg:' . $Name . ' ── LogicalID : ' . $_logicalId . ' ── ' . (__('Mise à jour de la liste de choix avec les valeurs', __FILE__)) . ' : ' . $listValue . ':/fg:');
		} else if ($updatenetwork != false) {
		} else {
			log::add('Freebox_OS', 'debug', ':fg-info:| ' . (__('Création Commande', __FILE__)) . ' : :/fg:' . $Name . ' ── ' . (__("Type / SubType", __FILE__)) . ' : '  . $Type . '/' . $SubType . ' ── LogicalID : ' . $_logicalId . ' ──  Template Widget / Ligne : ' . $Template . '/' . $forceLineB . ' ── ' . (__('Type de générique', __FILE__)) . ' : ' . $generic_type . ' ── ' . (__('Inverser Affichage', __FILE__)) . ' : '  .  $invertBinary_display . ' ── ' . (__('Inverser Valeur Binaire', __FILE__)) . ' : '  .  $invertBinary_config . ' ── ' . (__('Icône', __FILE__)) . ' : ' . $icon . ' ── ' . (__('Min/Max', __FILE__)) . ' : ' . $valuemin . '/' . $valuemax . ' ── Calcul/Arrondi : ' . $_calculValueOffset . '/' . $_historizeRound . ' ── ' . (__('Ordre', __FILE__)) . ' : ' . $_order);
		}
		$Cmd = $this->getCmd($Type, $_logicalId);
		if (!is_object($Cmd)) {
			$VerifName = $Name;
			$Cmd = new Freebox_OSCmd();
			$Cmd->setId(null);
			$Cmd->setLogicalId($_logicalId);
			$Cmd->setEqLogic_id($this->getId());
			$count = 0;
			if ($name_connectivity_type != null) {
				if (is_object(cmd::byEqLogicIdCmdName($this->getId(), $VerifName))) {
					$VerifName = $VerifName . ' (' . $name_connectivity_type . ')';
				}
				if (is_object(cmd::byEqLogicIdCmdName($this->getId(), $VerifName))) {
					$VerifName = $VerifName . ' (' . $name_connectivity_type . ' - ' . $_logicalId . ')';
				}
			}
			while (is_object(cmd::byEqLogicIdCmdName($this->getId(), $VerifName))) {
				$count++;
				$VerifName = $Name . '(' . $count . ')';
			}
			$Cmd->setName($VerifName);

			$Cmd->setType($Type);
			$Cmd->setSubType($SubType);
			if ($SubType == 'numeric') {
				if ($unite != null) {
					$Cmd->setUnite($unite);
				}
				if ($valuemin != 'default') {
					$Cmd->setConfiguration('minValue', $valuemin);
				}
				if ($valuemax != 'default') {
					$Cmd->setConfiguration('maxValue', $valuemax);
				}
			}
			$Cmd->save();

			if ($Template != null) {
				$Cmd->setTemplate('dashboard', $Template);
				$Cmd->setTemplate('mobile', $Template);
				if ($PARATemplate != null) {
					$Cmd->setDisplay('parameters', $PARATemplate);
					log::add('Freebox_OS', 'debug', ':fg-info:| ' . (__('Création Commande', __FILE__)) . ' : :/fg:' . 'TEST');
				}
			}
			$Cmd->setIsVisible($IsVisible);
			$Cmd->setIsHistorized($IsHistorized);
			if ($invertBinary_display != null && $SubType == 'binary') {
				$Cmd->setDisplay('invertBinary', 1);
			}
			if ($invertBinary_config != null) {
				$Cmd->setConfiguration('invertBinary', 1);
			}
			if ($invertSlide != null) {
				if ($Type == 'action') {
					$Cmd->setConfiguration('invertslide', 1);
				} else {
					$Cmd->setDisplay('invertBinary', 1);
				}
			}
			if ($icon != null) {
				$Cmd->setDisplay('icon', '<i class="' . $icon . '"></i>');
			}
			if ($forceLineB != null) {
				$Cmd->setDisplay('forceReturnLineBefore', 1);
			}
			if ($forceLineA != null) {
				$Cmd->setDisplay('forceReturnLineAfter', 1);
			}
			if ($_iconname != null) {
				$Cmd->setDisplay('showIconAndNamedashboard', 1);
				$Cmd->setDisplay('showIconAndNamemobile', 1);
				//$Cmd->setDisplay('title_disable', true);
			}
			if ($_display_parameters != null) {
				$Cmd->setDisplay('parameters', $_display_parameters);
				$Cmd->save();
			}
			if ($_noiconname != null) {
				$Cmd->setDisplay('showNameOndashboard', 0);
				$Cmd->setDisplay('showNameOnmobile', 0);
			}
			if ($_calculValueOffset != null) {
				$Cmd->setConfiguration('calculValueOffset', $_calculValueOffset);
			}
			if ($_historizeRound != null) {
				$Cmd->setConfiguration('historizeRound', $_historizeRound);
			}

			if ($request != null) {
				$Cmd->setConfiguration('request', $request);
			}

			$Cmd->save();
			if ($_order != null) {
				$Cmd->setOrder($_order);
			}
		}

		if ($_home_config_eq != null) { // Compatibilité Homebridge
			if ($_home_config_eq == 'SetModeAbsent') {
				$this->setConfiguration($_home_config_eq, $Cmd->getId() . "|" . $Name);
				$this->setConfiguration('SetModePresent', "NOT");
				$this->setConfiguration('ModeAbsent', $Name);
				log::add('Freebox_OS', 'debug', '| ───▶︎ ' . (__('Paramétrage du Mode Homebridge', __FILE__)) . ' Set Mode : SetModePresent => NOT' . ' -- ' . (__('Paramétrage du Mode Homebridge Set Mode', __FILE__)) . ' : ' . $_home_config_eq);
			} else if ($_home_config_eq == 'SetModeNuit') {
				$this->setConfiguration($_home_config_eq, $Cmd->getId() . "|" . $Name);
				$this->setConfiguration('ModeNuit', $Name);
				log::add('Freebox_OS', 'debug', '| ───▶︎ ' . (__('Paramétrage du Mode Homebridge Set Mode', __FILE__)) . ' : ' . $_home_config_eq);
			} else if ($_home_config_eq == 'mouv_sensor') {
				$this->setConfiguration('info', $_home_config_eq);
				if ($invertBinary_config != null  && $SubType == 'binary') { //Correction pour prise en compte fonction Core
					log::add('Freebox_OS', 'debug', '| ───▶︎ ' . (__('Application Correctif pour prendre en compte fonction Core pour la commande', __FILE__)) . ' : ' . $Name . ' - ' . (__('Type de capteur', __FILE__)) . ' :' . $_home_config_eq);
					$Cmd->setConfiguration('invertBinary', $invertBinary_config);
					$Cmd->setDisplay('invertBinary', $invertBinary_display);
				}
				$Cmd->setConfiguration('info', $_home_config_eq);
			}
		}
		if ($_eq_type_home != null) { // Node
			$Cmd->setConfiguration('TypeNode', $_eq_type_home);
		}
		$this->save(true);
		if ($generic_type != null) {
			$Cmd->setGeneric_type($generic_type);
		}
		if ($_logicalId_slider != null && $link_I != 'CARD') { // logical Id spécial Slider
			$Cmd->setConfiguration('logicalId_slider', $link_I);
		} else {
			if ($link_I === 'CARD') {
				$Cmd->setConfiguration('WIFI_CARD', $link_I);
			}
		}
		if ($Type == 'info') {
			if ($repeatevent === true || $repeatevent === 'never') {
				$Cmd->setConfiguration('repeatEventManagement', 'never');
			} else {
				$Cmd->setConfiguration('repeatEventManagement', 'always');
			}
		}

		if ($valuemin != 'default') {
			$Cmd->setConfiguration('minValue', $valuemin);
		}
		if ($valuemax != 'default') {
			$Cmd->setConfiguration('maxValue', $valuemax);
		}
		if (is_object($link_I) && $Type == 'action') {
			$Cmd->setValue($link_I->getId());
		}
		if ($link_logicalId != 'default') {
			if ($link_logicalId == 'DELETE') {
				$Cmd->setConfiguration('logicalId', NULL);
			} else {
				$Cmd->setConfiguration('logicalId', $link_logicalId);
			}
		}
		// Mise à jour des noms de la commande pour WIFI
		if ($link_I == 'CARD') {
			if ($Name != $Cmd->getName()) {
				log::add('Freebox_OS', 'debug', '| ───▶︎ :fg-info:' . (__('Nom différent sur la Freebox pour la commande Wifi', __FILE__)) . ' ::/fg: ' . $Name . ':fg-info: -- ' . (__('Nom de la commande Jeedom', __FILE__)) . ' ::/fg: ' . $Cmd->getName());
				if (is_object(cmd::byEqLogicIdCmdName($this->getId(), $Name))) {
					$VerifName = $Name .  ' - (' . $_logicalId . ')';
					log::add('Freebox_OS', 'debug', '|  :fg-warning:└───▶︎  ' . __('Une commande porte déjà ce nom', __FILE__) . ' ::/fg: ' . $VerifName);
				} else {
					$VerifName  = $Name;
					log::add('Freebox_OS', 'debug', '|  :fg-success:└───▶︎  ' . __('Aucune commande ne porte ce nom, changement du nom  OK', __FILE__) . ' ::/fg: ' . $VerifName);
				}
				if ($VerifName  != $Cmd->getName()) {
					$Cmd->setName($VerifName);
					$Cmd->save();
				}
			}
		}

		// Mise à jour des noms de la commande pour le Network
		if ($updatenetwork != false) {
			if ($updatenetwork['updatename'] == true) {
				if ($Name != $Cmd->getName()) {
					log::add('Freebox_OS', 'debug', '| ───▶︎ :fg-info:' . (__('Nom différent sur la Freebox', __FILE__)) . ' ::/fg: ' . $Name . ':fg-info: -- ' . (__('Nom de la commande Jeedom', __FILE__)) . ' ::/fg: ' . $Cmd->getName());
					if ($name_connectivity_type != 'Wifi Ethernet ?') {
						if (is_object(cmd::byEqLogicIdCmdName($this->getId(), $Name))) {
							$VerifName = $Name . ' (' . ucwords($name_connectivity_type)  . ')';
							log::add('Freebox_OS', 'debug', '|  :fg-warning:└───▶︎  ' . __('Une commande porte déjà ce nom donc ajout du type de connection', __FILE__) . ' ::/fg: ' . $VerifName);
						} else {
							$VerifName = $Name;
							log::add('Freebox_OS', 'debug', '|  :fg-success:└───▶︎  ' . __('Aucune commande ne porte ce nom, changement du nom OK', __FILE__) . ' ::/fg: ' . $VerifName);
						}
					} else {
						if (is_object(cmd::byEqLogicIdCmdName($this->getId(), $Name))) {
							$VerifName = $Name . ' (' . $updatenetwork['mac_address'] . ')';
							log::add('Freebox_OS', 'debug', '|  :fg-warning:└───▶︎  ' . __('Une commande porte déjà ce nom donc ajout de l\'adresse MAC', __FILE__) . ' ::/fg: ' . $VerifName);
						} else {
							$VerifName = $Name;
							log::add('Freebox_OS', 'debug', '|  :fg-success:└───▶︎  ' . __('Aucune commande ne porte ce nom, changement du nom OK', __FILE__) . ' ::/fg: ' . $VerifName);
						}
					}
					$Name_wifi = $Name . '(Wifi)';
					$Name_ethernet = $Name . '(Ethernet)';


					if ($VerifName === $Cmd->getName() || $Name_wifi === $Cmd->getName() || $Name_ethernet === $Cmd->getName()) {
						$Cmd->setName($VerifName);
						$Cmd->save();
					} else {
						if ($name_connectivity_type != null) {
							if (is_object(cmd::byEqLogicIdCmdName($this->getId(), $Name))) {
								$Name = $VerifName;
							}
						}
						if (is_object(cmd::byEqLogicIdCmdName($this->getId(), $VerifName))) {
							$VerifName = $VerifName . ' - (' . $_logicalId . ')';
						}
						if ($VerifName != $Cmd->getName()) {
							$Cmd->setName($VerifName);
							$Cmd->save();
						}
					}
				}
			}
			if (isset($updatenetwork['UpdateVisible']) && $updatenetwork['UpdateVisible'] == true) {
				if ($updatenetwork['IsVisible_option'] == 0) {
					$Cmd->setIsVisible(0);
					$Cmd->save();
				} else {
					$Cmd->setIsVisible(1);
					$Cmd->save();
				}
			}
			$Cmd->setConfiguration('host_type', $updatenetwork['host_type']);
			if (isset($updatenetwork['repeatevent'])) {
				if ($repeatevent == $updatenetwork['repeatevent'] && $Type == 'info') {
					$Cmd->setConfiguration('repeatEventManagement', 'never');
				}
			}
			$Cmd->setConfiguration('IPV4', $updatenetwork['IPV4']);
			$Cmd->setConfiguration('IPV6', $updatenetwork['IPV6']);
			$Cmd->setConfiguration('mac_address', $updatenetwork['mac_address']);
			$Cmd->setConfiguration('invertBinary', 0); //│===============================> Correction Bug du 14.01.2024
			if ($updatenetwork['order'] != null) {
				$Cmd->setOrder($updatenetwork['order']);
			}
		}
		// Mise à jour des noms de la commande pour le Wifi en cas de changement de box		
		if ($forceIcone_widget == true) {
			if ($icon != null) {
				$Cmd->setDisplay('icon', '<i class="' . $icon . '"></i>');
			}
			if ($Template != null) {
				$Cmd->setTemplate('dashboard', $Template);
				$Cmd->setTemplate('mobile', $Template);
			}
			$Cmd->setIsVisible($IsVisible);

			if ($forceLineB != null) {
				$Cmd->setDisplay('forceReturnLineBefore', 1);
			}
			if ($forceLineA != null) {
				$Cmd->setDisplay('forceReturnLineAfter', 1);
			}

			if ($_iconname != null) {
				$Cmd->setDisplay('showIconAndNamedashboard', 1);
			}
		}
		if ($listValue != null) {
			$Cmd->setConfiguration('listValue', $listValue);
		}

		$Cmd->save();

		// Création de la commande refresh
		$createRefreshCmd  = true;
		$refresh = $this->getCmd(null, 'refresh');
		if (!is_object($refresh)) {
			$refresh = cmd::byEqLogicIdCmdName($this->getId(), __('Rafraichir', __FILE__));
			if (is_object($refresh)) {
				$createRefreshCmd = false;
			}
		}
		if ($createRefreshCmd) {
			if (!is_object($refresh)) {
				$refresh = new Freebox_OSCmd();
				$refresh->setLogicalId('refresh');
				$refresh->setIsVisible(1);
				$refresh->setName(__('Rafraichir', __FILE__));
			}
			$refresh->setType('action');
			$refresh->setSubType('other');
			$refresh->setEqLogic_id($this->getId());
			$refresh->save();
		}
		return $Cmd;
	}
	/*     * *********************Méthodes d'instance************************* */
	public function preInsert() {}

	public function postInsert() {}

	public function preSave()
	{
		switch ($this->getLogicalId()) {
			case 'AirPlay':
				$Free_API = new Free_API();
				$parametre["enabled"] = $this->getIsEnable();
				$parametre["password"] = $this->getConfiguration('password');
				$Free_API->airmedia('config', $parametre, null);
				break;
		}
	}

	public function postSave()
	{
		if ($this->getConfiguration('eq_group') === 'tiles') {
			if ($this->getConfiguration('type') === 'alarm_control') {
				log::add('Freebox_OS', 'debug', '──────────▶︎ :fg-warning: ' . (__('SAUVEGARDE : Mise à jour des paramètrages spécifiques pour Homebridge', __FILE__))  . ' ::/fg: ' . $this->getName() . '/' . $this->getConfiguration('type'));
				foreach ($this->getCmd('action') as $Cmd) {
					if (is_object($Cmd)) {
						switch ($Cmd->getLogicalId()) {
							case "1":
								$_home_config_eq = 'SetModeAbsent';
								$_home_mode = 'ModeAbsent';
								break;
							case "2":
								$_home_config_eq = 'SetModeNuit';
								$_home_mode = 'ModeNuit';
								break;
						}
						if (isset($_home_config_eq)) {
							if ($_home_config_eq != null) {
								log::add('Freebox_OS', 'debug', '| ───▶︎ ' . (__('Mode', __FILE__)) . ' : ' . $_home_config_eq . '(' . (__('Commandee', __FILE__))  . ' : ' . $Cmd->getName() . ')');
								$this->setConfiguration($_home_mode, $Cmd->getName());
								$this->save(true);
								$this->setConfiguration($_home_config_eq, $Cmd->getId() . "|" . $Cmd->getName());
								$this->save(true);
								if ($_home_config_eq == 'SetModeAbsent') {
									$this->setConfiguration('SetModePresent', "NOT");
								} else {
									$this->setConfiguration($_home_config_eq, $Cmd->getId() . "|" . $Cmd->getName());
								}

								$_home_config_eq = null;
							}
						}
					}
				}
			}
		}

		if ($this->getIsEnable()) {
			if (($this->getConfiguration('eq_group') == 'nodes' || $this->getConfiguration('eq_group') == 'tiles') && (config::byKey('TYPE_FREEBOX_TILES', 'Freebox_OS') == 'OK' && config::byKey('FREEBOX_TILES_CRON', 'Freebox_OS') == 1)) {
			} else {

				Free_Refresh::RefreshInformation($this->getId());
			}
		}
		//log::add('Freebox_OS', 'debug', '───────────────────────────────────────────');

		$createRefreshCmd = true;
		$refresh = $this->getCmd(null, 'refresh');
		if (!is_object($refresh)) {
			$refresh = cmd::byEqLogicIdCmdName($this->getId(), __('Rafraichir', __FILE__));
			if (is_object($refresh)) {
				$createRefreshCmd = false;
			}
		}
		if ($createRefreshCmd) {
			if (!is_object($refresh)) {
				$refresh = new Freebox_OSCmd();
				$refresh->setLogicalId('refresh');
				$refresh->setIsVisible(1);
				$refresh->setName(__('Rafraichir', __FILE__));
			}
			$refresh->setType('action');
			$refresh->setSubType('other');
			$refresh->setEqLogic_id($this->getId());
			$refresh->save();
		}
	}

	public function preUpdate()
	{
		if (!$this->getIsEnable()) return;
		if (config::byKey('FREEBOX_TILES_CRON', 'Freebox_OS') == 1 && $this->getConfiguration('eq_group') == 'tiles') {
			log::add('Freebox_OS', 'debug', '| ───▶︎ CRON : ' . (__('Pas de vérification car Cron global titles actif', __FILE__)));
		} else {
			if ($this->getConfiguration('autorefresh') == '') {
				log::add('Freebox_OS', 'error', '[CRITICAL] CRON : ' . (__('Temps de rafraichissement est vide pour l\'équipement', __FILE__)) . ' : ' . $this->getName() . ' ' . $this->getConfiguration('autorefresh'));
				throw new Exception(__('Le champ "Temps de rafraichissement (cron)" ne peut être vide', __FILE__) . ' : ' . $this->getName());
			}
		}
	}

	public function postUpdate() {}

	public function preRemove() {}

	public function postRemove() {}
	public static function getConfigForCommunity()
	{
		$box = "Box [" . config::byKey('TYPE_FREEBOX', 'Freebox_OS') . '] ; Box_name [' . config::byKey('TYPE_FREEBOX_NAME', 'Freebox_OS') . ']';
		$box_Firmware = "Firmware [" . config::byKey('TYPE_FIRMWARE', 'Freebox_OS') . ']';
		$box_mode = (__('Mode', __FILE__)) . ' [' . config::byKey('TYPE_FREEBOX_MODE', 'Freebox_OS') . ']';
		$IP = "IP Box [" . config::byKey('FREEBOX_SERVER_IP', 'Freebox_OS') . ']';
		$ligne1 = $box . ' ; ' . $box_Firmware . ' ; ' . $box_mode . ' ; ' . $IP;

		$Name = (__('Nom', __FILE__)) . ' [' . config::byKey('FREEBOX_SERVER_DEVICE_NAME', 'Freebox_OS') . ']';
		$API = "API [" . config::byKey('FREEBOX_API', 'Freebox_OS') . ']';
		$tiles = (__('Box compatible avec la domotique', __FILE__)) . ' [' . config::byKey('TYPE_FREEBOX_TILES', 'Freebox_OS') . ']';
		$tiles_cron = (__('Cron pour la domotique', __FILE__)) . ' [' .  config::byKey('FREEBOX_TILES_CRON', 'Freebox_OS') . ']';
		$VM = (__('Box compatible avec les VM', __FILE__)) . ' [' . config::byKey('FREEBOX_VM', 'Freebox_OS') . ']';
		$ligne2 = $Name . ' ; ' . $API . ' ; ' . $tiles . ' ; ' . $tiles_cron . ' ; ' . $VM;

		$SEARCH_EQ = "EQ [" . config::byKey('SEARCH_EQ', 'Freebox_OS') . ']';
		$SEARCH_TILES = "Tiles [" . config::byKey('SEARCH_TILES', 'Freebox_OS') . ']';
		$SEARCH_PARENTAL = (__('Parental', __FILE__)) . ' [' . config::byKey('SEARCH_PARENTAL', 'Freebox_OS') . ']';
		$ligne3 = 'Scans : ' . $SEARCH_EQ . ' ; ' . $SEARCH_TILES . ' ; ' . $SEARCH_PARENTAL;

		$WFI_ECO =  __('Box compatible avec le mode Eco Wifi', __FILE__) .  ' [' . config::byKey('FREEBOX_HAS_ECO_WFI', 'Freebox_OS') . ']';
		$LCD_LED_RD =  __('Box compatible avec les LED rouges', __FILE__) .  ' [' . config::byKey('FREEBOX_LED_RD', 'Freebox_OS') . ']';
		$LCD_TEXTE =  __('Box compatible avec l\'orientation du texte sur l\'afficheur', __FILE__) .  ' [' . config::byKey('FREEBOX_LCD_TEXTE', 'Freebox_OS') . ']';
		$ligne4 = $WFI_ECO . ' ; ' . $LCD_LED_RD . ' ; ' . $LCD_TEXTE;

		$FreeboxInfo = '<br>```<br>' . $ligne1 . '<br>' . $ligne2 . '<br>' . $ligne3 . '<br>' . $ligne4 . '<br>```	';
		return $FreeboxInfo;
	}

	/*     * **********************Getteur Setteur*************************** */

	public static function RefreshToken()
	{
		log::add('Freebox_OS', 'debug', '──────────▶︎ :fg-warning: Refresh Token :/fg: ◀︎───────────');
		$cron = cron::byClassAndFunction('Freebox_OS', 'FreeboxPUT');
		if (is_object($cron)) {
			$cron->stop();
			log::add('Freebox_OS', 'debug', ' OK  CRON ' . (__('Arrêt', __FILE__)) . ' Freebox PUT');
		}
		$cron = cron::byClassAndFunction('Freebox_OS', 'FreeboxGET');
		if (is_object($cron)) {
			$cron->stop();
			log::add('Freebox_OS', 'debug', ' OK  CRON ' . (__('Arrêt', __FILE__)) . ' Freebox GET');
		}
		/*$cron = cron::byClassAndFunction('Freebox_OS', 'FreeboxAPI');
		if (is_object($cron)) {
			$cron->stop();
			log::add('Freebox_OS', 'debug', ' OK  CRON ' . (__('Arrêt', __FILE__)) . ' Freebox API');
		}*/
		sleep(1);
		$Free_API = new Free_API();
		$Free_API->close_session();
		if ($Free_API->getFreeboxOpenSession() === false) {
			self::deamon_stop();
			log::add('Freebox_OS', 'debug', ' [REFRESH TOKEN] : FALSE / ' . $Free_API->getFreeboxOpenSession());
		}
		sleep(1);
		$cron = cron::byClassAndFunction('Freebox_OS', 'FreeboxPUT');
		if (!is_object($cron)) {
			throw new Exception(__('Tache cron FreeboxPUT introuvable', __FILE__));
		} else {
			$cron->run();
			log::add('Freebox_OS', 'debug', ' OK  ' . (__('Redémarrage', __FILE__)) . ' CRON Freebox PUT');
		}
		if (config::byKey('TYPE_FREEBOX_TILES', 'Freebox_OS') == 'OK') {
			if (config::byKey('FREEBOX_TILES_CRON', 'Freebox_OS') == 1) {
				$cron = cron::byClassAndFunction('Freebox_OS', 'FreeboxGET');
				if (!is_object($cron)) {
					throw new Exception(__('Tache cron FreeboxGET introuvable', __FILE__));
				} else {
					$cron->run();
					log::add('Freebox_OS', 'debug', ' OK  ' . (__('Redémarrage', __FILE__)) . ' CRON Freebox GET');
				}
			}
		}
		log::add('Freebox_OS', 'debug', '───────────────────────────────────────────');
	}
	public static function getlogicalinfo()
	{
		return array(
			'4GID' => '4G',
			'4GName' => '4G',
			'airmediaID' => 'airmedia',
			'airmediaName' => 'Air Média',
			'connexionID' => 'connexion',
			'connexionName' => (__('Freebox débits', __FILE__)),
			'diskID' => 'disk',
			'diskName' => (__('Disque Dur', __FILE__)),
			'downloadsID' => 'downloads',
			'downloadsName' => (__('Téléchargements', __FILE__)),
			'freeplugID' => 'freeplug',
			'freeplugName' => 'Freeplug',
			'homeadaptersID' => 'homeadapters',
			'homeadaptersName' => 'Home Adapters',
			'LCDID' => 'LCD',
			'LCDName' => (__('Afficheur LCD', __FILE__)),
			'managementID' => 'management',
			'managementName' => (__('Gestion réseau', __FILE__)),
			'networkID' => 'network',
			'networkName' => (__('Appareils connectés', __FILE__)),
			'netshareID' => 'netshare',
			'netshareName' => (__('Partage Windows - Mac', __FILE__)),
			'networkwifiguestID' => 'networkwifiguest',
			'networkwifiguestName' => (__('Appareils connectés Wifi Invité', __FILE__)),
			'notificationID' => 'notification',
			'notificationName' => (__('Notification', __FILE__)),
			'parentalID' => 'parental',
			'parentalName' => 'Parental',
			'phoneID' => 'phone',
			'phoneName' => (__('Téléphone', __FILE__)),
			'playerID' => 'player',
			'playerName' => 'Player',
			'systemID' => 'system',
			'systemName' => (__('Système', __FILE__)),
			'VMID' => 'VM',
			'VMName' => 'VM',
			'wifiID' => 'wifi',
			'wifiName' => (__('Wifi', __FILE__)),
			'wifiguestID' => 'wifiguest',
			'wifiguestName' => (__('Wifi Invité', __FILE__)),
			'wifimmac_filter' => 'Wifi Filtrage Adresse Mac',
			'wifiWPSID' => 'wifiWPS',
			'wifiWPSName' => 'Wifi WPS',
			'wifiAPID' => 'wifiAP',
			'wifiAPName' => 'Wifi Access Points',
			'wifistandbyName' => (__('Planification Wifi', __FILE__)),
			'wifiECOName' => (__('Mode Eco Wifi', __FILE__))
		);
	}
	public static function FreeboxAPI($type_Log = 'info')
	{
		log::add('Freebox_OS', $type_Log, '┌── :fg-success: ' . (__('Check Version API de la Freebox', __FILE__)) . ' :/fg:──');
		log::add('Freebox_OS', $type_Log, '|:fg-warning: ' . (__('Il est possible d\'avoir le message suivant dans les messages : API NON COMPATIBLE : Version d\'API inconnue', __FILE__)) . ' :/fg:');
		$Free_API = new Free_API();
		$result = $Free_API->universal_get('universalAPI', null, null, 'api_version', true, true, true);
		log::add('Freebox_OS', $type_Log, '| :fg-info:───▶︎ ' . (__('Nom du type de Box', __FILE__)) . ' ::/fg: ' . $result['box_model_name']);
		log::add('Freebox_OS', $type_Log, '| :fg-info:───▶︎ API URL ::/fg: ' . $result['api_base_url']);
		log::add('Freebox_OS', $type_Log, '| :fg-info:───▶︎ Port https ::/fg: ' . $result['https_port']);
		log::add('Freebox_OS', $type_Log, '| :fg-info:───▶︎ ' . (__('Nom de la Box', __FILE__)) . ' ::/fg: ' . $result['device_name']);
		log::add('Freebox_OS', $type_Log, '| :fg-info:───▶︎ ' . (__('Https disponible', __FILE__)) . ' ::/fg: ' . $result['https_available']);
		log::add('Freebox_OS', $type_Log, '| :fg-info:───▶︎ ' . (__('Modele de la Box', __FILE__)) . ' ::/fg: ' . $result['box_model']);
		log::add('Freebox_OS', $type_Log, '| :fg-info:───▶︎ ' . (__('Type de box', __FILE__)) . ' ::/fg: ' . $result['device_type']);
		log::add('Freebox_OS', $type_Log, '| :fg-info:───▶︎ API domaine ::/fg: ' . $result['api_domain']);
		log::add('Freebox_OS', $type_Log, '| :fg-info:───▶︎ API version ::/fg: ' . $result['api_version']);
		$API_version = 'v'  . $result['api_version'];
		$API_version = strstr($API_version, '.', true);
		log::add('Freebox_OS', $type_Log, '| :fg-info:───▶︎ ' . (__('Version actuelle dans la base', __FILE__)) . ' ::/fg: ' . config::byKey('FREEBOX_API', 'Freebox_OS'));
		config::save('FREEBOX_API', $API_version, 'Freebox_OS');
		log::add('Freebox_OS', $type_Log, '| :fg-info:───▶︎ ' . (__('Mise à jour de Version dans la base', __FILE__)) . ' ::/fg: ' . config::byKey('FREEBOX_API', 'Freebox_OS'));
		log::add('Freebox_OS', $type_Log, '└────────────────────');
		Free_CreateEq::createEq('box', true, 'Debug');
		return $API_version;
	}
	public static function updateLogicalID($eq_version, $_update = false)
	{
		$eqLogics = eqLogic::byType('Freebox_OS');
		$logicalinfo = Freebox_OS::getlogicalinfo();
		if ($eq_version == 2) {
			if (config::byKey('TYPE_FREEBOX_TILES', 'Freebox_OS') == 'OK') {
				if (!is_object(config::byKey('FREEBOX_TILES_CRON', 'Freebox_OS'))) {
					config::save('FREEBOX_TILES_CRON', init(1), 'Freebox_OS');
					Free_CreateTil::createTil('SetSettingTiles');
				}
			}
		}
		foreach ($eqLogics as $eqLogic) {
			if ($eqLogic->getConfiguration('type') === 'alarm_control') {
				$type_eq = 'alarm_control';
			} else if ($eqLogic->getConfiguration('type') === 'camera') {
				$type_eq = 'camera';
			} else if ($eqLogic->getConfiguration('type') === 'freeplug') {
				$type_eq = 'freeplug';
			} else if ($eqLogic->getConfiguration('type') === 'parental') {
				$type_eq = 'parental_controls';
			} else if ($eqLogic->getConfiguration('type') === 'player') {
				$type_eq = 'player';
			} else if ($eqLogic->getConfiguration('type') === 'VM') {
				$type_eq = 'VM';
			} else {
				$type_eq = $eqLogic->getLogicalId();
			}
			if ($eqLogic->getConfiguration('VersionLogicalID', 0) == $eq_version) continue;

			log::add('Freebox_OS', 'debug', '│ Fonction updateLogicalID : Update eqLogic : ' . $eqLogic->getLogicalId() . ' - ' . $eqLogic->getName());
			switch ($type_eq) {
				case 'airmedia':
					$eqLogic->setLogicalId($logicalinfo['airmediaID']);
					//$eqLogic->setName($logicalinfo['airmediaName']);
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'system');
					break;
				case 'alarm_control':
					// Update spécifique pour l'alarme
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'tiles');
					//$eqLogic->save();
					break;
				case 'camera':
					// Update spécifique pour les caméras
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'nodes');
					break;
				case 'connexion':
					$eqLogic->setLogicalId($logicalinfo['connexionID']);
					//$eqLogic->setName($logicalinfo['connexionName']);
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'system');
					break;
				case 'disk':
					$eqLogic->setLogicalId($logicalinfo['diskID']);
					//$eqLogic->setName($logicalinfo['diskName']);
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'system');
					break;
				case 'downloads':
					$eqLogic->setLogicalId($logicalinfo['downloadsID']);
					//$eqLogic->setName($logicalinfo['downloadsName']);
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'system');
					break;
				case 'freeplug':
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'system');
					break;
				case 'homeadapters':
					$eqLogic->setLogicalId($logicalinfo['homeadaptersID']);
					//$eqLogic->setName($logicalinfo['homeadaptersName']);
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'tiles_SP');
					break;
				case 'parental_controls':
					//Pour les contrôles parentaux
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'parental_controls');
					break;
				case 'phone':
					$eqLogic->setLogicalId($logicalinfo['phoneID']);
					//$eqLogic->setName($logicalinfo['phoneName']);
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'system');
					break;
				case 'player':
					//Pour les players
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'system');
					break;
				case 'management':
					$eqLogic->setLogicalId($logicalinfo['managementID']);
					//$eqLogic->setName($logicalinfo['networkName']);
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'system');
					break;
				case 'network':
					$eqLogic->setLogicalId($logicalinfo['networkID']);
					//$eqLogic->setName($logicalinfo['networkName']);
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'system');
					break;
				case 'netshare':
					$eqLogic->setLogicalId($logicalinfo['netshareID']);
					//$eqLogic->setName($logicalinfo['netshareName']);
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'system');
					break;
				case 'networkwifiguest':
					$eqLogic->setLogicalId($logicalinfo['networkwifiguestID']);
					//$eqLogic->setName($logicalinfo['networkwifiguestName']);
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'system');
					break;
				case 'LCD':
					$eqLogic->setLogicalId($logicalinfo['LCDID']);
					//$eqLogic->setName($logicalinfo['LCDName']);
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'system');
					break;
				case 'system':
					$eqLogic->setLogicalId($logicalinfo['systemID']);
					//$eqLogic->setName($logicalinfo['systemName']);
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'system');
					break;
				case 'VM':
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'system');
					break;
				case 'wifi':
					$eqLogic->setLogicalId($logicalinfo['wifiID']);
					//$eqLogic->setName($logicalinfo['wifiName']);
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'system');
					break;
				default:
					$eqLogic->setConfiguration('eq_group', 'tiles');
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					break;
			}
			$eqLogic->save(true);
			log::add('Freebox_OS', 'debug', '│ .' . (__('Fonction passe en version en', __FILE__)) . ' V' . $eq_version  . ' - ' . (__('Pour', __FILE__)) . ' : ' . $eqLogic->getLogicalId() . ' - ' . $eqLogic->getName());
			//if (!$_update) $eqLogic->setName($eqName);

		}
	}
}

class Freebox_OSCmd extends cmd
{
	public function dontRemoveCmd()
	{
		if ($this->getLogicalId() == 'refresh') {
			return true;
		}
		return false;
	}
	public function execute($_options = array())
	{
		//log::add('Freebox_OS', 'debug', '********************  Action pour l\'action : ' . $this->getName());
		$array = cache::byKey("Freebox_OS::actionlist")->getValue();
		if (!is_array($array)) {
			$array = [];
		}
		$update = array(
			'This' => $this,
			'LogicalId' => $this->getLogicalId(),
			'SubType' => $this->getSubType(),
			'Name' => $this->getName(),
			'Value' => $this->getvalue(),
			'Config' => $this->getConfiguration('logicalId'),
			'EqLogic' => $this->getEqLogic(),
			'NameEqLogic' => $this->getEqLogic()->getName(),
			'LogicalIdEqLogic' => $this->getEqLogic()->getLogicalId(),
			'Options' => $_options,
		);

		array_push($array, $update);
		cache::set("Freebox_OS::actionlist", $array);
		//Free_Update::UpdateAction($this->getLogicalId(), $this->getSubType(), $this->getName(), $this->getvalue(), $this->getConfiguration('logicalId'), $this->getEqLogic(), $_options, $this);
	}
}
