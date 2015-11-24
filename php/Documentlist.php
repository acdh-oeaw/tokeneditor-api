<?php 

class Documentlist {
	public function createlist($userid,$con) {
		//tokens with concrete document_id to json => to json als eigene methode?
		$query = $con->prepare("SELECT document_id, name as name FROM documents JOIN documents_users du USING (document_id) where du.user_id = ?");
		$query->execute(array($userid));
		$result = $query->fetchAll();
		//print_r($result);
		return $result;
	}
}
