<?php

class Free_API
{
    private $ErrorLoop = 0;
    private $serveur;
    private $app_id;
    private $app_name;
    private $app_version;
    private $device_name;
    private $track_id;
    private $app_token;
    private $API_version;

    public function __construct()
    {
        $this->serveur = trim(config::byKey('FREEBOX_SERVER_IP', 'Freebox_OS'));
        $this->app_id = trim(config::byKey('FREEBOX_SERVER_APP_ID', 'Freebox_OS'));
        $this->app_name = trim(config::byKey('FREEBOX_SERVER_APP_NAME', 'Freebox_OS'));
        $this->app_version = trim(config::byKey('FREEBOX_SERVER_APP_VERSION', 'Freebox_OS'));
        $this->device_name = trim(config::byKey('FREEBOX_SERVER_DEVICE_NAME', 'Freebox_OS'));
        $this->track_id = config::byKey('FREEBOX_SERVER_TRACK_ID', 'Freebox_OS');
        $this->app_token = config::byKey('FREEBOX_SERVER_APP_TOKEN', 'Freebox_OS');
        $this->API_version = config::byKey('FREEBOX_API', 'Freebox_OS');
        // Gestion API
        $Config_KEY = config::byKey('FREEBOX_API', 'Freebox_OS');
        if (empty($Config_KEY)) {
            $this->API_version = config::byKey('FREEBOX_API_DEFAUT', 'Freebox_OS');
            log::add('Freebox_OS', 'debug', '───▶︎ ' . (__('Version API Non Défini Compatible avec la Freebox', __FILE__)) . ' : ' . $this->API_version);
        } else {
            $this->API_version = config::byKey('FREEBOX_API', 'Freebox_OS');
        }
    }

    public function track_id() //Doit correspondre a la donction "auth" de freboxsession.js homebridge freebox
    {
        try {
            $API_version = $this->API_version;
            if ($API_version == null) {
                $API_version = config::byKey('FREEBOX_API_DEFAUT', 'Freebox_OS');
                log::add('Freebox_OS', 'debug', '───▶︎ ' . (__('La version API est nulle mise en place version provisoire', __FILE__)) . ' : ' . $API_version);
            };
            $_URL = $this->serveur . '/api/' . $API_version . '/login/authorize/';
            $http = new com_http($_URL);
            $http->setPost(
                json_encode(
                    array(
                        'app_id' => $this->app_id,
                        'app_name' => $this->app_name,
                        'app_version' => $this->app_version,
                        'device_name' => $this->device_name
                    )
                )
            );
            $result = $http->exec(30, 2);
            if (is_json($result)) {
                return json_decode($result, true);
            }
            return $result;
        } catch (Exception $e) {
            log::add('Freebox_OS', 'error', '[Freebox TrackId] : ' . $e->getCode());
        }
    }

    public function ask_track_authorization()
    {
        try {
            $API_version = $this->API_version;
            if ($API_version == null) {
                $API_version = config::byKey('FREEBOX_API_DEFAUT', 'Freebox_OS');
                log::add('Freebox_OS', 'debug', '───▶︎ ' . (__('La version API est nulle mise en place version provisoire', __FILE__)) . ' : ' . $API_version);
            };
            $_URL = $this->serveur . '/api/' . $API_version . '/login/authorize/';
            $http = new com_http($_URL . $this->track_id);
            $result = $http->exec(30, 2);
            if (is_json($result)) {
                return json_decode($result, true);
            }
            return $result;
        } catch (Exception $e) {
            log::add('Freebox_OS', 'error', '[Freebox Autorisation] : ' . $e->getCode());
        }
    }

    public function getFreeboxPassword()
    {
        try {
            $API_version = $this->API_version;
            if ($API_version == null) {
                $API_version = config::byKey('FREEBOX_API_DEFAUT', 'Freebox_OS');
                log::add('Freebox_OS', 'debug', '───▶︎ ' . (__('La version API est nulle mise en place version provisoire', __FILE__)) . ' : ' . $API_version);
            };
            $_URL = $this->serveur . '/api/' . $API_version . '/login/';
            $http = new com_http($_URL);
            $json = $http->exec(30, 2);
            log::add('Freebox_OS', 'debug', '[Freebox Password] : ' . $json);
            $json_connect = json_decode($json, true);
            if ($json_connect['success'])
                cache::set('Freebox_OS::Challenge', $json_connect['result']['challenge'], 0);
            else
                return false;
            return true;
        } catch (Exception $e) {
            log::add('Freebox_OS', 'error', '[Freebox Password] : ' . $e->getCode());
        }
    }

    public function getFreeboxOpenSession() //Doit correspondre a la fonction session de freboxsession.js homebridge freebox
    {
        try {
            $challenge = cache::byKey('Freebox_OS::Challenge');
            if (!is_object($challenge) || $challenge->getValue('') == '') {
                if ($this->getFreeboxPassword() === false)
                    return false;
                $challenge = cache::byKey('Freebox_OS::Challenge');
            }
            $API_version = $this->API_version;
            if ($API_version == null) {
                $API_version = config::byKey('FREEBOX_API_DEFAUT', 'Freebox_OS');
                log::add('Freebox_OS', 'debug', '───▶︎ ' . (__('La version API est nulle mise en place version provisoire', __FILE__)) . ' : ' . $API_version);
            };
            $_URL = $this->serveur . '/api/' . $API_version . '/login/session/';
            $http = new com_http($_URL);
            $http->setPost(json_encode(array(
                'app_id' => $this->app_id,
                'app_version' =>  $API_version, // Ajout suivant fonction session Free homebridge
                'password' => hash_hmac('sha1', $challenge->getValue(''), $this->app_token)
            )));
            $json = $http->exec(30, 2);
            log::add('Freebox_OS', 'debug', '[Freebox Open Session] : ' . $json);
            $result = json_decode($json, true);

            if (!$result['success']) {
                $this->ErrorLoop++;
                $this->close_session();
                if ($this->ErrorLoop < 5) {
                    if ($this->getFreeboxOpenSession() === false)
                        return false;
                }
                log::add('Freebox_OS', 'debug', '[Freebox Etat Session] : KO / ' . $result['success']);
            } else {
                cache::set('Freebox_OS::SessionToken', $result['result']['session_token'], 0);
                log::add('Freebox_OS', 'debug', '[Freebox Etat Session] : OK / ' . $result['success']);
                return true;
            }
            return false;
        } catch (Exception $e) {
            log::add('Freebox_OS', 'error', '[Freebox Open Session] : ' . $e->getCode());
        }
    }

    public function getFreeboxOpenSessionData()
    {
        try {
            $challenge = cache::byKey('Freebox_OS::Challenge');
            if (!is_object($challenge) || $challenge->getValue('') == '') {
                if ($this->getFreeboxPassword() === false)
                    return false;
                $challenge = cache::byKey('Freebox_OS::Challenge');
            }
            $API_version = $this->API_version;
            if ($API_version == null) {
                $API_version = config::byKey('FREEBOX_API_DEFAUT', 'Freebox_OS');
                log::add('Freebox_OS', 'debug', '───▶︎ ' . (__('La version API est nulle mise en place version provisoire', __FILE__)) . ' : ' . $API_version);
            };
            $_URL = $this->serveur . '/api/' . $API_version . '/login/session';
            $http = new com_http($_URL);
            $http->setPost(json_encode(array(
                'app_id' => $this->app_id,
                'password' => hash_hmac('sha1', $challenge->getValue(''), $this->app_token)
            )));
            $json = $http->exec(30, 2);
            log::add('Freebox_OS', 'debug', '[get Freebox Open Session Data] : ' . $json);
            $result = json_decode($json, true);
            return $result;
        } catch (Exception $e) {
            log::add('Freebox_OS', 'error', '[get Freebox Open Session Data] : ' . $e->getCode());
        }
    }

    public function fetch($api_url, $params = array(), $method = 'GET', $Type_log = false)
    {
        try {
            $session_token = cache::byKey('Freebox_OS::SessionToken');
            $url = 'http://' . $this->serveur . $api_url;
            while ($session_token->getValue('') == '') {
                $session_token = cache::byKey('Freebox_OS::SessionToken');
            }
            if (!isset($Type_log['log_request'])) {
                $Type_log['log_request'] = true;
            }
            if ($Type_log['log_request']  != false) {
                if (empty($params)) {
                    $params_log = '';
                } else {
                    $params_log = json_encode($params);
                }
                $requetURL = '[Freebox Request Connexion] : ' . $method . ' ' . (__('sur la l\'adresse', __FILE__)) . ' : ' . $url  .  $params_log;
                log::add('Freebox_OS', 'debug', $requetURL);
            };
            $ch = curl_init();
            //CURLOPT_URL : l'url cible que la requête devra appeler (une chaine de caractères typée URL).
            curl_setopt($ch, CURLOPT_URL, $url);
            // Force une nouvelle connection
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
            //CURLOPT_HEADER : si nous souhaitons ou non récupérer les informations de l'entête (boolean). 
            curl_setopt($ch, CURLOPT_HEADER, false);
            //CURLOPT_RETURNTRANSFER : si nous voulons ou non récupérer le contenu de la requête appelée (boolean). 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            //Cookie pour la session
            curl_setopt($ch, CURLOPT_COOKIESESSION, true);
            //Timeout
            $_timeout = 60;
            //CURLOPT_CONNECTTIMEOUT : le délais maximum exprimé en secondes avant l'abandon de la connexion au serveur lors de l'établissement de la connexion (entier). 
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $_timeout);
            //CURLOPT_TIMEOUT : le délais maximum exprimé en secondes avant l'abandon de la résolution de la requête curl lors de son éxécution (entier). 
            curl_setopt($ch, CURLOPT_TIMEOUT, $_timeout);
            curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            if ($method == "POST") {
                //CURLOPT_POST : si la requête doit utiliser le protocole POST pour sa résolution (boolean). 
                curl_setopt($ch, CURLOPT_POST, true);
            }
            if ($method == "DELETE" || $method == "PUT" ||  $method == "POST" || $method == "GET") {
                //CURLOPT_CUSTOMREQUEST : pour forcer le format de la commande HTTP (chaine de caractères, PUT,GET,POST,CONNECT,HEAD,etc.).
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            }
            if ($params) {
                //CURLOPT_POSTFIELDS : le tableau de paramètres à assigner à une requête POST (tableau associatif). 
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            }
            //CURLOPT_HTTPHEADER : un tableau non associatif permettant de modifier des paramètres du header envoyé par la requête (tableau).
            $token =  $session_token->getValue('');
            $headers = array("Content-Type: application/json", "X-Fbx-App-Auth: $token");
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            //curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Fbx-App-Auth: " . $session_token->getValue('')));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $content = curl_exec($ch);
            $errorno = 0;
            //Contrôler la présence d'erreur fatale dans l'éxécution de la requête par curl en php
            if (curl_errno($ch) !== 0) {
                $error = curl_error($ch);
                $errorno = curl_errno($ch);
            }
            curl_close($ch);
            if (!isset($Type_log['log_result'])) {
                $Type_log['log_result'] = true;
            }
            if ($Type_log['log_result'] != false) {
                log::add('Freebox_OS', 'debug', '[Freebox Request Result] : ' . $content);
            }
            if ($errorno !== 0) {
                return '[WARNING] ' . (__('Erreur de connexion cURL vers', __FILE__)) . ' ' . $this->serveur . $api_url . ': ' . $error;
            } else {
                $result = json_decode($content, true);
                if ($result == null) return false;
                if (isset($result['success']) || isset($result['error_code'])) {
                    if (!$result['success']) {
                        $msg = $this->msg_box($result['error_code'], $result['msg'], $api_url);
                        $log_level_color_start = '';
                        $log_level_color_end = '';
                        if ($msg['log_level'] === 'Debug') {
                            $log_level_color_start = ':fg-warning: ───▶︎ ';
                            $log_level_color_end = ':/fg:';
                        }
                        log::add('Freebox_OS', $msg['log_level'],  $log_level_color_start . $msg['msg_box1'] . ' ───▶︎ ' . $log_level_color_end . (__('Code Erreur', __FILE__)) . ' = ' . $result['error_code']);
                        if ($msg['return_result'] == false) {
                            return false;
                        } else if ($msg['return_result'] == 'auth_required') {
                            $this->close_session();
                            $this->getFreeboxOpenSessionData();
                            log::add('Freebox_OS', $msg['log_level'], $msg['msg_box2'] . ' : ' . $result['error_code']);
                            $result = 'auth_required';
                        } else if ($msg['return_result'] == 'result') {
                            return $result;
                        }
                    }
                }
                return $result;
            }
        } catch (Exception $e) {
            log::add('Freebox_OS', 'error', '[Freebox Request] : '  . $e->getCode());
        }
    }
    private static function msg_box($error_code, $msg = null, $api_url = null)
    {
        $msg_box2 = null;
        $return_result = false;
        if (strpos($api_url, '/player/') !== false     && $error_code == 'invalid_api_version') {
            $log_level = 'Debug';
            log::add('Freebox_OS', 'debug', ':fg-warning: ───▶︎ ' . (__('Annulation du message d\'erreur pour le Player avec la version de l\'API', __FILE__))  .  ':/fg:' . ' : ' . $api_url);
        } else {
            $log_level = 'Error';
        }

        switch ($error_code) {
            case "insufficient_rights":
            case "missing_right":
                $msg_box1 = (__('Erreur Autorisation : Les autorisations de votre application ne vous permettent pas d\'accéder à cette API', __FILE__));
                break;
            case "auth_required":
                $msg_box1 = (__('[Redémarrage session à cause de l\'erreur]', __FILE__));
                $msg_box2 = (__('[Redémarrage session Terminée à cause de l\'erreur]', __FILE__));
                $return_result = 'auth_required';
                $log_level = 'Debug';
                break;
            case "denied_from_external_ip":
                $msg_box1 = (__('Erreur Accès : Vous essayez d\'obtenir un Token d\'application depuis une adresse IP distante', __FILE__));
                break;
            case "nosta":
                if ($msg == 'Erreur freeplug : Pas de plug avec cet identifiant') {
                    $log_level = 'Debug';
                    $msg_box1 = (__('Pas de Freeplug avec cet identifiant', __FILE__));
                } else {
                    $msg_box1 = (__('[Message inconnue]', __FILE__));
                }
                break;
            case "new_apps_denied":
                $msg_box1 = (__('Erreur Application : L\'application a été désactivé', __FILE__));
                break;
            case "apps_denied":
                $msg_box1 = (__('Erreur Application : L\'accès à l\'API depuis les applications a été désactivé', __FILE__));
                break;
            case "invalid_token":
                $msg_box1 =  (__('Erreur : Le Token que vous essayez d\'utiliser est non valide ou a été révoqué', __FILE__));
                break;
            case "pending_token":
                $msg_box1 =  (__('Erreur : Le Token que vous essayez d\'utiliser n\'a pas encore été validé par l\'utilisateur', __FILE__));
                break;
            case "invalid_api_version":
                $msg_box1 = (__('La version de l\'API n\'est pas compatible', __FILE__));
                $return_result = 'result';
                break;
            case "invalid_request":
                $msg_box1 =  (__('Requête non valide : Impossible d\'analyser JSON', __FILE__));
                break;
            case "ratelimited":
                $msg_box1 =  (__('Erreur AUTRE', __FILE__));
                break;
            case "no_such_vm":
                $msg_box1 = (__('Erreur VM : La VM n\'existe pas ou l\'application n\'ai pas comptatible avec la BOX', __FILE__));
                break;
            case "nohost":
                $log_level = 'Debug';
                $msg_box1 = (__('Pas d\'appareil connecté avec cette adresse MAC', __FILE__));
                break;
            case "not_found":
                $log_level = 'Debug';
                if (strpos($api_url, '/home/nodes/') || $api_url == strpos($api_url, '/home/tileset/')) {
                    $msg_box1 = (__('Pas d\'équipement domotique avec cet ID', __FILE__));
                    $log_level = 'Error';
                } else if (strpos($api_url, '/storage/')) {
                    $msg_box1 = (__('Pas de disque ou de partition avec cet ID', __FILE__));
                    $log_level = 'Error';
                } else {
                    $msg_box1 = (__('Pas d\'équipement avec cet ID', __FILE__));
                }
                break;
            case "nodev":
                if (strpos($api_url, '/lan/browser/') == true) {
                    //$log_level = 'Debug';
                    $msg_box1 = (__('Interface réseau : Interface invalide ou adresse MAC introuvable', __FILE__));
                } else {
                    $msg_box1 = (__('Erreur de la modification de l\’hôte', __FILE__));
                }
                break;
            case "exist":
                if (strpos($api_url, '/wifi/mac_filter/') == true) {
                    $msg_box1 = (__('Impossible d’ajouter une entrée de filtrage MAC : Entrée déjà existante    ', __FILE__));
                } else {
                    $msg_box1 = (__('[Message inconnue]', __FILE__));
                }
                $msg_box1 = (__('Impossible d’ajouter une entrée de filtrage MAC : Entrée déjà existante    ', __FILE__));

                break;
            case "inval":
                if (strpos($api_url, '/lan/browser/') === true) {
                    $msg_box1 = (__('Modification réseau : Paramètre invalide', __FILE__));
                } else {
                    $msg_box1 = (__('Erreur de la modification de l\’hôte', __FILE__));
                }
                break;
            case "service_down":
                $log_level = 'Debug';
                $msg_box1 = (__('Pas d\'accès à internet', __FILE__));
                break;
            case "nodev":
                $log_level = 'Debug';
                $msg_box1 = (__('Aucun appareil trouvé avec ce nom', __FILE__));
                break;
            case "noent":
                if (strpos($api_url, '/dhcp/static_lease/') == true) {
                    $msg_box1 = (__('Modification réseau : Impossible de récupérer la liste des baux statiques DHCP : Pas d\’entrée avec cet identifiant', __FILE__));
                } else {
                    $log_level = 'Debug';
                    if ($msg == 'Aucun module 4G détecté') {
                        $msg_box1 = (__('Aucun module 4G détecté', __FILE__));
                    } else if ($msg == 'Impossible de récupérer le network control : Pas de contrôle de reseau existant avec ce profil') {
                        $msg_box1 = (__('Pas de contrôle de parental existant avec ce profil', __FILE__));
                    } else {
                        $msg_box1 = (__('ID invalide ou ID de règle invalide', __FILE__));
                    }
                }
                break;
            case "internal_error":
                $log_level = 'Debug';
                $msg_box1 = (__('Erreur interne', __FILE__));
                break;
            default:
                $msg_box1 = (__('[Message inconnue]', __FILE__));
                break;
        }
        $msgbox = array(
            'msg_box1' => $msg_box1,
            'msg_box2' => $msg_box2,
            'return_result' => $return_result,
            'log_level' => $log_level
        );
        return $msgbox;
    }

    public function close_session()
    {
        log::add('Freebox_OS', 'debug', ' OK  Close Session  ');
        try {
            $Challenge = cache::byKey('Freebox_OS::Challenge');
            if (is_object($Challenge)) {
                $Challenge->remove();
            }
            $session_token = cache::byKey('Freebox_OS::SessionToken');
            if (!is_object($session_token) || $session_token->getValue('') == '') {
                return;
            }
            $API_version = $this->API_version;
            if ($API_version == null) {
                $API_version = config::byKey('FREEBOX_API_DEFAUT', 'Freebox_OS');
                log::add('Freebox_OS', 'debug', '───▶︎ ' . (__('La version API est nulle mise en place version provisoire', __FILE__)) . ' : ' . $API_version);
            };
            $_URL = $this->serveur . '/api/' . $API_version . '/login/logout/';
            $http = new com_http($_URL);
            $http->setPost(array());
            $json = $http->exec(2, 2);
            log::add('Freebox_OS', 'debug', '[Freebox Close Session] : ' . $json);
            $SessionToken = cache::byKey('Freebox_OS::SessionToken');

            if (is_object($SessionToken)) {
                $SessionToken->remove();
            }
            return $json;
        } catch (Exception $e) {
            log::add('Freebox_OS', 'debug', '[Freebox Close Session] : ' . $e->getCode() . ' ' . (__('ou session déjà fermée', __FILE__)));
        }
    }

    public function PortForwarding($id, $fonction = "GET", $active = null, $Mac = null)
    {
        $API_version = $this->API_version;
        $PortForwardingUrl = '/' . 'api/' . $API_version . '/fw/redir/';
        $Type_log = array(
            "log_request" =>  true,
            "log_result" => true
        );
        $PortForwarding = $this->fetch($PortForwardingUrl, null, "GET", $Type_log);
        $id = str_replace("ether-", "", $id);
        $id = strtoupper($id);
        log::add('Freebox_OS', 'debug', '───▶︎ ' . (__('Lecture des Ports l\'adresse Mac', __FILE__)) . ' : '  . $Mac . ' - ' . (__('FONCTION', __FILE__)) . ' ' . $fonction . ' - ' . (__('action', __FILE__)) . ' ' . $active);
        if ($PortForwarding === false) {
            log::add('Freebox_OS', 'debug', '───▶︎ ' . (__('Aucune donnée', __FILE__)));
            return false;
        }
        if ($fonction == "GET") {
            $result = array();
            foreach ($PortForwarding['result'] as $value) {
                if ($value['host']['l2ident']['id'] == $Mac) {
                    $enabled = "0";
                    if ($value['enabled'] == true) $enabled = "1";
                    array_push($result, array(
                        'id' => $value['id'],
                        'enabled' => $enabled,
                        'src_ip' => $value['src_ip'],
                        'wan_port_start' => $value['wan_port_start'],
                        'wan_port_end' => $value['wan_port_end'],
                        'ip_proto' => $value['ip_proto'],
                        'lan_ip' => $value['lan_ip'],
                        'lan_port' => $value['lan_port'],
                        'comment' => $value['comment']
                    ));
                };
            }
            return $result;
        } elseif ($fonction == "PUT") {
            if ($active == 1) {
                $this->fetch($PortForwardingUrl . $id, array("enabled" => true), $fonction, $Type_log);
                return true;
            } elseif ($active == 0) {
                $this->fetch($PortForwardingUrl . $id, array("enabled" => false), $fonction, $Type_log);
                return true;
            } elseif ($active == 3) {
                $this->fetch($PortForwardingUrl . $id, null, "DELETE", $Type_log);
                return true;
            }
        }
    }

    public function universal_get($update = 'wifi', $id = null, $boucle = 4, $update_type = 'config', $log_request = true, $log_result = true, $_onlyresult = false)
    {
        $API_version = $this->API_version;
        if ($API_version == null) {
            $API_version = config::byKey('FREEBOX_API_DEFAUT', 'Freebox_OS');
            log::add('Freebox_OS', 'debug', '───▶︎ ' . (__('La version API est nulle mise en place version provisoire', __FILE__)) . ' : ' . $API_version);
        };
        $config_log = null;
        $fonction = "GET";
        $Parameter = null;
        if ($id != null) {
            $id = '/' . $id;
        } else if ($id == null && $update == 'tiles') {
            $id = '/all';
        }
        switch ($update) {
            case 'network':
                $config = 'api/' . $API_version . '/' . $update_type;
                break;
            case 'universalAPI':
                if ($update_type == 'api_version') {
                    $config =   $update_type;
                } else {
                    $config = 'api/' . $API_version . '/' . $update_type . $id;
                }
                break;
            case 'network_ID':
                $config = 'api/' . $API_version . '/lan/browser/' . $update_type  . $id;
                break;
            case 'tiles':
                $config = 'api/' . $API_version . '/home/tileset' . $id;
                $config_log = 'Traitement de la Mise à jour de l\'id ';
                break;
            case 'WebSocket':
                $config = 'api/' . $API_version . '/ws/event';
                $config_log = (__('Traitement de la Mise à jour de WebSocket', __FILE__));
                $Parameter = array(
                    "action" => 'notification',
                    "success" => true,
                    "source" => 'vm',
                    "event" => 'VmStateChange',
                );
                break;
            case 'PortForwarding':
                $config = '/api/' . $API_version . '/fw/redir/';
                $config_log = (__('Redirection de port', __FILE__));
                break;
        }
        $Type_log = array(
            "log_request" =>  $log_request,
            "log_result" => $log_result
        );
        $result = $this->fetch('/' . $config, $Parameter, $fonction, $Type_log);
        if ($result == 'auth_required') {
            $result = $this->fetch('/' . $config, $Parameter, $fonction, $Type_log);
        }
        if ($result === 'invalid_api_version') {
            $result = 'invalid_api_version';
            return $result;
        }
        if ($result === false) {
            return false;
        }
        if (isset($result['success'])) {
            $value = 0;
            if ($update_type == 'freeplug') {
                $update = 'freeplug';
            }
            switch ($update) {
                default:
                    if ($config_log != null && $id != null && $id != '/all') {
                        if ($log_request == true) {
                            log::add('Freebox_OS', 'debug', '───▶︎ ' . $config_log . ' : ' . $id);
                        }
                    }
                    if (isset($result['result'])) {
                        if ($_onlyresult == false) {
                            return $result['result'];
                        } else {
                            return $result;
                        }
                    } else {
                        $result = null;
                        return $result;
                    }
                    break;
            }


            return $value;
        } else {
            if ($update == "network_ping" || $update == "network_ID" || $update_type == "api_version") {
                return $result;
            } else if ($update_type == 'lte/config' || $update == 'parental') {
                return $result['msg'];
            } else {
                return false;
            }
        }
    }
    public function downloads_put($Etat)
    {
        $API_version = $this->API_version;
        $DownloadUrl = '/api/' . $API_version . '/downloads/';
        $result = $this->fetch($DownloadUrl);

        if ($result == 'auth_required') {
            $result = $this->fetch($DownloadUrl);
        }
        if ($result === false)
            return false;
        $nbDL = count($result['result']);
        for ($i = 0; $i < $nbDL; ++$i) {
            if ($Etat == 0)
                $downloads = $this->fetch($DownloadUrl  . $result['result'][$i]['id'], array("status" => "stopped"), "PUT");
            if ($Etat == 1)
                $downloads = $this->fetch($DownloadUrl . $result['result'][$i]['id'], array("status" => "downloading"), "PUT");
        }
        if ($downloads === false)
            return false;
        if ($downloads['success'])
            return $downloads['success'];
        else
            return false;
    }
    public function universal_put($parametre, $update = 'wifi', $id = null, $nodeId = null, $_options = null, $_status_cmd = null, $_options_2 = null)
    {
        $API_version = $this->API_version;
        $fonction = "PUT";
        $config_log = null;
        $cmd_config = null;
        if ($id != null) {
            $id = $id . '/';
        }
        $Type_log = array(
            "log_request" =>  true,
            "log_result" => true
        );
        switch ($update) {
            case 'notification_ID':
                $config = 'api/' . $API_version . '/notif/targets/' . $id;
                if ($_options == 'DELETE') {
                    $fonction = $_options;
                }
                break;
            case 'parental':
                $config_log = (__('Mise à jour du : Contrôle Parental', __FILE__));
                $cmd_config = 'parental';
                $config = "/api/" . $API_version . "/network_control/" . $id;
                $jsontestprofile = $this->fetch($config);
                $jsontestprofile = $jsontestprofile['result'];
                if ($parametre == "denied") {
                    $jsontestprofile['override_until'] = 0;
                    $jsontestprofile['override'] = true;
                    $jsontestprofile['override_mode'] = "denied";
                } else if ($parametre == "tempDenied") {
                    $date = new DateTime();
                    $timestamp = $date->getTimestamp();
                    $jsontestprofile['override_until'] = $timestamp + $_options['select'];
                    $jsontestprofile['override'] = true;
                    if ($_status_cmd == 'denied') {
                        $jsontestprofile['override_mode'] = "allowed";
                    } else {
                        $jsontestprofile['override_mode'] = "denied";
                    }
                } else {
                    $jsontestprofile['override'] = false;
                }
                $parametre = $jsontestprofile;
                $config = "api/" . $API_version . "/network_control/" . $id;
                break;
            case 'reboot':
                $config = 'api/' . $API_version . '/system/reboot';
                $fonction = "POST";
                break;
            case 'universalAPI':
                $config = 'api/' . $API_version . '/' . $_options_2;
                $cmd_config = $_options;
                break;
            case 'universal_put':
                if ($_status_cmd == "DELETE" || $_status_cmd == "PUT" || $_status_cmd == "device") {
                    $config = 'api/' . $API_version . '/' . $_options  . $id;
                    $fonction = $_status_cmd;
                } else {
                    //log::add('Freebox_OS', 'debug', '───▶︎ ' . (__('Requête', __FILE__)) . ' : ' . $_options);
                    $config = 'api/' . $API_version . '/' . $_options;
                    $fonction = "POST";
                }
                log::add('Freebox_OS', 'debug', ':fg-info:───▶︎ ' . (__('Type de requête', __FILE__)) . ' ::/fg: ' . $fonction);
                break;
            case 'VM':
                $config = 'api/' . $API_version . '/vm/' . $id  . '/' . $_options_2;
                $fonction = "POST";
                break;
            case 'wifi':
                $config = 'api/' . $API_version . '/wifi/' . $_options;
                if ($_options == 'wps/start') {
                    $fonction = "POST";
                    $cmd_config = 'bssid';
                } else if ($_options == 'wps/stop') {
                    $fonction = "POST";
                    $cmd_config = 'session_id';
                } else if ($_options == 'mac_filter') {
                    log::add('Freebox_OS', 'debug', '───▶︎ ' . (__('Fonction', __FILE__)) . ' : ' . $_options_2['function']);
                    $fonction = $_options_2['function'];
                    if ($fonction != 'POST') {
                        $id = $_options_2['mac_address'] . '-' . $_options_2['filter'];
                        $parametre = null;
                    } else {
                        $_filter = $_options_2['filter'];
                        $mac_adress = $_options_2['mac_address'];
                        $comment = $_options_2['comment'];
                        $id = null;
                        $parametre = array("mac" => $mac_adress, "type" => $_filter, "comment" => $comment);
                    }
                    log::add('Freebox_OS', 'debug', '───▶︎ ' . (__('Fonction 2', __FILE__)) . ' : ' . $fonction);
                } else if ($_options == 'config' && $_options_2 == 'mac_filter_state') {
                    $cmd_config = 'mac_filter_state';
                } else {
                    $cmd_config = 'enabled';
                }
                if ($_options == 'wifi') {
                    $config_log = (__('Mise à jour de : Etat du Wifi', __FILE__)) . ' ' . $_options;
                } else {
                    $config_log = null;
                }
                break;
            case 'set_tiles':
                //log::add('Freebox_OS', 'debug', '───▶︎ Info nodeid : ' . $nodeId . ' -- Id: ' . $id . ' -- Paramètre : ' . $parametre);
                $config = 'api/' . $API_version . '/home/endpoints/';
                $cmd_config = 'enabled';
                $config_log = (__('Mise à jour de', __FILE__)) . ' : ';
                break;
        }
        if (isset($parametre['value_type'])) {
            if ($parametre['value_type'] === 'bool' && $parametre['value'] === 1) {
                $parametre['value'] = 'true';
            } elseif ($parametre['value_type'] === 'bool' && $parametre['value'] === 0) {
                $parametre['value'] = 'false';
            }
        } elseif ($parametre == '0') {
            $parametre = false;
        } elseif ($parametre == '1') {
            if ($_options != 'wps/stop') {
                $parametre = true;
            }
        }
        if ($update == 'parental' || $update == 'VM') {
            $return = $this->fetch('/' . $config . '', $parametre, $fonction, $Type_log);
        } else if ($update == 'universal_put') {
            $return = $this->fetch('/' . $config,  $_options_2, $fonction, $Type_log);
            if (isset($return['success'])) {
                $return_ID = $return['success'];
            } else {
                $return_ID = $return;
            }
            return $return_ID;
        } else if ($update == 'set_tiles') {
            $return = $this->fetch('/' . $config . $nodeId . '/' . $id, $parametre, "PUT", $Type_log);
        } else if ($_options == 'mac_filter') {
            $return = $this->fetch('/' . $config  . '/' . $id, $parametre, $fonction, $Type_log);
        } else if ($update == 'phone') {
            $return = $this->fetch('/' . $config . '/', null, $fonction, $Type_log);
        } else {
            if ($config_log != null) {
                log::add('Freebox_OS', 'debug', '───▶︎ ' . $config_log . ' ' . (__('avec la valeur', __FILE__)) . ' : ' . $parametre);
            }
            if ($cmd_config != null) {
                $requet = array($cmd_config => $parametre);
            } else {
                $requet = null;
            }
            $return = $this->fetch('/' . $config . '/', $requet, $fonction, $Type_log);

            if ($return === false) {
                return false;
            }
            switch ($update) {
                case 'wifi':
                case '4G':
                    return $return['result']['enabled'];

                    break;
                case 'settile':
                    return $return['result'];
                    break;
                default:
                    return $return;
                    break;
            }
        }
    }

    public function nb_appel_absence()
    {
        // Outgoing
        $listNumber_outgoing = '';
        $listNumber_outgoing_new = '';
        // Missed
        $listNumber_missed = '';
        $listNumber_missed_new = '';
        // Accepted
        $listNumber_accepted = '';
        $listNumber_accepted_new = '';
        $Free_API = new Free_API();
        $result = $Free_API->universal_get('universalAPI', null, null, 'call/log/', true, true, true);
        $retourFbx = array('missed' => 0, 'listmissed' => "", 'missed_new' => 0, 'listmissed_new' => "", 'accepted' => 0, 'listaccepted' => "", 'accepted_new' => 0, 'listaccepted_new' => "", 'outgoing' => 0, 'listoutgoing' => "");
        if ($result === false) {
            return false;
        }
        if (isset($result['success'])) {
            if ($result['success']) {
                $timestampToday = mktime(0, 0, 0, date('n'), date('j'), date('Y'));

                if (isset($result['result'])) {
                    $nb_call = count($result['result']);
                    // Outgoing
                    $cptAppel_outgoing = 0;
                    $cptAppel_outgoing_new = 0;
                    // Missed
                    $cptAppel_missed = 0;
                    $cptAppel_missed_new = 0;
                    // Accepted
                    $cptAppel_accepted = 0;
                    $cptAppel_accepted_new = 0;
                    for ($k = 0; $k < $nb_call; $k++) {
                        $jour = $result['result'][$k]['datetime'];
                        $time = date('H:i', $result['result'][$k]['datetime']);
                        if ($timestampToday <= $jour) {
                            if ($result['result'][$k]['name'] == null) {
                                $name = $result['result'][$k]['number'];
                            } else {
                                $name = $result['result'][$k]['name'];
                            }

                            if ($result['result'][$k]['type'] == 'missed') {
                                if ($result['result'][$k]['new'] == true) {
                                    // Uniquement les nouveaux appels
                                    $cptAppel_missed_new++;
                                    if ($listNumber_missed_new === '') {
                                        $newligne = null;
                                    } else {
                                        $newligne = '<br>';
                                    }
                                    $listNumber_missed_new .= $newligne . $name . ' ' . (__('à', __FILE__)) . ' ' . $time . ' ' . (__('de', __FILE__)) . ' ' . $this->fmt_duree($result['result'][$k]['duration']);
                                } else {
                                    // Ensemble des appels
                                    $cptAppel_missed++;
                                    if ($listNumber_missed === '') {
                                        $newligne = null;
                                    } else {
                                        $newligne = '<br>';
                                    }
                                    $listNumber_missed .= $newligne . $name . ' ' . (__('à', __FILE__)) . ' ' . $time . ' ' . (__('de', __FILE__)) . ' '  . $this->fmt_duree($result['result'][$k]['duration']);
                                }
                            }
                            if ($result['result'][$k]['type'] == 'accepted') {
                                if ($result['result'][$k]['new'] == true) {
                                    // Uniquement les nouveaux appels
                                    $cptAppel_accepted_new++;
                                    if ($listNumber_accepted_new === '') {
                                        $newligne = null;
                                    } else {
                                        $newligne = '<br>';
                                    }
                                    $listNumber_accepted_new .= $newligne . $name . ' ' . (__('à', __FILE__)) . ' ' . $time . ' ' . (__('de', __FILE__)) . ' ' . $this->fmt_duree($result['result'][$k]['duration']);
                                } else {
                                    // Ensemble des appels
                                    $cptAppel_accepted++;
                                    if ($listNumber_accepted === '') {
                                        $newligne = null;
                                    } else {
                                        $newligne = '<br>';
                                    }
                                    $listNumber_accepted .= $newligne . $name . ' ' . (__('à', __FILE__)) . ' ' . $time . ' ' . (__('de', __FILE__)) . ' '  . $this->fmt_duree($result['result'][$k]['duration']);
                                }
                            }
                            if ($result['result'][$k]['type'] == 'outgoing') {
                                $cptAppel_outgoing++;
                                if ($result['result'][$k]['new'] == true) {
                                    $cptAppel_outgoing_new++;
                                }
                                if ($listNumber_outgoing === '') {
                                    $newligne = null;
                                } else {
                                    $newligne = '<br>';
                                }
                                $listNumber_outgoing .= $newligne . $name . ' ' . (__('à', __FILE__)) . ' ' . $time . ' ' . (__('de', __FILE__)) . ' ' . $this->fmt_duree($result['result'][$k]['duration']);
                            }
                        }
                    }
                    $retourFbx = array('missed' => $cptAppel_missed, 'listmissed' => $listNumber_missed, 'missed_new' => $cptAppel_missed_new, 'listmissed_new' => $listNumber_missed_new, 'accepted' => $cptAppel_accepted, 'listaccepted' => $listNumber_accepted, 'accepted_new' => $cptAppel_accepted_new, 'listaccepted_new' => $listNumber_accepted_new, 'outgoing' => $cptAppel_outgoing, 'listoutgoing' => $listNumber_outgoing);
                }
                return $retourFbx;
            } else {
                return false;
            }
        } else {
            log::add('Freebox_OS', 'debug', ':fg-warning:───▶︎ ' .  (__('AUCUN APPEL', __FILE__))  .  ':/fg:');
            return $retourFbx;
        }
    }

    function fmt_duree($duree)
    {
        if (floor($duree) == 0) return '0s';
        $h = floor($duree / 3600);
        $m = floor(($duree % 3600) / 60);
        $s = $duree % 60;
        $fmt = '';
        if ($h > 0) $fmt .= $h . 'h ';
        if ($m > 0) $fmt .= $m . 'min ';
        if ($s > 0) $fmt .= $s . 's';
        return ($fmt);
    }

    public function mac_filter_list()
    {
        $API_version = $this->API_version;
        $whitelist = null;
        $blacklist = null;
        $Type_log = array(
            "log_request" =>  true,
            "log_result" => true
        );
        $result = $this->fetch('/api/' . $API_version . '/wifi/mac_filter/', null, null, $Type_log);
        if ($result === false)
            return false;
        if ($result['success']) {
            if (isset($result['result'])) {
                $nb_mac = count($result['result']);

                for ($k = 0; $k < $nb_mac; $k++) {
                    $name = $result['result'][$k]['hostname'];
                    if ($result['result'][$k]['type'] == 'whitelist') {
                        if ($whitelist == null) {
                            $whitelist  = $name . " - " . $result['result'][$k]['mac'] . " - " . $result['result'][$k]['comment'];
                        } else {
                            $whitelist  .= '<br>' . $name . " - " . $result['result'][$k]['mac'] . " - " . $result['result'][$k]['comment'];
                        }
                    }
                    if ($result['result'][$k]['type'] == 'blacklist') {
                        if ($blacklist == null) {
                            $blacklist .= $name . " - " . $result['result'][$k]['mac'] . " - " . $result['result'][$k]['comment'];
                        } else {
                            $blacklist .= '<br>' . $name . " - " . $result['result'][$k]['mac'] . " - " . $result['result'][$k]['comment'];
                        }
                    }
                }
                $return = array('blacklist' => $blacklist, 'whitelist' => $whitelist);
            } else {
                $return = array('blacklist' => 'vide', 'whitelist' => 'vide');
            }
            return $return;
        } else {
            return false;
        }
    }
}
