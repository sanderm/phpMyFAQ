<?php
/**
* $Id: Auth.php,v 1.14 2007-08-21 20:16:36 thorstenr Exp $
*
* manages user authentication.
*
* Subclasses of Auth implement authentication functionality with different
* types. The class AuthLdap for example provides authentication functionality
* LDAP-database access, AuthDb with database access.
*
* Authentication functionality includes creation of a new login-and-password
* deletion of an existing login-and-password combination and validation of
* given by a user. These functions are provided by the database-specific
* see documentation of the database-specific authentication classes AuthMysql,
* or AuthLdap for further details.
*
* Passwords are usually encrypted before stored in a database. For
* and security, a password encryption method may be chosen. See documentation
* Enc class for further details.
*
* Instead of calling the database-specific subclasses directly, the static
* selectDb(dbtype) may be called which returns a valid database-specific
* object. See documentation of the static method selectDb for further details.
*
* @author    Lars Tiedemann <php@larstiedemann.de>
* @package   PMF
* @since     2005-09-30
* @copyright (c) 2005-2007 phpMyFAQ Team
*
* The contents of this file are subject to the Mozilla Public License
* Version 1.1 (the "License"); you may not use this file except in
* compliance with the License. You may obtain a copy of the License at
* http://www.mozilla.org/MPL/
*
* Software distributed under the License is distributed on an "AS IS"
* basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
* License for the specific language governing rights and limitations
* under the License.
*/

/**
* provides methods for password encryption.
*
* Subclasses (extends) of this class provide the encrypt() method that returns
* encrypted string. For special encryption methods, just create a new class as
* extend of this class and has the method encrypt().
*
* @author       Lars Tiedemann <php@larstiedemann.de>
* @since        2005-09-18
*/

require_once dirname(__FILE__).'/Enc.php';

/**
* manages user authentication.
*
* Subclasses of Auth implement authentication functionality with different
* types. The class AuthLdap for expamle provides authentication functionality
* LDAP-database access, AuthMysql with MySQL-database access.
*
* Authentication functionality includes creation of a new login-and-password
* deletion of an existing login-and-password combination and validation of
* given by a user. These functions are provided by the database-specific
* see documentation of the database-specific authentication classes AuthMysql,
* or AuthLdap for further details.
*
* Passwords are usually encrypted before stored in a database. For
* and security, a password encryption method may be chosen. See documentation
* Enc class for further details.
*
* Instead of calling the database-specific subclasses directly, the static
* selectDb(dbtype) may be called which returns a valid database-specific
* object. See documentation of the static method selectDb for further details.
*
* @access       public
* @author       Lars Tiedemann <php@larstiedemann.de>
* @package      PMF
* @since        2005-09-30
*/
class PMF_Auth
{
    /**
     * Error constants
     *
     * @var const
     */
    const PMF_USERERROR_NO_AUTHTYPE = 'Specified authentication access class could not be found. ';

    /**
     * private container that stores the encryption object.
     *
     * @var object
     */
    private $_enc_container = null;

    /**
     * public array that contains error messages.
     *
     * @var array
     */
    public $errors = array();

    /**
     * authentication access methods
     *
     * @var array
     */
    private $_auth_typemap = array(
        'db'   => 'AuthDb',
        'ldap' => 'AuthLdap');

    /**
     * Short description of attribute read_only
     *
     * @var bool
     */
    private $_read_only = false;

    /**
     * constructor
     *
     * @access   public
     * @return   void
     * @author   Lars Tiedemann, <php@larstiedemann.de>
     */
    function __construct()
    {
    }

    /**
     * instantiates a new encryption object, stores it in a private container
     * returns it.
     *
     * This method instantiates a new Enc object by calling the static
     * method. The specified encryption method enctype is passed to
     * The result is stored in the private container variable _enc_container and
     *
     * @param  string $enctype encryption type
     * @return object
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     */
    public function selectEncType($enctype)
    {
        $this->_enc_container = PMF_Enc::selectEnc($enctype);
        return $this->_enc_container;
    }

    /**
     * Returns a string with error messages.
     *
     * The string returned by error() contains messages for all errors that
     * during object procesing. Messages are separated by new lines.
     *
     * Error messages are stored in the public array errors.
     *
     * @return   string
     * @access   public
     * @author   Lars Tiedemann, <php@larstiedemann.de>
     */
    public function error()
    {
        $message = '';
        if (!is_array($this->errors)) {
            $this->errors = array((string) $this->errors);
        }
        foreach ($this->errors as $error) {
            $message .= $error."\n";
        }
        $message .= $this->_enc_container->error();
        return $message;
    }

    /**
     * Returns an authentication object with the specified database access.
     *
     * This method is called statically. The parameter database specifies the
     * of database access for the authentication object.
     *
     * If the given database-type is not supported, selectAuth() will return an
     * object without database access and with an error message. See the
     * of the error() method for further details.
     *
     * @param    string
     * @return   object
     * @access   public
     * @author   Lars Tiedemann, <php@larstiedemann.de>
     */
    public static function selectAuth($database)
    {
        // verify selected database
        $auth = new PMF_Auth();
        $database = strtolower($database);
        if (!isset($auth->_auth_typemap[$database])) {
            $auth->errors[] = PMF_USERERROR_NO_AUTHTYPE;
            return $auth;
        }
        $classfile = dirname(__FILE__)."/".$auth->_auth_typemap[$database].".php";
        if (!file_exists($classfile)) {
            $auth->errors[] = PMF_USERERROR_NO_AUTHTYPE;
            return $auth;
        }
        require_once $classfile;
        // instantiate
        $authclass = "PMF_".$auth->_auth_typemap[$database];
        $auth = new $authclass();
        return $auth;
    }

    /**
     * Short description of method read_only
     *
     * @param  bool $read_only boolean flag
     * @return bool
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     */
    public function read_only($read_only = null)
    {
        if ($read_only === null) {
            return $this->_read_only;
        }
        $old_read_only = $this->_read_only;
        $this->_read_only = (bool) $read_only;
        return $old_read_only;
    }

    /**
     * Short description of method encrypt
     *
     * @param  string $str string
     * @return string
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     */
    function encrypt($str)
    {
        return $this->_enc_container->encrypt($str);
    }
}