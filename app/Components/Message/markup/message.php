<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver $c
 * @var string                               $heading
 * @var string|null                          $detail
 */
?>
<section class="<?php echo esc_attr($c('message')); ?>">
	<p class="<?php echo esc_attr($c('message-heading')); ?>"><?php echo esc_html($heading); ?></p>
<?php if (null !== $detail) { ?>
	<p class="<?php echo esc_attr($c('message-detail')); ?>"><?php echo esc_html($detail); ?></p>
<?php } ?>
</section>
