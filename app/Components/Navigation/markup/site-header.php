<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver $c
 * @var string                               $siteName
 * @var string                               $homeUrl
 * @var string                               $navigation already-rendered nav markup
 */
?>
<header class="<?php echo esc_attr($c('site-header')); ?>">
	<p class="<?php echo esc_attr($c('site-header-name')); ?>">
		<a href="<?php echo esc_url($homeUrl); ?>"><?php echo esc_html($siteName); ?></a>
	</p>
<?php if ('' !== $navigation) { ?>
	<?php echo $navigation; ?>
<?php } ?>
</header>
