<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver  $c
 * @var Iniznet\Howdah\Components\Layout\Size $gap
 * @var string                                $label
 * @var list<string>                          $children
 */
?>
<section class="<?php echo esc_attr(trim($c('section').' '.$c('section--'.$gap->value))); ?>" aria-label="<?php echo esc_attr($label); ?>">
<?php foreach ($children as $child) { ?>
	<?php echo $child; ?>
<?php } ?>
</section>
