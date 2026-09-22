<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver    $c
 * @var string                                  $message
 * @var Iniznet\Howdah\Components\Overlays\Tone $tone
 */
?>
<div class="<?php echo esc_attr(trim($c('toast').' '.$c('toast--'.$tone->value))); ?>" role="status"><?php echo esc_html($message); ?></div>
