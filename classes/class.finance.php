<?php

/**
 * Enter description here ...
 * @author natxty
 *
 */
class Finance {
	
    
    
	var $types = array();
	var $trans_types = array();
	
	//database
	var $dbhost = 'localhost';
	var $dbuser = 'root';
	var $dbpass = 'root';
	var $dbname = 'finance';
	
	//tag file
	var $infile = 'category_terms.csv';
	
	function __construct($debug=FALSE) {
	    $this->debug = $debug;
		$link = $this->connectDB($this->dbhost,$this->dbuser,$this->dbpass,$this->dbname);
		if(!$link['success']) { echo $link['message']; }
		$this->initTypes();	
	}
	
	function initTypes() {
		
	    $trans_types = array();
		
		$q = 'select * from transtypes';
		$r = mysql_query($q) or $errors[] = mysql_error();
		while($s = mysql_fetch_object($r)) {
			$trans_types[$s->typeid] = $s->typename;
		}
		
		$this->types = $trans_types;
	}
	
	function connectDB($dbhost,$dbuser,$dbpass,$dbname) {
	    
	    $link = mysql_connect($dbhost,$dbuser,$dbpass);
	    if(!$link) {
	        $output['success'] = FALSE;
	        $output['message'] = mysql_error();
	        return $output;
	    }
	    
	    $dbselect = mysql_select_db($dbname, $link);
	    if(!$dbselect) {
	        $output['success'] = FALSE;
	        $output['message'] = mysql_error();
	        return $output;
	    }
	    
	    $output['success'] = TRUE;
	    $output['message'] = '';
	    return $output;
	}
	
	
	function processType($str) {
		
		if(in_array($str, $this->types)) {
			$result['status'] = 'exists';
			$result['id'] = array_search($str, $this->types);
		} else {
			//we'll insert the new category and get ourselves an id
			$data = array('category' => $str);
			$insert = $this->insertRecord('categories', $data);
			if($insert['status'] == 'success') {
				$result['status'] = 'new';
				$result['id'] = $insert['insert_id'];
			} else {
				$result['status'] = 'error';
				$result['message'] = $insert['message'];
			}
			
		}
		
		return $result;
	}
	
	
	function processDate($datestring,$type = 'oneline',$delimiter='/') {
	    //we could try to detect input, but for now we're only getting one type of datestring
	    $dt = explode("/", $datestring);
	    list($m,$d,$y) = $dt;
	    
	    //to-do: expand to more types
	    switch($type) {
	        case 'oneline':
	        default:
	            return $m.$delimiter.$d.$delimiter.$y;
	            break;
	            
	    }
	}
	
    function processPayee($payee,$pattern = '/([0-9]{2}\/[0-9]{2}(.+))/') {
        
        $category = '';
        
        $payee = trim(preg_replace($pattern, '', $payee));
        $output['payee'] = $payee;
        //if(stristr($payee, 'starbucks')) { $category = 'coffee'; }
        $terms = $this->tokenize($payee);    
        $category = $this->matchCategory($payee);
        $output['category'] = $category;
	    return $output;
	}
	
	function matchCategory($term) {
	    $tagset = $this->readCSV($this->infile);
	    foreach($tagset as $row => $array) {
	        //echo "Term: ".$term."<br />Tags: ".$array[1]."<br />\n";
	        if(stristr($array[1], $term)) {
                $match = $array[0];
	        } else {
	            $match = '';
	        }
	    }
	    return $match;
	}
	
	/**
	 * General CSV processing funtion
	 * Enter description here ...
	 * @param unknown_type $string
	 */
	function readCSV($filename, $forTags = FALSE) {
	    
    	if ((!$handle = fopen($filename, "r"))) {
    	    return FALSE;
    	}
    	$row = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            
            $num = count($data);            
            for ($c=0; $c < $num; $c++) {
                $array[$row][] = $data[$c];
            }
            $row++;
        }
        
        fclose($handle);
        
        return $array;
	}
	
	function tokenize($string) {
	    //let's strip the numbers
	    $string = preg_replace('/([0-9]+)/', '', $string);
	    $expl = explode(' ',$string);
	    
	    $output = '';
	    foreach($expl as $key => $value) {
	        if($value != '') $output .= $value.', ';
	    }
	    $output = substr($output, 0, -2);
	    return $output;
	}
	
	function cleanToken($token) {
	    
	    $search = array("'", "&");
	    $replace = array('','and');
	    
	    return str_replace($search, $replace, $token);
	}
	
	/**
	 * Insert Records into a given table in the database
	 * 
	 * @param string $table - the name of the table we're inserting into
	 * @param array $data - an associative array of the data
	 */
	
	function insertRecord(string $table, array $data) {
		
		//init sql strings
		$inserts = "insert into $table (";
		$values = "values (";
		
		//flatten array into necessary key/value pairs:
		foreach($data as $key => $value) {
			$inserts .= "$key, ";
			$value = strClean($value); //clean up the insert!
			$values .= "'$value', ";
		}
		
		//cap off sql strings
		$inserts .= ") "; //keep space!
		$values = substr($values, 0, -2); //carve off last 2 (comma + space)
		$values .= ")";
		
		//perform the query, get result object or error
		$query = $inserts.$values;
		$result = mysql_query($query) or $error = mysql_error();
		
		//construct the return packet
		if(!$error) {
			$return['status'] = 'success';
			$return['insert_id'] = mysql_insert_id();
		} else {
			$return['status'] = 'error';
			$return['message'] = $error;
		}
		
		return $return;
		
	}
	
	function getCategories($mode='tree',$filter='none') {
	    
	    //tree view, default for now...
	    $q = 'SELECT * FROM `categories` WHERE parent = 0';
	    $parents = $this->dbGetArray($q);
	    foreach($parents as $key => $array) {
	        $categories[] = array('catid' => $array['catid'], 'category' => $array['category'], 'parent' => $array['parent']);
	        $q = 'SELECT * FROM `categories` WHERE parent = '.$array['catid'];
	        $children = $this->dbGetArray($q);
	        foreach($children as $key => $charray) {
	            $categories[] = array('catid' => $charray['catid'], 'category' => $charray['category'], 'parent' => $charray['parent']);
	        }
	        
	    }
	    return $categories;
	}
	
    function getCategoriesForm($type = 'select') {
	    
	    $categories = $this->getCategories();
	    if($type=='select') {
	        $formout = "<select name=''>\n";
	        foreach($categories as $id => $array) {
	            $value = $array['id'];
	            $option = $array['category'];
	            $formout .= "<option value = \"$value\">$option</option>\n";
	        }
	        $formout .= "</select>\n\n";
	    }
	    
	    return $formout;
	}
	
    function dbError($error=false,$query=false,$debug=false) {
        echo "<p><strong>Error: Database error!</strong></p>\n" ;
        if ($debug) { echo "<p>Query: $query</p>\n<p>Error: $error</p>\n" ; }
        die();
    }

    function dbQuery($query,$ignore_errors=false) {

        if ($ignore_errors) { $result = mysql_query($query); }
        else { $result = mysql_query($query) or die($this->dbError(mysql_error(),$query)); }
        return $result;
    }
    
    
    
    function dbGetArray($query,$ignore_errors=false,$amode='assoc') {
        ($amode=='num') ? $mode = MYSQL_NUM : $mode = MYSQL_ASSOC;
        $result = $this->dbQuery($query,$ignore_errors);
        $a = array();
        while ($row = mysql_fetch_array($result, $mode)) {
            $a[] = $row;
        }
        return $a;
    }

    
    function dbNumRows($result) {
        $numrows = mysql_num_rows($result);
        return $numrows;
    }

    function dbAffectedRows() {
        $arows = mysql_affected_rows();
        return $arows;
    }
    
    function dbInsertId() {
        $insert_id = mysql_insert_id();
        return $insert_id;
    }
	
	
	function strClean($string) {
		//if magic_quotes_gpc is enabled, we'll need to strip extra slashes...
		if(get_magic_quotes_gpc() == 1) {
			$string = stripslashes($string);
		}
		return mysql_real_escape_string($string);	
	}	
}
?>