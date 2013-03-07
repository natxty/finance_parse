<?php

include('classes/class.finance.php');


//run our inits
$finance = new finance();



$content = "<table>\n\n<tr class=\"odd\">\n\n<th>Type</th>\n<th>Date</th>\n<th>Payee</th>\n<th>Category</th>\n<th>Tokens</th>\n<th>Amount</th>\n</tr>\n\n";
$row = 1;
if (($handle = fopen("JPMC_20110907.CSV", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        unset($td);
        $num = count($data);
        $row++;
        for ($c=0; $c < 4; $c++) {
            switch($c) {
				case 0:
					//transaction type (debit, etc.)
					//we'll use $finance->processType($data[$c]);
					//then we'll begin our SQL construction
					$type = $data[$c];
					break;
				case 1:
					//date
					//secure
					$date = $finance->processDate($data[$c],'oneline','-');
					break;
				case 2:
					//payee, need to parse
					$rawpayee =  $finance->processPayee($data[$c]);
					extract($rawpayee);
					$tokens = $finance->tokenize($payee);
					break;
				case 3:
					//amount
					$amount =  $data[$c];
					break;
				default:
					break;
			}
			
        }
        (intval($row/2) == $row/2) ? $class = 'even' : $class = 'odd';
        $content .= "<tr class=\"$class\">\n\n<td>$type</td>\n<td>$date</td>\n<td>$payee</td>\n<td>$category</td>\n<td>$tokens</td>\n<td>$amount</td>\n\n</tr>\n\n";
    }
    fclose($handle);
}
$content .= "</table>\n\n";

include('templates/default.php');

?>


