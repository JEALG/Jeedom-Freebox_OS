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

class Free_Update
{
    public static function UpdateAction($logicalId, $logicalId_type, $logicalId_name, $logicalId_value, $logicalId_conf, $logicalId_eq, $_options, $_cmd)
    {
        if ($logicalId != 'refresh' && $logicalId != 'WakeonLAN') {
            //log::add('Freebox_OS', 'debug', '┌───────── Update commande ');
            //log::add('Freebox_OS', 'debug', '│ Connexion sur la freebox pour mise à jour de : ' . $logicalId_name);
        }
        $Free_API = new Free_API();
        $API_version = config::byKey('FREEBOX_API', 'Freebox_OS');

        if ($logicalId_eq->getconfiguration('type') == 'parental' || $logicalId_eq->getConfiguration('type') == 'player'  || $logicalId_eq->getConfiguration('type') == 'freeplug' || $logicalId_eq->getConfiguration('type') == 'VM') {
            $update = $logicalId_eq->getconfiguration('type');
        } else {
            $update = $logicalId_eq->getLogicalId();
        }
        switch ($update) {
            case 'airmedia':
                if ($logicalId != 'refresh') {
                    Free_Update::update_airmedia($logicalId, $logicalId_type, $logicalId_eq, $Free_API, $_options, $_cmd, $logicalId_value);
                }
                if ($logicalId != 'media' && $logicalId != 'password') {
                    Free_Refresh::RefreshInformation($logicalId_eq->getId());
                    log::add('Freebox_OS', 'debug', '└─────────');
                }
                break;
            case 'connexion':
                Free_Refresh::RefreshInformation($logicalId_eq->getId());
                break;
            case 'disk':
                Free_Refresh::RefreshInformation($logicalId_eq->getId());
                break;
            case 'downloads':
                Free_Update::update_download($logicalId, $logicalId_type, $logicalId_eq, $Free_API, $_options);
                Free_Refresh::RefreshInformation($logicalId_eq->getId());
                break;
            case 'freeplug':
                if ($logicalId != 'refresh') {
                    Free_Update::update_freeplug($logicalId, $logicalId_type, $logicalId_eq, $Free_API, $_options, $_cmd, $update);
                }
                Free_Refresh::RefreshInformation($logicalId_eq->getId());
                break;
            case 'LCD':
                if ($logicalId != 'refresh') {
                    Free_Update::update_LCD($logicalId, $logicalId_type, $logicalId_eq, $Free_API, $_options);
                }
                Free_Refresh::RefreshInformation($logicalId_eq->getId());
                break;
            case 'homeadapters':
                if ($logicalId != 'refresh') {
                    Free_Update::update_homeadapters($logicalId, $logicalId_type, $logicalId_eq, $Free_API, $_options, $_cmd, $update);
                }
                Free_Refresh::RefreshInformation($logicalId_eq->getId());
                break;
            case 'parental':
                if ($logicalId != 'refresh') {
                    Free_Update::update_parental($logicalId, $logicalId_type, $logicalId_eq, $Free_API, $_options, $_cmd, $update);
                }
                Free_Refresh::RefreshInformation($logicalId_eq->getId());
                break;
            case 'phone':
                Free_Update::update_phone($logicalId, $logicalId_type, $logicalId_eq, $Free_API, $_options);
                Free_Refresh::RefreshInformation($logicalId_eq->getId());
                break;
            case 'player':
                if ($logicalId != 'refresh') {
                    Free_Update::update_player($logicalId, $logicalId_type, $logicalId_eq, $Free_API, $_options, $_cmd, $update);
                }
                Free_Refresh::RefreshInformation($logicalId_eq->getId());
                break;
            case 'management':
                $typerefresh = Free_Update::update_management($logicalId, $logicalId_type, $logicalId_eq, $Free_API, $_options);
                if ($logicalId == 'start') {
                    Free_Refresh::RefreshInformation($logicalId_eq->getId(), $typerefresh);
                }
                break;
            case 'netshare':
                Free_Update::update_netshare($logicalId, $logicalId_type, $logicalId_eq, $Free_API, $_options);
                Free_Refresh::RefreshInformation($logicalId_eq->getId());
                break;
            case 'network':
            case 'networkwifiguest':
                if ($logicalId != 'refresh') {
                    Free_Update::update_network($logicalId, $logicalId_type, $logicalId_eq, $Free_API, $_options, $update);
                }
                // A supprimer lors de la prochaine mise a jour
                if ($logicalId != 'WakeonLAN') {
                    Free_Refresh::RefreshInformation($logicalId_eq->getId());
                }
                break;
            case 'system':
                Free_Update::update_system($logicalId, $logicalId_type, $logicalId_eq, $Free_API, $_options);
                if ($logicalId != 'reboot') {
                    Free_Refresh::RefreshInformation($logicalId_eq->getId());
                }
                break;
            case 'VM':
                if ($logicalId != 'refresh') {
                    Free_Update::update_VM($logicalId, $logicalId_type, $logicalId_eq, $Free_API, $_options, $update);
                }
                break;
            case 'wifi':
                if ($logicalId != 'refresh') {
                    Free_Update::update_wifi($logicalId, $logicalId_type, $logicalId_eq, $Free_API, $_options);
                }
                Free_Refresh::RefreshInformation($logicalId_eq->getId());
                break;
            default:
                Free_Update::update_default($logicalId, $logicalId_type, $logicalId_eq, $Free_API, $_options, $_cmd, $logicalId_conf);
                //if ($logicalId == 'refresh' || config::byKey('FREEBOX_TILES_CRON', 'Freebox_OS') == 0) {
                Free_Refresh::RefreshInformation($logicalId_eq->getId());
                //}
                break;
        }
    }

    private static function update_airmedia($logicalId, $logicalId_type, $logicalId_eq, $Free_API, $_options, $_cmd, $logicalId_value)
    {
        $logicalinfo = Freebox_OS::getlogicalinfo();
        //$result = $Free_API->universal_get('universalAPI', null, null, 'airmedia/receivers', true, true, null);

        foreach ($logicalId_eq->getCmd('info') as $Cmd) {
            if (is_object($Cmd)) {
                if ($Cmd->getLogicalId() == 'receivers_info') {
                    if ($logicalId == 'receivers') {
                        $logicalId_eq->checkAndUpdateCmd($Cmd->getLogicalId(), $_options['select']);
                        //log::add('Freebox_OS', 'debug', '│ Airmedia Player choisi : ' . $_options['select']);
                    }
                    $receivers_value = $Cmd->execCmd();
                } else if ($Cmd->getLogicalId() == "media_type_info") {
                    if ($logicalId == 'media_type') {
                        $logicalId_eq->checkAndUpdateCmd($Cmd->getLogicalId(), $_options['select']);
                        //log::add('Freebox_OS', 'debug', '│ Type de média choisi : ' . $_options['select']);
                    }
                    $media_type_value = $Cmd->execCmd();
                } else if ($Cmd->getLogicalId() == "media_info") {
                    if ($logicalId == 'media') {
                        $logicalId_eq->checkAndUpdateCmd($Cmd->getLogicalId(), $_options['message']);
                        //log::add('Freebox_OS', 'debug', '│ URL choisi : ' . $_options['message']);
                    }
                    $media_value = $Cmd->execCmd();
                } else if ($Cmd->getLogicalId() == "password_info") {
                    if ($logicalId == 'password') {
                        $logicalId_eq->checkAndUpdateCmd($Cmd->getLogicalId(), $_options['message']);
                        //log::add('Freebox_OS', 'debug', '│ Mot de passe choisi : ' . $_options['message']);
                    }
                    $password_value = $Cmd->execCmd();
                }
            }
        }
        if ($logicalId == 'start' || $logicalId == 'stop') {
            $Parameter["media_type"] =  $media_type_value;
            $Parameter["media"] = $media_value;
            $Parameter["password"] = $password_value;
            $Parameter["action"] = $logicalId;
            log::add('Freebox_OS', 'debug', '│ ' . (__('Player', __FILE__)) . ' : ' . $receivers_value . ' -- ' . (__('Média type', __FILE__)) . ' : ' . $media_type_value . ' -- ' . (__('Média', __FILE__)) . ' : ' . $media_value . ' -- ' . (__('Mot de Passe', __FILE__)) . ' : ' . $password_value . ' -- Action : ' . $logicalId);
            if ($media_type_value == NULL || $receivers_value == NULL) {
                log::add('Freebox_OS', 'error', '[AirPlay] ' . (__('Impossible d\'envoyer la demande, les paramètres sont incomplets Player', __FILE__)) . ' : ' . $receivers_value . '-- Media type : ' . $media_type_value . ' -- Media : ' . $media_value);
                return;
            }
            if ($media_value == NULL && $logicalId == 'start') {
                log::add('Freebox_OS', 'error', '[AirPlay] ' . (__('Impossible d\'envoyer la demande, Pas de média', __FILE__)) . ' : '  . $media_value);
                return;
            }
            $Free_API->universal_put($Parameter, 'universal_put', null, null, 'airmedia/receivers/' . $receivers_value  . '/', 'POST', $Parameter);
        }
    }

    private static function update_download($logicalId, $logicalId_type, $logicalId_eq, $Free_API, $_options)
    {
        $result = $Free_API->universal_get('download', null, null, 'stats/');
        if ($result != false) {
            switch ($logicalId) {
                case "stop_dl":
                    $Free_API->downloads_put(0);
                    break;
                case "start_dl":
                    $Free_API->downloads_put(1);
                    break;
            }
        }
        if ($result != false) {
            switch ($logicalId) {
                case 'mode_download':
                    if ($_options['select'] == 'slow_planning' || $_options['select'] == 'normal_planning' || $_options['select'] == 'hibernate_planning') {
                        $parametre = true;
                    } else {
                        $parametre = false;
                    };
                    $option = array(
                        "throttling" => $_options['select'],
                        "is_scheduled" => $parametre
                    );
                    $Free_API->universal_put($_options['select'], 'universal_put', null, null, 'downloads/throttling', 'PUT', $option);
                    break;
            }
        }
    }
    private static function update_homeadapters($logicalId, $logicalId_type, $logicalId_eq, $Free_API, $_options)
    {
        if (stripos($logicalId, 'PB_On')  !== false) {
            $parametre = 'active';
            $ID_logicalID = substr($logicalId, 5);
        } else {
            $parametre = 'disabled';
            $ID_logicalID = substr($logicalId, 6);
        }
        $option = array(
            'status' => $parametre
        );
        log::add('Freebox_OS', 'debug', '│ ' . (__('Récupération ID', __FILE__)) . ' : ' . $ID_logicalID);
        $Free_API->universal_put('default', 'universal_put', $ID_logicalID, null, 'home/adapters/', 'PUT', $option);
    }
    private static function update_freeplug($logicalId, $logicalId_type, $logicalId_eq, $Free_API, $_options)
    {
        $Free_API->universal_put(null, 'universal_put', null, null, 'freeplug/' . $logicalId_eq->getLogicalId() . '/' . $logicalId, 'POST', null);
    }
    private static function update_LCD($logicalId, $logicalId_type, $logicalId_eq, $Free_API, $_options)
    {
        $_enabled = true;
        $_OFF = substr($logicalId, -3);
        $_ON = substr($logicalId, -2);
        if ($_OFF == "Off") {
            $_enabled = FALSE;
            $param = substr($logicalId, 0, -3);
            // log::add('Freebox_OS', 'debug', '│' . (__('Récupération du nom de la commande', __FILE__)) . ' : '  . $param);
        } else if ($_ON == "On") {
            $param = substr($logicalId, 0, -2);
            // log::add('Freebox_OS', 'debug', '│' . (__('Récupération du nom de la commande', __FILE__)) . ' : '  . $param);
        }
        switch ($logicalId) {
            case 'brightness_action':
                $Free_API->universal_put(1, 'universal_put', null, null, 'lcd/config', 'PUT', array('brightness' => $_options['slider']));
                break;
            case 'led_strip_animation_action':
                if ($_options['select'] != null) {
                    $led_strip_enabled = true;
                } else {
                    $led_strip_enabled = false;
                }
                $Free_API->universal_put(1, 'universal_put', null, null, 'lcd/config', 'PUT', array('led_strip_animation' => $_options['select'], 'led_strip_enabled' => $led_strip_enabled));
                break;
            case 'led_strip_brightness_action':
                $Free_API->universal_put(1, 'universal_put', null, null, 'lcd/config', 'PUT', array('led_strip_brightness' => $_options['slider']));
                break;
            case 'orientation':
                if ($_options['select'] != 0) {
                    $orientation_forced = true;
                } else {
                    $orientation_forced = false;
                }
                $Free_API->universal_put(1, 'universal_put', null, null, 'lcd/config', 'PUT', array('orientation' => $_options['select'], 'orientation_forced' => $orientation_forced));
                break;
            default:
                $option = array(
                    $param =>  $_enabled
                );
                $Free_API->universal_put(1, 'universal_put', null, null, 'lcd/config', 'PUT', $option);
                break;
        }
    }
    private static function update_netshare($logicalId, $logicalId_type, $logicalId_eq, $Free_API, $_options)
    {
        $_enabled = true;
        $_OFF = substr($logicalId, -3);
        $_ON = substr($logicalId, -2);
        if ($_OFF == "Off") {
            $_enabled = FALSE;
            $param = substr($logicalId, 0, -3);
            //log::add('Freebox_OS', 'debug', '│' . (__('Récupération du nom de la commande', __FILE__)) . ' : '  . $param);
        } else if ($_ON == "On") {
            $param = substr($logicalId, 0, -2);
            //log::add('Freebox_OS', 'debug', '│' . (__('Récupération du nom de la commande', __FILE__)) . ' : '  . $param);
        }
        switch ($logicalId) {
            case "FTP_enabledOn":
            case "FTP_enabledOff":
                $Free_API->universal_put($_enabled, 'universalAPI', null, null, 'enabled', null, 'ftp/config');
                break;
            case "file_share_enabledOn":
            case "file_share_enabledOff":
                $Free_API->universal_put($_enabled, 'universalAPI', null, null, 'file_share_enabled', null, 'netshare/samba');
                break;
            case "logon_enabledOn":
            case "logon_enabledOff":
                $Free_API->universal_put($_enabled, 'universalAPI', null, null, 'logon_enabled', null, 'netshare/samba');
                break;
            case "mac_share_enabledOn":
            case "mac_share_enabledOff":
                $Free_API->universal_put($_enabled, 'universalAPI', null, null, 'enabled', null, 'netshare/afp');
                break;
            case "guest_allowOn":
            case "guest_allowOff":
                $Free_API->universal_put($_enabled, 'universalAPI', null, null, 'guest_allow', null, 'netshare/afp');
                break;
            case "print_share_enabledOn":
            case "print_share_enabledOff":
                $Free_API->universal_put($_enabled, 'universalAPI', null, null, 'print_share_enabled', null, 'netshare/samba');
                break;
            case "smbv2_enabledOn":
            case "smbv2_enabledOff":
                $Free_API->universal_put($_enabled, 'universalAPI', null, null, 'smbv2_enabled', null, 'netshare/samba');
                break;
        }
    }
    private static function update_management($logicalId, $logicalId_type, $logicalId_eq, $Free_API, $_options)
    {
        $option = null;
        switch ($logicalId) {
            case "host":
            case "host_mac":
            case "host_type":
            case "method":
            case "add_del_ip":
            case "primary_name":
            case "comment":
            case "mac_filter":
            case "start":
                foreach ($logicalId_eq->getCmd('info') as $Cmd) {
                    if (is_object($Cmd)) {
                        if ($Cmd->getLogicalId() == 'host_info') {
                            if ($logicalId == 'host' || $logicalId == 'host_mac') {
                                $logicalId_eq->checkAndUpdateCmd($Cmd->getLogicalId(), $_options['select']);
                            }
                            $host_value = $Cmd->execCmd();
                            // Juste Adresse Mac sans ID
                            $host_value_mac = strlen($host_value);
                            if ($host_value_mac > 17) {
                                $host_value_mac_start = $host_value_mac - 17;
                                $host_value_mac_ID = substr($host_value, $host_value_mac_start, 17);
                                $host_value_mac_ID = strtoupper($host_value_mac_ID);
                            } else {
                                $host_value_mac_ID =  $host_value_mac;
                                $host_value_mac_ID = strtoupper($host_value_mac);
                            }
                            //log::add('Freebox_OS', 'debug', '│ Adresse MAC uniquement : ' . $host_value_mac_ID);
                        } else if ($Cmd->getLogicalId() == 'host_type_info') {
                            if ($logicalId == 'host_type') {
                                $logicalId_eq->checkAndUpdateCmd($Cmd->getLogicalId(), $_options['select']);
                            }
                            $host_type_value = $Cmd->execCmd();
                        } else if ($Cmd->getLogicalId() == 'method_info') {
                            if ($logicalId == 'method') {
                                $logicalId_eq->checkAndUpdateCmd($Cmd->getLogicalId(), $_options['select']);
                            }
                            $method_value = $Cmd->execCmd();
                        } else if ($Cmd->getLogicalId() == 'add_del_ip_info') {
                            if ($logicalId == 'add_del_ip') {
                                $logicalId_eq->checkAndUpdateCmd($Cmd->getLogicalId(), $_options['message']);
                            }
                            $add_del_ip_value = $Cmd->execCmd();
                        } else if ($Cmd->getLogicalId() == 'mac_filter_info') {
                            if ($logicalId == 'mac_filter') {
                                $logicalId_eq->checkAndUpdateCmd($Cmd->getLogicalId(), $_options['select']);
                            }
                            $mac_filter_value = $Cmd->execCmd();
                        } else if ($Cmd->getLogicalId() == 'primary_name_info') {
                            if ($logicalId == 'primary_name') {
                                $logicalId_eq->checkAndUpdateCmd($Cmd->getLogicalId(), $_options['message']);
                            }
                            $primary_name_value = $Cmd->execCmd();
                        } else if ($Cmd->getLogicalId() == 'comment_info') {
                            if ($logicalId == 'comment') {
                                $logicalId_eq->checkAndUpdateCmd($Cmd->getLogicalId(), $_options['message']);
                            }
                            $comment_value = $Cmd->execCmd();
                        }
                    }
                }
                if ($logicalId == 'start') {
                    log::add('Freebox_OS', 'debug', ':fg-info:───▶︎ Méthode :/fg:: ' . $method_value);
                    $list = null;
                    $update_TYPE = null;
                    //Option par défaut
                    $option = array(
                        "mac" => $host_value_mac_ID,
                        "ip" => $add_del_ip_value,
                        "comment" => $comment_value
                    );
                    switch ($method_value) {
                        case 'DEVICE': // DEVICE
                            $update_TYPE = 'DEVICE';
                            //$update_IP = 'IP';
                            break;
                        case 'NO_DHCP': // AUCUNE ACTION ICI
                            break;
                        case 'POST': //IP_DEVICE
                            //Ajout IP
                            $update_TYPE = 'IP_DEVICE';
                            // Contrôle Equipement
                            $result = $Free_API->universal_get('universalAPI', null, null, 'dhcp/static_lease/', true, true, true);
                            if (isset($result['result'])) {
                                $result_network = $result['result'];
                                foreach ($result_network as $result) {
                                    if ($result['id'] == $host_value_mac_ID) {
                                        log::add('Freebox_OS', 'debug', ':fg-sucess:'  . (__('Equipement avec déjà un paramètrage IP', __FILE__)) . ' ::/fg: ' . $result['mac']);
                                        if ($method_value == 'POST' && $method_value != 'DELETE') {
                                            $method_value = 'PUT';
                                        }
                                        break;
                                    }
                                }
                            }
                            break;
                        case 'DELETE': // IP
                            $update_TYPE = 'IP';
                            break;
                        case 'IP_DEVICE': // IP_DEVICE
                            $update_TYPE = 'IP_DEVICE';
                            $method_value = 'PUT';
                            break;
                        case 'PUT': // IP_DEVICE
                            $update_TYPE = 'IP';
                            break;
                        case 'ADD_blacklist': // WIFI
                        case 'ADD_whitelist': // WIFI
                            $result = $Free_API->universal_get('universalAPI', null, null, 'wifi/mac_filter/' . $mac_filter_value, true, true, true);
                            $result = $result['result'];
                            $exist = null;
                            $host_value_mac = $host_value_mac_ID;
                            foreach ($result as $resultwifi) {
                                if ($resultwifi['mac'] == $host_value_mac_ID) {
                                    $exist = true;
                                    $host_value_mac_ID = $resultwifi['id'];
                                }
                            }
                            $update_TYPE  = 'WIFI';
                            if ($method_value === 'ADD_blacklist') {
                                $mac_filter_value = 'blacklist';
                            } else {
                                $mac_filter_value = 'whitelist';
                            }
                            $option = array(
                                "comment" => $comment_value,
                                "type" => $mac_filter_value,
                                "mac" => $host_value_mac,
                            );
                            if ($exist == true) {
                                $method_value = 'PUT';
                            } else {
                                $host_value_mac_ID = null;
                                $method_value = 'POST';
                            }
                            break;
                        case 'DEL_blacklist': // WIFI
                        case 'DEL_whitelist': // WIFI
                            $result = $Free_API->universal_get('universalAPI', null, null, 'wifi/mac_filter/' . $mac_filter_value, true, true, true);
                            $result = $result['result'];
                            $exist = null;
                            $host_value_mac = $host_value_mac_ID;
                            foreach ($result as $resultwifi) {
                                if ($resultwifi['mac'] == $host_value_mac_ID) {
                                    $exist = true;
                                    $host_value_mac_ID = $resultwifi['id'];
                                }
                            }
                            if ($exist == true) {
                                $update_TYPE  = 'WIFI';
                                if ($method_value === 'DEL_blacklist') {
                                    $mac_filter_value = 'blacklist';
                                } else {
                                    $mac_filter_value = 'whitelist';
                                }
                                $method_value = 'DELETE';
                                $option = array(
                                    "mac" => $host_value_mac_ID,
                                    "type" => $mac_filter_value,
                                    "comment" => $comment_value
                                );
                            };
                            break;
                        case 'POST_WOL': // POST_WOL
                            $update_TYPE  = 'POST_WOL';
                            $method_value = 'POST';
                            break;
                        default:
                            break;
                    }
                    $method_value_new = null;
                    switch ($update_TYPE) {
                        case 'IP_DEVICE':
                        case 'DEVICE':
                        case 'IP':
                            $list = 'method_info,host_type_info,add_del_ip_info,comment_info,host_info,primary_name_info';
                            if ($primary_name_value === '' || $primary_name_value === null || $host_type_value === null || $host_type_value === '' || $comment_value === null || $comment_value === '') {
                                $result = $Free_API->universal_get('universalAPI', null, null, 'dhcp/static_lease/' . $host_value_mac_ID, true, true, false);
                                if (isset($result['mac'])) {
                                    if ($primary_name_value === '' || $primary_name_value === null) { // Récupération du nom de l'équipement
                                        $primary_name_value = $result['hostname'];
                                        log::add('Freebox_OS', 'debug', ':fg-warning:' . (__('Récupération du nom de l\'équipement => Pour éviter de bloquer', __FILE__)) . ' ::/fg: ' . $primary_name_value);
                                    }
                                    if ($host_type_value === '' || $host_type_value === null) { // Récupération du type de l'équipement
                                        $host_type_value = $result['host']['host_type'];
                                        log::add('Freebox_OS', 'debug', ':fg-warning:' . (__('Récupération du type de l\'équipement => Pour éviter de bloquer', __FILE__)) . ' ::/fg: ' . $host_type_value);
                                    }
                                    if ($comment_value === '' || $comment_value === null) { // Récupération du commentaire
                                        $comment_value  = $result['comment'];
                                        log::add('Freebox_OS', 'debug', ':fg-warning:' . (__('Récupération le commentaire de l\'équipement => Pour éviter de bloquer', __FILE__)) . ' ::/fg: ' . $comment_value);
                                    }
                                    if ($update_TYPE == 'IP') {
                                        if ($add_del_ip_value == $result['ip']) {
                                            log::add('Freebox_OS', 'debug', ':fg-warning:' . (__('L\'équipement a déjà la même adresse IP affecté', __FILE__)) . ':/fg: : ' . $result['ip']);
                                        } else {
                                            log::add('Freebox_OS', 'debug', ':fg-warning:' . (__('L\'équipement a déjà une adresse IP affecté', __FILE__)) . ':/fg: : ' . $result['ip']);
                                        }
                                    }
                                } else {
                                    if ($update_TYPE == 'IP' || $update_TYPE == 'IP_DEVICE') {
                                        $method_value_new = 'POST';
                                        log::add('Freebox_OS', 'debug', ':fg-warning:' . (__('L\'équipement n\'a pas d\'adresse IP affecté', __FILE__)) . ':/fg: ' . $host_value_mac_ID);
                                        $search = $Free_API->universal_get('universalAPI', null, null, 'lan/browser/pub/', true, true, true);
                                        if (isset($search['result'])) {
                                            foreach ($search['result'] as $searchs) {
                                                if ($searchs['l2ident']['id'] == $host_value_mac_ID) {
                                                    if ($primary_name_value === '' || $primary_name_value === null) { // Récupération du nom de l'équipement
                                                        $primary_name_value = $searchs['primary_name'];
                                                        //log::add('Freebox_OS', 'debug', '| ───▶︎ :fg-success:' . (__('Appareil(s) avec adresse IP fixe', __FILE__)) . ' ::/fg: ' . $primary_name_value);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            switch ($update_TYPE) {
                                case 'DEVICE':
                                case 'IP_DEVICE':
                                    log::add('Freebox_OS', 'debug', ':fg-info:───▶︎ Mise à jour:/fg: du Nom ou du type d\'équipement');
                                    log::add('Freebox_OS', 'debug', ':fg-info:───▶︎ Appareil/Nom : :/fg:' . $primary_name_value . ' -- Type : ' . $host_type_value . ' -- Action à faire : ' . $method_value . ' -- Commentaire : ' . $comment_value . ' -- ACTION : ' . $logicalId);
                                    if ($primary_name_value === null || $host_value === null || $host_type_value === null || $primary_name_value === '' || $host_value === '') {
                                        log::add('Freebox_OS', 'error', (__('Gestion réseau : Les données sont incomplètes', __FILE__)) . ' ───▶︎ ' . (__('Impossible de continuer', __FILE__)));
                                        break;
                                    }
                                    $option = array(
                                        "id" => $host_value,
                                        "primary_name" => $primary_name_value,
                                        'host_type'  => $host_type_value
                                    );
                                    if ($method_value === 'DEVICE') {
                                        $host_value_mac_ID = null;
                                    }
                                    $Free_API->universal_put(null, 'universal_put', $host_value, null, 'lan/browser/pub/', 'PUT', $option);
                                    if ($update_TYPE == 'DEVICE') {
                                        break;
                                    }
                                case 'IP':
                                case 'IP_DEVICE':
                                    log::add('Freebox_OS', 'debug', ':fg-info:───▶︎ Mise à jour:/fg: l\'IP et du commentaires');
                                    log::add('Freebox_OS', 'debug', ':fg-info:───▶︎ Appareil/Nom : :/fg:' . $host_value . '/' . $primary_name_value . ' -- IP : ' . $add_del_ip_value . ' -- Action à faire : ' . $method_value . ' -- Commentaire : ' . $comment_value . ' -- ACTION : ' . $logicalId);
                                    if ($host_value === '0' || $host_value === null || $add_del_ip_value === null || $add_del_ip_value === '') {
                                        log::add('Freebox_OS', 'error', (__('Gestion réseau : IP ou adresse mac vide', __FILE__)) . ' ───▶︎ ' . (__('Impossible de continuer', __FILE__)));
                                        break;
                                    }

                                    $option = array(
                                        "mac" => $host_value_mac_ID,
                                        "ip" => $add_del_ip_value,
                                        "comment" => $comment_value
                                    );
                                    if ($method_value_new === 'POST') {
                                        $method_value = 'POST';
                                    }
                                    if ($method_value == 'POST') {
                                        $host_value_mac_ID = null;
                                    }
                                    $Free_API->universal_put(null, 'universal_put', null, null, 'dhcp/static_lease/' . $host_value_mac_ID, $method_value, $option);
                                    if ($update_TYPE == 'IP') {
                                        break;
                                    }
                            }

                            break;
                        case 'POST_WOL':
                            $list = 'method_info,host_info,comment_info';
                            log::add('Freebox_OS', 'debug', ':fg-info:───▶︎ Appareil/Nom : :/fg:' . $host_value . '/' . $primary_name_value . ' -- Action à faire : ' . $method_value . ' -- Commentaire : ' . $comment_value . ' -- ACTION : ' . $logicalId);
                            if ($host_value === '0' || $host_value === null) {
                                log::add('Freebox_OS', 'error', (__('Gestion réseau : Adresse mac vide', __FILE__)) . ' ───▶︎ ' . (__('Impossible de continuer', __FILE__)));
                                break;
                            }
                            $option = array(
                                "mac"  => $host_value_mac_ID,
                                "password" => $comment_value
                            );
                            $Free_API->universal_put(null, 'universal_put', null, null, 'lan/wol/pub/', null, $option);
                            break;
                        case 'WIFI':
                            $list = 'method_info,host_info,comment_info';
                            log::add('Freebox_OS', 'debug', ':fg-info:───▶︎ Appareil/Nom : :/fg:' . $host_value . '/' . $primary_name_value . '/' . $mac_filter_value  . ' -- Action à faire : ' . $method_value . ' -- Commentaire : ' . $comment_value . ' -- ACTION : ' . $logicalId);
                            if ($host_value === null || $add_del_ip_value === null || $mac_filter_value === null) {
                                log::add('Freebox_OS', 'error', (__('Gestion réseau : IP ou adresse mac vide', __FILE__)) . ' ───▶︎ ' . (__('Impossible de continuer', __FILE__)));
                                break;
                            }
                            if ($host_value_mac_ID == null) {
                                $Free_API->universal_put(null, 'universal_put', null, null, 'wifi/mac_filter/', $method_value, $option);
                            } else {
                                $Free_API->universal_put(null, 'universal_put', null, null, 'wifi/mac_filter/' . $host_value_mac_ID, $method_value, $option);
                            }
                    }
                }
        }
        return $list;
    }
    private static function update_network($logicalId, $logicalId_type, $logicalId_eq, $Free_API, $_options, $network)
    {
        $option = null;
        switch ($logicalId) {
            case "search":
                Free_CreateEq::createEq($network, true);
                break;
            case "WakeonLAN":
                // Commande a supprimer
                log::add('Freebox_OS', 'ERROR', '│ ' . (__('METHODE OBSOLETE => MERCI DE REGARDER LA DOCUMENTATION', __FILE__)));
                if ($_options['mac_address'] == null) {
                    log::add('Freebox_OS', 'error', 'Adresse mac vide');
                    break;
                }
                $option = array(
                    "mac" => $_options['mac_address'],
                    "password" => $_options['password']
                );
                $Free_API->universal_put(null, 'universal_put', $_options['mac_address'], null, 'lan/wol/pub/', null, $option);
                break;
            case "add_del_mac":
                // Commande a supprimer
                log::add('Freebox_OS', 'ERROR', '│ ' . (__('METHODE OBSOLETE => MERCI DE REGARDER LA DOCUMENTATION', __FILE__)));
                if ($_options['ip'] == null || $_options['mac_address'] == null) {
                    log::add('Freebox_OS', 'error', 'IP ou adresse mac vide');
                    break;
                }
                $option = array(
                    "mac" => $_options['mac_address'],
                    "ip" => $_options['ip'],
                    "comment" => $_options['comment']
                );
                if ($_options['function'] != 'device') {
                    $Free_API->universal_put(null, 'universal_put', $_options['mac_address'], null, 'dhcp/static_lease/', $_options['function'], $option);
                }
                $option = array(
                    "id" => 'ether-' . $_options['mac_address'],
                    "primary_name" => $_options['name'],
                    'host_type'  => $_options['type']
                );
                $Free_API->universal_put(null, 'universal_put', 'ether-' . $_options['mac_address'], null, 'lan/browser/pub/', 'PUT', $option);
                break;
            case "redir":
                if ($_options['lan_ip'] == null) {
                    log::add('Freebox_OS', 'error', (__('Adresse IP vide', __FILE__)));
                    break;
                }
                $option = array(
                    'enabled' =>  $_options['enable_lan'],
                    'comment' =>  $_options['comment'],
                    'lan_port' =>  $_options['lan_port'],
                    'wan_port_end' =>  $_options['wan_port_end'],
                    'wan_port_start' =>  $_options['wan_port_start'],
                    'lan_ip' =>  $_options['lan_ip'],
                    'ip_proto' => $_options['ip_proto'],
                    'src_ip' =>  $_options['src_ip']
                );
                $Free_API->universal_put(null, 'universal_put', null, null, 'fw/redir', 'POST', $option);
                break;
        }
    }
    private static function update_parental($logicalId, $logicalId_type, $logicalId_eq, $Free_API, $_options, $_cmd, $update)
    {
        $cmd = cmd::byid($_cmd->getvalue());

        if ($cmd !== false) {
            $_status = $cmd->execCmd();
        }
        $Free_API->universal_put($logicalId, $update, $logicalId_eq->getConfiguration('action'), null, $_options, $_status);
        Free_Refresh::RefreshInformation($logicalId_eq->getId());
    }
    private static function update_player($logicalId, $logicalId_type, $logicalId_eq, $Free_API, $_options, $_cmd, $update)
    {
        $ID_Player = $logicalId_eq->getlogicalId();
        $ID_Player = str_replace('player_', '', $ID_Player);
        $player_API_VERSION = $logicalId_eq->getConfiguration('player_API_VERSION');
        $_enabled = true;
        $_OFF = substr($logicalId, -3);
        if ($_OFF == "Off") {
            $_enabled = FALSE;
        }
        switch ($logicalId) {
            case "app":
                $option = array(
                    "url" =>  $_options['select']
                );
                $playerURL = '/api/' . $player_API_VERSION . '/control/open';
                $Free_API->universal_put(null, 'universal_put', null, null, 'player/' . $ID_Player .  $playerURL, 'POST', $option);
                break;
            case "channel":
                log::add('Freebox_OS', 'debug', '───▶︎ ' . (__('ID du Player', __FILE__)) . ' : ' . $ID_Player . ' -- ' . (__('Choix de la Chaîne', __FILE__)) . ' : ' . $_options['slider']);
                $channel = 'tv:?channel=' . $_options['slider'];
                $option = array(
                    "url" =>  $channel
                );
                $playerURL = '/api/' . $player_API_VERSION . '/control/open';
                $Free_API->universal_put(null, 'universal_put', null, null, 'player/' . $ID_Player .  $playerURL, 'POST', $option);
                break;
            case "mediactrl":
                log::add('Freebox_OS', 'debug', '───▶︎ ' . (__('ID du Player', __FILE__)) . ' : ' . $ID_Player . ' -- ' . (__('Choix du contrôle', __FILE__)) . ' : ' . $_options['select']);
                $option = array(
                    "cmd" =>  $_options['select']
                );
                $playerURL = '/api/' . $player_API_VERSION . '/control/mediactrl/';
                $Free_API->universal_put(null, 'universal_put', null, null, 'player/' . $ID_Player .  $playerURL, 'POST', $option);
                break;
            case 'muteOn':
            case 'muteOff':
                log::add('Freebox_OS', 'debug', '───▶︎ ' . (__('ID du Player', __FILE__)) . ' : ' . $ID_Player . ' -- ' . (__('Mute', __FILE__)) . ' : ' . $_enabled);
                $option = array(
                    "mute" => $_enabled
                );
                $playerURL = '/api/' . $player_API_VERSION . '/control/volume/';
                $Free_API->universal_put(null, 'universal_put', null, null, 'player/' . $ID_Player .  $playerURL, 'PUT', $option);
                break;
            case "reboot":
                log::add('Freebox_OS', 'debug', '───▶︎ ' . (__('ID du Player', __FILE__)) . ' : ' . $ID_Player . ' -- ' . (__('Redémarrer', __FILE__)));
                $option = null;
                $playerURL = '/api/' . $player_API_VERSION . '/system/reboot/';
                $Free_API->universal_put(null, 'universal_put', null, null, 'player/' . $ID_Player .  $playerURL, 'POST', $option);
                break;
            case "volume":
                log::add('Freebox_OS', 'debug', '───▶︎ ' . (__('ID du Player', __FILE__)) . ' : ' . $ID_Player . ' -- ' . (__('Volume', __FILE__)) . ' : ' . $_options['slider']);
                $option = array(
                    "volume" => $_options['slider']
                );
                $playerURL = '/api/' . $player_API_VERSION . '/control/volume/';
                $Free_API->universal_put(null, 'universal_put', null, null, 'player/' . $ID_Player .  $playerURL, 'PUT', $option);
                break;
            case "uuid":
                $channeluuid = 'tv:?channel.tv?' . $_options['select'];
                $option = array(
                    "url" =>  $channeluuid
                );
                $playerURL = '/api/' . $player_API_VERSION . '/control/open';
                $Free_API->universal_put(null, 'universal_put', null, null, 'player/' . $ID_Player .  $playerURL, 'POST', $option);
                break;
            default:
                $Free_API->universal_put($logicalId, 'player_ID_ctrl', $logicalId_eq->getConfiguration('action'), null, $_options);
                break;
        }
    }
    private static function update_phone($logicalId, $logicalId_type, $logicalId_eq, $Free_API, $_options)
    {
        switch ($logicalId) {
            case "phone_dell_call":
                $Free_API->universal_put(null, 'universal_put', null, null, 'call/log/delete_all', 'POST', null);
                break;
            case "phone_read_call":
                $Free_API->universal_put(null, 'universal_put', null, null, 'call/log/mark_all_as_read', 'POST', null);
                break;
        }
    }
    private static function update_system($logicalId, $logicalId_type, $logicalId_eq, $Free_API, $_options)
    {
        $_enabled = true;
        $_OFF = substr($logicalId, -3);
        if ($_OFF == "Off") {
            $_enabled = FALSE;
        }
        switch ($logicalId) {
            case "reboot":
                $Free_API->universal_put(null, 'reboot', null, null, null);
                break;
            case '4GOn':
            case '4GOff':
                $Free_API->universal_put($_enabled, 'universalAPI', null, null, 'enabled', null, 'connection/aggregation');
                break;
            case 'avalaible':
                $Free_API->universal_put(null, 'universal_put', null, null, 'lang', 'POST', array('lang' => $_options['select']), null);
                break;
        }
    }
    private static function update_VM($logicalId, $logicalId_type, $logicalId_eq, $Free_API, $_options, $update)
    {
        $Free_API->universal_put(null, $logicalId_eq->getconfiguration('type'), $logicalId_eq->getConfiguration('action'), null, null, null, $logicalId);
    }
    private static function update_wifi($logicalId, $logicalId_type, $logicalId_eq, $Free_API, $_options)
    {
        $value = false;
        if ($logicalId != 'refresh') {
            switch ($logicalId) {
                case 'mac_filter_state':
                    $Free_API->universal_put($_options['select'], 'wifi', null, null, 'config', null, 'mac_filter_state');
                    break;
                case 'mode_planning':
                    $result = $Free_API->universal_get('universalAPI', null, null, 'standby/config', true, true, true);
                    if ($_options['select'] === 'off') {
                        $use_planning = false;
                        $planning_mode = $result['result']['planning_mode'];
                    } else {
                        $planning_mode = $_options['select'];
                        $use_planning = true;
                    }
                    $option = array(
                        'use_planning' => $use_planning,
                        'planning_mode' => $planning_mode,
                        'mapping' => $result['result']['mapping'],
                        'resolution' => $result['result']['resolution']
                    );
                    $Free_API->universal_put(1, 'universal_put', null, null, 'standby/config', 'PUT', $option);
                    break;
                case 'wifiStatutOn':
                case 'wifiStatutOff':
                case 'power_savingOn':
                case 'power_savingOff':
                    if ($logicalId == 'wifiStatutOn' || $logicalId == 'wifiStatutOff') {
                        $param = 'enabled';
                    } else {
                        $param = 'power_saving';
                    }
                    if ($logicalId == 'wifiStatutOn' || $logicalId == 'power_savingOn') {
                        $value = true;
                    }
                    $option = array(
                        $param =>  $value
                    );
                    $Free_API->universal_put(1, 'universal_put', null, null, 'wifi/config', 'PUT', $option);
                    break;
                case 'use_planningOn':
                case 'use_planningOff':
                    if ($logicalId == 'use_planningOn') {
                        $value = true;
                    }
                    $option = array(
                        "use_planning" =>  $value
                    );
                    $Free_API->universal_put(1, 'universal_put', null, null, 'wifi/planning', 'PUT', $option);
                    break;
                case 'wifiSessionWPSOff':
                case 'wifiWPSOff':
                    $parametre = 1;
                    $Free_API->universal_put($parametre, 'wifi', null, null, 'wps/stop', null, null);
                    break;

                default:
                    $Free_API->universal_put($logicalId, 'wifi', null, null, 'wps/start', null, null);
                    break;
            }
        }
    }

    private static function update_default($logicalId, $logicalId_type, $logicalId_eq, $Free_API, $_options, $_cmd, $logicalId_conf)
    {
        //$_execute = 1;
        switch ($logicalId_type) {
            case 'slider':
                if ($_cmd->getConfiguration('invertslide') == 1) {
                    log::add('Freebox_OS', 'debug', '│ Option ETAT Inverser Curseur ACTIVE');
                    $parametre['value'] = ($_cmd->getConfiguration('maxValue') - $_cmd->getConfiguration('minValue')) - $_options['slider'];

                    /*if ($_options['slider'] === $_cmd->getConfiguration('maxValue')) {
                        $parametre['value'] = $_cmd->getConfiguration('minValue');
                    } else if ($_options['slider'] === $_cmd->getConfiguration('minValue')) {
                        $parametre['value'] = $_cmd->getConfiguration('maxValue');
                    } else {
                        $parametre['value'] = ($_cmd->getConfiguration('maxValue') - $_cmd->getConfiguration('minValue')) - $_options['slider'];
                    }*/
                    //$parametre['value'] = ($_cmd->getConfiguration('maxValue') - $_cmd->getConfiguration('minValue')) - $_options['slider'];
                } else {
                    $parametre['value'] = (int) $_options['slider'];
                }
                $parametre['value_type'] = 'int';

                $action = $logicalId_eq->getConfiguration('action');
                $type = $logicalId_eq->getConfiguration('type');
                log::add('Freebox_OS', 'debug', '│ type : ' . $type . ' -- action : ' . $action . ' -- valeur type : ' . $parametre['value_type'] . ' -- Etat Option Inverser  : ' . $_cmd->getConfiguration('invertslide') . ' -- valeur  : ' . $parametre['value'] . ' -- valeur slider : ' . $_options['slider']);
                if ($action == 'intensity_picker' || $action == 'color_picker') {
                    // $cmd = cmd::byid($_cmd->getConfiguration('binaryID'));
                    /*if ($cmd !== false) {
                        if ($cmd->execCmd() == 0) {
                            $_execute = 0;
                            log::add('Freebox_OS', 'debug', '│ Pas d\'action car l\'équipement est éteint');
                        }
                    }*/
                }
                break;
            case 'color':
                break;
            case 'message':
                $parametre['value'] = $_options['message'];
                $parametre['value_type'] = 'void';
                break;
            case 'select':
                $parametre['value'] = $_options['select'];
                $parametre['value_type'] = 'void';
                break;
            default:
                $parametre['value_type'] = 'bool';
                if ($logicalId_conf >= 0 && (stripos($logicalId, 'PB_On') !== FALSE || stripos($logicalId, 'PB_Off') !== FALSE)) {
                    if (stripos($logicalId, 'PB_On')  !== false) {
                        $parametre['value'] = true;
                        $logicalId_conf = substr($logicalId, 5);
                    } else {
                        $parametre['value'] = false;
                        $logicalId_conf = substr($logicalId, 6);
                    }
                    //log::add('Freebox_OS', 'debug', '│ Récupération ID : ' . $logicalId_conf);
                    log::add('Freebox_OS', 'debug', '│ Paramétrage spécifique BP ON/OFF (' . $logicalId . ' avec Id ' . $logicalId_conf . ') : ' . $parametre['value']);
                    $logicalId = $logicalId_conf;
                } else {
                    if (stripos($logicalId, 'PB_UP') !== false || stripos($logicalId, 'PB_DOWN') !== false) {
                        $parametre['value_type'] = 'void';
                        if (stripos($logicalId, 'PB_UP') !== false) {
                            $parametre['value'] = true;
                            $logicalId_conf = substr($logicalId, 5);
                        } else {
                            $parametre['value'] = false;
                            $logicalId_conf = substr($logicalId, 7);
                        }
                        log::add('Freebox_OS', 'debug', '│ Paramétrage spécifique BP UP/DOWN (' . $logicalId . ' Récupération ID ' . ' avec Id ' . $logicalId_conf . $logicalId_conf . ') : ' . $parametre['value']);
                        $logicalId = $logicalId_conf;
                    } else {
                        $parametre['value'] = true;
                        $Listener = cmd::byId(str_replace('#', '', $_cmd->getValue()));

                        if (is_object($Listener)) {
                            $parametre['value'] = $Listener->execCmd();
                        }
                        if ($_cmd->getConfiguration('invertslide')) {
                            $parametre['value'] = !$parametre['value'];
                        }
                    }
                }
                break;
        }
        if ($logicalId != 'refresh') {
            sleep(2);
            //if ($_execute == 1)  
            $Free_API->universal_put($parametre, 'set_tiles', $logicalId, $logicalId_eq->getLogicalId(), null);
        }
    }
}
