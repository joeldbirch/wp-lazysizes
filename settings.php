<?php
add_action( 'admin_menu', 'lazysizes_add_admin_menu' );
add_action( 'admin_init', 'lazysizes_settings_init' );


function lazysizes_add_admin_menu(  ) {

    add_options_page( 'wp-lazysizes', 'wp-lazysizes', 'manage_options', 'wp-lazysizes', 'lazysizes_options_page' );

}


function lazysizes_settings_init(  ) {
    //delete_option( 'lazysizes_settings' );
    global $lazySizesDefaults;
    register_setting( 'pluginPage', 'lazysizes_settings' );
    add_option( 'lazysizes_settings', $lazySizesDefaults);

    add_settings_section(
        'lazysizes_pluginPage_section',
        __( 'lazySizes Configuration', 'wordpress' ),
        'lazysizes_settings_section_callback',
        'pluginPage'
    );

    add_settings_field(
        'expand',
        __( 'expand/threshold', 'wordpress' ),
        'lazysizes_expandSetting',
        'pluginPage',
        'lazysizes_pluginPage_section'
    );

    add_settings_field(
        'intrinsicRatiox',
        __( 'responsive intrinsic ratio box', 'wordpress' ),
        'lazysizes_intrinsicRatioSetting',
        'pluginPage',
        'lazysizes_pluginPage_section'
    );

    add_settings_field(
        'optimumx',
        __( 'optimumx (max. HighDPI)', 'wordpress' ),
        'lazysizes_optimumxSetting',
        'pluginPage',
        'lazysizes_pluginPage_section'
    );


}

function lazysizes_optimumxSetting(  ) {

    $options = get_option( 'lazysizes_settings' );
    ?>
    <select name='lazysizes_settings[optimumx]'>
        <option value='auto' <?php selected( $options['optimumx'], 'auto' ); ?>>auto</option>
        <option value='false' <?php selected( $options['optimumx'], 'false' ); ?>>no HIGH DPI constraints</option>
        <option value='2' <?php selected( $options['optimumx'], 2 ); ?>>2</option>
        <option value='1.6' <?php selected( $options['optimumx'], 1.6 ); ?>>1.6</option>
        <option value='1.2' <?php selected( $options['optimumx'], 1.2 ); ?>>1.2</option>
    </select>
<?php

}

function lazysizes_expandSetting(  ) {

    $options = get_option( 'lazysizes_settings' );
    ?>
    <input type='number' min="20" max="300" name='lazysizes_settings[expand]' value='<?php echo $options['expand']; ?>'>
<?php

}


function lazysizes_intrinsicRatioSetting(  ) {

    $options = get_option( 'lazysizes_settings' );
    ?>
    <select name='lazysizes_settings[intrinsicRatio]'>
        <option value='false' <?php selected( $options['intrinsicRatio'], 'false' ); ?>>no intrinsic ratio box</option>
        <option value='true' <?php selected( $options['intrinsicRatio'], 'true' ); ?>>intrinsic ratio box (recommended)</option>
        <option value='animated' <?php selected( $options['intrinsicRatio'], 'animated' ); ?>>animated intrinsic ratio box</option>
    </select>

<?php

}


function lazysizes_settings_section_callback(  ) {

    //echo __( '', 'wordpress' );

}


function lazysizes_options_page(  ) {

    ?>
    <form action='options.php' method='post'>

        <!-- <h2>lazySizes configuration</h2> -->

        <?php
        settings_fields( 'pluginPage' );
        do_settings_sections( 'pluginPage' );
        submit_button();
        ?>

    </form>
<?php

}

?>
