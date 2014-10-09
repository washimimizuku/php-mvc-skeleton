<?php
/**
 * IPDetection.class.php
 *
 * Path: /lib/utils/IPDetection.class.php
 *
 * @author Nuno Barreto <n.barreto@ydigitalmedia.com>
 * @package utils
 */

/**
 * IPDetection class
 *
 * @author Nuno Barreto <n.barreto@ydigitalmedia.com>
 */
class IPDetection
{
    private static function checkIP($ip) {
        $private_ips = array (
            array('0.0.0.0','2.255.255.255'),
            array('10.0.0.0','10.255.255.255'),
            array('127.0.0.0','127.255.255.255'),
            array('169.254.0.0','169.254.255.255'),
            array('172.16.0.0','172.31.255.255'),
            array('192.0.2.0','192.0.2.255'),
            array('192.168.0.0','192.168.255.255'),
            array('255.255.255.0','255.255.255.255')
        );

        if (!empty($ip) && ip2long($ip)!=-1 && ip2long($ip)!=false) {
            foreach ($private_ips as $r) {
                $min = ip2long($r[0]);
                $max = ip2long($r[1]);
                if ((ip2long($ip) >= $min) && (ip2long($ip) <= $max)) {
                    return false;
                }
            }
            return true;
        } else {
            return false;
        }
    }

    public static function getIP()
    {
        if (isset($_SERVER["HTTP_CLIENT_IP"]) && self::checkIP($_SERVER["HTTP_CLIENT_IP"])) {
            return $_SERVER["HTTP_CLIENT_IP"];
        }
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            foreach (explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]) as $ip) {
                if (self::checkIP(trim($ip))) {
                    return $ip;
                }
            }
        }
        if (isset($_SERVER["HTTP_X_FORWARDED"]) && self::checkIP($_SERVER["HTTP_X_FORWARDED"])) {
            return $_SERVER["HTTP_X_FORWARDED"];
        } elseif (isset($_SERVER["HTTP_X_CLUSTER_CLIENT_IP"]) && self::checkIP($_SERVER["HTTP_X_CLUSTER_CLIENT_IP"])) {
            return $_SERVER["HTTP_X_CLUSTER_CLIENT_IP"];
        } elseif (isset($_SERVER["HTTP_FORWARDED_FOR"]) && self::checkIP($_SERVER["HTTP_FORWARDED_FOR"])) {
            return $_SERVER["HTTP_FORWARDED_FOR"];
        } elseif (isset($_SERVER["HTTP_FORWARDED"]) && self::checkIP($_SERVER["HTTP_FORWARDED"])) {
            return $_SERVER["HTTP_FORWARDED"];
        } elseif(isset($_SERVER["REMOTE_ADDR"])) {
            return $_SERVER["REMOTE_ADDR"];
        }
        else{
            return null;
        }

    }
}
?>
