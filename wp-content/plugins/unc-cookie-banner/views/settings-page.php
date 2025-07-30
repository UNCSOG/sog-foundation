<?php
if (!isset($unc_cookie_banner_text)) {
    $unc_cookie_banner_text = '';
}

if (!isset($unc_cookie_banner_button_text)) {
    $unc_cookie_banner_button_text = '';
}

if (!isset($unc_cookie_banner_exclude_blogs)) {
    $unc_cookie_banner_exclude_blogs = '';
}
?>

<div class="wrap">
    <h1 style="text-align: left;"><?php echo esc_html(get_admin_page_title()); ?></h1>
    <form method="post" action="" style="text-align: left;">
        <?php wp_nonce_field('unc_cookie_banner_settings_action', 'unc_cookie_banner_settings_nonce'); ?>

        <table class="form-table" style="text-align: left; padding:0;">
            <tbody>
                <tr style="text-align: left;">
                    <th scope="row" colspan="2" style="text-align: left; padding-bottom: 10px;">
                        <label for="unc_cookie_banner_text" style="text-align: left;">Banner Text</label>
                    </th>
                </tr>

                <tr style="text-align: left;">
                    <td colspan="2" style="text-align: left; padding:0;">
                        <textarea name="unc_cookie_banner_text" id="unc_cookie_banner_text" rows="5" cols="75" style="text-align: left;"><?php echo esc_textarea($unc_cookie_banner_text); ?></textarea>
                    </td>
                </tr>

                <tr style="text-align: left;">
                    <th scope="row" colspan="2" style="text-align: left; padding-bottom: 10px;">
                        <label for="unc_cookie_banner_button_text" style="text-align: left;">Button Text</label>
                    </th>
                </tr>

                <tr style="text-align: left;">
                    <td colspan="2" style="text-align: left; padding:0;">
                        <input name="unc_cookie_banner_button_text" type="text" id="unc_cookie_banner_button_text" value="<?php echo esc_attr($unc_cookie_banner_button_text); ?>" class="regular-text" style="text-align: left;">
                    </td>
                </tr>

                <?php if(is_multisite()): // Render these fields only if the site is multisite ?>
                    <tr style="text-align: left;">
                        <th scope="row" colspan="2" style="text-align: left; padding-bottom: 10px;">
                            <label for="unc_cookie_banner_exclude_blogs" style="text-align: left; ">Exclude Blogs</label>
                            <p class="description" style="margin-bottom: 0; text-align: left;">Comma-separated list of blog IDs</p>
                        </th>
                    </tr>

                    <tr style="text-align: left;">
                        <td colspan="2" style="text-align: left; padding:0;">
                            <textarea name="unc_cookie_banner_exclude_blogs" id="unc_cookie_banner_exclude_blogs" rows="5" cols="75" style="text-align: left;"><?php echo esc_textarea($unc_cookie_banner_exclude_blogs); ?></textarea>
                        </td>
                    </tr>
                <?php endif; // End of multisite conditional ?>
            </tbody>
        </table>

        <?php submit_button(); ?>
    </form>
</div>
