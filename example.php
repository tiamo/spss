<?php
require 'SPSSReader.php';
$SPSS = new SPSSReader('data.sav');

?>
<!DOCTYPE html>

<h2>Variables view</h2>
<table border="1" width="100%">
	<tr>
		<th>Name</th>
		<th>Type</th>
		<th>Label</th>
		<th>Missing values</th>
		<th>Align</th>
		<th>Width</th>
		<th>Measure</th>
	</tr>
	<?php foreach($SPSS->variables as $var): ?>
	<tr>
		<td><?=$var->name?></td>
		<td><?=$var->typeCode==0?'Numeric':'String'?></td>
		<td><?=$var->label?></td>
		<td><?=sizeof($var->missingValues)?></td>
		<td><?=$var->params->alignLabel?></td>
		<td><?=$var->params->width?></td>
		<td><?=$var->params->measureLabel?></td>
	</tr>
	<?php endforeach; ?>
</table>

<h2>Data view</h2>
<table border="1" width="100%">
<tr>
	<th></th>
	<?php foreach($SPSS->variables as $var): ?>
	<th><?=$var->name?></th>
	<?php endforeach; ?>
</tr>
<?php
	for($case=0;$case<$SPSS->header->numberOfCases;$case++) {
		echo '<tr>';
			echo '<td>'.($case+1).'</td>';
		foreach($SPSS->variables as $i=> $var) {
			echo '<td align="'.$var->params->alignLabel.'">'.($var->data[$case]>=0?$var->data[$case]:'.').'</td>';
		}
		echo '</tr>';
	}
?>
</table>
