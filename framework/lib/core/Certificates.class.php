<?php
class SSLFailure extends Exception {}
class CertificateManagerException extends Exception {}

/**
 * Class Certificates
 *
 * @author Nuno Barreto <nbarreto@gmail.com>
 */
class Certificates {
    private $SSLConfig;

    function __construct() {
        $this->SSLConfig = getenv('app_root').'configs/openssl.cnf';
    }

    function fileUploadErrorMessage($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return _('The uploaded file exceeds the upload_max_filesize directive in php.ini');
            case UPLOAD_ERR_FORM_SIZE:
                return _('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form');
            case UPLOAD_ERR_PARTIAL:
                return _('The uploaded file was only partially uploaded');
            case UPLOAD_ERR_NO_FILE:
                return _('No file was uploaded');
            case UPLOAD_ERR_NO_TMP_DIR:
                return _('Missing a temporary folder');
            case UPLOAD_ERR_CANT_WRITE:
                return _('Failed to write file to disk');
            case UPLOAD_ERR_EXTENSION:
                return _('File upload stopped by extension');
            }
        return _('Unknown upload error');
    }

    function uploadFile($file) {
        if (empty($file)) {
            $file = array('error' => UPLOAD_ERR_NO_FILE);
        }

        if ($file['error'] != UPLOAD_ERR_OK) {
            throw new SSLFailure($this->fileUploadErrorMessage($file['error']));
        }

        $f = @file_get_contents($file['tmp_name']);
        if (empty($f)) {
            throw new SSLFailure(_('file is empty or a read error occurred'));
        }

        return $f;
    }

    function pem2der($pem_data, $type) {
        $begin = "-----BEGIN $type-----";
        $end = "-----END $type-----";
        $pem_data = preg_replace("/[\r\n]+/s", "\n", $pem_data);
        $match = array();

        if (!preg_match("/$begin\n(.*?)\n$end/s", $pem_data, $match))
            return NULL;

        $pem_data = preg_replace("/\n/s", '', $match[1]);
        $der_data = base64_decode($pem_data);

        if (base64_encode($der_data) != $pem_data)
            return NULL;

        return $der_data;
    }

    function der2pem($der_data, $type) {
        return "-----BEGIN $type-----\n" .
            chunk_split(base64_encode($der_data), 64, "\n") .
            "-----END $type-----\n";
    }

    function parseKeyCertificate($certdata, $certdescr = '', $keydescr = '', $keydata = NULL, $keyreq = FALSE, $inpw = '') {
        $pkcs12 = array();
        @openssl_pkcs12_read($certdata, $pkcs12, $inpw);
        $data = '';

        if (isset($pkcs12['cert']))
            $data .= $pkcs12['cert'] . "\n";

        if (isset($pkcs12['pkey']))
            $data .= $pkcs12['pkey'] . "\n";

        if (!empty($data))
            $certdata = $data;

        $cert = @openssl_x509_read($certdata);

        if (empty($cert)) {
            $cert = @openssl_x509_read($this->der2pem($certdata, 'CERTIFICATE'));

            if (!empty($cert))
                $certdata = '';
        }

        if (empty($cert))
            throw new SSLFailure("$certdescr: " .
                _('cannot parse certificate data: the passphrase mismatch or the file content is probably not a valid certificate.'));

        //  Check if the key is given within the certificate.

        $key = @openssl_pkey_get_private($certdata, $inpw);

        if (!$key) {
            if (empty($keydata)) {
                if (!$keyreq) {
                    $pemcert = '';
                    @openssl_x509_export($cert, $pemcert);
                    return array('cert' => $cert,
                        'pemcert' => $pemcert);
                    }

                throw new SSLFailure("$keydescr: " .
                    _('private key needed and not given'));
                }

            $key = @openssl_pkey_get_private($keydata, $inpw);

            foreach (array('RSA ', 'ENCRYPTED ', '') as $a) {
                if (!empty($key))
                    break;

                $key = @openssl_pkey_get_private($this->der2pem($keydata,
                   $a . 'PRIVATE KEY'), $inpw);
                }

            if (empty($key))
                throw new SSLFailure("$keydescr: " .
                    _('Cannot parse key data: passphrase mismatch or the file content is probably not a valid key.'));
        }

        //  Check the presence of the private key.

        $kdetails = @openssl_pkey_get_details($key);
        $privpresent = isset($kdetails['rsa']['d']);

        if ($keyreq && !$privpresent)
            throw new SSLFailure("$keydescr " .
                _('does not contain a private key'));

        //  Make sure the key matches the certificate.

        $pubcert = @openssl_pkey_get_details(@openssl_pkey_get_public($cert));
        $pubcert = @$pubcert['key'];
        $pubkey = @$kdetails['key'];

        if (empty($pubcert) || $pubcert !== $pubkey || ($privpresent &&
            !openssl_x509_check_private_key($cert, $key)))
            throw new SSLFailure(_('certificate and key do not match'));

        //  Everything is OK. Return the PEM and resources of both parts.

        $pemcert = '';
        @openssl_x509_export($cert, $pemcert);
        $pemkey = '';
        @openssl_pkey_export($key, $pemkey, '', array('config' => $this->SSLConfig,
            'encrypt_key' => FALSE));
        return array('cert' => $cert, 'pemcert' => $pemcert,
            'key' => $key, 'pemkey' => $pemkey);
    }

    function loadKeyCertificate($certfile, $certdescr, $keydescr = '', $keyfile = NULL, $keyreq = FALSE, $inpw = '') {
        try {
            $cert = $this->uploadFile($certfile);
        }
        catch (SSLFailure $e) {
            throw new SSLFailure("$certdescr: " . $e->getMessage());
        }
        $key = NULL;

        if (!empty($keyfile) && $keyfile['error'] != UPLOAD_ERR_NO_FILE) {
            try {
                $key = $this->uploadFile($keyfile);
            }
            catch (SSLFailure $e) {
                throw new SSLFailure("$keydescr: " . $e->getMessage());
            }
        }

        return $this->parseKeyCertificate($cert, $certdescr, $keydescr, $key, $keyreq, $inpw);
    }

    function sslHash($cert) {
        if (!is_array($cert)) {
            $cert = openssl_x509_parse($cert);
        }

        return $cert['hash'];
    }

    function sslIsIssuer($cert, $ca) {
        //  check if the given CA is the signer of the certificate.

        $ca = openssl_x509_parse($ca);
        $cert = openssl_x509_parse($cert);
        $cs = $ca['subject'];
        $ci = $cert['issuer'];

        foreach ($ci as $n => $v) {
            if (!isset($cs[$n]) || $cs[$n] !== $v)
                break;

            unset($ci[$n]);
            unset($cs[$n]);
        }

        return empty($cs) && empty($ci);
    }

    function translit($s) {
        return preg_replace('/[^a-z0-9]+/i', '_', $s);
    }

    function downloader($data, $name, $prompt, $type = 'application/octet-stream') {
        $downloads = array('name' => $name, 'data' => $data, 'type' => $type,
            'prompt' => $prompt);

        return $downloads;
    }

    function getCADescription($ca) {
        if (!isset($ca))
            return;

        $d = openssl_x509_parse($ca);
        return $d;
    }

    function getCA() {
        if (!isset($_SESSION['CA'])) {
            throw new CertificateManagerException(_('Please enter a CA first'));
        }

        return array('cert' => openssl_x509_read($_SESSION['CA']),
            'key' => openssl_pkey_get_private($_SESSION['CAKEY']));
    }

    function generateDownloads($cert, $key, $downloadkey = FALSE, $expDate, $outpw, $format) {
        //  Determine the file name prefix.
        $downloads = array();
        $d = openssl_x509_parse($cert);
        $filename = '';

        if (isset($d['subject']['O']))
            $filename = $d['subject']['O'];

        if (isset($d['subject']['CN']))
            $filename .= ' ' . $d['subject']['CN'];

        $filename = $this->translit(trim($filename));

        if (empty($filename))
            $filename = _('unknown');

        if ($format == 'pkcs12') {
            $p12cert = '';

            if (!@openssl_pkcs12_export($cert, $p12cert, $key, $outpw,
                array('config' => $this->SSLConfig,
                'encrypt_key' => !empty($outpw)))) {
                throw new CertificateManagerException(_('Certificate formatting failed'));
            }

            $downloads[0] = $this->downloader($p12cert, "$filename.pfx",
                _('Download the certificate and key'));
            $downloads[0]["hash"] = urlencode(base64_encode(serialize($downloads[0])));
            return $downloads;
            }

        $certdata = '';

        if (!@openssl_x509_export($cert, $certdata)) {
            throw new CertificateManagerException(_('Certificate formatting failed'));
            }

        if (!empty($key)) {
            $keydata = '';

            if (!openssl_pkey_export($key, $keydata, $outpw,
                array('config' => $this->SSLConfig,
                'encrypt_key' => !empty($outpw)))) {
                throw new CertificateManagerException(_('Key pair exportation failed'));
                }
            }

        if ($format == 'der') {
            $certdata = $this->pem2der($certdata, 'CERTIFICATE');

            if ($downloadkey && isset($keydata)) {
                foreach (array('RSA ', 'ENCRYPTED ', '') as $qualif) {
                    $a = $this->pem2der($keydata, "{$qualif}PRIVATE KEY");

                    if ($a != NULL) {
                        $keydata = $a;
                        break;
                        }
                    }
                }
            }

        $downloads[0] = $this->downloader($certdata, "$filename-crt.$format",
            _('Download the certificate'));
        $downloads[0]["hash"] = urlencode(base64_encode(serialize($downloads[0])));

        if ($downloadkey && !empty($keydata)) {
            $downloads[1] = $this->downloader($keydata, "$filename-key.$format",
                _('Download the key'));
            $downloads[1]["hash"] = urlencode(base64_encode(serialize($downloads[1])));
        }
        return $downloads;
    }

    function signCSR($csr, $serial, $key = NULL, $downloadkey = FALSE, $expDate, $password, $format) {
        $ca = $this->getCA();

        if (empty($expDate)) {
            throw new CertificateManagerException(_('Please specify the expiration date'));
        }

        list($year, $month, $day) = explode('-', $expDate);
        date_default_timezone_set('Europe/Zurich');
        $expDate = mktime(0, 0, 0,$month, $day, $year);
        $days = 1 + ($expDate - time() - 1) / 86400;
        $cert = openssl_csr_sign($csr, $ca['cert'], $ca['key'], $days,
            array('config' => $this->SSLConfig, 'x509_extensions' => 'usr_cert'),
            $serial);

        if (empty($cert)) {
            throw new CertificateManagerException(_('Certificate generation failed'));
            }

        return $this->generateDownloads($cert, $key, $downloadkey, $expDate, $password, $format);
    }

    function selectCA($CAfilename, $CAkeyfilename, $inpw) {
        $keycert = $this->loadKeyCertificate($CAfilename, _('CA certificate file'), _('CA private key file'), $CAkeyfilename, TRUE, $inpw);

        if (empty($keycert))
            return;

        //  Make sure the certificate is really a CA.

        $cert = $keycert['cert'];
        $d = openssl_x509_parse($cert);

        if (isset($d['extensions']['basicConstraints'])) {
            $bc = $d['extensions']['basicConstraints'];

            if (!is_array($bc))
                $bc = array($bc);

            foreach ($bc as $v) {
                if (preg_match('/,\s*CA\s*:\s*TRUE\s*,/', ",$v,")) {
                    $_SESSION['CA'] = $keycert['pemcert'];
                    $_SESSION['CAKEY'] = $keycert['pemkey'];
                    $_SESSION['CAORG'] = '';

                    if (isset($d['subject']['O']))
                        $_SESSION['CAORG'] = $d['subject']['O'];

                    return $d;          // OK.
                }
            }
        } else {
            throw new CertificateManagerException(_('This certificate is not a CA'));
        }
    }

    function createCertificate($userID, $expiration, $password, $format) {
        $ca = $this->getCA();

        if (!$ca) {
            throw new CertificateManagerException(_('No CA'));
        }

        if (empty($userID)) {
            throw new CertificateManagerException(_('Please specify the user ID'));
        }

        $key = openssl_pkey_new(array('config' => $this->SSLConfig, 'private_key_bits' => 1024));

        if (!$key) {
            throw new CertificateManagerException(_('Key pair generation failed'));
        }

        $dn = array('CN' => $userID);

        if (!empty($_SESSION['CAORG']))
            $dn['O'] = $_SESSION['CAORG'];

        $csr = openssl_csr_new($dn, $key, array('config' => $this->SSLConfig));

        if (!$csr) {
            throw new CertificateManagerException(_('Certificate generation failed'));
        }

        return $this->signCSR($csr, rand(0, getrandmax()), $key, TRUE, $expiration, $password, $format);
    }

    function signCertificate($csrfilename, $expDate, $format) {

        $ca = $this->getCA();

        if (!$ca)
            return;

        if ($csrfilename['error'] != UPLOAD_ERR_OK) {
            throw new CertificateManagerException(
                htmlspecialchars(_('Certificate request file') . ': ' .
                    $this->fileUploadErrorMessage($csrfilename['error'])));
        }

        $f = @file_get_contents($csrfilename['tmp_name']);

        if (empty($f)) {
            throw new CertificateManagerException(_('certificate request file') . ': ' .
                _('file is empty or a read error occurred'));
            }

        $dn = @openssl_csr_get_subject($f);
        $key = @openssl_csr_get_public_key($f);

        if (empty($dn) || empty($key)) {
            $f = $this->der2pem($f, 'CERTIFICATE REQUEST');
            $dn = @openssl_csr_get_subject($f);
            $key = @openssl_csr_get_public_key($f);

            if (empty($dn) || empty($key)) {
                throw new CertificateManagerException(_('File data is not a valid certificate request'));
                }
            }

        //  Special processing: application client certificates must
        //      have the same organization as the CA.

        if (empty($dn['O']))
            $dn['O'] = $_SESSION['CAORG'];
        else if ($dn['O'] != $_SESSION['CAORG']) {
            throw new CertificateManagerException(_('Certificate request targets another organization: ') .
                htmlspecialchars($dn['O']));
            }

        if (empty($dn['CN'])) {
            throw new CertificateManagerException(_('Certificate request has no common name'));
            }

        $csr = @openssl_csr_new($dn, $key, array('config' => $this->SSLConfig));

        if (empty($csr)) {
            throw new CertificateManagerException(_('Cannot load the certificate request. Please report this error and send the CSR file to DATASPHERE'));
            }

        //  Sign the request.

        $downloads = array();
        if ($downloads = $this->signCSR($csr, rand(0, getrandmax()), FALSE, $expDate, "", $format)) {
            $signed_dn = '';
            $prefix = '';

            foreach ($dn as $k => $v) {
                $signed_dn .= "$prefix$k=$v";
                $prefix= ', ';
            }
        }

        return array('signed_dn' => $signed_dn, 'downloads' => $downloads);
    }

    function renewCertificate($certfilename, $certkeyfilename, $expiration, $format) {

        $ca = $this->getCA();

        if (!$ca)
            return;

        $oldcert = $this->loadKeyCertificate($certfilename, _('Certificate file'), _('Key file'), $certkeyfilename, '', '');

        if (empty($oldcert))
            return;

        $d = openssl_x509_parse($oldcert['cert']);

        if (isset($oldcert['key']))
            $key = $oldcert['key'];
        else
            $key = @openssl_pkey_get_public($oldcert['cert']);

        if (empty($d) || empty($key) || empty($d['subject'])) {
            throw new CertificateManagerException(_('Cannot retrieve info from old certificate'));
            }

        //  Make sure the current CA is the signer of the old certificate.

        $c = openssl_x509_parse($_SESSION['CA']);
        $cs = $c['subject'];
        $ci = $d['issuer'];

        foreach ($ci as $n => $v) {
            if (!isset($cs[$n]) || $cs[$n] !== $v)
                break;

            unset($ci[$n]);
            unset($cs[$n]);
            }

        if (!empty($cs) || !empty($ci)) {
            throw new CertificateManagerException(_('The current CA is not the signer of this certificate'));
            }

        $dn = $d['subject'];
        $csr = @openssl_csr_new($dn, $key, array('config' => $this->SSLConfig));

        if (empty($csr)) {
            throw new CertificateManagerException(_('Cannot rebuild a certificate request. Please report this error and send the certificate file to DATASPHERE'));
            }

        return $this->signCSR($csr, $d['serialNumber'] + 1,
            isset($oldcert['key'])? $oldcert['key']: NULL, FALSE, $expiration, "", $format);
    }

    function requestCertificate($organization, $format, $outpw) {
        if (empty($organization)) {
            throw new CertificateManagerException(_('Please specify the organization identifier'));
        }

        $key = openssl_pkey_new(array('config' => $this->SSLConfig,
            'private_key_bits' => 1024));

        if (!$key) {
            throw new CertificateManagerException(_('Key pair generation failed'));
        }

        $csr = openssl_csr_new(array('O' => $organization,
            'CN' => "DATASPHERE workflow $organization CA"), $key,
            array('config' => $this->SSLConfig));

        if (!$csr) {
            throw new CertificateManagerException(_('Certificate request generation failed'));
        }

        $pemcsr = '';

        if (!openssl_csr_export($csr, $pemcsr)) {
            throw new CertificateManagerException(_('Certificate request exportation failed'));
        }

        $pemkey = '';

        if (!openssl_pkey_export($key, $pemkey, $outpw,
            array('config' => $this->SSLConfig, 'encrypt_key' => !empty($outpw)))) {
            throw new CertificateManagerException(_('Key pair exportation failed'));
        }

        if ($format == 'der') {
            foreach (array('RSA ', 'ENCRYPTED ', '') as $qualif) {
                $a = $this->pem2der($pemkey, "{$qualif}PRIVATE KEY");

                if ($a != NULL) {
                    $pemkey = $a;
                    break;
                    }
                }

            $pemcsr = $this->pem2der($pemcsr, 'CERTIFICATE REQUEST');
        }

        $printorg = $this->translit($organization);
        $downloads[0] = $this->downloader($pemkey, "$printorg-key.$format",
            _('Download the key pair'));
        $downloads[0]["hash"] = urlencode(base64_encode(serialize($downloads[0])));
        $downloads[1] = $this->downloader($pemcsr, "$printorg-csr.$format",
            _('Download the CA request'));
        $downloads[1]["hash"] = urlencode(base64_encode(serialize($downloads[1])));

        return $downloads;
    }

    function convertCertificate($certfilename, $certkeyfilename, $format, $password, $newPassword) {
        $cert = $this->loadKeyCertificate($certfilename, _('Certificate file'),
            _('Key file'), $certkeyfilename, FALSE, $password);

        if (empty($cert)) {
            return;
        }

        return $this->generateDownloads($cert['cert'], isset($cert['key'])? $cert['key']: NULL, TRUE, '', $newPassword, $format);
    }

    function encode_uint($i) {
        //  Encode an unsigned integer as a byte string, MSB first.

        $j = $i >> 8;
        $i = chr($i & 0xFF);

        if ($j) {
            $i = encode_uint($j) . $i;
        }

        return $i;
    }


    function asn1_encode($type, $data) {
        //  Encode the given data into an ASN1 record of the given type.

        $r = chr($type);
        $l = strlen($data);

        if ($l <= 127) {
            $r .= chr($l);      // Single byte length.
        } else {
            $l = encode_uint($l);   // Multi-byte length.
            $r .= chr(0x80 + strlen($l)) . $l;
        }

        return $r . $data;
    }


    function asn1_encode_integer($i) {
        //  Encode the given (unsigned) data as an ASN1 INTEGER.

        if (!is_string($i)) {
            $i = encode_uint($i);
        }

        if (ord($i[0]) & 0x80) {
            $i = "\x00$i";      // Be sure sign is positive.
        }

        return asn1_encode(0x02 /* INTEGER */, $i);
    }


    function subjectKeyIdentifier($cert, $separator = NULL) {
        //  Compute the subject key identifier for the given certificate
        //      from its private key.
        //  Return it as a 20-byte binary string if separator is
        //      not given, or as an ASCII hexadecimal byte string
        //      with the given separator between each byte value.
        //  Certificate can be specified as a PEM string or a resource.

        $privateKey = openssl_pkey_get_public($cert);
        $details = openssl_pkey_get_details($privateKey);
        $asn1_n = asn1_encode_integer($details['rsa']['n']);
        $asn1_e = asn1_encode_integer($details['rsa']['e']);
        $asn1_key = asn1_encode(0x30 /* SEQUENCE */, $asn1_n . $asn1_e);

        if (is_null($separator)) {
            return sha1($asn1_key, TRUE);
        }

        return implode($separator, str_split(strtoupper(sha1($asn1_key)), 2));
    }

    /**
     * Store a Certificate on server
     * @param type $cert
     * @return boolean
     */
    function store_ca($cert) {
        $config         = ApplicationConfig::getInstance();

        $hash           = Certificates::sslHash($cert);
        $analysedCert   = openssl_x509_parse($cert);

        $success        = true;

        //$cafn = $config->ca_dir . '/CA_' . $this->translit($d['subject']['O']) . '.crt';
        for ($i = 0;; $i++) {
            $cafn = $config->ca_dir . '/CA_' . Utils::translit($analysedCert['subject']['O']) .".$i.crt";
            if (!file_exists($cafn)) {
                break;
            }
        }

        for ($i = 0;; $i++) {
            $hfn = $config->ca_hash_dir . "/$hash.$i";
            if (!file_exists($hfn)) {
                break;
            }
        }

        if (!@file_put_contents($cafn, $cert)) {
            $success    = false;
        }

        if (!@symlink($cafn, $hfn)) {
            @unlink($cafn);
            $success    = false;
        }

        return $success;
    }

    /**
     * Delete a certificate
     * @param type $filename
     */
    function delete_ca($filename) {
        $config     = ApplicationConfig::getInstance();
        $cafn       = $config->ca_dir . '/' . $filename;
        $cert       = @file_get_contents($cafn);
        if (!empty($cert)) {
            $hash   = Certificates::sslHash($cert);
            $dir    = $config->ca_hash_dir;
            $dirfd  = @opendir($dir);

            if (!empty($dirfd)) {
                //  Delete hashed links to the target CA and latch other links
                //      with same hash.
                $matches            = array();
                $links              = array();
                while (($file = readdir($dirfd)) !== FALSE) {
                    if (preg_match("/^$hash\.(0|[1-9][0-9]*)$/", $file, $matches)) {
                        $n          = (int) $matches[1];
                        $hfn        = "$dir/$file";
                        $altcert    = @file_get_contents($hfn);

                        if ($altcert == $cert) {
                            @unlink($hfn);
                        } else {
                            $links[] = $n;
                        }
                    }
                }

                @closedir($dirfd);

                //  Now fill the numbering "holes" by renaming links with
                //      higher numbers.
                rsort($links, SORT_NUMERIC);

                for ($n = 0; !empty($links); $n++) {
                    $m = array_pop($links);

                    if ($n < $m) {
                        $links[] = $m;
                        $m = array_shift($links);
                        @rename("$dir/$hash.$m", "$dir/$hash.$n");
                    }
                }
            }
        }

        //  Finally delete the target CA.
        @unlink($cafn);
    }

    function removeCertificate ($caFileName) {
        $config = ApplicationConfig::getInstance();
//        $authorizations = new Authorizations();
//        if (!$authorizations->checkAuthorization($config->o, $config->u, 'admin_organizations', 'erase')) {
//            header('Location: '.$config->basePath);
//            Utils::abort();
//        }

        chdir($config->ca_dir);
        $cafn = '*.*';
        $files = glob($cafn);

        foreach ($files as $filename) {
            if (is_link($filename)) {
                if (preg_match("/.*\/$caFileName/", readlink($filename))) {
                    unlink($caFileName);
                    unlink($filename);
                }
            }

        }
    }
}
?>
