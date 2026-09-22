<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver $c
 * @var string                               $legend
 * @var string                               $controls
 */
?>
<fieldset class="<?php echo esc_attr($c('fieldset')); ?>">
	<legend class="<?php echo esc_attr($c('fieldset-legend')); ?>"><?php echo esc_html($legend); ?></legend>
<?php echo $controls; ?>
</fieldset>
