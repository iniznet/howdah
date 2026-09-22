<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver $c
 * @var string                               $id
 * @var string                               $name
 * @var string                               $inputValue
 * @var string                               $label
 * @var bool                                 $checked
 * @var string|null                          $describedBy
 */
?>
<div class="<?php echo esc_attr($c('choice')); ?>">
	<input type="radio" class="<?php echo esc_attr($c('radio')); ?>" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($inputValue); ?>"<?php echo $checked ? ' checked' : ''; ?><?php echo null !== $describedBy ? ' aria-describedby="'.esc_attr($describedBy).'"' : ''; ?>>
	<label class="<?php echo esc_attr($c('label')); ?>" for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></label>
</div>
