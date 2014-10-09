<?php

////////////////////////////////////////////////////////////////////////////////
//  LDAP additional support.
////////////////////////////////////////////////////////////////////////////////
//  Scopes.
define('LDAP_SCOPE_BASE', 0);
define('LDAP_SCOPE_ONELEVEL', 1);
define('LDAP_SCOPE_SUBTREE', 2);


//  Error codes.

define('LDAP_SUCCESS', 0x00);
define('LDAP_OPERATIONS_ERROR', 0x01);
define('LDAP_PROTOCOL_ERROR', 0x02);
define('LDAP_TIMELIMIT_EXCEEDED', 0x03);
define('LDAP_SIZELIMIT_EXCEEDED', 0x04);
define('LDAP_COMPARE_FALSE', 0x05);
define('LDAP_COMPARE_TRUE', 0x06);
define('LDAP_AUTH_METHOD_NOT_SUPPORTED', 0x07);
define('LDAP_STRONG_AUTH_REQUIRED', 0x08);
define('LDAP_PARTIAL_RESULTS', 0x09);
define('LDAP_REFERRAL', 0x0a);
define('LDAP_ADMINLIMIT_EXCEEDED', 0x0b);
define('LDAP_UNAVAILABLE_CRITICAL_EXTENSION', 0x0c);
define('LDAP_CONFIDENTIALITY_REQUIRED', 0x0d);
define('LDAP_SASL_BIND_INPROGRESS', 0x0e);
define('LDAP_NO_SUCH_ATTRIBUTE', 0x10);
define('LDAP_UNDEFINED_TYPE', 0x11);
define('LDAP_INAPPROPRIATE_MATCHING', 0x12);
define('LDAP_CONSTRAINT_VIOLATION', 0x13);
define('LDAP_TYPE_OR_VALUE_EXISTS', 0x14);
define('LDAP_INVALID_SYNTAX', 0x15);
define('LDAP_NO_SUCH_OBJECT', 0x20);
define('LDAP_ALIAS_PROBLEM', 0x21);
define('LDAP_INVALID_DN_SYNTAX', 0x22);
define('LDAP_IS_LEAF', 0x23);
define('LDAP_ALIAS_DEREF_PROBLEM', 0x24);
define('LDAP_INAPPROPRIATE_AUTH', 0x30);
define('LDAP_INVALID_CREDENTIALS', 0x31);
define('LDAP_INSUFFICIENT_ACCESS', 0x32);
define('LDAP_BUSY', 0x33);
define('LDAP_UNAVAILABLE', 0x34);
define('LDAP_UNWILLING_TO_PERFORM', 0x35);
define('LDAP_LOOP_DETECT', 0x36);
define('LDAP_SORT_CONTROL_MISSING', 0x3C);
define('LDAP_INDEX_RANGE_ERROR', 0x3D);
define('LDAP_NAMING_VIOLATION', 0x40);
define('LDAP_OBJECT_CLASS_VIOLATION', 0x41);
define('LDAP_NOT_ALLOWED_ON_NONLEAF', 0x42);
define('LDAP_NOT_ALLOWED_ON_RDN', 0x43);
define('LDAP_ALREADY_EXISTS', 0x44);
define('LDAP_NO_OBJECT_CLASS_MODS', 0x45);
define('LDAP_RESULTS_TOO_LARGE', 0x46);
define('LDAP_AFFECTS_MULTIPLE_DSAS', 0x47);
define('LDAP_OTHER', 0x50);
define('LDAP_SERVER_DOWN', 0x51);
define('LDAP_LOCAL_ERROR', 0x52);
define('LDAP_ENCODING_ERROR', 0x53);
define('LDAP_DECODING_ERROR', 0x54);
define('LDAP_TIMEOUT', 0x55);
define('LDAP_AUTH_UNKNOWN', 0x56);
define('LDAP_FILTER_ERROR', 0x57);
define('LDAP_USER_CANCELLED', 0x58);
define('LDAP_PARAM_ERROR', 0x59);
define('LDAP_NO_MEMORY', 0x5a);
define('LDAP_CONNECT_ERROR', 0x5b);
define('LDAP_NOT_SUPPORTED', 0x5c);
define('LDAP_CONTROL_NOT_FOUND', 0x5d);
define('LDAP_NO_RESULTS_RETURNED', 0x5e);
define('LDAP_MORE_RESULTS_TO_RETURN', 0x5f);
define('LDAP_CLIENT_LOOP', 0x60);
define('LDAP_REFERRAL_LIMIT_EXCEEDED', 0x61);


function &parse_ldap_uri($uri)

{
    //  Parse an LDAP URI.
    //
    //  Elements of the returned array are:
    //  scheme      string      Normally: ldap.
    //  user        string      Authentication user ID, if some.
    //  pass        string      Authentication password,
    //                      if some.
    //  host        string      Server host name.
    //  port        number      Server port, if specified.
    //  basedn      string      The base DN to use, if
    //                      specified.
    //  attributes  array of strings
    //                  The attributes to return, if
    //                      some given.
    //  scope       string      'base', 'one' or 'sub', if
    //                      given.
    //  filter      string      LDAP search filter, if some.
    //  extensions  array of arrays of strings
    //                  Extensions if some. Each
    //                      extension is an array
    //                      with key 'type' being
    //                      the extension name and
    //                      key 'value' the
    //                      extension value if some.
    //  fragment    string      URI fragment part.

    $puri = @parse_url($uri);

    if (!$puri)
        return;

    if (isset($puri['scheme']))
        $puri['scheme'] = rawurldecode($puri['scheme']);

    if (isset($puri['user']))
        $puri['user'] = rawurldecode($puri['user']);

    if (isset($puri['pass']))
        $puri['pass'] = rawurldecode($puri['pass']);

    if (isset($puri['path'])) {
        $puri['basedn'] = rawurldecode(substr($puri['path'], 1));
        unset($puri['path']);
        }

    if (isset($puri['query'])) {
        $parts = explode('?', $puri['query'], 4);
        $n = count($parts);
        unset($puri['query']);

        if ($n == 1 || $parts[0] !== '')
            $puri['attributes'] = array_map('rawurldecode',
                explode(',', $parts[0]));

        if ($n == 2 || $parts[1] !== '')
            $puri['scope'] = rawurldecode($parts[1]);

        if ($n == 3 || $parts[2] !== '')
            $puri['filter'] = rawurldecode($parts[2]);

        if ($n == 4) {
            $exts = explode(',', $parts[3]);
            $a = array();

            foreach ($exts as $ext) {
                $ep = explode('=', $ext, 2);
                $e = array('negate' => FALSE);

                if (substr($ep[0] . ' ', 0, 1) == '!') {
                    $e['negate'] = TRUE;
                    $ep[0] = ltrim(substr($ep[0], 1));
                    }

                $e['type'] = rawurldecode($ep[0]);

                if (isset($ep[1]))
                    $e['value'] = rawurldecode($ep[1]);

                $a[] = $e;
                }

            $puri['extensions'] = $a;
            }
        }

    return $puri;
}

function parse_dn($dn, $rdnFilter = array())
{
    $parsedDN   = array();
    
    $explodedDN = ldap_explode_dn($dn, 0);
    
    if ($explodedDN['count'] > 0) {
        unset($explodedDN['count']);
        
        foreach ($explodedDN as $dnPart) {
            list($attrib, $value)   = explode('=', $dnPart);
            if (is_array($rdnFilter) AND count($rdnFilter) AND !in_array($attrib, $rdnFilter)) {
                continue;
            }
            $elementDN['attrib']    = $attrib;
            $elementDN['value']     = $value;
            $parsedDN[]             = $elementDN;
        }
    }
    
    return $parsedDN;
}

function ldap_find()

{
    //  resource ldap_find(resource $link_identifier, string $base_dn,
    //      mixed $scope, string $filter [, mixed $attributes [,
    //      int $attrsonly [, int $sizelimit [, int $timelimit [,
    //      int $deref]]]]])
    //  Implements the original ldap_search with a scope parameter at
    //      position 4. It can be 'base', 'one' or 'sub', or
    //      one of the LDAP_SCOPE_ figurative constant.

    $args = func_get_args();
    $res = array_shift($args);
    $dn = array_shift($args);
    $scope = array_shift($args);
    $filter = array_shift($args);

    if (!count($args))
        $attributes = array();
    else {
        $attributes = array_shift($args);

        if (is_string($attributes))
            $attributes = array($attributes);
        elseif (!isset($attributes))
            $attributes = array();
        }

    if (is_string($scope))
        switch (strtolower($scope)) {

        case 'base':
            $proc = 'ldap_read';
            break;

        case 'one':
            $proc = 'ldap_list';
            break;

        case 'sub':
            $proc = 'ldap_search';
            break;

        default:
            return FALSE;
            }
    elseif (is_numeric($scope))
        switch ($scope) {

        case LDAP_SCOPE_BASE:
            $proc = 'ldap_read';
            break;

        case LDAP_SCOPE_ONELEVEL:
            $proc = 'ldap_list';
            break;

        case LDAP_SCOPE_SUBTREE:
            $proc = 'ldap_search';
            break;

        default:
            return FALSE;
            }
    else
        return FALSE;

    switch (count($args)) {

    case 0:
        return $proc($res, $dn, $filter, $attributes);

    case 1:
        return $proc($res, $dn, $filter, $attributes, $args[0]);

    case 2:
        return $proc($res, $dn, $filter, $attributes, $args[0],
            $args[1]);

    case 3:
        return $proc($res, $dn, $filter, $attributes, $args[0],
            $args[1], $args[2]);

    case 4:
        return $proc($res, $dn, $filter, $attributes, $args[0],
            $args[1], $args[2], $args[3]);
        }

    return $proc($res, $dn, $filter, $attributes, $args[0], $args[1],
        $args[2], $args[3], $args[4]);
}


function ldap_get_entries_no_counts($linkres, &$setres)

{
    if (is_array($setres))
        $res = &$setres;
    else
        $res = @ldap_get_entries($linkres, $setres);

    if (!$res)
        return $res;

    unset($res['count']);

    foreach ($res as &$v)
        if (is_array($v))
            ldap_get_entries_no_counts($linkres, $v);

    return $res;
}


function ldap_get_entries_short($linkres, &$setres, $keepdn = FALSE)

{
    //  Like ldap_get_entries, but suppress count items, numeric
    //      and possibly dn.

    $res = ldap_get_entries_no_counts($linkres, $setres);

    if (!$res)
        return $res;

    foreach ($res as &$item) {
        if (!$keepdn)
            unset($item['dn']);

        foreach ($item as $k => &$v)
            if (is_int($k))
                unset($item[$k]);
        }

    return $res;
}


function ldap_delete_tree($ds, $dn)

{
    if (($dn == 'cn=corporates,dc=linuxdev,dc=datasphere,dc=ch') || ($dn == 'cn=corporates,dc=sb,dc=datasphere,dc=ch') || (strlen($dn) < 46)) {
        Utils::abort('Tried to delete all tree: '.$dn);
    }

    //  Searching for sub entries.

    $sr = @ldap_list($ds, $dn, 'ObjectClass=*', array(''));

    if (!$sr)
        return FALSE;

    $info = ldap_get_entries($ds, $sr);

    if ($info) {
        for ($i = 0; $i < $info['count']; $i++) {
            $result = ldap_delete_tree($ds, $info[$i]['dn']);

            if(!$result)
                return FALSE;
            }
        }

    return ldap_delete($ds, $dn);
}


function ldapdnspecialchars($s)

{
    //  Escape characters that have a special meaning in an LDAP dn.

    return preg_replace_callback('/[\\x00-\\x1F\\x7F]/',
        create_function('$c', 'return sprintf("\\%02X", ord($c[0]));'),
        preg_replace('/(^ )|(^#)|( $)|[+,"\\<>;\/=]/', '\\\\$0', $s));
}


function ldapdnunescape($s)

{
    return preg_replace_callback('/\\\\(([0-9a-f]{2})|.)/i',
        create_function('$m', '$m = $m[1]; return strlen($m) == 1? $m: ' .
        'chr("0x$m" + 0);'), $s);
}


function ldap_build_dn()    // attrname, value, attrname, value, ...

{
    //  Build a dn from attribute name and value tuples.
    //  A tuple is ignored if value is empty.
    //  If name is empty, value is an already escaped rdn.
    //  Else the corresponding rdn is built after escaping name and
    //      value.
    //  If the argument count is odd, the last argument is
    //      taken as an already escaped dn trailer.

    $args = func_get_args();
    $suffix = (count($args) & 01)? array_pop($args): '';
    $dn = '';
    $prefix = '';

    while (count($args)) {
        $name = array_shift($args);
        $value = array_shift($args);

        if (!$value)
            continue;

        $dn .= $prefix;
        $prefix = ',';

        if (!$name)
            $dn .=  $value;
        else
            $dn .= ldapdnspecialchars($name) . '=' .
                ldapdnspecialchars($value);
        }

    return $suffix? "$dn$prefix$suffix": $dn;
}


function ldapfilterspecialchars($s)

{
    //  Escape characters that have a special meaning in an LDAP filter.

    return preg_replace_callback('/[\\x00-\\x1F\\x7F()*\\]/',
        create_function('$c', 'return sprintf("\\%02X", ord($c[0]));'), $s);
}


function ldapfilterunescape($s)

{
    return preg_replace_callback('/\\\\([0-9a-f]{2})/i',
        create_function('$m', 'return chr("0x{$m[1]}" + 0);'), $s);
}


//  LDAP password encryption hash objects.

class ldap_hash

{
var $name;              // The hash name.

//  Private storage and methods.

var $token;             // The stored hash token.
var $saltlen;           // The needed salt length.
var $encrypt;           // Encryption function.

//  Public methods.

function __construct($name, $token, $saltlen, $encrypt)

{
    //  $name       The encryption hash name.
    //  $token      The token to prefix an encrypted string for
    //              this hash.
    //  $saltlen    The length in bytes of the $salt random
    //              parameter of the encryption function.
    //  $encrypt    A PHP code string returning the hash-encrypted
    //              `$password' variable using the
    //              `$salt' randomizing value.

    $this->name = $name;
    $this->token = $token;
    $this->saltlen = $saltlen;
    $this->encrypt = create_function('$password, $salt', $encrypt);
}


function make_password($password, $saltfunc)

{
    //  Return the hash-encrypted $password using the `$saltfunc'
    //      function for randomization.
    //  The salt conputation function is of the form:
    //  string salt_function(numeric $salt_length)

    $salt = '';

    if ($this->saltlen)
        $salt = $saltfunc($this->saltlen);

    $result = call_user_func($this->encrypt, $password, $salt);

    if ($this->token != '')
        $result = '{' . $this->token . '}' . $result;

    return $result;
}
}


function ldap_make_password($method, $password) {
    //  Create LDAP password from method and clear password.

    $method = strtolower($method);

    //  Build supported LDAP hash functions.
    $ldap_hashes = array();
    
    if (function_exists('mhash') && function_exists('mhash_keygen_s2k'))
        $ldap_hashes[] = new ldap_hash('ssha', 'ssha', 0,
            'mt_srand((double) microtime() * 1000000);' .
            '$salt = mhash_keygen_s2k(MHASH_SHA1, $password,' .
            '  substr(pack(\'h*\', md5(mt_rand())), 0, 8), 4);' .
            'return base64_encode(mhash(MHASH_SHA1,' .
            '  $password . $salt) . $salt);');
    
    if (function_exists('sha1')) {
        // Use php 4.3.0+ sha1 function, if it is available.
    
        $ldap_hashes[] = new ldap_hash('sha', 'sha', 0,
            'return base64_encode(pack(\'H*\', sha1($password)));');
        }
    elseif (function_exists('mhash'))
        $ldap_hashes[] = new ldap_hash('sha', 'sha', 0,
            'return base64_encode(mhash(MHASH_SHA1, $password));');
    
    if (function_exists('mhash') && function_exists('mhash_keygen_s2k'))
        $ldap_hashes[] = new ldap_hash('smd5', 'smd5', 0,
            'mt_srand((double) microtime() * 1000000);' .
            '$salt = mhash_keygen_s2k(MHASH_MD5, $password,' .
            '  substr(pack(\'h*\', md5(mt_rand())), 0, 8), 4);' .
            'return base64_encode(mhash(MHASH_MD5,' .
            '  $password . $salt) . $salt);');
    
    //  Extended DES crypt. See OpenBSD crypt man page.
    
    if (defined('CRYPT_EXT_DES') && CRYPT_EXT_DES != 0)
        $ldap_hashes[] = new ldap_hash('ext_des', 'crypt', 8,
            'return crypt($password, \'_\' . $salt);');
    
    //  Hardcoded to second blowfish version and set number of rounds.
    
    if (defined('CRYPT_BLOWFISH') && CRYPT_BLOWFISH != 0)
        $ldap_hashes[] = new ldap_hash('blowfish', 'crypt', 13,
            'return crypt($password, \'$2a$12$\' . $salt);');
    
    if (defined('CRYPT_MD5') && CRYPT_MD5 != 0)
        $ldap_hashes[] = new ldap_hash('md5crypt', 'crypt', 9,
            'return crypt($password, \'$1$\' . $salt);');
    
    $ldap_hashes[] = new ldap_hash('md5', 'md5', 0,
        'return base64_encode(pack(\'H*\', md5($password)));');
    
    $ldap_hashes[] = new ldap_hash('crypt', 'crypt', 2,
        'return crypt($password, $salt);');
    
    $ldap_hashes[] = new ldap_hash('clear', '', 0, 'return $password;');


    foreach ($ldap_hashes as $h)
        if ($h->name == $method)
            return $h->make_password($password, 'random_salt');

    return FALSE;
}

?>
