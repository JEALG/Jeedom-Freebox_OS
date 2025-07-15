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
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class Free_CreateTV
{
    public static function createTV($create = 'default')
    {
        $logicalinfo = Freebox_OS::getlogicalinfo();
        if (version_compare(jeedom::version(), "4", "<")) {
            $templatecore_V4 = null;
        } else {
            $templatecore_V4  = 'core::';
        };
        switch ($create) {
            default:
                Free_CreateTV::createTV_player($logicalinfo, $templatecore_V4);
                break;
        }
    }
    private static function createTV_player($logicalinfo, $templatecore_V4)
    {
        log::add('Freebox_OS', 'debug', '┌── :fg-success:' . (__('Début de création des commandes pour', __FILE__)) . ' ::/fg: ' . $logicalinfo['playerName'] . ' ──');
        $Free_API = new Free_API();
        $TemplatePlayer = 'Freebox_OS::Player';
        $order = 0;
        $result = $Free_API->universal_get('universalAPI', null, null, 'player', false, true, true);
        $nb_player = 1;
        if (isset($result['result'])) {
            $result = $result['result'];
            if ($result != null) {
                foreach ($result as $Equipement) {
                    if ($Equipement['device_name'] == null) {
                        $_devicename = 'Player - ' . $Equipement['id'] . ' - ' . $Equipement['mac'];
                    } else {
                        $_devicename = $Equipement['device_name'];
                    }
                    log::add('Freebox_OS', 'debug', '| ───▶︎ ' . (__('CONFIGURATION PLAYER', __FILE__)) . ' : ' . $nb_player . ' - ' . $_devicename);
                    $player_ID = $Equipement['mac'];
                    $player_STATE = 'KO';
                    $player_API_VERSION = '_';
                    $player_ID_MAC = 'MAC';
                    if (isset($Equipement['id'])) {
                        $player_log = ' -- ' . (__('Il n\'est pas possible de récupérer le status du Player donc pas de création de la commande d\'état', __FILE__));

                        if ($Equipement['id']) {
                            if ($Equipement['id'] != null) {
                                if (isset($Equipement['api_version'])) {
                                    $player_API_VERSION = 'v'  . $Equipement['api_version'];
                                    $player_API_VERSION = strstr($player_API_VERSION, '.', true);
                                }
                                $results_playerID = $Free_API->universal_get('universalAPI', null, null, 'player/' . $Equipement['id'] . '/api/' . $player_API_VERSION . '/status', true, true, false);
                                $player_ID = $Equipement['id'];
                                if (isset($results_playerID['power_state'])) {
                                    log::add('Freebox_OS', 'debug', '| ───▶︎ :fg-info:' . __('ETAT PLAYER', __FILE__) . ' ::/fg: ' . $results_playerID['power_state']);
                                    if ($results_playerID['power_state'] == 'running' || $results_playerID['power_state'] == 'standby') {
                                        $player_STATE = 'OK';
                                        $player_log = ' -- ' . (__('Il est possible de récupérer le status du Player', __FILE__));
                                    }
                                    $player_ID_MAC = 'ID';
                                    log::add('Freebox_OS', 'debug', '| ───▶︎ :fg-info:' . __('PLAYER', __FILE__) . ' ::/fg:  ' . $_devicename . ' -- Id : ' . $Equipement['id'] . $player_log);
                                } else {
                                    log::add('Freebox_OS', 'debug', '|:fg-warning: ───▶︎ ' . __('PLAYER', __FILE__) . ' : ' . $_devicename . ' -- Id : ' . $Equipement['id'] . $player_log . ':/fg:');
                                }
                            } else {
                                log::add('Freebox_OS', 'debug', '|:fg-warning: ───▶︎ ' . __('PLAYER', __FILE__) . ' : ' . $_devicename . ' -- Mac : ' . $Equipement['mac'] . ' -- ' . __('L\'ID est vide', __FILE__) . ' ───▶︎ ' . $player_log . ':/fg:');
                            }

                            $Player_config = array(
                                "player_ID_MAC" =>  $player_ID_MAC,
                                "player_API_VERSION" => $player_API_VERSION,
                                "player_MAC_ADDRESS" => $Equipement['mac']
                            );
                            $EqLogic = Freebox_OS::AddEqLogic($_devicename, 'player_' . $player_ID, 'multimedia', true, 'player', $player_ID, $player_ID, '*/5 * * * *', null, $player_STATE, 'system', true, $Player_config);
                            $order = 10;
                            $EqLogic->AddCommand(__('Type', __FILE__), 'stb_type', 'info', 'string', null, null, null, 0, 'default', 'default', 0, null, 0, 'default', 'default', $order++, '0', false, false);
                            $EqLogic->AddCommand(__('Modèle', __FILE__), 'device_model', 'info', 'string', null, null, null, 0, 'default', 'default', 0, null, 0, 'default', 'default', $order++, '0', false, false);
                            if (isset($Equipement['api_version'])) {
                                $EqLogic->AddCommand(__('Version', __FILE__), 'api_version', 'info', 'string', null, null, null, 0, 'default', 'default', 0, null, 0, 'default', 'default', $order++, '0', false, false);
                            }
                            if ($player_STATE == 'OK') {
                                $iconvolume = 'fas fa-volume-down icon_green';
                                $iconmediactrl = 'fas fa-tv icon_green';
                                $iconmute = 'fas fa-volume-mute';
                                $iconmuteoff = 'fas fa-volume-mute icon_green';
                                $iconmuteon = 'fas fa-volume-mute icon_red';
                                $iconReboot = 'fas fa-sync icon_red';
                                $EqLogic->AddCommand(__('Etat', __FILE__), 'power_state', 'info', 'string', $TemplatePlayer, null, null, 1, 'default', 'default', 0, null, 0, 'default', 'default', $order++, '0', false, false);
                                $EqLogic->AddCommand(__('Nom de la chaîne', __FILE__), 'channelName', 'info', 'string', null, null, null, 1, 'default', 'default', 0, null, 1, 'default', 'dafault', $order++, '0', false, false);
                                //$listchanel = Free_CreateTV::listTV_player($logicalinfo, $templatecore_V4);
                                //$EqLogic->AddCommand(__('Liste des chaînes', __FILE__), 'uuid', 'action', 'select', null, null, null, 1, 'default', 'default', 0,  $iconmediactrl, 1, 'default', 'default', $order++, '0', 'default', false, null, true, null, null, null, null, null, null, null, null, $listchanel);
                                //36
                                $PARATemplate = array(
                                    "step" => "1"
                                );
                                $channelNumber = $EqLogic->AddCommand(__('Numéro de chaîne', __FILE__), 'channelNumber', 'info', 'numeric', null, null, null, 0, 'default', 'default', 0, null, 1, 'default', 'dafault', $order++, '0', false, false, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, $PARATemplate);
                                //Ligne ci-dessous a désactiver lors de la publication
                                $EqLogic->AddCommand(__('Choix de la chaîne', __FILE__), 'channel', 'action', 'slider', "core::button", null, null, 1, $channelNumber, 'channelNumber', 0, null, 1, '0', 3500, $order++, '0', false, false, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, $PARATemplate);
                                //
                                $package = $EqLogic->AddCommand(__('Nom Application', __FILE__), 'package', 'info', 'string', null, null, null, 0, 'default', 'default', 0, null, 1, 'default', 'dafault', $order++, '0', false, false);
                                $listAPP = "app:fr.freebox.tv|" . __('Allumer la TV avec la dernière chaine ', __FILE__) . ";app:fr.freebox.radio|" . __('Radio', __FILE__) . ";https://www.netflix.com|Netfix" . ";https://www.primevideo.com|Prime vidéo" . ";https://www.youtube.com|Youtube" .  ";pvr://|" . __('Mes enregistrements', __FILE__) . ";vodservice://replay|Replay";
                                $EqLogic->AddCommand(__('Lancer Application', __FILE__), 'app', 'action', 'select', null, null, null, 1, $package, 'package', 0,  $iconmediactrl, 1, 'default', 'default', $order++, '0', 'default', false, null, true, null, null, null, null, null, null, null, null, $listAPP);

                                $listValue = "play|" . __('Play', __FILE__) . ";pause|" . __('Pause', __FILE__) . ";play_pause|" . __('Play - Pause', __FILE__) . ";stop|" . __('Stop', __FILE__) . ";prev|" . __('Précédent', __FILE__) . ";next|" . __('Suivant', __FILE__) . ";select_stream|" . __('Sélectionner la qualité du flux', __FILE__) . ";select_audio_track|" . __('Sélectionner la piste audio', __FILE__) . ";select_srt_track|" . __('Sélectionner les sous-titres', __FILE__) . ";record|" . __('Enregistrement', __FILE__) . ";record_stop  |" . __('Enregistrement Stop', __FILE__);
                                $playback_state = $EqLogic->AddCommand(__('Etat du player', __FILE__), 'playback_state', 'info', 'string', null, null, null, 1, 'defaut', 'default', 0,  $iconmediactrl, 1, 'default', 'default', $order++, '0', 'default', false, null, true, null, null, null, null, null, null, null, null, null);
                                $EqLogic->AddCommand(__('Contrôle player', __FILE__), 'mediactrl', 'action', 'select', null, null, null, 1, $playback_state, 'playback_state', 0,  $iconmediactrl, 1, 'default', 'default', $order++, '0', 'default', false, null, true, null, null, null, null, null, null, null, null, $listValue);
                                $Volume = $EqLogic->AddCommand(__('Volume', __FILE__), 'volume', 'info', 'numeric', null, null, 'SWITCH_STATE', 0, null, null, 0, $iconvolume, 1, null, null, $order++, 1, true, 'never', null, true, null, null, null, null, null, null, null, null);
                                $EqLogic->AddCommand(__('Choix du Volume', __FILE__), 'volume', 'action', 'slider', null, null, null, 1, $Volume, 'volume', 0,  $iconvolume, 1, '0', 100, $order++, '0', false, false);
                                $Mute = $EqLogic->AddCommand(__('Mute', __FILE__), 'mute', 'info', 'binary', null, null, 'SWITCH_STATE', 0, null, null, 0, $iconmute, 1, null, null, $order++, 1, true, 'never', null, true, null, null, null, null, null, null, null, null);
                                $EqLogic->AddCommand(__('Mute On', __FILE__), 'muteOn', 'action', 'other', 'core::toggleLine', null, 'SWITCH_ON', 1,  $Mute, 'mute', 0, $iconmuteon, 1, null, null, $order++, '0', true, 'never', null, true, null, null, null, null, null, null, null, null);
                                $EqLogic->AddCommand(__('Mute Off', __FILE__), 'muteOff', 'action', 'other', 'core::toggleLine', null, 'SWITCH_OFF', 1,  $Mute, 'mute', 0, $iconmuteoff, 1, null, null, $order++, '0', true, 'never', null, true, null, null, null, null, null, null, null, null);
                                $EqLogic->AddCommand(__('Redémarrage', __FILE__), 'reboot', 'action', 'other',  $templatecore_V4 . 'line', null, null, 1, 'default', 'default', 0, $iconReboot, true, 'default', 'default',   $order++, '0', true, null, null, true, null, null, null, null, null, null, true, null, null, null, null, null, null, null, null, null, null);
                                if (config::byKey('TYPE_FREEBOX_MODE', 'Freebox_OS') == 'router') {
                                    log::add('Freebox_OS', 'debug', '|:fg-success: ───▶︎ ' . (__('BOX EN MODE ROUTER : Création de la commande Adresse IPV4 du player', __FILE__)) . ':/fg:');
                                    $EqLogic->AddCommand(__('Adresse IPV4 du player', __FILE__), 'addr', 'info', 'string', null, null, null, 0, 'default', 'default', 0, null, 0, 'default', 'dafault', $order++, '0', false, false);
                                } else {
                                    log::add('Freebox_OS', 'debug', '|:fg-warning: ───▶︎ ' . (__('BOX EN MODE BRIDGE : Pas de création de la commande Adresse IPV4 du player', __FILE__)) . ':/fg:');
                                }
                            }
                        }
                    } else {
                        $Player_config = array(
                            "player_ID_MAC" =>  $player_ID_MAC,
                            "player_API_VERSION" => $player_API_VERSION,
                            "player_MAC_ADDRESS" => $Equipement['mac']
                        );
                        $EqLogic = Freebox_OS::AddEqLogic($_devicename, 'player_' . $player_ID, 'multimedia', true, 'player', $player_ID, $player_ID, '*/5 * * * *', null, $player_STATE, 'system', true, $Player_config);

                        log::add('Freebox_OS', 'debug', '|:fg-warning: ───▶︎ ' . (__('AUCUNE INFO supplémentaire disponible pour le player ou absence d\'ID', __FILE__)) . ':/fg:');
                    }
                    $order = 0;
                    $EqLogic->AddCommand(__('Mac', __FILE__), 'mac', 'info', 'string', null, null, null, 0, 'default', 'default', 0, null, 0, 'default', 'default', $order++, '0', false, false);
                    $order = 100;
                    $EqLogic->AddCommand(__('Disponible sur le réseau', __FILE__), 'reachable', 'info', 'binary', null, null, null, 1, 'default', 'default', 0, null, 1, 'default', 'default', $order++, '0', false, false);
                    $EqLogic->AddCommand(__('Disponible depuis le', __FILE__), 'last_time_reachable', 'info', 'string', null, null, null, 1, 'default', 'default', 0, null, 1, 'default', 'default', $order++, '0', false, false);
                    $EqLogic->AddCommand(__('API Disponible', __FILE__), 'api_available', 'info', 'binary', null, null, null, 1, 'default', 'default', 0, null, 0, 'default', 'default', $order++, '0', false, false);
                    log::add('Freebox_OS', 'debug', '| ───▶︎ ' . (__('FIN CONFIGURATION PLAYER', __FILE__)) . ' : ' . $nb_player . ' / ' . $_devicename);
                    $nb_player++;
                }
            } else {
                log::add('Freebox_OS', 'debug', '|:fg-warning: ───▶︎ ' . (__('PAS DE', __FILE__)) . ' ' . $logicalinfo['playerName'] . ' ' . (__('SUR VOTRE BOX', __FILE__)) . ':/fg:');
            }
        }
        log::add('Freebox_OS', 'debug', '└────────────────────');
    }
    private static function listTV_player($logicalinfo, $templatecore_V4)
    {
        log::add('Freebox_OS', 'debug', '┌── :fg-success:' . (__('Début de création de la liste des chaines TV', __FILE__)) . ' ::/fg: ' . ' ──');
        $Free_API = new Free_API();
        $result = $Free_API->universal_get('universalAPI', null, null, 'tv/channels/', true, true, true);
        $list = null;
        if (isset($result['result'])) {
            $resultlist = $result['result'];
            foreach ($resultlist  as $listUUID) {
                $UUID = $listUUID['uuid'];
                if ($list == null) {
                    $list = $listUUID['uuid'] . '|' . $listUUID['name'];
                } else {
                    $list .= ';' . $listUUID['uuid'] . '|' . $listUUID['name'];
                }
            }
        }
        log::add('Freebox_OS', 'debug', '└────────────────────');
        return $list;
    }
}
