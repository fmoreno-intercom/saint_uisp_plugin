<?php
require_once __DIR__ . '/../vendor/autoload.php';
class ubnt_email {
    private $clientcrm;
    private $log;
    private $db;
    private $organizationid;

    function __construct($client, $db, $pluginUrl) {
        $actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $this->organizationid = 1;
        $this->clientcrm = $client;        
        $this->db = $db;
        $this->log = ($pluginUrl == $actual_link) ? \Ubnt\UcrmPluginSdk\Service\PluginLogManager::create() : null;
    }
    function send_email($organizationid, $clientId, $to_send, $subject, $body) {
        $result = $this->clientcrm->post(
            'email/' . $organizationid . '/enqueue',
            [
                'clientId' => $clientId,
                'to' => $to_send,
                'subject' => $subject,
                'body' => $body
            ]
        );
        return $result;
    }
    function send_mail_change_plan($email, $clientname, $cliendid, $pass_new, $plan_name, $precio, $tmp_pass) {
        $subject = "Cambio de contraseña IPTV";
        $body = $this->email_body_cambio_plan($cliente_name, $plan_name, $precio, $tmp_pass);
        if ($body) {
            $result = $this->send_email($this->organizationid, $cliendid, $email, $subject, $body);
        } else {
            $result = false;
        }
        
        return $result;
    }
    function send_mail_change_password($email, $clientname, $cliendid, $pass_new) {
        $subject = "Cambio de contraseña IPTV";
        $body = $this->email_body_cambio_pwd($clientname, $pass_new);
        if ($body) {
            $result = $this->send_email($this->organizationid, $cliendid, $email, $subject, $body);
        } else {
            $result = false;
        }
        
        return $result;
    }
    function email_body_cambio_plan($cliente_name, $plan_name, $precio, $tmp_pass) {
        $body = <<<EOD
        <p>Saludos $cliente_name,</p>
        <p>Gracias por activar nuestro servicio de IPTV</p>
        <p>El Plan contratado es: $plan_name y el costo es de: $precio$</p>
        <br>
        <p>Su contraseña temporal es: $tmp_pass</p>
        <br>
        <p>Para poder empezar a disfrutar el servicio, necesita descargar la applicacion de Netplu IPTC</p>
        <div class="dowloadbuttons">
            <h2>Haz clic aquí para descargar</h2>
            <a href="https://play.google.com/store/apps/details?id=com.nathnetwork.netplusintercomservicios" target="_blank"><img class="playstore bnt" src="http://www2.netplus.com.ve/wp-content/uploads/2022/01/playstorebutton.png" title="Disponible próximamente" style="width: 26%"/></a>
            <a href="http://www2.netplus.com.ve/apk/Netplus-IntercomServicios-5.0.1-v722.apk"><img class="btn" src="http://www2.netplus.com.ve/wp-content/uploads/2022/01/descargadirecta.png" title="Descarga el APK" style="width: 30%" /></a>
        </div>
        <br>
        <p>Para cualquier duda o comentario, por favor comuníquese con nosotros a través de nuestro chat en nuestra página web: <a href="https://mi.intercomservisios.com">https://mi.intercomservicios.com</a></p>
        EOD;
        return $body;
    }
    function email_body_cambio_pwd($cliente_name, $tmp_pass) {
        $body = <<<EOD
        <p>Saludos $cliente_name,</p>
        <p>Ha solicitado un cambio de contraseña</p>
        <p>Su nueva contraseña es: $tmp_pass</p>
        <br>
        <p>Para poder seguir disfrutando del servicio, recuerde tener la applicacion Netplus IPTV</p>
        <p>Si no la posee, puede descargarla de los siguiente enlaces:</p>
        <div class="dowloadbuttons">
            <h2>Haz clic aquí para descargar</h2>
            <a href="https://play.google.com/store/apps/details?id=com.nathnetwork.netplusintercomservicios" target="_blank"><img class="playstore bnt" src="http://www2.netplus.com.ve/wp-content/uploads/2022/01/playstorebutton.png" title="Disponible próximamente" style="width: 26%"/></a>
            <a href="http://www2.netplus.com.ve/apk/Netplus-IntercomServicios-5.0.1-v722.apk"><img class="btn" src="http://www2.netplus.com.ve/wp-content/uploads/2022/01/descargadirecta.png" title="Descarga el APK" style="width: 30%" /></a>
        </div>
        <br>
        <p>Su contraseña temporal es: %s</p>
        <br>
        <p>Para cualquier duda o comentario, por favor comuníquese con nosotros a través de nuestro chat en nuestra página web: <a href="https://mi.intercomservisios.com">https://mi.intercomservicios.com</a></p>
        EOD;
        //$body = sprintf($mybody, $cliente_name, $tmp_pass);
        //$body = sprintf("Test %s y %s", $cliente_name, $tmp_pass);
        return $body;
    }

}