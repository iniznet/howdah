<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver $c
 * @var string                               $id
 * @var string                               $title
 * @var string                               $body
 */
?>
<dialog class="<?php echo esc_attr($c('dialog')); ?>" id="<?php echo esc_attr($id); ?>" aria-labelledby="<?php echo esc_attr($id); ?>-title">
	<h2 class="<?php echo esc_attr($c('dialog-title')); ?>" id="<?php echo esc_attr($id); ?>-title"><?php echo esc_html($title); ?></h2>
	<div class="<?php echo esc_attr($c('dialog-body')); ?>"><?php echo esc_html($body); ?></div>
	<form method="dialog">
		<button class="<?php echo esc_attr($c('button')); ?>" type="submit"><?php esc_html_e('Close', 'howdah'); ?></button>
	</form>
</dialog>
