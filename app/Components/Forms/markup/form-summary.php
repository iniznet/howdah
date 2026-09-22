<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver $c
 * @var list<string>                         $messages
 */
?>
<div class="<?php echo esc_attr($c('form-summary')); ?>" aria-live="polite">
	<p class="<?php echo esc_attr($c('form-summary-title')); ?>"><?php esc_html_e('Please fix the following:', 'howdah'); ?></p>
	<ul class="<?php echo esc_attr($c('form-summary-list')); ?>">
<?php foreach ($messages as $message) { ?>
		<li><?php echo esc_html($message); ?></li>
<?php } ?>
	</ul>
</div>
