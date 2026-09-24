<?php
/**
 * @var Iniznet\Mahout\Ui\ClassResolver $c
 * @var string                          $action
 * @var string                          $term
 */
?>
<form class="<?php echo esc_attr($c('search-form')); ?>" role="search" method="get" action="<?php echo esc_url($action); ?>">
	<label class="<?php echo esc_attr($c('search-form-label')); ?>" for="howdah-search-input"><?php esc_html_e('Search this site', 'howdah'); ?></label>
	<div class="<?php echo esc_attr($c('search-form-row')); ?>">
		<input class="<?php echo esc_attr($c('search-form-input')); ?>" type="search" id="howdah-search-input" name="s" value="<?php echo esc_attr($term); ?>">
		<button class="<?php echo esc_attr($c('button')); ?>" type="submit"><?php esc_html_e('Search', 'howdah'); ?></button>
	</div>
</form>
