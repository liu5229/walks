<?php
//https://docs.open.alipay.com/api_28/alipay.fund.trans.uni.transfer/
require_once 'alipay/AopClient.php';
require_once 'alipay/request/AlipayFundTransUniTransferRequest.php';
require_once 'alipay/request/AlipaySystemOauthTokenRequest.php';
require_once 'alipay/request/AlipayUserInfoShareRequest.php';

class Alipay {
    protected $aop;

    public function __construct () {
        $aop = new AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = ALI_APPID;
        $aop->rsaPrivateKey = ALI_PRIVATEKEY;
//        $aop->alipayrsaPublicKey = ALI_PUBLICKEY;
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';
        $this->aop = $aop;
    }
    
    public function transfer ($transferArr) {
        try {
            $request = new AlipayFundTransUniTransferRequest ();
            $requestArr = array(
                'out_biz_no' => '20200101',//to do
                'trans_amount' => $transferArr['price'] ?? 0,//to do
                'product_code' => 'TRANS_ACCOUNT_NO_PWD',
                'order_title' => '计步宝提现',
                'payee_info' => array(
                    'identity' => $transferArr['phone'] ?? '',
                    'identity_type' => 'ALIPAY_LOGON_ID',
                    'name' => $transferArr['name'] ?? ''
                ),//to do
                'remark' => '提现到账',
            );
            $request->setBizContent(json_encode($requestArr));
//            $request->setBizContent("{" .
//            "\"out_biz_no\":\"201806300001\"," .
//            "\"trans_amount\":23.00," .
//            "\"product_code\":\"STD_RED_PACKET\"," .
//            "\"biz_scene\":\"PERSONAL_COLLECTION\"," .
//            "\"order_title\":\"营销红包\"," .
//            "\"original_order_id\":\"20190620110075000006640000063056\"," .
//            "\"payer_info\":{" .
//            "\"identity\":\"208812*****41234\"," .
//            "\"identity_type\":\"ALIPAY_USER_ID\"," .
//            "\"name\":\"黄龙国际有限公司\"," .
//            "\"bankcard_ext_info\":{" .
//            "\"inst_name\":\"招商银行\"," .
//            "\"account_type\":\"1\"," .
//            "\"inst_province\":\"江苏省\"," .
//            "\"inst_city\":\"南京市\"," .
//            "\"inst_branch_name\":\"新街口支行\"," .
//            "\"bank_code\":\"123456\"" .
//            "      }," .
//            "\"merchant_user_info\":\"{\\\"merchant_user_id\\\":\\\"123456\\\"}\"," .
//            "\"ext_info\":\"{\\\"alipay_anonymous_uid\\\":\\\"2088123412341234\\\"}\"" .
//            "    }," .
//            "\"payee_info\":{" .
//            "\"identity\":\"208812*****41234\"," .
//            "\"identity_type\":\"ALIPAY_USER_ID\"," .
//            "\"name\":\"黄龙国际有限公司\"," .
//            "\"bankcard_ext_info\":{" .
//            "\"inst_name\":\"招商银行\"," .
//            "\"account_type\":\"1\"," .
//            "\"inst_province\":\"江苏省\"," .
//            "\"inst_city\":\"南京市\"," .
//            "\"inst_branch_name\":\"新街口支行\"," .
//            "\"bank_code\":\"123456\"" .
//            "      }," .
//            "\"merchant_user_info\":\"{\\\"merchant_user_id\\\":\\\"123456\\\"}\"," .
//            "\"ext_info\":\"{\\\"alipay_anonymous_uid\\\":\\\"2088123412341234\\\"}\"" .
//            "    }," .
//            "\"remark\":\"红包领取\"," .
//            "\"business_params\":\"{\\\"withdraw_timeliness\\\":\\\"T0\\\",\\\"sub_biz_scene\\\":\\\"REDPACKET\\\"}\"," .
//            "\"passback_params\":\"{\\\"merchantBizType\\\":\\\"peerPay\\\"}\"" .
//            "  }");
            $result = $this->aop->execute($request); 
            
            $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
            $resultCode = $result->$responseNode->code;
            if(!empty($resultCode)&&$resultCode == 10000){
                return TRUE;
            } else {
                file_put_contents(LOG_DIR . 'alipay.log', date('Y-m-d H:i:s') . "|" . $resultCode . PHP_EOL, FILE_APPEND);
                return FALSE;
            }
        } catch (Exception $e) {
            file_put_contents(LOG_DIR . 'alipay.log', date('Y-m-d H:i:s') . "|" . $e->getErrorMessage() . PHP_EOL, FILE_APPEND);
            return FALSE;
        }
    }

    public function token ($code) {
        $request = new \AlipaySystemOauthTokenRequest ();
        $request->setGrantType("authorization_code");
//        $code = "255ad1ecf4fd47c9971c14db1479YA53";
        $request->setCode($code);
        $result = $this->aop->execute ( $request);
        if (isset($result->error_response)) {
            return FALSE;
        }
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        if (isset($result->$responseNode->code) && $result->$responseNode->code != 10000){
            return FALSE;
        } else {
            return $result->$responseNode->access_token;
        }
    }

    public function info ($token) {
//        $accessToken = 'kuaijieB44d5dccae72a41719abd742da87ffX53';
        $request = new \AlipayUserInfoShareRequest ();
        $result = $this->aop->execute ( $request , $token );
        if (isset($result->error_response)) {
            return FALSE;
        }
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        if (isset($result->$responseNode->code) && $result->$responseNode->code != 10000){
            return FALSE;
        } else {
            return $result->$responseNode->user_id;
        }
    }
    
}




