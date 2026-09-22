<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver $c
 * @var string                               $title
 * @var string                               $description
 * @var string                               $actions already-rendered action markup
 */
?>
<div class="<?php echo esc_attr($c('empty-state')); ?>">
	<p class="<?php echo esc_attr($c('empty-state-title')); ?>"><?php echo esc_html($title); ?></p>
	<p class="<?php echo esc_attr($c('empty-state-description')); ?>"><?php echo esc_html($description); ?></p>
<?php if ('' !== $actions) { ?>
	<div class="<?php echo esc_attr($c('empty-state-actions')); ?>"><?php echo $actions; ?></div>
<?php } ?>
</div>
