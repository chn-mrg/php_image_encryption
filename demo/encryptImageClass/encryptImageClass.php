<?php

namespace encryptImageClass;
use Exception;

class encryptImageClass
{
    private $privateKey,$publicKey;
    private $imageBase64;
    private $encryptData;
    private $imageSize;

    /* __construct 构造函数
     * @private_key_bits 密钥长度
     */
    public function __construct($privateKeyBits=512){
        $this->refreshClass($privateKeyBits);
    }

    /* refreshClass 刷新初始化本类
     * @private_key_bits 密钥长度
     */
    public function refreshClass($privateKeyBits=512){
        $this->privateKey = $this->publicKey = $this->imageBase64 = $this->encryptData = $this->imageSize = "";
        $config = array(
            "digest_alg"    => "sha512",
            "private_key_bits" => $privateKeyBits,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        );
        $res = openssl_pkey_new($config);
        openssl_pkey_export($res, $privateKey);
        $publicKey = openssl_pkey_get_details($res)["key"];
        $this->setPrivateKey($privateKey);
        $this->setPublicKey($publicKey);
        return $this;
    }


    public function setKey($privateKey,$publicKey){
        $password = "testKey";
        if (!@openssl_private_encrypt($password, $encrypted, $privateKey))
        {
            throw new Exception("privateKey error," . openssl_error_string());
        }
        if (!@openssl_public_decrypt($encrypted, $decrypted, $publicKey))
        {
            throw new Exception("publicKey error," . openssl_error_string());
        }
        if($password == $decrypted){
            $this->setPrivateKey($privateKey);
            $this->setPublicKey($publicKey);
            return $this;
        }else{
            throw new Exception("Keys not true");
        }

    }

    /* setPrivateKey 写入私钥
     *  @privateKey 私钥
     */
    private function setPrivateKey($privateKey){
        $this->privateKey = $privateKey;
        return $this;
    }

    /* setPublicKey 写入公钥
     *  @publicKey 公钥
     */
    private function setPublicKey($publicKey){
        $this->publicKey = $publicKey;
        return $this;
    }

    /* getPrivateKey 获取私钥
     *
     */
    public function getPrivateKey(){
        return $this->privateKey;
    }

    /* getPublicKey 获取公钥
     *
     */
    public function getPublicKey(){
        return $this->publicKey;
    }

    /* getKeyBits 获取密钥长度
     * @type 加密方法
     */
    public function getKeyBits($type = "private"){
        if($type=="public"){
            $key = openssl_pkey_get_public($this->publicKey);
        }else{
            $key = openssl_pkey_get_private($this->privateKey);
        }
        return openssl_pkey_get_details($key)["bits"];
    }

    /* getEncryptImage 获取密钥可加密字节长度
     * @type 加密方法
     */
    private function getEncryptBits($type){
        $keyBits = $this->getKeyBits($type);
        return $keyBits/8-11;
    }

    /* encryptImage 开始加密图片
     * @imagePath 图片地址
     */
    public function encryptImage($imagePath){
        $imageFile = $this->getImgFile($imagePath);
        $this->imageBase64 = $this->getImageBase64($imageFile);
        return $this;
    }

    /* privateEncrypt 使用私钥加密
     * @delimiter 加密分隔符
     */
    public function privateEncrypt($delimiter = "|{RSAIMG}|"){
        $imageData = str_split($this->imageBase64, $this->getEncryptBits("private"));
        $imageEncrypt = "";
        foreach ($imageData as $key => $encryptItem)
        {
            $isEncrypted = @openssl_private_encrypt($encryptItem, $encrypted, $this->privateKey);
            if (!$isEncrypted)
            {
                throw new Exception("privateKey error," . openssl_error_string());
            }
            $imageEncrypt .= ($key==0?"":$delimiter).base64_encode($encrypted);
        }
        $this->encryptData = [
            "imageEncrypt"=>$imageEncrypt,
            "delimiter"=>$delimiter
        ];
        return $this;
    }

    /* publicEncrypt 使用公钥加密
     * @delimiter 加密分隔符
     */
    public function publicEncrypt($delimiter = "|{RSAIMG}|"){
        $imageData = str_split($this->imageBase64, $this->getEncryptBits("public"));
        $imageEncrypt = "";
        foreach ($imageData as $key => $encryptItem)
        {
            $isEncrypted = @openssl_public_encrypt($encryptItem, $encrypted, $this->publicKey);
            if (!$isEncrypted)
            {
                throw new Exception("privateKey error," . openssl_error_string());
            }
            $imageEncrypt .= ($key==0?"":$delimiter).base64_encode($encrypted);
        }
        $this->encryptData = [
            "imageEncrypt"=>$imageEncrypt,
            "delimiter"=>$delimiter
        ];
        return $this;
    }

    /* getData 获取加密后的数据
     *
     */
    public function getData(){
        return $this->encryptData;
    }

    /* saveFile 保存文件
     *
     */
    public function saveFile($filePath){
        $filePath .= ".".$this->getImageSize("type");
        $file = fopen($filePath,"w");
        if ($file){
            $encryptData = $this->encryptData;
            fwrite($file,$encryptData["imageEncrypt"]);
            fclose($file);
            return [
                "filePath"=>$filePath,
                "delimiter"=>$encryptData["delimiter"]
            ];
        }else{
            throw new Exception("Err: write file fail");
        }
    }

    public function getImageSize($type = false){
        $imageSize = $this->imageSize;
        $imageSize["type"] = substr($imageSize["mime"],strripos($imageSize["mime"],"/")+1);
        return $type?$imageSize[$type]:$imageSize;
    }

    /* getImageBase64 获取图片base64
     * @imageFile array[imageData 图片数据,imageSize 图片大小]
     */
    private function getImageBase64($imageFile){
        return "data:{$this->imageSize["mime"]};base64,".chunk_split(base64_encode($imageFile));
    }

    /* getImgFile 获取图片文件
     * @imagePath 图片地址
     */
    private function getImgFile($imagePath){
        $imageSize = @getimagesize($imagePath);
        if($imageSize){
            $this->imageSize = $imageSize;
            return file_get_contents($imagePath);
        }else{
            throw new Exception("Err: Url:\"{$imagePath}\" is not image");
        }
    }

}