<?php
/**
 * @var Iniznet\Howdah\Admin\MigrationSnapshot $snapshot
 * @var list<string>                           $environment pre-escaped name and value pairs
 */
?>
<div class="wrap">
	<h1><?php echo esc_html__('Site status', 'howdah'); ?></h1>
	<table class="widefat striped" style="max-width: 640px;">
		<tbody>
			<tr>
				<th scope="row"><?php echo esc_html__('Schema version (code)', 'howdah'); ?></th>
				<td><?php echo esc_html((string) $snapshot->codeVersion); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php echo esc_html__('Schema version (stored)', 'howdah'); ?></th>
				<td><?php echo esc_html((string) $snapshot->storedVersion); ?></td>
			</tr>
<?php if (!$snapshot->isCurrent()) { ?>
			<tr>
				<th scope="row"><?php echo esc_html__('Pending migrations', 'howdah'); ?></th>
				<td>
<?php if ([] === $snapshot->pending) { ?>
					<?php echo esc_html__('The stored schema is ahead of the code.', 'howdah'); ?>
<?php } else { ?>
					<ul>
<?php foreach ($snapshot->pending as $migration) { ?>
						<li><?php echo esc_html($migration); ?></li>
<?php } ?>
					</ul>
<?php } ?>
				</td>
			</tr>
<?php } ?>
			<tr>
				<th scope="row"><?php echo esc_html__('Applied migrations', 'howdah'); ?></th>
				<td><?php echo esc_html((string) count($snapshot->applied)); ?></td>
			</tr>
<?php foreach ($environment as $pair) { ?>
			<tr><th scope="row"><?php echo esc_html($pair[0]); ?></th><td><?php echo esc_html($pair[1]); ?></td></tr>
<?php } ?>
		</tbody>
	</table>
</div>
