<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver         $c
 * @var string                                       $id
 * @var string                                       $name
 * @var string                                       $label
 * @var list<Iniznet\Howdah\Components\Forms\Option> $options
 * @var bool                                         $required
 * @var bool                                         $invalid
 * @var string|null                                  $describedBy
 */
?>
<div class="<?php echo esc_attr($c('field')); ?>">
	<label class="<?php echo esc_attr($c('label')); ?>" for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></label>
	<select class="<?php echo esc_attr($c('select')); ?>" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>"<?php echo $required ? ' required' : ''; ?><?php echo $invalid ? ' aria-invalid="true"' : ''; ?><?php echo null !== $describedBy ? ' aria-describedby="'.esc_attr($describedBy).'"' : ''; ?>>
<?php foreach ($options as $option) { ?>
		<option value="<?php echo esc_attr($option->value); ?>"<?php echo $option->selected ? ' selected' : ''; ?>><?php echo esc_html($option->label); ?></option>
<?php } ?>
	</select>
</div>
