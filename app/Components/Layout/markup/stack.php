<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver  $c
 * @var Iniznet\Howdah\Components\Layout\Size $gap
 * @var list<string>                          $children
 */
?>
<div class="<?php echo esc_attr(trim($c('stack').' '.$c('stack--'.$gap->value))); ?>">
<?php foreach ($children as $child) { ?>
	<?php echo $child; ?>
<?php } ?>
</div>
