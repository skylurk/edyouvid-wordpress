<?php
/**
 * License Options
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$license_class = $GLOBALS['Learndash_Retake_Quiz_Options']->get_license_class();
?>
<div id="lrtq_license_options">
    <form method="post">
        <h2><?php _e( 'License Configuration', 'ld_retake_quiz' ); ?></h2>
        <h3><?php _e( 'Please enter a valid license key to receive latest add-on updates automatically.', 'ld_retake_quiz' ); ?></h3>
        <table class="form-table">
            <tr>
                <th style="width:100px;"><label
                        for="<?php echo $license_class->get_license_key_field(); ?>"><?php _e( 'License Key', 'ld_retake_quiz' ); ?></label>
                </th>
                <td>
                    <input class="regular-text" type="text" id="<?php echo $license_class->get_license_key_field(); ?>"
                           placeholder="<?php echo __( 'Enter license key provided with plugin', 'ld_retake_quiz' ); ?>"
                           name="<?php echo $license_class->get_license_key_field(); ?>"
                           value="<?php echo get_option( 'wn_lrtq_license_key' ); ?>"
                        <?php echo ( $license_class->get_license_handler()->is_active() ) ? 'readonly' : ''; ?>>
                </td>
            </tr>
        </table>
        <p class="submit">
            <?php if( ! $license_class->get_license_handler()->is_active() ) : ?>
                <input type="submit" name="lrtq_activate_license" value="<?php _e( 'Activate', 'ld_retake_quiz' ); ?>"
                       class="button-primary"/>
            <?php endif; ?>

            <?php if( $license_class->get_license_handler()->is_active() ) : ?>
                <input type="submit" name="lrtq_deactivate_license" value="<?php _e( 'Deactivate', 'ld_retake_quiz' ); ?>"
                       class="button-primary"/>
            <?php endif; ?>
        </p>
    </form>
</div>