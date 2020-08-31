<?php
require_once CORE_DIR . '/aliyun-oss.phar';
use OSS\OssClient;
use OSS\Core\OssException;
Class Oss {
    protected $accessKeyId;
    protected $accessKeySecret;
    protected $endpoint;
    protected $bucket;

    public function __construct () {
// 阿里云主账号AccessKey拥有所有API的访问权限，风险很高。强烈建议您创建并使用RAM账号进行API访问或日常运维，请登录RAM控制台创建RAM账号。
        $this->accessKeyId = ALI_KEYID;
        $this->accessKeySecret = ALI_KEYSECRET;
// Endpoint以杭州为例，其它Region请按实际情况填写。
        $this->endpoint = OSS_ENDPOINT;
// 设置存储空间名称。
        $this->bucket = OSS_BUCKET;
    }

    public function upload ($uploadPos, $file) {
        try {
            $ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);
            $ossClient->uploadFile($this->bucket, $uploadPos, $file);
            return TRUE;
        } catch (OssException $e) {
            return $e->getMessage();
        }
    }
    
}
