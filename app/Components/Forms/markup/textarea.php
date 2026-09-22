<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver $c
 * @var string                               $id
 * @var string                               $name
 * @var string                               $label
 * @var string                               $value
 * @var int                                  $rows
 * @var bool                                 $required
 * @var bool                                 $invalid
 * @var string|null                          $describedBy
 */
?>
<div class="<?php echo esc_attr($c('field')); ?>">
	<label class="<?php echo esc_attr($c('label')); ?>" for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></label>
	<textarea class="<?php echo esc_attr($c('textarea')); ?>" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" rows="<?php echo (int) $rows; ?>"<?php echo $required ? ' required' : ''; ?><?php echo $invalid ? ' aria-invalid="true"' : ''; ?><?php echo null !== $describedBy ? ' aria-describedby="'.esc_attr($describedBy).'"' : ''; ?>><?php echo esc_textarea($value); ?></textarea>
</div>
