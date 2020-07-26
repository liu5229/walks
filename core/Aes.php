<?php

/**
 * @des 3DES加密算法,cbc模式,pkcs5Padding字符填充方式
 */
class Aes {

    private $hex_iv = ''; # converted JAVA byte code in to HEX and placed it here

    private $key = '3WkzTvoaCAKQ9cRNHzRgCtHtf6PWsFtNQRrmQpt'; #Same as in JAVA

    function __construct($key = '3WkzTvoaCAKQ9cRNHzRgCtHtf6PWsFtNQRrmQpt') {
        $this->key = md5($key);
        $this->hex_iv = substr($this->key, -8);
    }

    public function encrypt($input)
    {
        $data = openssl_encrypt($input, 'des-ede3-cbc', $this->key, 1, $this->hex_iv);
        $data = base64_encode($data);
        return $data;
    }

    public function decrypt($input)
    {
        $decrypted = openssl_decrypt(base64_decode(str_replace(" ", "+", $input)), 'des-ede3-cbc', $this->key, 1, $this->hex_iv);
        return $decrypted;
    }

}
