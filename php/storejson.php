<?php 
require_once('config.inc.php');
$con = new PDO($CONFIG['dbConn'], $CONFIG['dbUser'], $CONFIG['dbPasswd']);
$userid = $_SERVER[$CONFIG['userid']];
$result = (json_decode($HTTP_RAW_POST_DATA,true));
//$documentId = $result['document_id'];
$documentId = $result['document_id'];
$tokenId = $result['token_id'];
//*$propertyxpath = $result["properties"];
$changedvalue = $result["value"];
$propofchangedval = $result["changedproperty"];

//$lemma = $item['lemma'];
//$query = "INSERT INTO values ($documentId, $tokenId, @lemma, $lemma) VALUES (?, ?, ?, ?)";
/*foreach ($propertyxpath as $i=>$row) {
    if ($row === null)
       unset($propertyxpath[$i]);
}*/
/*foreach ($propertyxpath as $item) {		
    
 echo "INSERT INTO values (document_id,property_xpath,token_id,user_id,value) VALUES (".$documentId.",'@".key($item)."',".$tokenId.",'".$userid."','".$item[key($item)]."')";

}*/
$lookup = $con->prepare("SELECT document_id,property_xpath,token_id,user_id FROM values where document_id = ? AND property_xpath = ? AND token_id = ? AND user_id = ?");
$lookupquery->execute(array($documentId, '@'.$propofchangedval, $tokenId, $userid));
if (num_rows($lookupquery)== 0) {
$query = $con->prepare("INSERT INTO values (document_id,property_xpath,token_id,user_id,value) VALUES (?, ?, ?, ?, ?)"); 
} else {
    $query = $con->prepare("UPDATE values SET document_id = ?,property_xpath = ?,token_id = ?,user_id = ?,value = ?"); 
}

$query->execute(array($documentId, '@'.$propofchangedval, $tokenId, $userid, $changedvalue));


//var_dump($propertyxpath]);
		
//$documentid = $_GET["docid"];


