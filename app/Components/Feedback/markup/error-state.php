<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver $c
 * @var string                               $title
 * @var string                               $description
 * @var string|null                          $reference a stable support code, never a value or a path
 */
?>
<div class="<?php echo esc_attr($c('error-state')); ?>" role="alert">
	<p class="<?php echo esc_attr($c('error-state-title')); ?>"><?php echo esc_html($title); ?></p>
	<p class="<?php echo esc_attr($c('error-state-description')); ?>"><?php echo esc_html($description); ?></p>
<?php if (null !== $reference && '' !== $reference) { ?>
	<p class="<?php echo esc_attr($c('error-state-reference')); ?>"><?php echo esc_html(sprintf(__('Reference: %s', 'howdah'), $reference)); ?></p>
<?php } ?>
</div>
