<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver         $c
 * @var string                                       $label the accessible name
 * @var Iniznet\Howdah\Components\Actions\ButtonType $type
 */
?>
<button class="<?php echo esc_attr($c('icon-button')); ?>" type="<?php echo esc_attr($type->value); ?>" aria-label="<?php echo esc_attr($label); ?>"></button>
