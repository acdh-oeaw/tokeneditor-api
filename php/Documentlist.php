<?php 

class Documentlist {
	public function createlist($userid,$con) {
		//tokens with concrete document_id to json => to json als eigene methode?
		$query = $con->prepare("
			SELECT document_id, name, count(*) AS tokens_count
			FROM 
				documents 
				JOIN documents_users USING (document_id) 
				JOIN tokens using (document_id)
			WHERE user_id = ?
			GROUP BY 1, 2
			ORDER BY 2
		");
		$query->execute(array($userid));
		$result = $query->fetchAll();
		//print_r($result);
		return $result;
	}
}
