<?php

namespace encryptImageClass;
class encryptImageClass
{
    private $privateKey,$publicKey;
    private $imageBase64;
    private $encryptData;

    /* __construct 构造函数
     * @private_key_bits 密钥长度
     */
    public function __construct($privateKeyBits=512){
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
    }

    /* setPrivateKey 写入私钥
     *  @privateKey 私钥
     */
    public function setPrivateKey($privateKey){
        $this->privateKey = $privateKey;
    }

    /* setPublicKey 写入公钥
     *  @publicKey 公钥
     */
    public function setPublicKey($publicKey){
        $this->publicKey = $publicKey;
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
            $isEncrypted = openssl_private_encrypt($encryptItem, $encrypted, $this->privateKey);
            if (!$isEncrypted)
            {
                throw new Exception('privateKey error,' . openssl_error_string());
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
            $isEncrypted = openssl_public_encrypt($encryptItem, $encrypted, $this->publicKey);
            if (!$isEncrypted)
            {
                throw new Exception('privateKey error,' . openssl_error_string());
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
            exit("Err: write file fail");
        }
    }

    /* getImageBase64 获取图片base64
     * @imageFile array[imageData 图片数据,imageSize 图片大小]
     */
    private function getImageBase64($imageFile){
        return "data:{$imageFile["imageSize"]["mime"]};base64,".chunk_split(base64_encode($imageFile["imageData"]));
    }

    /* getImgFile 获取图片文件
     * @imagePath 图片地址
     */
    private function getImgFile($imagePath){
        $imageSize = @getimagesize($imagePath);
        if($imageSize){
            return [
                "imageData"=>file_get_contents($imagePath),
                "imageSize"=>$imageSize
            ];
        }else{
            exit("Err: Url:\"{$imagePath}\" is not image");
        }
    }

}