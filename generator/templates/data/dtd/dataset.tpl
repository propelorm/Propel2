<!ELEMENT dataset (
    <?php
	$vc = 0;
	foreach($tables as $tbl) {
		if($vc++) {
			echo ",";
		}
		echo $tbl->getPhpName() ?>*<?php } ?> 
)>
<!ATTLIST dataset
    name CDATA #REQUIRED
>

