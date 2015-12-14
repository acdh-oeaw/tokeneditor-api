<?php 
require_once('config.inc.php');
$con = new PDO($CONFIG['dbConn'], $CONFIG['dbUser'], $CONFIG['dbPasswd']);
$userid = $_SERVER[$CONFIG['userid']];
//$documentId = $result['document_id'];
$documentId = filter_input(INPUT_POST, 'document_id');
$tokenId = filter_input(INPUT_POST, 'token_id');
$changedvalue = filter_input(INPUT_POST, "value");
$propofchangedval = filter_input(INPUT_POST, "property_xpath");

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
    $lookup->execute(array($documentId, $propofchangedval, $tokenId, $userid));
    $resultlkup = $lookup->fetch(PDO::FETCH_COLUMN);
   
    if ($resultlkup > 0) {
        $updquery = $con->prepare("UPDATE values SET value = ? WHERE document_id = ? AND property_xpath = ? AND token_id = ? AND user_id = ?"); 
     $updquery->execute(array($changedvalue, $documentId, $propofchangedval, $tokenId, $userid));
        
}   else {
      
      $query = $con->prepare("INSERT INTO values (document_id,property_xpath,token_id,user_id,value) VALUES (?, ?, ?, ?, ?)"); 
     //   $query->execute(array($documentId, '@'.$propofchangedval, $tokenId, $userid, $changedvalue));
         $query->execute(array($documentId, $propofchangedval, $tokenId, $userid, $changedvalue));
     
}

echo(json_encode(array('status' => 'OK')));
