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
$propofchangedval = "@".$result["changedproperty"];

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
   $lkresult =  $lookup->execute(array($documentId, $propofchangedval, $tokenId, $userid));
    
   
    if ($lkresult === false) {
        
        $query = $con->prepare("INSERT INTO values (document_id,property_xpath,token_id,user_id,value) VALUES (?, ?, ?, ?, ?)"); 
     //   $query->execute(array($documentId, '@'.$propofchangedval, $tokenId, $userid, $changedvalue));
         $query->execute(array($documentId, $propofchangedval, $tokenId, $userid, $changedvalue));
}   else {
  
        $updquery = $con->prepare("UPDATE values SET value = ? WHERE document_id = ? AND property_xpath = ? AND token_id = ? AND user_id = ?"); 
     $updquery->execute(array($changedvalue, $documentId, $propofchangedval, $tokenId, $userid));
     
}



//var_dump($propertyxpath]);
		
//$documentid = $_GET["docid"];


