<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver $c
 * @var string                               $colophon
 */
?>
<footer class="<?php echo esc_attr($c('site-footer')); ?>">
	<p class="<?php echo esc_attr($c('site-footer-colophon')); ?>">
<?php echo esc_html(sprintf(
    /* translators: %s: the current year. */
    __('© %s', 'howdah'),
    date_i18n('Y'),
)); ?>
<?php if ('' !== $colophon) { ?>
		<?php echo esc_html($colophon); ?>
<?php } ?>
	</p>
</footer>
