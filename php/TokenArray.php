<?php 



class TokenArray {
function generateJSON($documentid,$con) {
//tokens with concrete document_id to json => to json als eigene methode? //
$query = $con->prepare("SELECT json_agg(json_build_object('document_id', document_id, 'token_id', token_id, 'value', t1.value, 'properties', properties)) FROM (SELECT document_id, token_id, t.value, json_agg(json_build_object(name, v.value)) AS properties FROM properties JOIN orig_values v USING (document_id, 
property_xpath) JOIN tokens t USING (document_id, token_id) WHERE document_id = $documentid group by 1, 2, 3) t1");
$query->execute();
//$result = $query->fetch(PDO::FETCH_COLUMN);
$result = $query->fetch(PDO::FETCH_COLUMN);
//print_r($result);
return $result;

}




}