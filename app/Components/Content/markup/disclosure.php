<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver $c
 * @var string                               $summary
 * @var string                               $body
 * @var bool                                 $open
 */
?>
<details class="<?php echo esc_attr($c('disclosure')); ?>"<?php echo $open ? ' open' : ''; ?>>
	<summary class="<?php echo esc_attr($c('disclosure-summary')); ?>"><?php echo esc_html($summary); ?></summary>
	<div class="<?php echo esc_attr($c('disclosure-body')); ?>"><?php echo esc_html($body); ?></div>
</details>
