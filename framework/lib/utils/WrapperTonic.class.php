<?php

/**
 * NECESSARIO CRIAR ESTA CLASSE PORQUE OS METODOS DE BEFORE/AFTER DO TONIC/RESOURCE ESTAO PROTECTED
 *
 */
class WrapperTonic extends \Tonic\Resource{
    /**
     * FUNÇÃO USADA PARA DEVOLVER A INFORMAÇÃO, CONSOANTE OS HEADERS PEDIDOS NO WEBSERVICES
     *
     */
    public function returnTypeAccHeaders($objTonic){
        $infoReq = $this->request;
        $self =& $this;

        $objTonic->after(function ($response) use ($infoReq, $self)  {
            //PARA JSON
            if($infoReq->contentType == "application/json"){
                $response->contentType = "application/json";
                $response->body = json_encode($response->body);
            }

            //PARA DEVOLVER PARA HTML
            if ($infoReq->contentType == "text/html" || $infoReq->contentType == "text/plain" || $infoReq->contentType == "application/xhtml+xml") {
                $response->contentType = "text/html";

                //PREVENT ERRORS
                ob_start();
                var_dump($response->body);
                $response->body = ob_get_clean();
            }

            //PARA DEVOLVER PARA XML
            if ($infoReq->contentType == "text/xml" || $infoReq->contentType == "application/xml") {
                $response->contentType = "text/xml";

                $nameClass = str_replace('REST', '', get_class ($self));

                $xml = Array2XML::createXML($nameClass, $response->body);
                $infoXML = $xml->saveXML();
                $response->body = $infoXML;
            }

            //PARA DEVOLVER EM JSON QUANDO É FORMULARIO DE FILES
            if($infoReq->contentType == "multipart/form-data"){
                $response->contentType = "application/json";
                $response->body = json_encode($response->body);
            }
        });
    }
}

?>
