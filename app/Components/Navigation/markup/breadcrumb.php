<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver             $c
 * @var list<Iniznet\Howdah\Components\Navigation\Crumb> $crumbs
 */
?>
<nav class="<?php echo esc_attr($c('breadcrumb')); ?>" aria-label="<?php esc_attr_e('Breadcrumb', 'howdah'); ?>">
	<ol class="<?php echo esc_attr($c('breadcrumb-list')); ?>">
<?php foreach ($crumbs as $crumb) { ?>
		<li>
<?php if (null === $crumb->href) { ?>
			<span aria-current="page"><?php echo esc_html($crumb->label); ?></span>
<?php } else { ?>
			<a href="<?php echo esc_url($crumb->href); ?>"><?php echo esc_html($crumb->label); ?></a>
<?php } ?>
		</li>
<?php } ?>
	</ol>
</nav>
