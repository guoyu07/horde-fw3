<?php
/**
 * $Horde: passwd/config/backends.php.dist,v 1.41.2.6 2009-09-18 14:31:58 jan Exp $
 *
 * This file is where you specify what backends people use to change
 * their passwords. There are a number of properties that you can set
 * for each backend:
 *
 * name: This is the plaintext, english name that you want displayed
 *       to people if you are using the drop down server list.  Also
 *       displayed on the main page (input form).
 *
 * password policy: The password policies for this backend. You are responsible
 *                  for the sanity checks of these options. Options are:
 *              minLength   Minimum length of the password
 *              maxLength   Maximum length of the password
 *              maxSpace    Maximum number of white space characters
 *
 *                  The following are the types of characters required
 *                  in a password.  Either specific characters, character
 *                  classes, or both can be required.  Specific types are:
 *
 *              minUpper    Minimum number of uppercase characters
 *              minLower    Minimum number of lowercase characters
 *              minNumeric  Minimum number of numeric characters (0-9)
 *              minAlphaNum Minimum number of alphanumeric characters
 *              minAlpha    Minimum number of alphabetic characters
 *              minSymbol   Minimum number of alphabetic characters
 *
 *                  Alternatively (or in addition to), the minimum number of
 *                  character classes can be configured by setting the
 *                  following.  The valid range is 0 through 4 character
 *                  classes may be required for a password. The classes are:
 *                  'upper', 'lower', 'number', and 'symbol'.  For example:
 *                  A password of 'p@ssw0rd' satisfies three classes ('number',
 *                  'lower', and 'symbol'), while 'passw0rd' only satisfies
 *                  two classes ('lower' and 'symbols').
 *
 *              minClasses  Minimum number (0 through 4) of character classes.
 *
 * driver:    The Passwd driver used to change the password. Valid
 *            Valid values are currently:
 *              ldap         Change the password on a ldap server
 *              smbldap      Change the password on a ldap server for both
 *                           ldap and samba auth
 *              sql          Change the password for sql authentication
 *                           (exim, pam_mysql, horde)
 *              poppassd     Change the password via a poppassd server
 *              smbpasswd    Change the password via the smbpasswd command
 *              expect       Change the password via an expect script
 *              vmailmgr     Change the password via a local vmailmgr daemon
 *              vpopmail     Change the password for sql based vpopmail
 *              servuftp     Change the password via a servuftp server
 *              pine         Change the password in a Pine-encoded file
 *              composite    Allows you to chain multiple drivers together
 *
 * no_reset:  Do not reset the authenticated user's credentials on success.
 *
 * params:    A params array containing any additional information that the
 *            Passwd driver needs.
 *
 *            The following is a list of supported encryption/hashing
 *            methods supported by Passwd.
 *
 *            1) plain
 *            2) crypt or crypt-des
 *            3) crypt-md5
 *            4) crypt-blowfish
 *            5) md5-hex
 *            6) md5-base64
 *            7) smd5
 *            8) sha
 *            9) ssha
 *
 *            md5 passwords have caused some problems in the past because
 *            there are different definitions of what is a "md5
 *            password".  Systems implement them in a different
 *            manner.  If you are using OpenLDAP as your backend or
 *            have migrated your passwords from your OS based passwd
 *            file, you will need to use the md5-base64 hashing
 *            method.  If you are using a SQL database or used the PHP
 *            md5() method to create your passwords, you will need to
 *            use the md5-hex hashing method.
 *
 * preferred: This is only useful if you want to use the same
 *            backend.php file for different machines: if the Hostname
 *            of the Passwd Machine is identical to one of those in
 *            the preferred list, then the corresponding option in the
 *            select box will include SELECTED, i.e. it is selected
 *            per default. Otherwise the first entry in the list is
 *            selected.
 *
 * show_encryption: If you are using the sql or the vpopmail backend
 *                  you have the choice whether or not to store the
 *                  encryption type with the password. If you are
 *                  using for example an SQL based PAM you will most
 *                  likely not want to store the encryption type as it
 *                  would cause PAM to never match the passwords.
 *
 */

$backends['hordesql'] = array (
    'name' => 'Horde Authentication',
    'preferred' => '',
    'password policy' => array(
        'minLength' => 3,
        'maxLength' => 8,
        'maxSpace' => 0,
        'minUpper' => 1,
        'minLower' => 1,
        'minNumeric' => 1,
        'minSymbols' => 1
    ),
    'driver' => 'sql',
    'params' => array_merge($conf['sql'],
                            array('table' => 'horde_users',
                                  'user_col' => 'user_uid',
                                  'pass_col' => 'user_pass',
                                  'show_encryption' => false)),
);

$backends['poppassd'] = array(
    'name' => 'Example Poppassd Server',
    'preferred' => '',
    'password policy' => array(),
    'driver' => 'poppassd',
    'params' => array(
        'host' => 'localhost',
        'port' => 106
    )
);

$backends['servuftp'] = array(
    'name' => 'Example Serv-U FTP Server',
    'preferred' => '',
    'password policy' => array(),
    'driver' => 'servuftp',
    'params' => array(
        'host' => 'localhost',
        'port' => 106,
        'timeout' => 30
    )
);

$backends['expect'] = array(
    'name' => 'Example Expect Script',
    'preferred' => '',
    'password policy' => array(),
    'driver' => 'expect',
    'params' => array(
        'program' => '/usr/bin/expect',
        'script' => dirname(__FILE__) . '/../scripts/passwd_expect',
        'params' => '-telnet -host localhost -output /tmp/passwd.log'
    )
);

$backends['sudo_expect'] = array(
    'name' => 'Example Expect with Sudo Script',
    'preferred' => '',
    'password policy' => array(),
    'driver' => 'procopen',
    'params' => array(
        'program' => '/usr/bin/expect ' . dirname(__FILE__) . '/../scripts/passwd_expect -sudo'
    )
);

$backends['smbpasswd'] = array(
    'name' => 'Example Samba Server',
    'preferred' => '',
    'password policy' => array(),
    'driver' => 'smbpasswd',
    'params' => array(
        'program' => '/usr/bin/smbpasswd',
        'host' => 'localhost'
    )
);

// NOTE: to set the ldap userdn, see horde/config/hooks.php
$backends['ldap'] = array(
    'name' => 'Example LDAP Server',
    'preferred' => 'www.example.com',
    'password policy' => array(
        'minLength' => 3,
        'maxLength' => 8
    ),
    'driver' => 'ldap',
    'params' => array(
        'host' => 'localhost',
        'port' => 389,
        'basedn' => 'o=example.com',
        'uid' => 'uid',
        // these attributes will enable shadow password policies.
        // 'shadowlastchange' => 'shadowlastchange',
        // 'shadowmin' => 'shadowmin',
        // this will be appended to the username when looking for the userdn.
        'realm' => '',
        'encryption' => 'crypt',
        // make sure the host == cn in the server certificate
        'tls' => false
    )
);

// NOTE: to set the ldap userdn, see horde/config/hooks.php
$backends['ldapadmin'] = array(
    'name' => 'Example LDAP Server with Admin Bindings',
    'preferred' => 'www.example.com',
    'password policy' => array(
        'minLength' => 3,
        'maxLength' => 8
    ),
    'driver' => 'ldap',
    'params' => array(
        'host' => 'localhost',
        'port' => 389,
        'basedn' => 'o=example.com',
        'admindn' => 'cn=admin,o=example.com',
        'adminpw' => 'somepassword',

        // LDAP object key attribute
        'uid' => 'uid',

        // these attributes will enable shadow password policies.
        // 'shadowlastchange' => 'shadowlastchange',
        // 'shadowmin' => 'shadowmin',
        'attribute' => 'clearPassword',

        // this will be appended to the username when looking for the userdn.
        'realm' => '',

        // Use this filter when searching for the user's DN.
        'filter' => '',

        // Hash method to use when storing the password
        'encryption' => 'crypt',
    
        // Only applies to LDAP servers. If set, should be 0 or 1. See the LDAP 
        // documentation about the corresponding parameter REFERRALS.
        // Windows 2003 Server require to set this parameter to 0
        //'referrals' => 0,
        

        // Whether to enable TLS for this LDAP connection
        // Note: make sure the host matches cn in the server certificate
        'tls' => false
    )
);

// NOTE: to set the ldap userdn, see horde/config/hooks.php
// NOTE: to make work with samba 2.x schema you must change lm_attribute and
// nt_attribute
$backends['smbldap'] = array(
    'name' => 'Example Samba/LDAP Server',
    'preferred' => 'www.example.com',
    'password policy' => array(
        'minLength' => 3,
        'maxLength' => 8
    ),
    'driver' => 'smbldap',
    'params' => array(
        'host' => 'localhost',
        'port' => 389,
        'basedn' => 'o=example.com',
        'uid' => 'uid',
        // This will be appended to the username when looking for the userdn.
        'realm' => '',
        'encryption' => 'crypt',
        // Make sure the host == cn in the server certificate.
        'tls' => false,
        // If any of the following attributes are commented out, they
        // won't be set on the LDAP server.
        'lm_attribute' => 'sambaLMPassword',
        'nt_attribute' => 'sambaNTPassword',
        'pw_set_attribute' => 'sambaPwdLastSet',
        'pw_expire_attribute' => 'sambaPwdMustChange',
         // The number of days until samba passwords expire. If this
         // is commented out, passwords will never expire.
        'pw_expire_time' => 180,
    )
);

$backends['sql'] = array (
    'name' => 'Exampe SQL Server',
    'preferred' => '',
    'password policy' => array(
        'minLength' => 3,
        'maxLength' => 8,
        'maxSpace' => 0,
        'minUpper' => 1,
        'minLower' => 1,
        'minNumeric' => 1,
        'minSymbols' => 1
    ),
    'driver' => 'sql',
    'params' => array(
        'phptype'    => 'mysql',
        'hostspec'   => 'localhost',
        'username'   => 'dbuser',
        'password'   => 'dbpasswd',
        'encryption' => 'md5-hex',
        'database'   => 'db',
        'table'      => 'users',
        'user_col'   => 'user_uid',
        'pass_col'   => 'user_pass',
        'show_encryption' => false
        // The following two settings allow you to specify custom queries for
        // lookup and modify functions if special functions need to be
        // performed.  In places where a username or a password needs to be
        // used, refer to this placeholder reference:
        //    %d -> gets substituted with the domain
        //    %u -> gets substituted with the user
        //    %U -> gets substituted with the user without a domain part
        //    %p -> gets substituted with the plaintext password
        //    %e -> gets substituted with the encrypted password
        //
        // 'query_lookup' => 'SELECT user_pass FROM horde_users WHERE user_uid = %u',
        // 'query_modify' => 'UPDATE horde_users SET user_pass = %e WHERE user_uid = %u',
    )
);

$backends['vmailmgr'] = array(
    'name' => 'Example VMailMgr Server',
    'preferred' => '',
    'password policy' => array(),
    'driver' => 'vmailmgr',
    'params' => array(
        'vmailinc' => '/your/path/to/the/vmail.inc'
    )
);

$backends['vpopmail'] = array (
    'name' => 'Example Vpopmail Server',
    'preferred' => '',
    'password policy' => array(
        'minLength' => 3,
        'maxLength' => 8,
        'maxSpace' => 0,
        'minUpper' => 0,
        'minLower' => 0,
        'minNumeric' => 0
    ),
    'driver' => 'vpopmail',
    'params' => array(
        'phptype'    => 'mysql',
        'hostspec'   => 'localhost',
        'username'   => '',
        'password'   => '',
        'encryption' => 'crypt',
        'database'   => 'vpopmail',
        'table'      => 'vpopmail',
        'name'    => 'pw_name',
        'domain'  => 'pw_domain',
        'passwd' =>  'pw_passwd',
        'clear_passwd' => 'pw_clear_passwd',
        'use_clear_passwd' => true,
        'show_encryption' => true
    )
);

$backends['pine'] = array(
    'name' => 'Example Pine Password File',
    'preferred' => '',
    'password policy' => array(),
    'driver' => 'pine',
    'no_reset' => true,
    'params' => array(
        // FTP server information.
        'host' => 'localhost',
        'port' => '21',
        'path' => '',
        'file' => '.pinepw',
        // Connect using the just-passed-in password?
        'use_new_passwd' => false,
        // Host string to look for in the encrypted file.
        'imaphost' => 'localhost'
    )
);

$backends['kolab'] = array(
    'name' => 'Local Kolab Server',
    'preferred' => '',
    'password policy' => array(
        'minLength' => 3,
        'maxLength' => 8
    ),
    'driver' => 'kolab',
    'params' => array()
);

$backends['myscript'] = array(
    'name' => 'example.com',
    'preferred' => 'localhost',
    'password policy' => array(),
    'driver' => 'procopen',
    'params' => array(
        'program' => '/path/to/my/script + myargs'
    )
);

// This is an example configuration for the http driver.  This allows
// connecting to an arbitrary URL that contains a password change form.
// The params 'username','oldPasswd','passwd1', and 'passwd2' params should be
// set to the name of the respective form input elements on the html form.  If
// there are additional form fields that the form requires, define them in the
// 'fields' array in the form 'formFieldName' => 'formFieldValue'.  The driver
// attempts to determine the success or failure based on searching the returned
// html page for the values listed in the 'eval_results' array.
$backends['http'] = array (
    'name' => 'Email password on IMAP server',
    'preferred' => '',
    'password policy' => array(
        'minLength' => 3,
        'maxLength' => 8,
        'maxSpace' => 0,
        'minUpper' => 0,
        'minLower' => 1,
        'minNumeric' => 1,
        'minSymbols' => 0
    ),
    'driver' => 'http',
    'params' => array(
         'url' => 'http://www.example.com/psoft/servlet/psoft.hsphere.CP',
         'username' => 'mbox',
         'oldPasswd' => 'old_password',
         'passwd1'   => 'password',
         'passwd2'   => 'password2',
         'fields' => array(
            'action'    => 'change_mbox_password',
            'ftemplate'  => 'design/mail_passw.html'
            ),
         'eval_results' => array(
            'success' => 'Password successfully changed',
            'badPass' => 'Bad old password',
            'badUser' => 'Mailbox not found'
            )
    )
);

$backends['soap'] = array(
    'name' => 'Example SOAP Server',
    'preferred' => '',
    'password policy' => array(),
    'driver' => 'soap',
    'params' => array(
        // If this service doesn't have a WSDL, the 'location' and 'uri'
        // parameters below must be specified instead.
        'wsdl' => 'http://www.example.com/service.wsdl',
        'method' => 'changePassword',
        // This is the order of the arguments to the method specified above.
        'arguments' => array('username', 'oldpassword', 'newpassword'),
        // These parameters are directly passed to the SoapClient object, see
        // http://ww.php.net/manual/en/soapclient.soapclient.php for a
        // complete list of possible parameters.
        'soap_params' => array(
            'location' => '',
            'uri' => '',
         ),
    )
);

// This is an example configuration for Postfix.admin 2.3.
// Set the 'password_policy' section as you wish.
// In most installations you probably only need to change the 
// hostspec and /or  password fields.
$backends['postfixadmin'] = array (
    'name' => 'Postfix Admin server',
    'preferred' => 'true',
    'password policy' => array(
        'minLength' => 6,
        'maxLength' => 20,
        'maxSpace' => 0,
        'minUpper' => 1,
        'minLower' => 1,
        'minNumeric' => 1,
        'minSymbols' => 0
    ),
    'driver' => 'sql',
    'params' => array(
        'phptype'    => 'mysql',
        'hostspec'   => 'localhost',
        'username'   => 'postfix',
        'password'   => 'PASSWORD',
        'encryption' => 'crypt-md5',
        'database'   => 'postfix',
        'table'      => 'mailbox',
        'user_col'   => 'username',
        'pass_col'   => 'password',
        'show_encryption' => false,
        // The following two settings allow you to specify custom queries for
        // lookup and modify functions if special functions need to be
        // performed.  In places where a username or a password needs to be
        // used, refer to this placeholder reference:
        //    %d -> gets substituted with the domain
        //    %u -> gets substituted with the user
        //    %U -> gets substituted with the user without a domain part
        //    %p -> gets substituted with the plaintext password
        //    %e -> gets substituted with the encrypted password
        //
        'query_lookup' => 'SELECT password FROM mailbox WHERE username = %u and active = 1', 
        'query_modify' => 'UPDATE mailbox SET password = %e WHERE username = %u'
    )
);

// This is an example configuration for chaining multiple drivers to allow for
// syncing of passwords across many backends using the composite driver as a
// wrapper.
//
// Each of the subdrivers may contain an optional parameter called 'required'
// that, when set to true, will cause the rest of the drivers be skipped if a
// particular one fails.
$backends['composite'] = array(
   'name' => 'Example All Services',
   'preferred' => '',
   'password policy' => array(
       'minLength' => 3,
       'maxLength' => 8,
       'minClasses' => 2,
   ),
   'driver' => 'composite',
   'params' => array('drivers' => array(
       'sql' => array(
           'name' => 'Horde Authentication',
           'driver' => 'sql',
           'required' => true,
           'params' => array(
               'phptype'    => 'mysql',
               'hostspec'   => 'localhost',
               'username'   => 'horde',
               'password'   => '',
               'encryption' => 'md5-hex',
               'database'   => 'horde',
               'table'      => 'horde_users',
               'user_col'   => 'user_uid',
               'pass_col'   => 'user_pass',
               'show_encryption' => false
               // 'query_lookup' => '',
               // 'query_modify' => '',
           ),
       ),
       'smbpasswd' => array(
           'name' => 'Samba Server',
           'driver' => 'smbpasswd',
           'params' => array(
               'program' => '/usr/bin/smbpasswd',
               'host' => 'localhost',
           ),
       ),
   )),
);
