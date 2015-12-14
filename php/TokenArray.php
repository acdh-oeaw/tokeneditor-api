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
	
	public function addFilter(){
		
	}
	
	public function generateJSON($documentid, $userid, $pagesize = 1000, $offset = 0) {
		list($filterTable, $filterParam) = $this->getFilters();
		$query = $this->con->prepare("
			SELECT json_agg(json_object(array_cat(array['token id', 'token'], names), array_cat(array[token_id::text, value], values))) AS data
			FROM (
				SELECT token_id, t.value, array_agg(COALESCE(uv.value, v.value)) AS values, array_agg(p.name) AS names
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
				) t");
		$params = array_merge($filterParam, array($documentid, $userid,$pagesize,$offset));
		$query->execute($params);
		$result = $query->fetch(PDO::FETCH_COLUMN);
		return $result;
	}
	
	private function getFilters(){
		if(count($this->filters) == 0 && $this->tokenIdFilter === null && $this->tokenValueFilter === null){
			return array('', array());
		}
		if($this->tokenIdFilter !== null){
			return array(
				"JOIN (
					SELECT ?::int AS token_id
				) f1 USING (token_id)",
				array($this->tokenIdFilter)
			);
		}
		if($this->tokenValue !== null){
			
		}
		
		foreach($this->filters as $prop=>$val){
			
		}
	}
	
}
