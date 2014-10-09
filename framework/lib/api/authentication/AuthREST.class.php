<?php
/**
 * AdServerAuthREST class
 *
 * Class to authenticate users, that returns access token or error
 *
 * To ask a token we need to send an authorisation header:
 *
 * At http://www.base64encode.org/, insert testclient:testpass in encode
 * Authorization: Basic encode(TestClient:TestSecret)
 * Authorization: Basic VGVzdENsaWVudDpUZXN0U2VjcmV0
 *
 * @author Nuno Barreto <nbarreto@gmail.com>
 *
 * @uri /auth
 *
 */
class AuthREST extends WrapperOauth{
    /**
     * @method POST
     *
     * @accepts application/json
     *
     * @provides application/json
     *
     * @return Tonic\Response
     */
    public function authJson(){
        return new Tonic\Response(200, json_encode(parent::auth()));
    }
}
