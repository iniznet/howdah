<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver $c
 * @var string                               $label
 * @var string                               $href
 */
?>
<a class="<?php echo esc_attr($c('tag')); ?>" href="<?php echo esc_url($href); ?>"><?php echo esc_html($label); ?></a>
