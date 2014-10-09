<?php

/**
 * ExampleActionREST class
 *
 * @author Nuno Barreto <nbarreto@gmail.com>
 *
 * @uri /action/:param1
 * @uri /action/:param1/:param2
 */
class ExampleActionREST extends Tonic\Resource {
    /**
     * @method POST
     * @accepts application/json
     * @provides application/json
     * @return Tonic\Response
     */
    function trackActionJSON() {
        $payload = json_decode($this->request->data);

        // Do something

        return new Tonic\Response(200, json_encode(array('message' => 'OK')));
    }
}

?>
