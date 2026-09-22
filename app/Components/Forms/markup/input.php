<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver      $c
 * @var string                                    $id
 * @var string                                    $name
 * @var string                                    $label
 * @var Iniznet\Howdah\Components\Forms\InputType $type
 * @var string                                    $value
 * @var bool                                      $required
 * @var bool                                      $invalid
 * @var string|null                               $describedBy
 */
?>
<div class="<?php echo esc_attr($c('field')); ?>">
	<label class="<?php echo esc_attr($c('label')); ?>" for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></label>
	<input class="<?php echo esc_attr($c('input')); ?>" type="<?php echo esc_attr($type->value); ?>" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>"<?php echo $required ? ' required' : ''; ?><?php echo $invalid ? ' aria-invalid="true"' : ''; ?><?php echo null !== $describedBy ? ' aria-describedby="'.esc_attr($describedBy).'"' : ''; ?>>
</div>
