<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver            $c
 * @var string                                          $label
 * @var Iniznet\Howdah\Components\Actions\ButtonType    $type
 * @var Iniznet\Howdah\Components\Actions\ButtonVariant $variant
 * @var bool                                            $disabled
 */
?>
<button class="<?php echo esc_attr(trim($c('button').' '.$c('button--'.$variant->value))); ?>" type="<?php echo esc_attr($type->value); ?>"<?php echo $disabled ? ' disabled' : ''; ?>>
	<?php echo esc_html($label); ?>
</button>
