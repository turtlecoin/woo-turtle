<?php
/**
 * library.php
 *
 * @author Fexra <fexra@protonmail.com>
 * 
 * Donate TRTLuzAzNs1E1RBFhteX56A5353vyHuSJ5AYYQfoN97PNbcMDvwQo4pUWHs7SYpuD9ThvA7AD3r742kwTmWh5o9WFaB9JXH8evP
 * 
 * Reality is construcuted by the consensus between your neurons.
 */
class Turtlecoin_Library {
    protected $url = null, $is_debug = false, $parameters_structure = 'array';
    protected $curl_options = array(
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 8
    );
    protected $host;
    protected $port;

    private $httpErrors = array(
        400 => '400 Bad Request',
        401 => '401 Unauthorized',
        403 => '403 Forbidden',
        404 => '404 Not Found',
        405 => '405 Method Not Allowed',
        406 => '406 Not Acceptable',
        408 => '408 Request Timeout',
        500 => '500 Internal Server Error',
        502 => '502 Bad Gateway',
        503 => '503 Service Unavailable'
    );

    public function __construct($pHost, $pPort) {
        $this->validate(false === extension_loaded('curl'), 'The curl extension must be loaded to use this class!');
        $this->validate(false === extension_loaded('json'), 'The json extension must be loaded to use this class!');
        
        $this->host = $pHost;
        $this->port = $pPort;
        $this->url = $pHost . ':' . $pPort . '/json_rpc';
    }

    public function validate($pFailed, $pErrMsg) {
        if ($pFailed) {
            echo $pErrMsg;
        }
    }

    public function setDebug($pIsDebug) {
        $this->is_debug = !empty($pIsDebug);
        return $this;
    }

    public function setCurlOptions($pOptionsArray) {
        if (is_array($pOptionsArray)) {
            $this->curl_options = $pOptionsArray + $this->curl_options;
        } else {
            echo 'Invalid options type.';
        }
        return $this;
    }

    public function _run($method, $params) {
        $result = $this->request($method, $params);
        return $result;
    }

    private function request($pMethod, $pParams) {
        static $requestId = 0;
        $requestId++;
        if(!$pParams) { $pParams = json_decode('{}'); }

        $request = json_encode(array('jsonrpc' => '2.0', 'method' => $pMethod, 'params' => $pParams, 'id' => $requestId));
        $responseMessage = $this->getResponse($request);
        
        //If debug is enabled
        $this->debug('Url: ' . $this->url . "\r\n", false);
        $this->debug('Request: <br> <br> ' . $request . "\r\n", false);
        $this->debug(' <br>Response: <br> <br> ' . $responseMessage . "\r\n", true);

        $responseDecoded = json_decode($responseMessage, true);
        
        $this->validate(empty($responseDecoded['id']), 'Invalid response data structure: ' . $responseMessage);
        $this->validate($responseDecoded['id'] != $requestId, 'Request id: ' . $requestId . ' is different from Response id: ' . $responseDecoded['id']);
        
        if(isset($responseDecoded['error'])) {
            $errorMessage = 'Request have return error: ' . $responseDecoded['error']['message'] . '; ' . "\n" . 'Request: ' . $request . '; ';
            
            if (isset($responseDecoded['error']['data'])) {
                $errorMessage .= "\n" . 'Error data: ' . $responseDecoded['error']['data'];
            }

            $this->validate(!is_null($responseDecoded['error']), $errorMessage);
        }
       
        return $responseDecoded['result'];
    }

    protected function debug($pAdd, $pShow = false) {
        static $debug, $startTime;
 
        if(false === $this->is_debug) {
            return;
        }

        $debug .= $pAdd;

        $startTime = empty($startTime) ? array_sum(explode(' ', microtime())) : $startTime;
        if (true === $pShow and !empty($debug)) {

            $endTime = array_sum(explode(' ', microtime()));
            $debug .= 'Request time: ' . round($endTime - $startTime, 3) . ' s Memory usage: ' . round(memory_get_usage() / 1024) . " kb\r\n";
            echo nl2br($debug);
            flush();
            $debug = $startTime = null;
        }
    }

    //Curl Request
    protected function getResponse($pRequest) {
        $ch = curl_init();
        if (!$ch) {
            throw new RuntimeException('Could\'t initialize a cURL session');
        }

        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $pRequest);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if (!curl_setopt_array($ch, $this->curl_options)) {
            throw new RuntimeException('Error while setting curl options');
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (isset($this->httpErrors[$httpCode])) {
            echo 'Response Http Error - ' . $this->httpErrors[$httpCode];
        }

        if (0 < curl_errno($ch)) {
           echo '[ERROR] Failed to connect to turtlecoin-wallet-rpc at ' . $this->host . ' port '. $this->port .'</br>';
        }

        curl_close($ch);
        return $response;
    }

    //Here is where you can start adding methods supported below:
    //
    //https://wiki.bytecoin.org/wiki/Bytecoin_RPC_Wallet_JSON_RPC_API

    public function getBalance() {
        $balance = $this->_run('getBalance');
        return $balance;
    }

    public function getStatus() {
        $status = $this->_run('getStatus');
        return $status['lastBlockHash'];
    }

    public function getPayments($lastBlockHash, $paymentId) {
        $payment_param = array('blockCount' => 100, 'blockHash' => $lastBlockHash, 'paymentId' => $paymentId);
        $get_payments = $this->_run('getTransactions', $payment_param);
        return $get_payments;
    }
}
