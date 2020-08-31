<?php
require __DIR__ . '/sms/autoload.php';

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

class Sms {
    
    public function __construct() {
        $this->doLogin();
    }
    
    public function doLogin() {
        // Create Client
        AlibabaCloud::accessKeyClient(ALI_KEYID, ALI_KEYSECRET)->regionId(SMS_REGIONID)->asDefaultClient();
    }
    
    public function sendMessage ($phone, $code) {
        try {
            // Chain calls and send RPC request
            $result = AlibabaCloud::rpc()
                    ->product('Dysmsapi')
                    // ->scheme('https') // https | http
                    ->version('2017-05-25')
                    ->action('SendSms')
                    ->method('POST')
                    ->host('dysmsapi.aliyuncs.com')
                    ->options([
                      'query' => [
                      'PhoneNumbers' => $phone,//13651868732
                      'SignName' => "计步宝" ,
                      'TemplateCode' => "SMS_181490038",
                      'TemplateParam' => json_encode(array('code' => $code))
                      ]])
                    ->request();
            if ('OK' == $result->Code) {
                return TRUE;
            } else {
                file_put_contents(LOG_DIR . 'sms.log', date('Y-m-d H:i:s') . "|" . $result->Message . PHP_EOL, FILE_APPEND);
                return FALSE;
            }
        } catch (ClientException $exception) {
            // Get client error message
            file_put_contents(LOG_DIR . 'sms.log', date('Y-m-d H:i:s') . "|" . $exception->getErrorMessage() . PHP_EOL, FILE_APPEND);
            return FALSE;
        } catch (ServerException $exception) {
            // Get server error message
            file_put_contents(LOG_DIR . 'sms.log', date('Y-m-d H:i:s') . "|" . $exception->getErrorMessage() . PHP_EOL, FILE_APPEND);
            return FALSE;
        }
        
    }
    
}
