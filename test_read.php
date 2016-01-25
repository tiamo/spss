<?php

require 'SPSSReader.php';
$SPSS = new SPSSReader('data.sav');

?>
<!DOCTYPE html>

<h2>Header</h2>
<table border="1" width="100%">
<?php foreach($SPSS->header as $key => $val): ?>
<tr>
	<th><?=$key?></th>
	<td><?=$val?></td>
</tr>
<?php endforeach; ?>
</table>

<h2>Variables view</h2>
<table border="1" width="100%">
	<tr>
		<th></th>
		<th>Name</th>
		<th>Type</th>
		<th>Width</th>
		<th>Decimals</th>
		<th>Label</th>
		<th>Values</th>
		<th>Missing values</th>
		<th>Align</th>
		<th>Columns</th>
		<th>Measure</th>
	</tr>
	<?php $i=0; foreach($SPSS->variables as $var): ?>
	<?php
		if ($var->isExtended) continue; // skip extended vars
		$i++; 
	?>
	<tr>
		<td><?=$i?></td>
		<td><?=isset($SPSS->extendedNames[$var->shortName]) ? $SPSS->extendedNames[$var->shortName] : $var->name?></td>
		<td><?= implode(',', $var->getPrintFormat()) ?> (#<?= $var->typeCode ?>)</td>
		<td><?=$var->getWidth()?></td>
		<td><?=$var->getDecimals()?></td>
		<td><?=$var->getLabel()?></td>
		<td>
			<?php
				foreach($var->valueLabels as $key => $val) {
					echo $key .') '. $val . '<br/>';
				}
			?>
		</td>
		<td><?=$var->getMissingLabel()?></td>
		<td><?=$var->getAlignmentLabel()?></td>
		<td><?=$var->getColumns()?></td>
		<td><?=$var->getMeasureLabel()?></td>
	</tr>
	<?php endforeach; ?>
</table>

<?php
$SPSS->loadData();
?>
<h2>Data view</h2>
<table border="1" width="100%">
<tr>
	<th></th>
	<?php foreach($SPSS->variables as $var): ?>
	<?php
		if ($var->isExtended) continue; // skip extended vars
	?>
	<th><?=$var->name?></th>
	<?php endforeach; ?>
</tr>
<?php
for($case=0;$case<$SPSS->header->numberOfCases;$case++) {
	echo '<tr>';
		echo '<td>'.($case+1).'</td>';
	foreach($SPSS->variables as $var) {
		if ($var->isExtended) continue; // skip extended vars
		echo '<td align="'.$var->getAlignmentLabel().'">'.($var->data[$case]==='NaN'?'.':$var->data[$case]).'</td>';
	}
	echo '</tr>';
}
?>
</table>
