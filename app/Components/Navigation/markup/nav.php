<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver               $c
 * @var list<Iniznet\Howdah\Components\Navigation\NavItem> $items
 * @var string                                             $label
 */
?>
<nav class="<?php echo esc_attr($c('nav')); ?>" aria-label="<?php echo esc_attr($label); ?>">
	<ul class="<?php echo esc_attr($c('nav-list')); ?>">
<?php foreach ($items as $item) { ?>
		<li><a href="<?php echo esc_url($item->href); ?>"<?php echo $item->current ? ' aria-current="page"' : ''; ?>><?php echo esc_html($item->label); ?></a></li>
<?php } ?>
	</ul>
</nav>
