<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver $c
 * @var string                               $src
 * @var string                               $alt
 * @var string                               $caption
 */
?>
<figure class="<?php echo esc_attr($c('figure')); ?>">
	<img src="<?php echo esc_url($src); ?>" alt="<?php echo esc_attr($alt); ?>" loading="lazy" decoding="async">
	<figcaption class="<?php echo esc_attr($c('figure-caption')); ?>"><?php echo esc_html($caption); ?></figcaption>
</figure>
