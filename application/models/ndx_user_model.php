<?php

/**
 * Includes the User_Model class as well as the required sub-classes
 * @package codeigniter.application.models
 */

/**
 * User_Model extends codeigniters base CI_Model to inherit all codeigniter magic!
 * @author Leon Revill
 * @package codeigniter.application.models
 */
class NDX_User_Model extends CI_Model {
    /*
     * A private variable to represent each column in the database
     */

    public $_ID;
    public $_USER_NAME;
    public $_PASSWORD;
    public $_MA_ADDR;
    public $_MAC_ADDR;
    public $_EXPIRY_TIME;
    public $_BANDWIDTH_UP;
    public $_BANDWIDTH_DOWN;
    public $_PORTAL_SUB_ID;
    public $_DATA_VOLUME;
    public $_IP_TYPE;
    

    function __construct() {

        parent::__construct();

//        $this->_ID = "";
//        $this->_USER_NAME = "";
        $this->_PASSWORD = "";
//        $this->_MA_ADDR = "";
//        $this->_MAC_ADDR = "";
//
//        $this->_EXPIRY_TIME = "";
        $this->_BANDWIDTH_UP = "4096";
        $this->_BANDWIDTH_DOWN = "4096";
        $this->_IP_TYPE = "PRIVATE";
//
//        $this->_PORTAL_SUB_ID = "";
    }

    public function get_ID() {
        return $this->_ID;
    }

    public function set_ID($_ID) {
        $this->_ID = $_ID;
    }

    public function get_USER_NAME() {
        return $this->_USER_NAME;
    }

    public function set_USER_NAME($_USER_NAME) {
        $this->_USER_NAME = $_USER_NAME;
    }

    public function get_PASSWORD() {
        return $this->_PASSWORD;
    }

    public function set_PASSWORD($_PASSWORD) {
        $this->_PASSWORD = $_PASSWORD;
    }

    public function get_MA_ADDR() {
        return $this->_MA_ADDR;
    }

    public function set_MA_ADDR($_MA_ADDR) {
        $this->_MA_ADDR = $_MA_ADDR;
    }

    public function get_MAC_ADDR() {
        return $this->_MAC_ADDR;
    }

    public function set_MAC_ADDR($_MAC_ADDR) {
        $this->_MAC_ADDR = $_MAC_ADDR;
    }

    public function get_EXPIRY_TIME() {
        return $this->_EXPIRY_TIME;
    }

    public function set_EXPIRY_TIME($_EXPIRY_TIME) {
        $this->_EXPIRY_TIME = $_EXPIRY_TIME;
    }

    public function get_BANDWIDTH_UP() {
        return $this->_BANDWIDTH_UP;
    }

    public function set_BANDWIDTH_UP($_BANDWIDTH_UP) {
        $this->_BANDWIDTH_UP = $_BANDWIDTH_UP;
    }

    public function get_BANDWIDTH_DOWN() {
        return $this->_BANDWIDTH_DOWN;
    }

    public function set_BANDWIDTH_DOWN($_BANDWIDTH_DOWN) {
        $this->_BANDWIDTH_DOWN = $_BANDWIDTH_DOWN;
    }

    public function get_PORTAL_SUB_ID() {
        return $this->_PORTAL_SUB_ID;
    }

    public function set_PORTAL_SUB_ID($_PORTAL_SUB_ID) {
        $this->_PORTAL_SUB_ID = $_PORTAL_SUB_ID;
    }

    public function get_DATA_VOLUME() {
        return $this->_DATA_VOLUME;
    }

    public function set_DATA_VOLUME($_DATA_VOLUME) {
        $this->_DATA_VOLUME = $_DATA_VOLUME;
    }

    public function getXML_RADIUS_LOGIN() {

        $xml = '<XML>' .
                '<USG COMMAND="RADIUS_LOGIN">' .
                '<SUB_USER_NAME>' . $this->_USER_NAME . '</SUB_USER_NAME>' .
                '<SUB_PASSWORD>' . $this->_PASSWORD . '</SUB_PASSWORD>' .
                '<SUB_MAC_ADDR>' . $this->_MA_ADDR . '</SUB_MAC_ADDR>' .
                '<PORTAL_SUB_ID>' . $this->_PORTAL_SUB_ID . '</PORTAL_SUB_ID></USG></XML>';

        return $xml;
    }

    public function getXML_RADIUS_LOGOUT() {

        $xml = '<XML>' .
                '<USG COMMAND="LOGOUT">' .
                '<SUB_MAC_ADDR>' . $this->_MA_ADDR . '</SUB_MAC_ADDR>' .
                '</USG>
                </XML>';

        return $xml;
    }

    public function getXML_CACHE_UPDATE() {

        $xml = '<XML>' .
                '<USG COMMAND="CACHE_UPDATE" MAC_ADDR="'. $this->_MA_ADDR . '" />' .
                '</XML>';
        return $xml;
    }

    public function getXML_USER_ADD() {

        $xml = '<XML>' .
                '<USG COMMAND="USER_ADD" MAC_ADDR="' . $this->_MA_ADDR . '">' .
                '<USER_NAME>' . $this->_USER_NAME . '</USER_NAME>' .
                '<PASSWORD ENCRYPT="TRUE">' . $this->_PASSWORD . '</PASSWORD>' .
                '<IP_TYPE>' . $this->_IP_TYPE . '</IP_TYPE>' .
                '<EXPIRY_TIME UNITS="MINUTES">' . $this->_EXPIRY_TIME . '</EXPIRY_TIME>' .
                '<BANDWIDTH_UP>' . $this->_BANDWIDTH_UP . '</BANDWIDTH_UP>' .
                '<BANDWIDTH_DOWN>' . $this->_BANDWIDTH_DOWN . '</BANDWIDTH_DOWN></USG>' .
                '</XML>';

        return $xml;
    }

    public function getXML_USER_DELETE() {

        $xml = '<XML>' .
                '<USG COMMAND="USER_DELETE">' .
                '<USER ID_TYPE="USER_NAME">' . $this->_USER_NAME . '</USER>' .
                '</USG>' .
                '</XML>';

        return $xml;
    }
    
    
    public function getXML_USER_DELETE_MAC() {

        $xml = '<XML>' .
                '<USG COMMAND="USER_DELETE">' .
                '<USER ID_TYPE="MAC_ADDR">' . $this->_MA_ADDR . '</USER>' .
                '</USG>' .
                '</XML>';

        return $xml;
    }

    public function getXML_USER_QUERY() {

        $xml = '<XML>' .
                '<USG COMMAND="USER_QUERY">' .
                '<USER ID_TYPE="MAC_ADDR">' . $this->_MA_ADDR . '</USER>' .
                '</USG>' .
                '</XML>';

        $xml = '' .
                '<USG COMMAND="USER_QUERY">' .
                '<USER ID_TYPE="MAC_ADDR">' . $this->_MA_ADDR . '</USER>' .
                '</USG>' .
                '';
        return $xml;
    }
    
    
    public function getXML_SUBSCRIBER_QUERY_CURRENT() {

        $xml = '' .
                '<USG COMMAND="SUBSCRIBER_QUERY_CURRENT">' .
                '<MAC_ADDR>' . $this->_MA_ADDR . '</MAC_ADDR>' .
                '</USG>' .
                '';
        return $xml;
    }

}

?>