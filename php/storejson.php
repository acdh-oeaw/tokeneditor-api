<?php 
require_once('config.inc.php');
$con = new PDO($CONFIG['dbConn'], $CONFIG['dbUser'], $CONFIG['dbPasswd']);
$userid = $_SERVER[$CONFIG['userid']];
$result = (json_decode($HTTP_RAW_POST_DATA,true));
$documentId = $result['document_id'];
$tokenId = $result['token_id'];
$propertyxpath = $result["properties"];
//$lemma = $item['lemma'];
//$query = "INSERT INTO values ($documentId, $tokenId, @lemma, $lemma) VALUES (?, ?, ?, ?)";
foreach ($propertyxpath as $i=>$row) {
    if ($row === null)
       unset($propertyxpath[$i]);
}
/*foreach ($propertyxpath as $item) {		
    
 echo "INSERT INTO values (document_id,property_xpath,token_id,user_id,value) VALUES (".$documentId.",'@".key($item)."',".$tokenId.",'".$userid."','".$item[key($item)]."')";

}*/
$query = $con->prepare("INSERT INTO values (document_id,property_xpath,token_id,user_id,value) VALUES (?, ?, ?, ?, ?)"); 
foreach ($propertyxpath as $item){
	$query->execute(array($documentId, '@'.key($item), $tokenId, $userid, $item[key($item)]));
};


//var_dump($propertyxpath]);
		
//$documentid = $_GET["docid"];


