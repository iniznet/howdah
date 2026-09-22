<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver $c
 * @var string                               $title
 * @var string                               $body
 * @var string                               $actions
 */
?>
<article class="<?php echo esc_attr($c('card')); ?>">
	<h3 class="<?php echo esc_attr($c('card-title')); ?>"><?php echo esc_html($title); ?></h3>
	<div class="<?php echo esc_attr($c('card-body')); ?>"><?php echo esc_html($body); ?></div>
<?php if ('' !== $actions) { ?>
	<div class="<?php echo esc_attr($c('card-actions')); ?>"><?php echo $actions; ?></div>
<?php } ?>
</article>
