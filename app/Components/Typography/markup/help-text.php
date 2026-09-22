<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver $c
 * @var string                               $id
 * @var string                               $text
 */
?>
<p class="<?php echo esc_attr($c('help-text')); ?>" id="<?php echo esc_attr($id); ?>"><?php echo esc_html($text); ?></p>
