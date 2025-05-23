<?php
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
function Freebox_OS_install()
{
	$cron = cron::byClassAndFunction('Freebox_OS', 'RefreshToken');
	if (!is_object($cron)) {
		$cron = new cron();
		$cron->setClass('Freebox_OS');
		$cron->setFunction('RefreshToken');
		$cron->setEnable(1);
		//$cron->setDeamon(1);
		$cron->setDeamonSleepTime(1);
		$cron->setSchedule('*/30 * * * *');
		$cron->setTimeout('10');
		$cron->save();
	}
	$cron = cron::byClassAndFunction('Freebox_OS', 'FreeboxPUT');
	if (!is_object($cron)) {
		$cron = new cron();
		$cron->setClass('Freebox_OS');
		$cron->setFunction('FreeboxPUT');
		$cron->setDeamon(1);
		$cron->setEnable(1);
		$cron->setSchedule('* * * * *');
		//$cron->setDeamonSleepTime(1);
		$cron->setTimeout('1440');
		$cron->save();
	}
	/*$cron = cron::byClassAndFunction('Freebox_OS', 'FreeboxAPI');
	if (!is_object($cron)) {
		$cron = new cron();
		$cron->setClass('Freebox_OS');
		$cron->setFunction('FreeboxAPI');
		//$cron->setDeamon(1);
		$cron->setEnable(1);
		$cron->setSchedule('0 0 * * 1');
		//$cron->setDeamonSleepTime(1);
		$cron->setTimeout('15');
		$cron->save();
	}*/
	updateConfig();
}
function Freebox_OS_update()
{
	$cron = cron::byClassAndFunction('Freebox_OS', 'RefreshToken');
	if (!is_object($cron)) {
		$cron = new cron();
		$cron->setClass('Freebox_OS');
		$cron->setFunction('RefreshToken');
		$cron->setEnable(1);
		$cron->setSchedule('*/30 * * * *');
		$cron->setTimeout('10');
		$cron->save();
	}
	$cron = cron::byClassAndFunction('Freebox_OS', 'FreeboxPUT');
	if (!is_object($cron)) {
		$cron = new cron();
		$cron->setClass('Freebox_OS');
		$cron->setFunction('FreeboxPUT');
		$cron->setEnable(1);
		$cron->setDeamon(1);
		$cron->setSchedule('* * * * *');
		$cron->setTimeout('1440');
		$cron->save();
	}
	$cron = cron::byClassAndFunction('Freebox_OS', 'FreeboxAPI');
	if (is_object($cron)) {
		$cron->stop();
		$cron->remove();
	}

	try {
		$plugin = plugin::byId('Freebox_OS');
		log::add('Freebox_OS', 'debug', '│ ' . (__('Mise à jour Plugin', __FILE__)));

		/*$WifiEX = 0;
		foreach (eqLogic::byLogicalId('Wifi', 'Freebox_OS', true) as $eqLogic) {
			$WifiEX = 1;
			log::add('Freebox_OS', 'debug', '│ Etape 1/3 : Migration Wifi déjà faite (' . $WifiEX . ')');
		}
		if ($WifiEX != 1) {
			$Wifi = Freebox_OS::AddEqLogic('Wifi', 'wifi', 'default', false, null, null);
			$link_IA = $Wifi->getId();
			log::add('Freebox_OS', 'debug', '│ Etape 1/3 : Création Equipement WIFI -- ID N° : ' . $link_IA);
		}*/

		log::add('Freebox_OS', 'debug', '│ Etape 1/4 : ' . (__('Mise à jour des nouveautées + corrections des commandes', __FILE__)));


		log::add('Freebox_OS', 'debug', '[WARNING] - ' . (__('DEBUT DE NETTOYAGE LORS MIGRATION DE BOX', __FILE__)));
		if (config::byKey('TYPE_FREEBOX', 'Freebox_OS') == 'fbxgw9r') {
			// Amélioration - Suppression des commandes en cas de migration de freebox de la delta a l'ultra
			removeLogicId('temp_cpu_cp_master');
			removeLogicId('temp_cpu_ap');
			removeLogicId('temp_cpu_cp_slave');
			removeLogicId('temp_hdd0'); // Température disque Dur
			removeLogicId('temp_t1');
			removeLogicId('temp_t2');
			removeLogicId('temp_t3');
			removeLogicId('fan1_speed');
			// Amélioration - Suppression des commandes en cas de migration de freebox de la revolution a l'ultra
			removeLogicId('temp_cpum');
			removeLogicId('temp_cpub');
			removeLogicId('temp_sw');
			removeLogicId('tx_used_rate_xdsl');
			removeLogicId('rx_used_rate_xdsl');
			removeLogicId('rx_max_rate_xdsl');
		}
		log::add('Freebox_OS', 'debug', '[  OK  ] - ' . (__('FIN DE NETTOYAGE LORS MIGRATION DE BOX', __FILE__)));

		log::add('Freebox_OS', 'debug', '│ Etape 2/4 : ' . (__('Changement de nom de certains équipements', __FILE__)));
		$eqLogics = eqLogic::byType($plugin->getId());
		foreach ($eqLogics as $eqLogic) {
			// Changement Id pour Wifi
			UpdateLogicalId($eqLogic, 'listblack', 'blacklist', null);
			UpdateLogicalId($eqLogic, 'listwhite', 'whitelist', null);
			UpdateLogicalId($eqLogic, 'wifimac_filter_state', 'mac_filter_state', null);
			UpdateLogicalId($eqLogic, 'wifiPlanning', 'use_planning', null);
			//Changement Téléphonie 20240725
			UpdateLogicalId($eqLogic, 'nbmissed', 'missed', null);
			UpdateLogicalId($eqLogic, 'nbaccepted', 'accepted', null);
			UpdateLogicalId($eqLogic, 'nboutgoing', 'outgoing', null);
			//Changement Nom Support Mode Éco-WiFi 20250111
			UpdateLogicalId($eqLogic, 'has_eco_wifi', null, null, __('Support Mode Éco-WiFi', __FILE__));
			UpdateLogicalId($eqLogic, 'planning_mode', null, null, __('Etat Mode de veille planning', __FILE__));
			UpdateLogicalId($eqLogic, 'wifiPlanningOn', 'use_planningOn', null, null);
			UpdateLogicalId($eqLogic, 'wifiPlanningOff', 'use_planningOff', null, null);
			UpdateLogicalId($eqLogic, 'wifiOn', 'wifiStatutOn', null, null);
			UpdateLogicalId($eqLogic, 'wifiOff', 'wifiStatutOff', null, null);
		}
		$eq_version = '2.2';
		Freebox_OS::updateLogicalID($eq_version, true);
		log::add('Freebox_OS', 'debug', '│ Etape 3/4 : ' . (__('Mise à jour du paramétrage Plugin tiles', __FILE__)));
		if ($eq_version === '2') {
			/* CRON GLOBAL TITLES
			if (config::byKey('TYPE_FREEBOX_TILES', 'Freebox_OS') == 'OK') {
				$Config_KEY = config::byKey('FREEBOX_TILES_CRON', 'Freebox_OS');
				if (empty($Config_KEY)) {
					config::save('FREEBOX_TILES_CRON', '1', 'Freebox_OS');
					Free_CreateTil::createTil('SetSettingTiles');
				}
			}*/
			/* UPDATE CMD BY CMD
			$Config_KEY = config::byKey('FREEBOX_TILES_CmdbyCmd', 'Freebox_OS');
			if (empty($Config_KEY)) {
				config::save('FREEBOX_TILES_CmdbyCmd', '1', 'Freebox_OS');
			}*/
		}
		log::add('Freebox_OS', 'debug', '│ Etape 4/4 : ' . (__('Création ou mise à jour des variables nécessaire pour le plugin', __FILE__)));
		updateConfig();

		//message::add('Freebox_OS', '{{Cette mise nécessite de lancer les divers Scans afin de bénéficier des nouveautés et surtout des correctifs}}');
	} catch (Exception $e) {
		$e = print_r($e, 1);
		log::add('Freebox_OS', 'error', 'Freebox_OS update ERROR : ' . $e);
	}
}
function Freebox_OS_remove()
{
	$cron = cron::byClassAndFunction('Freebox_OS', 'RefreshToken');
	if (is_object($cron)) {
		$cron->stop();
		$cron->remove();
	}
	$cron = cron::byClassAndFunction('Freebox_OS', 'FreeboxPUT');
	if (is_object($cron)) {
		$cron->stop();
		$cron->remove();
	}
	$cron = cron::byClassAndFunction('Freebox_OS', 'FreeboxGET');
	if (is_object($cron)) {
		$cron->stop();
		$cron->remove();
	}
	$cron = cron::byClassAndFunction('Freebox_OS', 'FreeboxAPI');
	if (is_object($cron)) {
		$cron->stop();
		$cron->remove();
	}
}

function updateLogicalId($eqLogic, $from, $to, $_historizeRound = null, $name = null, $unite = null)
{
	$Cmd = $eqLogic->getCmd(null, $from);
	if (is_object($Cmd)) {
		if ($to != null) {
			$Cmd->setLogicalId($to);
		}
		if ($_historizeRound != null) {
			$Cmd->setConfiguration('historizeRound', $_historizeRound);
		}
		if ($name != null) {
			$Cmd->setName($name);
		}
		if ($unite != null) {
			if ($unite == 'DELETE') {
				$unite = null;
			}
			$Cmd->setUnite($unite);
		}
		$Cmd->save();
	}
}
function removeLogicId($cmdDel)
{
	$eqLogics = eqLogic::byType('Freebox_OS');
	foreach ($eqLogics as $eqLogic) {
		$cmd = $eqLogic->getCmd(null, $cmdDel);
		if (is_object($cmd)) {
			$cmd->remove();
		}
	}
}

function updateConfig()
{
	$FREEBOX_API = 'v13';
	$Config_KEY = 'FREEBOX_SERVER_IP';
	$Config_value = 'mafreebox.freebox.fr';
	$Config = config::byKey($Config_KEY, 'Freebox_OS');
	if (empty($Config)) {
		config::save($Config_KEY, $Config_value, 'Freebox_OS');
	}
	$Config_KEY = 'FREEBOX_SERVER_APP_NAME';
	$Config_value = 'Plugin Freebox OS';
	$Config = config::byKey($Config_KEY, 'Freebox_OS');
	if (empty($Config)) {
		config::save($Config_KEY, $Config_value, 'Freebox_OS');
	}
	$Config_KEY = 'FREEBOX_SERVER_APP_ID';
	$Config_value = 'plugin.freebox.jeedom';
	$Config = config::byKey($Config_KEY, 'Freebox_OS');
	if (empty($Config)) {
		config::save($Config_KEY, $Config_value, 'Freebox_OS');
	}
	$Config_KEY = 'FREEBOX_API';
	$Config_value = $FREEBOX_API;
	$Config = config::byKey($Config_KEY, 'Freebox_OS');
	if (empty($Config)) {
		config::save($Config_KEY, $Config_value, 'Freebox_OS');
	}
	$Config_KEY = 'FREEBOX_API_DEFAUT';
	$Config_value = $FREEBOX_API;
	$Config = config::byKey($Config_KEY, 'Freebox_OS');
	if (empty($Config)) {
		config::save($Config_KEY, $Config_value, 'Freebox_OS');
	}
	if (config::byKey($Config, 'Freebox_OS', 0) != $$Config_value) {
		config::save($Config_KEY, $Config_value, 'Freebox_OS');
	}
	$Config_KEY = 'FREEBOX_SERVER_DEVICE_NAME';
	$Config_value = config::byKey("name");
	$Config = config::byKey($Config_KEY, 'Freebox_OS');
	if (empty($Config)) {
		config::save($Config_KEY, $Config_value, 'Freebox_OS');
	}
	$Config_KEY = 'FREEBOX_REBOOT_DEAMON';
	$Config_value = FALSE;
	$Config = config::byKey($Config_KEY, 'Freebox_OS');
	if (empty($Config)) {
		config::save($Config_KEY, $Config_value, 'Freebox_OS');
	}
	$version = 1;
	if (config::byKey('FREEBOX_CONFIG_V', 'Freebox_OS', 0) != $version) {
		Freebox_OS::resetConfig();
		config::save('FREEBOX_CONFIG_V', $version, 'Freebox_OS');
	}
}
