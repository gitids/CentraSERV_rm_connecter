<?php

error_reporting(E_ALL);


if (0 > version_compare(PHP_VERSION, '5')) {
    die('This file was generated for PHP 5');
}

Class PMS_Socket extends CI_Model {

    public $TransID = "";
    public $service_port = "";
    public $address = "";
    public $fsock;

    public function __construct() {
        parent::__construct();
        //ini_set('max_execution_time', 10);
        $this->service_port = 64000;
        $this->address = '192.168.1.181';
        
        $this->address = '127.0.0.1';
    }

    public function chkServer($hostip, $port) {
        
        return TRUE;
        //return FALSE;
        if (!$this->fsock = @fsockopen($hostip, $port, $errno, $errstr, 2)) { // attempt to connect 
            return FALSE;
            //echo "Server is down";
        } else {
            //echo "Server is up";
            if ($this->fsock) {
                @fclose($this->fsock); //close connection 
                return TRUE;
            }
        }
    }
    
    public function send_PMS($REQdata = "",$APP = "SRV") {
      
        if ($this->chkServer($this->address, $this->service_port) === FALSE) {
            return FALSE;
        }

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        
        if ($socket === false) {
            return FALSE;
            echo "Failed: " . socket_strerror(socket_last_error($socket)) . "\n";
        }
        
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 1, 'usec' => 100));
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 1, 'usec' => 100));

        // socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array("sec" => 1, "usec" => 1)); 
        //echo "Attempting to connect to '$address' on port '$service_port'...'\n";
        $result = @socket_connect($socket, $this->address, $this->service_port);
        //$result = $this->socket_connect_timeout($socket, $this->address, $this->service_port, 1000);

        if ($result === false) {
            return FALSE;
            echo "Failed: " . socket_strerror(socket_last_error($socket)) . "\n";
        }

        $in = $REQdata;
        $out = '';
        
        $data = array(
            'Message' => $in,
            'Type' => "S",
            'Status' => "S"
        );

        $this->db->set("AddDT", "NOW(3)", FALSE);
        $this->db->set("ProsDT", "NOW(3)", FALSE);
        $this->db->insert('communication', $data);
        //$this->db->insert('pms_communication', $data);

        socket_send($socket, $in, strlen($in), MSG_WAITALL);
        //socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => 1, "usec" => 5));
        //socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => 1, "usec" => 5));

        $r_data = "";
        $out = @socket_read($socket, 2048, PHP_BINARY_READ);
        //

        if ($out === FALSE) {
            
        } else {
            
            if(is_string($out))
            {
                
            }
            else
            {
              //$out=""
            }           
        }
        
        socket_close($socket);
        @fclose($this->fsock); //close connection 
        if (strlen($out) > 0)
            return $out;
        return "";
    }    
}

?>