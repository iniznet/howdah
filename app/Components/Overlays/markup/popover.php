<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver $c
 * @var string                               $id
 * @var string                               $triggerLabel
 * @var string                               $body
 */
?>
<button class="<?php echo esc_attr($c('button')); ?>" type="button" popovertarget="<?php echo esc_attr($id); ?>"><?php echo esc_html($triggerLabel); ?></button>
<div id="<?php echo esc_attr($id); ?>" class="<?php echo esc_attr($c('popover')); ?>" popover><?php echo esc_html($body); ?></div>
