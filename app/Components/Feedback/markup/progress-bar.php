<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver $c
 * @var string                               $label
 * @var int                                  $value
 */
?>
<div class="<?php echo esc_attr($c('progress')); ?>" role="progressbar" aria-label="<?php echo esc_attr($label); ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo (int) $value; ?>">
	<div class="<?php echo esc_attr($c('progress-bar')); ?>" style="inline-size: <?php echo (int) $value; ?>%;"></div>
</div>
