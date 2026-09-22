<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver $c
 * @var string                               $label
 */
?>
<div class="<?php echo esc_attr($c('loading-state')); ?>" role="status">
	<span class="<?php echo esc_attr($c('spinner')); ?>" aria-hidden="true"></span>
	<span class="<?php echo esc_attr($c('loading-state-label')); ?>"><?php echo esc_html($label); ?></span>
</div>
