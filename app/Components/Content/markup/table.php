<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver $c
 * @var list<string>                         $headers
 * @var list<list<string>>                   $rows
 * @var string                               $caption
 */
?>
<table class="<?php echo esc_attr($c('table')); ?>">
<?php if ('' !== $caption) { ?>
	<caption class="<?php echo esc_attr($c('table-caption')); ?>"><?php echo esc_html($caption); ?></caption>
<?php } ?>
	<thead>
		<tr>
<?php foreach ($headers as $header) { ?>
			<th scope="col"><?php echo esc_html($header); ?></th>
<?php } ?>
		</tr>
	</thead>
	<tbody>
<?php foreach ($rows as $row) { ?>
		<tr>
<?php foreach ($row as $cell) { ?>
			<td><?php echo esc_html($cell); ?></td>
<?php } ?>
		</tr>
<?php } ?>
	</tbody>
</table>
