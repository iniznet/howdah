<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver $c
 * @var string                               $id
 * @var string                               $name
 * @var string                               $label
 * @var list<string>                         $suggestions
 */
?>
<div class="<?php echo esc_attr($c('field')); ?>">
	<label class="<?php echo esc_attr($c('label')); ?>" for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></label>
	<input class="<?php echo esc_attr($c('input')); ?>" type="text" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" role="combobox" aria-expanded="false" aria-controls="<?php echo esc_attr($id); ?>-list" aria-autocomplete="list" autocomplete="off">
	<ul class="<?php echo esc_attr($c('combobox-list')); ?>" id="<?php echo esc_attr($id); ?>-list" role="listbox" hidden>
<?php foreach ($suggestions as $suggestion) { ?>
		<li role="option"><?php echo esc_html($suggestion); ?></li>
<?php } ?>
	</ul>
</div>
