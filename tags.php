<?php

include('classes/class.finance.php');


//run our inits
$finance = new finance();

$content = '';

$tagset = $finance->readCSV("category_terms.csv");


//$content = $tagset;


foreach($tagset as $id => $data) {
    $num = count($data);
    $tags = '';
    for ($c=0; $c < $num; $c++) {
        switch($c) {
        	case 0:
        		//category, clean it, then match with DB
        		$category = trim($data[$c]);
        		break;
        	case 1:
        		//tags, parse, process
        		if($data[$c]) {
        			$terms = explode(",", $data[$c]);
        			
        			foreach($terms as $key => $tag) {
        			    $tag = trim(strtolower($tag));
        			    $tag = $finance->cleanToken($tag);
        			    
        			    $tags .= '<span class="tag">'.$tag.'</span>';
        			}
        		}
        		break;
        	default:
        		break;
        }
			
    }
    $content .= "<div class='termCluster'>\n";
    $content .= "<b>$category</b><br />";
    $content .= "$tags\n";
    $content .= "</div>\n\n";
        
    
}



include('templates/default.php');

?>


