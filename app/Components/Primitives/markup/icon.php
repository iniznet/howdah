<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver $c
 * @var string                               $path
 * @var string|null                          $label
 */
?>
<svg class="<?php echo esc_attr($c('icon')); ?>" width="1em" height="1em" viewBox="0 0 24 24" fill="currentColor" focusable="false" aria-hidden="<?php echo null === $label ? 'true' : 'false'; ?>"<?php if (null !== $label) { ?> role="img" aria-label="<?php echo esc_attr($label); ?>"<?php } ?>>
	<path d="<?php echo esc_attr($path); ?>"/>
</svg>
