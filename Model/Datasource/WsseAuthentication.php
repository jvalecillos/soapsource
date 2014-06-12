<?php
/**
 * Basic authentication
 *
 * @package       Cake.Network.Http
 */
class WsseAuthentication {

/**
 * Authentication
 *
 * @param HttpSocket $http
 * @param array $authInfo
 * @return void
 * @see http://www.ietf.org/rfc/rfc2617.txt
 */
	public static function authentication(SoapClient $soap, $authInfo) {
		//Set headers for soapclient object 
	       	$header = self::_generateHeader($authInfo['headers']['ns'], $authInfo['headers']['container'], $authInfo['login'], $authInfo['password']);
                $soap->__setSoapHeaders(array($header)); 
	}

/**
 * Generate wsse authentication header
 *
 * @param string $namespace
 * @param string $container
 * @param string $user
 * @param string $pass
 * @return SoapHeader
 */
	protected static function _generateHeader($namespace, $container, $username, $password) {
		//Create Soap Variables for UserName and Password 
                $objSoapVarUser = new SoapVar($username, XSD_STRING, NULL, $namespace, NULL, $namespace); 
                $objSoapVarPass = new SoapVar($password, XSD_STRING, NULL, $namespace, NULL, $namespace); 

                //Create Object and pass in soap var 
                $objWSSEAuth = new Object();
                $objWSSEAuth->Username = $objSoapVarUser;
                $objWSSEAuth->Password = $objSoapVarPass;

                //Create SoapVar out of object
                $objSoapVarWSSEAuth = new SoapVar($objWSSEAuth, SOAP_ENC_OBJECT, NULL, $namespace, 'UsernameToken', $namespace); 

                //Create object for Token node 
                $objWSSEToken = new Object();
                $objWSSEToken->UsernameToken = $objSoapVarWSSEAuth;

                //Create SoapVar out of object
                $objSoapVarWSSEToken = new SoapVar($objWSSEToken, SOAP_ENC_OBJECT, NULL, $namespace, 'UsernameToken', $namespace); 

                //Create SoapVar for 'Security' node 
                $objSoapVarHeaderVal = new SoapVar($objSoapVarWSSEToken, SOAP_ENC_OBJECT, NULL, $namespace, $container, $namespace);   

                //Create header object out of security soapvar 
                $objSoapVarWSSEHeader = new SoapHeader($namespace, $container, $objSoapVarHeaderVal); 

                return $objSoapVarWSSEHeader;        
	}

}