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
		list($filterQuery, $filterParam) = $this->getFilters($documentId, $userId);
		$queryStr = "
			WITH filter AS (" . $filterQuery . ")
			SELECT
				json_build_object(
					'tokenCount', (SELECT count(*) FROM filter), 
					'data', COALESCE( 
						json_agg(json_object(array_cat(array['token_id', 'token'], names), array_cat(array[token_id::text, value], values))), 
						array_to_json(array[]::text[]) 
					) 
				) 
			FROM ( 
				SELECT 
					token_id, t.value, 
					array_agg(COALESCE(uv.value, v.value) ORDER BY ord) AS values, 
					array_agg(p.name ORDER BY ord) AS names 
				FROM
					(
						SELECT * 
						FROM filter 
						LIMIT ? 
						OFFSET ?
					) f
					JOIN tokens t USING (document_id, token_id) 
					JOIN properties p USING (document_id)
					JOIN orig_values v USING (document_id, property_xpath, token_id) 
					LEFT JOIN (
						SELECT *
						FROM values 
						WHERE user_id = ?
					) uv USING (document_id, property_xpath, token_id) 
				GROUP BY 1, 2 
				ORDER BY token_id 
			) t";
		
		$query = $this->con->prepare($queryStr);
		$params = array_merge($filterParam, array($pageSize, $offset, $userId));
		$query->execute($params);				
		$result = $query->fetch(PDO::FETCH_COLUMN);
		
		return $result;
	}

	public function getTokensOnly($documentId, $userId, $pageSize = 1000, $offset = 0){
		list($filterQuery, $filterParam) = $this->getFilters($documentId, $userId);
		$queryStr = "
			WITH filter AS (" . $filterQuery . ")
			SELECT 
				json_build_object(
					'tokenCount', (SELECT count(*) FROM filter),
					'data', COALESCE(
						json_agg(json_build_object('tokenId', token_id, 'token', value) ORDER BY token_id),
						'[]'
					)
				)
			FROM 
				documents_users
				JOIN tokens USING (document_id)
				JOIN (
					SELECT * 
					FROM filter 
					LIMIT ?
					OFFSET ?
				) t USING (document_id, token_id)
			WHERE user_id = ?";
		$query = $this->con->prepare($queryStr);
		$params = array_merge($filterParam, array($pageSize, $offset, $userId));
		$query->execute($params);
		$result = $query->fetch(PDO::FETCH_COLUMN);
		
		return $result ? $result : '[]';
	}
	
	private function getFilters($docId, $userId){
		$query = $this->con->prepare("SELECT property_xpath, name FROM properties WHERE document_id = ?");
		$query->execute(array($docId));
		$propDict = array();
		while($prop = $query->fetch(PDO::FETCH_OBJ)){
			$propDict[$prop->name] = $prop->property_xpath;
		}
		
		$query = "";
		$n = 1;
		$params = array();
		
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
						AND lower(value) LIKE lower(?)
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
					FROM 
						orig_values o
						LEFT JOIN values v USING (document_id, property_xpath, token_id)
					WHERE
						document_id = ?
						AND property_xpath = ?
						AND (user_id = ? OR user_id IS NULL)
						AND COALESCE(v.value, o.value) ILIKE ?
				) f" . $n++ . " USING (token_id)";
			$params[] = $docId;
			$params[] = $propDict[$prop];
			$params[] = $userId;
			$params[] = $val;
		}
		
		$query = "				
			SELECT DISTINCT document_id, token_id
			FROM
				documents_users
				JOIN tokens USING (document_id)
				" . $query . " 
			WHERE document_id = ? AND user_id = ?
			ORDER BY token_id";
		$params[] = $docId;
		$params[] = $userId;
		
		return array($query, $params);
	}
	
}