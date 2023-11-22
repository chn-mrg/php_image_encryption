<?php
    require_once 'encryptImageClass.php';
    use encryptImageClass\encryptImageClass;
    $encryptImageClass = new encryptImageClass(512);

//    $key = [
//        "privateKey"=>"", //私钥
//        "publicKey"=>"" //公钥
//    ];
//    $encryptImageClass->setKey($key["privateKey"],$key["publicKey"]);
//    echo $encryptImageClass->getPrivateKey();
//    echo $encryptImageClass->getPublicKey();
    $imageUlr = "https://www.baidu.com/img/PCtm_d9c8750bed0b3c7d089fa7d55720d6cf.png";
    $filePath = "image/test";
    $publicKey = base64_encode($encryptImageClass->getPublicKey());
    $img1 = $encryptImageClass->encryptImage($imageUlr);
    $img1->privateEncrypt();
    $fileData = $img1->saveFile($filePath);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
</head>
<body>
<img id="img" alt="" data-src="/<?=$fileData["filePath"]?>" src="">
</body>
<script src="js/jsencrypt.js"></script>
<script>


    let rsaDecrypt = new JSEncrypt();
    let publicKey = "";
    function getPublicKey(callback){
        publicKey = window.atob("<?=$publicKey?>");
        rsaDecrypt.setPublicKey(publicKey);
        callback();
    }
    function getImg(){
        const xhrImg = new XMLHttpRequest();
        xhrImg.open('GET',document.getElementById("img").dataset.src);
        xhrImg.send(null);
        xhrImg.onload = function (){
            if (xhrImg.status === 200){
                const imgEncrypt = xhrImg.responseText;
                const imgEncryptData = imgEncrypt.split("<?=$fileData["delimiter"]?>")

                let imgDecrypt = "";
                imgEncryptData.forEach(function (item){
                    console.log(item);
                    imgDecrypt += rsaDecrypt.decrypt(item)
                })
                console.log(imgDecrypt);
                document.getElementById("img").src=window.URL.createObjectURL(dataURItoBlob(imgDecrypt));
            }
        }
    }

    function dataURItoBlob(base64Data) {
        //console.log(base64Data);//data:image/png;base64,
        var byteString;
        if(base64Data.split(',')[0].indexOf('base64') >= 0)
            byteString = atob(base64Data.split(',')[1]);//base64 解码
        else{
            byteString = unescape(base64Data.split(',')[1]);
        }
        var mimeString = base64Data.split(',')[0].split(':')[1].split(';')[0];//mime类型 -- image/png

        // var arrayBuffer = new ArrayBuffer(byteString.length); //创建缓冲数组
        // var ia = new Uint8Array(arrayBuffer);//创建视图
        var ia = new Uint8Array(byteString.length);//创建视图
        for(var i = 0; i < byteString.length; i++) {
            ia[i] = byteString.charCodeAt(i);
        }
        var blob = new Blob([ia], {
            type: mimeString
        });
        return blob;
    }
    getPublicKey(getImg);


</script>
</html>
