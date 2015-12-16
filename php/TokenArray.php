<?php 

class TokenArray {
	private $con;
	private $tokenIdFilter;
	private $tokenValueFilter;
	private $filters = array();
	
	public function __construct(PDO $con) {
		$this->con = $con;
	}
	
	public function setTokenIdFilter($id){
		$this->tokenIdFilter = $id;
	}
	
	public function setTokenValueFilter($val){
		$this->tokenValueFilter = $val;
	}
	
	/**
	 * 
	 * @param type $prop property xpath
	 * @param type $val filter value
	 */
	public function addFilter($prop, $val){
		$this->filters[$prop] = $val;
	}
	
	public function generateJSON($documentId, $userId, $pageSize = 1000, $offset = 0) {
		list($filterTable, $filterParam) = $this->getFilters($documentId);
		$queryStr = "
			SELECT json_agg(json_object(array_cat(array['token_id', 'token'], names), array_cat(array[token_id::text, value], values))) AS data
			FROM (
				SELECT token_id, t.value, array_agg(COALESCE(uv.value, v.value) ORDER BY ord) AS values, array_agg(p.name ORDER BY ord) AS names
				FROM 
					properties p
					JOIN orig_values v USING (document_id, property_xpath) 
					JOIN tokens t USING (document_id, token_id) 
					LEFT JOIN values uv USING (document_id, property_xpath, token_id)
					" . $filterTable . " 
				WHERE document_id = ? AND (user_id = ? OR user_id is NULL) 
				GROUP BY 1, 2
				ORDER BY token_id 
				LIMIT ? 
				OFFSET ?
				) t";
		$query = $this->con->prepare($queryStr);
		$params = array_merge($filterParam, array($documentId, $userId, $pageSize, $offset));
		$query->execute($params);
		$result = $query->fetch(PDO::FETCH_COLUMN);
		return $result;
	}
	
	private function getFilters($docId){
		$query = $this->con->prepare("SELECT property_xpath, name FROM properties WHERE document_id = ?");
		$query->execute(array($docId));
		$propDict = array();
		while($prop = $query->fetch(PDO::FETCH_OBJ)){
			$propDict[$prop->name] = $prop->property_xpath;
		}
		
		$query = "";
		$n = 1;
		$params = array();
		if(count($this->filters) == 0 && $this->tokenIdFilter === null && $this->tokenValueFilter === null){
			return array($query, $params);
		}
		if($this->tokenIdFilter !== null){
			$query .= "
				JOIN (
					SELECT ?::int AS token_id
				) f" . $n++ . " USING (token_id)";
			$params[] = $this->tokenIdFilter;
		}
		if($this->tokenValueFilter !== null){
			$query .= "
				JOIN (
					SELECT token_id
					FROM tokens
					WHERE 
						document_id = ?
						AND value = ?
				) f" . $n++ . " USING (token_id)";
			$params[] = $docId;
			$params[] = $this->tokenValueFilter;
		}
		
		foreach($this->filters as $prop=>$val){
			if(!isset($propDict[$prop])){
				continue;
			}
			$query .= "
				JOIN (
					SELECT token_id
					FROM orig_values
					WHERE
						document_id = ?
						AND property_xpath = ?
						AND value = ?
				) f" . $n++ . " USING (token_id)";
			$params[] = $docId;
			$params[] = $propDict[$prop];
			$params[] = $val;
		}
		
		return array($query, $params);
	}
	
}
