<?php
App::import('Soap.Model/Datasource', 'WsseAuthentication');
/**
 * SoapSource
 * 
 * A SOAP Client Datasource
 * Connects to a SOAP server using the configured wsdl file
 *
 * PHP Version 5
 *
 * Copyright 2008 Pagebakers, www.pagebakers.nl
 *
 * This library is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link        http://github.com/Pagebakers/soapsource/
 * @copyright   Copyright 2008 Pagebakers
 * @license     http://www.gnu.org/licenses/lgpl.html
 *
*/
class SoapSource extends DataSource {
    
    /**
     * Description for this DataSource
     *
     * @var string
     */
    public $description = 'Soap Client DataSource';

    /**
     * The SoapClient instance
     *
     * @var object
     */
    public $client = null;
    
    /**
     * The current connection status
     *
     * @var boolean
     */
    public $connected = false;

    /**
     * Response headers of the last request
     * @var array
     */
    private $responseHeaders;
    
    /**
     * Response errors
     * @var array
     */
    public $errors;

    /**
     * Result
     */
    public $result;

    /**
     * The default configuration
     *
     * @var array
     */
    public $_baseConfig = array(
        'wsdl' => null,
        'location' => '',
        'uri' => '',
        'login' => '',
        'password' => '',
        'authentication' => 'SOAP_AUTHENTICATION_BASIC'
    );
    
    /**
     * Constructor
     *
     * @param array $config An array defining the configuration settings
     */
    public function __construct($config) {
        parent::__construct($config);
        
        $this->connect();
    }

    /**
     * Connects to the SOAP server using the wsdl in the configuration
     *
     * @param array $config An array defining the new configuration settings
     * @return boolean True on success, false on failure
     */ 
    public function connect() {        
        if(!class_exists('SoapClient')) {
            $this->errors = 'Class SoapClient not found, please enable Soap extensions';
            $this->showError();
            return false;
        }
        // Set Soap options
        $options = array('trace' => Configure::read('debug') > 0);
        if(!empty($this->config['location'])) {
            $options['location'] = $this->config['location'];
        }
        if(!empty($this->config['uri'])) {
            $options['uri'] = $this->config['uri'];
        }
        if(!empty($this->config['login'])){
            $options['login'] = $this->config['login'];
            $options['password'] = $this->config['password'];
            $options['authentication'] = $this->config['authentication'];
        }
        if(!empty($this->config['proxy_host'])) {
            $options['proxy_host'] = $this->config['proxy_host'];
        }
        if(!empty($this->config['proxy_port'])) {
            $options['proxy_port'] = $this->config['proxy_port'];
        }
        
         /** Workaround to prevent SoapClient throwing a RuntimeException **/
        if (extension_loaded('curl') && Configure::read('debug') > 0 && !empty($this->config['wsdl']) && empty($this->config['curl_off']))
        {
            $ch = curl_init($this->config['wsdl']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FAILONERROR, true);
            if (!curl_exec($ch))
            {
                throw new Exception("Unable to load WSDL File " . $this->config['wsdl']);
            }
        }
                
        try {
            $this->client = new SoapClient($this->config['wsdl'], $options);
        } catch(SoapFault $fault) {
            $this->errors = $fault->faultstring;
            $this->showError();
        }
        
        if ($this->client) {
            $this->connected = true;            
        }

        return $this->connected;
    }
    
    /**
     * Sets the SoapClient instance to null
     *
     * @return boolean True
     */
    public function close() {
        $this->client = null;
        $this->connected = false;
        return true;
    }

    /**
     * Returns the available SOAP methods
     *
     * @return array List of SOAP methods
     */
    public function listSources($data = null) {
       return $this->client->__getFunctions();
    }

/**
 * Used to create new records. The "C" CRUD.
 *
 * To-be-overridden in subclasses.
 *
 * @param Model $Model The Model to be created.
 * @param array $fields An Array of fields to be saved.
 * @param array $values An Array of values to save.
 * @return boolean success
 */
    public function create(Model $Model, $fields = null, $values = null) {

        if(!isset($Model->soapActions['create'])){
            return false;
        }

        return $this->query($Model->soapActions['create'], array($Model->request));
    }

/**
 * Used to read records from the Datasource. The "R" in CRUD
 *
 * To-be-overridden in subclasses.
 *
 * @param Model $Model The model being read.
 * @param array $queryData An array of query data used to find the data you want
 * @param integer $recursive Number of levels of association
 * @return mixed
 */
    public function read(Model $Model, $queryData = array(), $recursive = null) {
        
        if(!isset($Model->soapActions['read'])){
            return false;
        }

        return $this->query($Model->soapActions['read'], array($Model->request));
    }

/**
 * Update a record(s) in the datasource.
 *
 * To-be-overridden in subclasses.
 *
 * @param Model $Model Instance of the model class being updated
 * @param array $fields Array of fields to be updated
 * @param array $values Array of values to be update $fields to.
 * @param mixed $conditions
 * @return boolean Success
 */
    public function update(Model $Model, $fields = null, $values = null, $conditions = null) {
        if(!isset($Model->soapActions['update'])){
            return false;
        }

        return $this->query($Model->soapActions['update'], array($Model->request));
    }

/**
 * Delete a record(s) in the datasource.
 *
 * To-be-overridden in subclasses.
 *
 * @param Model $Model The model class having record(s) deleted
 * @param mixed $conditions The conditions to use for deleting.
 * @return boolean Success
 */
    public function delete(Model $Model, $conditions = null) {
        if(!isset($Model->soapActions['delete'])){
            return false;
        }

        return $this->query($Model->soapActions['delete'], array($Model->request));
    }

    /**
     * Query the SOAP server with the given method and parameters
     *
     * @return mixed Returns the result on success, false on failure
     */
    public function query() {
        $this->errors = false;
        
        if(!$this->connected) {
            return false;
        }
        
        $args = func_get_args();

        $method = null;
        $queryData = null;

        if(count($args) == 2) {
            $method = $args[0];
            $queryData = $args[1];
        } elseif(count($args) == 3 && !empty($args[2]) && !empty($this->config['headers'])) {
            $method = $args[0];
            $queryData = $args[1];
            $headerData = $args[2];
        } elseif(count($args) > 2 && !empty($args[1])) {
            $method = $args[0];
            $queryData = $args[1][0];
        }

        if(!isset($method) || !isset($queryData)) {
            return false;
        }

        WsseAuthentication::authentication($this->client, $this->config);

        try {                                    
            $this->result = $this->client->__soapCall($method, $queryData, null, null, $this->responseHeaders);             
        } catch (SoapFault $fault) {
            $this->errors = $fault->faultstring;
        }
        
        if($this->errors) {                                    
            return false;   
        } else {                                           
            return $this->result;
        }
    }

    /**
     * Returns the HTTP headers of the last SOAP response
     * @return array 
     */
    public function getHttpResponseHeaders(){
        return $this->client->__getLastResponseHeaders();
    }

    /**
     * Returns the SOAP headers of the last SOAP response
     * @return array
     */
    public function getResponseHeaders() {
        return $this->responseHeaders;
    }
    
    /**
     * Returns the last SOAP response
     *
     * @return string The last SOAP response
    */
    public function getResponse() {
       return $this->client->__getLastResponse();
    }
  
    /**
     * Returns the last SOAP request
     *
     * @return string The last SOAP request
    */  
    public function getRequest() {
        return $this->client->__getLastRequest();
    }
    
    /**
     * Shows an error message and outputs the SOAP result if passed
     *
     * @param string $result A SOAP result
     * @return string The last SOAP response
    */
    public function showError($result = null) {
        if(Configure::read() > 0) {
            if($this->errors) {                
                trigger_error('<span style = "color:Red;text-align:left"><b>SOAP Error:</b> ' .$this->errors . '</span>', E_USER_WARNING);
            }
            if($result) {
                e(sprintf("<p><b>Result:</b> %s </p>", $result));
            }
        }
    }

}
?>