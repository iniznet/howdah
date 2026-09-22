<?php
/**
 * @var Iniznet\Howdah\Features\Settings\DisplayOptions $options
 * @var array<string, string|bool>                      $values the stored value per option id
 */
?>
<div class="wrap">
	<h1><?php echo esc_html__('Theme settings', 'howdah'); ?></h1>
	<form method="post" action="">
<?php wp_nonce_field(Iniznet\Howdah\Admin\ThemeSettingsScreen::nonceAction()); ?>
		<input type="hidden" name="howdah_settings_submit" value="1" />
		<table class="form-table" role="presentation">
<?php foreach ($options as $option) {
    $value = $values[$option->id] ?? $option->default; ?>
			<tr>
				<th scope="row"><label for="<?php echo esc_attr($option->id); ?>"><?php echo esc_html($option->label); ?></label></th>
				<td>
<?php if (Iniznet\Howdah\Features\Settings\DisplayOption::BOOLEAN === $option->type) { ?>
					<input type="checkbox" id="<?php echo esc_attr($option->id); ?>"
						name="howdah_settings[<?php echo esc_attr($option->id); ?>]" value="1"
						<?php checked((bool) $value); ?>>
<?php } elseif (Iniznet\Howdah\Features\Settings\DisplayOption::CHOICE === $option->type) { ?>
					<select id="<?php echo esc_attr($option->id); ?>"
						name="howdah_settings[<?php echo esc_attr($option->id); ?>]">
<?php foreach ($option->choices as $choice) { ?>
						<option value="<?php echo esc_attr($choice); ?>" <?php selected((string) $value, $choice); ?>>
							<?php echo esc_html($choice); ?>
						</option>
<?php } ?>
					</select>
<?php } else { ?>
					<input type="text" class="regular-text" id="<?php echo esc_attr($option->id); ?>"
						name="howdah_settings[<?php echo esc_attr($option->id); ?>]"
						value="<?php echo esc_attr((string) $value); ?>">
<?php } ?>
				</td>
			</tr>
<?php } ?>
		</table>
<?php submit_button(); ?>
	</form>
</div>
