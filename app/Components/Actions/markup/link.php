<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver $c
 * @var string                               $href
 * @var string                               $label
 * @var bool                                 $external
 * @var bool                                 $quiet
 */
?>
<a class="<?php echo esc_attr(trim($c($quiet ? 'link link--quiet' : 'link'))); ?>" href="<?php echo esc_url($href); ?>"<?php if ($external) { ?> rel="noopener noreferrer" target="_blank"<?php } ?>>
	<?php echo esc_html($label); ?>
</a>
