<?php

error_reporting(E_ALL);

if (0 > version_compare(PHP_VERSION, '5')) {
    die('This file was generated for PHP 5');
}

//require_once('Portal Class Diagram/class.Logger.php');

Class Http_Request extends CI_Model {

    public function __construct() {
        parent::__construct();

//        $this->load->model("Comman_Settings");
//        $this->Comman_Settings->load();
    }

    public function httpGet($url) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//  curl_setopt($ch,CURLOPT_HEADER, false); 

        $output = curl_exec($ch);

        curl_close($ch);
        return $output;
    }

    public function httpPost($url=null, $para = array(), $TIMEOUT = 0) {

//        if ($TIMEOUT < 0) {
//            $TIMEOUT = $this->Comman_Settings->HTTP_Reguest_Timeout;
//        }
//        
//$url ='http://127.0.0.1/CentralSM/test_srv.php';
//$context  = stream_context_create($stream_options);
//$response = file_get_contents($url, false, $context);
        $data = http_build_query($para);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, $TIMEOUT);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-type: application/x-www-form-urlencoded',
            'Content-length: ' . strlen($data)
        ));
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

}

/* end of class Portal Class Diagram_PMS_Handler */
?>