<?php
interface ZabbixException { }

class HttpConnectionException extends \RuntimeException implements ZabbixException { }

class ZabbixApiExeption extends \RuntimeException implements ZabbixException { }

class ZabbixApi {
    private $host =null;
    private $id = null;
    private $token = null;
    private $api_prot = null;
    private $api_path = null;

    public function __construct($host, $api_prot = 'http', $api_path = '/api_jsonrpc.php') {
        $this->host = (string)$host;
        $this->id = date('YmdHis');
        $this->api_prot = $api_prot;
        $this->api_path = $api_path;
    }

    public function isConnected() {
        if (empty($this->token)) {
            return false;
        } else {
            return true;
        }
    }

    public function login($user, $pass) {
        $params = array(
            'user'      => $user,
            'password'  => $pass
        );
        $response = $this->executeRequest('user.login', $params);
        $this->token = $response['result'];
    }

    public function executeRequest($method, $params) {
        $request = array(
            'jsonrpc'   => '2.0',
            'method'    => $method,
            'params'    => $params,
            'id'        => $this->id,
            'auth'      => $this->token
        );

        $request_json = json_encode($request);

        $url = $this->api_prot . '://' . $this->host . $this->api_path;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => array('Content-Type: application/json-rpc'),
            CURLOPT_FAILONERROR => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $request_json 
        ]);

        $response = curl_exec($ch);
        
        $errno = curl_errno($ch);
        $error = curl_error($ch);

        curl_close($ch);
        
        $response = json_decode($response, true);       

        if (CURLE_OK !== $errno or empty($response)) {
            throw new HttpConnectionException($error, $errno);
        } elseif ( ! empty($response['error']) ) {
            throw new ZabbixApiExeption($response['error']['data'], $response['error']['code']);            
        }

//      echo json_encode($response, JSON_PRETTY_PRINT);
        return $response;
    }

    public function logout() {
        $response = $this->executeRequest('user.logout', []);
        return $response['result'];
    } 
}
?>
