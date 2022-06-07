<!DOCTYPE html>
<html>
    <head>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
        <link rel="stylesheet" href="index.css">
	<title>Tema 3 - Speech to Text</title>
    </head>
    <body>
 		
<?php

require_once 'vendor/autoload.php';
require_once "./random_string.php";

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use MicrosoftAzure\Storage\Blob\Models\CreateContainerOptions;
use MicrosoftAzure\Storage\Blob\Models\PublicAccessType;

function get_conn(){
	try {
    		$conn = new PDO("sqlsrv:server = tcp:tema3.database.windows.net,1433; Database = tema3DB", "cristiana", "laboratorSTD6");    		
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $conn;
	}
	catch (PDOException $e) {
    		print("Error connecting to SQL Server.");
    		die(print_r($e));
	}
}
//returneaza un string pe care il vom concatena la un string mare format din toate <li> - urile
function add_list_object($file_name,$blob_addr, $content){
$li = '<div class="card-body" style="width: 50rem; border-radius:20px; opacity:90%;"> <li class="list-group-item d-flex justify-content-start align-items-start">'
	 . '<div class="ms-4"> ' 
		 . '<div class="fw-bold">' . $file_name. '</div>' . $content 
	 . '</div><span style="display: inline-block;" class="badge bg-light rounded-pill">'
	. '<a href="'. $blob_addr . '">Link to blob storage</a><br> </span>'.
	'</li></div>';
return  $li;
}

function make_list_from_db($conn){

$select_q = 'select * from [dbo].[filedata];';
echo ' <div class="d-flex flex-column min-vh-100 justify-content-center align-items-center"> ';
echo '<ol class="list-group list-group-numbered">';
foreach ($conn->query($select_q ) as $row){
	$li = add_list_object($row["file_name"], $row["blob_addr"] , $row["text_result"]);
	echo $li;
}	

echo ' </ol>';
echo ' <a href="/index.html" class="badge-light">Back to Upload</a> ';
echo '</div>';

}
//https://github.com/Desseres/Cognitive-Services-Speech-to-Text/blob/master/app/Helper/MicrosoftSpeechAPI.php
function call_speech_to_text($method, $url, $data = false)
{
    $curl = curl_init();

    switch ($method)
    {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);

            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_PUT, 1);
            break;
        default:
            if ($data)
                $url = sprintf("%s?%s", $url, http_build_query($data));
    }
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
		'User-Agent: PostmanRuntime/7.26.10',
		'Ocp-Apim-Subscription-Key: 0f628b76943f477bb69b9366374aaa7d',
		'Host: eastus.api.cognitive.microsoft.com',
		'Content-type: audio/wav; codec="audio/pcm";',
		'Connection: Keep-Alive',
		'Transfer-Encoding: chunked'));
    curl_setopt($curl, CURLINFO_HEADER_OUT, true);
    curl_setopt($curl, CURLOPT_HEADER, 1);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	
    $result = curl_exec($curl);
    curl_close($curl);
    if ($result === false) 
            printf("cUrl error (#%d): %s<br>\n", curl_errno($s), htmlspecialchars(curl_error($s)));
    return $result;
}
function get_uploaded_file_content()
{
      $target_file=basename($_FILES["fileToUpload"]["name"]);
   move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file);
   $handle = fopen($target_file, "rb");
   $content = fread($handle, $_FILES["fileToUpload"]["size"]);
   fclose($handle);
   unlink($target_file);
   return $content;
}

function get_json_result($result)
{
   $pos_start=strpos($result,"{");
   $pos_end=strpos($result,"}");
   $between = substr($result, $pos_start, $pos_end + 1 - $pos_start);
   $json=json_decode($between,true);
   return $json;
}
function make_blob_from_file($fileToUpload,$content){
    
    $connectionString = "DefaultEndpointsProtocol=https;AccountName=tema3storage;AccountKey=flqJ4fDglAt2uGQf7Vsec4bGdZt4fypzqCLROrByZH9ujEz0AQnuuzblEkvj8i0SZhZ84hOAQq2w+AStsY0CYw==;EndpointSuffix=core.windows.net";
    $blobClient = BlobRestProxy::createBlobService($connectionString);
    
    if (!isset($_GET["Cleanup"])) {
	$createContainerOptions = new CreateContainerOptions();
        $createContainerOptions->setPublicAccess(PublicAccessType::CONTAINER_AND_BLOBS);

        $createContainerOptions->addMetaData("key1", "value1");
        $createContainerOptions->addMetaData("key2", "value2");
        $containerName = "blockblobs".generateRandomString();

        try {
            $blobClient->createContainer($containerName, $createContainerOptions);
            $myfile = fopen($fileToUpload, "w") or die("Unable to open file!");
            
            $blobClient->createBlockBlob($containerName, $fileToUpload, $content);
            //https://stackoverflow.com/questions/41272480/php-getting-blob-url-after-uploaded-to-azure-storage
            $blobURL = "https://tema3storage.blob.core.windows.net/" . $containerName . "/" .$fileToUpload;
            
            return $blobURL;
           
        } catch(ServiceException $e){
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code.": ".$error_message."<br />";
        } catch(InvalidArgumentTypeException $e){
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code.": ".$error_message."<br />";
        }
    }
    return null;
    
}
$target_file = basename($_FILES["fileToUpload"]["name"]);
$uploadOk = 1;
$audioFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
$conn = get_conn();


if ($_FILES["fileToUpload"]["size"] > 500000) {
    echo "Sorry, your file is too large.";
    $uploadOk = 0;
}
if($audioFileType != "wav" ) {
    echo "Sorry, only .wav files are allowed.";
    $uploadOk = 0;
}
if ($uploadOk == 0) {
    echo "Sorry, your file was not uploaded.";
}else{
   $content = get_uploaded_file_content(); 
   $language = filter_input(INPUT_POST, 'languages', FILTER_SANITIZE_STRING);
   $url = 'https://eastus.stt.speech.microsoft.com/speech/recognition/conversation/cognitiveservices/v1?language='.$language;
   $result = call_speech_to_text("POST", $url, $content);
   $json = get_json_result($result);
   if( $json["RecognitionStatus"] == 'Success'){
        $new_file=pathinfo($_FILES["fileToUpload"]["name"])['filename'] .'.txt';  
        file_put_contents($new_file,$json["DisplayText"]);
        $blobURL = make_blob_from_file($new_file, $json["DisplayText"]);
	unlink($new_file);
	$json["DisplayText"] = str_replace("'","''",$json["DisplayText"]);
	$insert_q="insert into [dbo].[filedata](file_name,blob_addr,text_result) values ( '" .$new_file .
                   "' , '" . $blobURL . "','" .$json["DisplayText"] ."');";
	$conn->query($insert_q);
	   }else{
      //error div
   }
    make_list_from_db($conn);
}

?>
</body>
</html>
