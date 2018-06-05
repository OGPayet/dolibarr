<?php

class apiClient {

    private $timeout, $username, $password;
    public $curl_response, $decoded, $info, $http_code;

    public function __construct($user = "", $password = "") {
        $this->timeout = 5;
        $this->username = "$user";
        $this->password = "$password";
    }

    public function send($url, $curl_post_data = array(), $method = "GET", $additionalHeaders = "") {
//$headers = array(
//    'Content-Type:application/json',
//    'Authorization: Basic '. base64_encode("user:password") // <---
//);
//        if ($method == "GET") // specific for dolead
//            $url.="?" . http_build_query($curl_post_data);
        $process = curl_init($url);
        curl_setopt($process, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($process, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($process, CURLOPT_HTTPHEADER, array($additionalHeaders));
//for debug header
        curl_setopt($process, CURLOPT_HEADER, true);
        curl_setopt($process, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($process, CURLOPT_USERPWD, $this->username . ":" . $this->password);
        curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($process, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, true);
// the method is here :
        curl_setopt($process, CURLOPT_CUSTOMREQUEST, $method);
        if ($curl_post_data !== array()) {
            curl_setopt($process, CURLOPT_POST, true);
            curl_setopt($process, CURLOPT_POSTFIELDS, ($curl_post_data));
        }



        // send data
        $this->curl_response = curl_exec($process);
        $header_size = curl_getinfo($process, CURLINFO_HEADER_SIZE);
        $this->curl_response = substr($this->curl_response, $header_size);
        // exctract header from data
        if ($this->curl_response === false) {
            $this->http_code = curl_getinfo($process, CURLINFO_HTTP_CODE);
            $this->info = curl_getinfo($process);
            die("error occured during curl exec. Additioanl info: code({$this->http_code}) " . curl_error($process));
            curl_close($process);
        } else {
            // debug
            //    $info = curl_getinfo($process);
            //    var_dump($info);
            $this->http_code = curl_getinfo($process, CURLINFO_HTTP_CODE);
            curl_close($process);
        }

        $this->decoded = json_decode($this->curl_response);
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                break;
            case JSON_ERROR_DEPTH:
                return json_encode('JSON_ERROR - Maximum stack depth exceeded');
                break;
            case JSON_ERROR_STATE_MISMATCH:
                return json_encode('JSON_ERROR - Underflow or the modes mismatch');
                break;
            case JSON_ERROR_CTRL_CHAR:
                return json_encode('JSON_ERROR - Unexpected control character found');
                break;
            case JSON_ERROR_SYNTAX:
                return json_encode('JSON_ERROR - Syntax error, malformed JSON');
                break;
            case JSON_ERROR_UTF8:
                return json_encode('JSON_ERROR - Malformed UTF-8 characters, possibly incorrectly encoded');
                break;
            default:
                return json_encode('JSON_ERROR - Unknown error');
                break;
        }
    }

}
