<?php

class Orgocdn_Admin
{
    private $ajax_nonce_string = 'best-nonce-ever';
    private $user_key;
    private $page_hook;
    private $page_slug = "orgocdn-admin-panel";
    private $group_slug = "orgocdn_settings_group";
    private $process;

    public function __construct()
    {
        $this->require_classes();

        $this->set_api_key();

        $this->load_textdomain();

        $this->make_background_process();
        $this->add_options();

        $this->add_actions();
        $this->add_ajax_actions();
    }

    public function admin_init()
    {
        global $whitelist_options;

        register_setting( $this->group_slug, 'orgotech_options', '%s');

        add_settings_section(
            'setting_section_1', // ID
            'Enter your API-key', // Title
            array( $this, 'print_section_info' ), // Callback
            $this->page_slug // Page
        );

        add_settings_field(
           'user_key', // ID
           'API Key', // Title
           array( $this, 'user_key_callback' ), // Callback
           $this->page_slug, // Page
           'setting_section_1' // Section
       );
    }

    private function get_nonce()
    {
        $nonce = wp_create_nonce( $this->ajax_nonce_string );
        return $nonce;
    }

    public function set_slugs()
    {
        $this->page_slug = "orgocdn-admin-panel";
    }

    public function set_api_key()
    {
        $this->user_key = get_option('orgotech_options')['user_key'];
    }

    public function add_actions()
    {
        add_action( 'admin_init', array( $this, 'admin_init' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    }

    public function make_background_process()
    {
        $this->process = new Orgocdn_Process();
    }

    public function add_options()
    {
        add_option('orgotech_options', "", "", "yes");
        add_option('orgotech_background_running', 0, "", "yes");
    }

    public function require_classes()
    {
        require_once(ORGOCDN__PLUGIN_DIR . '/class/wp-async-request.php');
        require_once(ORGOCDN__PLUGIN_DIR . '/class/wp-background-process.php');
        require_once(ORGOCDN__PLUGIN_DIR . '/class/background-process.php');
    }

    public function load_textdomain()
    {
        load_plugin_textdomain( 'orgocdn' );
    }


    public function add_ajax_actions()
    {
        add_action( 'wp_ajax_get_api',        array( $this, 'ajax_get_api') );

        add_action( 'wp_ajax_run_optimizer', array( $this, 'ajax_run_optimizer'));
        add_action( 'wp_ajax_stop_optimizer', array( $this, 'ajax_stop_optimizer'));
        add_action( 'wp_ajax_done_optimizer', array( $this, 'ajax_done_optimizer'));

        add_action( 'wp_ajax_get_progress',   array( $this, 'ajax_get_progress'));
        add_action( 'wp_ajax_get_progress_numbers',   array( $this, 'ajax_get_progress_numbers'));
    }

    public function admin_menu()
    {
        load_plugin_textdomain( 'orgocdn' );
        $page_hook = add_menu_page( __('ORGOTECH', 'orgocdn'), __('ORGOTECH', 'orgocdn'), 'manage_options', $this->page_slug, array( $this, 'admin_display_page' ) );
        add_action( 'admin_print_scripts-' . $page_hook,  array( $this, 'load_resources' ) );
    }

    public function load_menu()
    {
        // Do nothing
    }


    public function print_section_info()
    {
        print('<p>It looks like you don\'t have a valid API key, please insert your API key here. <br/>If you don\'t have an API key, <a href="https://app.orgocdn.com/signup">signup here</a> to get it. </p>');
    }

    public function register_styles()
    {
        wp_register_style('orgocdn_stylesheet', ORGOCDN__PLUGIN_URL.'includes/css/orgocdn_style.css');
        wp_register_style('orgocdn_bootstrap', ORGOCDN__PLUGIN_URL . 'includes/css/bootstrap.min.css');
        wp_register_script('orgocdn_bootstrap_script', ORGOCDN__PLUGIN_URL . 'includes/script/bootstrap.min.js');
    }

    public function enqueue_styles()
    {
        wp_enqueue_script('orgocdn_bootstrap_script');
        wp_enqueue_style('orgocdn_bootstrap');
        wp_enqueue_style('orgocdn_stylesheet');
    }

    public function load_resources()
    {
        $this->register_styles();
        $this->enqueue_styles();
    }

    public function admin_display_page()
    {
        $options = get_option('orgotech_options');

        $nonce = wp_create_nonce( $this->ajax_nonce_string );

        if($options['user_key']  && !$options['domain_key'])
        {
            $user_key   = $options['user_key'];
            $post       = array('user_key' => $user_key, 'domain' => $_SERVER['HTTP_HOST']);
            $ch         = curl_init('https://app.orgocdn.com/api/v1/create');

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

            $response   = curl_exec($ch);
            curl_close($ch);

            $domain_key = json_decode($response)->api_key;

            $options['domain_key'] = $domain_key;
            update_option('orgotech_options', $options);
        }

        if(!get_option('orgotech_options')['domain_key'])
        {
            ?>
            <form method="post" action="options.php">
            <?php
                settings_fields( 'orgocdn_settings_group' );
                do_settings_sections( $this->page_slug );
                submit_button();
            ?>
            <?php
        }
        else
        {
            $api = json_decode($this->get_api_string());
            include(ORGOCDN__PLUGIN_DIR . 'views/orgocdn-admin-panel.php');
        }
    }

    public function user_key_callback()
    {
        print(
            '<div class="col-md-5"><input class="form-control" type="text" id="user_key" name="orgotech_options[user_key]" value="' . get_option('orgotech_options')['user_key'] . '" /></div>'
       );
    }

    public function ajax_get_numberof_images()
    {

    }

    public function get_prefixed($number)
    {
        $i = 0;
        while($number > 1024)
        {
            $number = $number  / 1024;
            $i++;
        }
        $prefix = "";
        switch($i)
        {
            case 0: $prefix = " B"; break;
            case 1: $prefix = " KB"; break;
            case 2: $prefix = " MB"; break;
            case 3: $prefix = " GB"; break;
            case 4: $prefix = " TB"; break;
        }

        return array('number' => $number, 'prefix' => $prefix);
    }

    public function ajax_get_everything()
    {
        global $wpdb;
        check_ajax_referer($this->ajax_nonce_string, 'verify', true);
        $i = 0;

        echo json_encode(array(
            'status' => get_option('orgotech_status'),
            'progress' => get_option('orgotech_progress'),
            'api' => json_decode($this->get_api_string())
        ));

        update_option('orgotech_update', "false");
        wp_die();
    }

    public function ajax_get_progress()
    {
        check_ajax_referer($this->ajax_nonce_string, 'verify', true);

        echo json_encode(get_option('orgotech_background_running'));
        wp_die();
    }

    public function ajax_get_progress_numbers()
    {
        check_ajax_referer($this->ajax_nonce_string, 'verify', true);
        echo json_encode($this->get_progress_numbers());
        wp_die();
    }

    public function get_progress_numbers()
    {
        global $wpdb;
        $tablename = $wpdb->prefix . 'orgotech_optimizations';
        $current = $wpdb->get_results("SELECT COUNT(ID) as count FROM $tablename WHERE status = 1")[0]->count + 0;
        $total = $wpdb->get_results("SELECT COUNT(ID) as count FROM $tablename WHERE status = 0")[0]->count + $current;

        return array('current' => $current, 'total' => $total);
    }

    public function ajax_run_optimizer()
    {
        check_ajax_referer($this->ajax_nonce_string, 'verify', true);

        if($status == 1)
        {
            wp_die();
        }

        $images = Orgocdn_Image::get_images();

        $i = 0;

        foreach($images as $image)
        {
            $this->process->push_to_queue($image);

            Orgocdn_Image::add_image( $image['id'], $image['path'],$image['url']);

            $i++;
        }
        if(count($images))
        {
            update_option('orgotech_background_running', 1);
            $this->process->save()->dispatch();
        }
        else
        {
            echo json_encode(false);
            wp_die();
        }

        wp_die();
    }

    public function ajax_stop_optimizer()
    {
        check_ajax_referer($this->ajax_nonce_string, 'verify', true);
        $this->process->cancel_process();
        update_option('orgotech_background_running', 0);
        wp_die();
    }

    public function ajax_done_optimizer()
    {
        check_ajax_referer($this->ajax_nonce_string, 'verify', true);
        update_option('orgotech_background_running', 0);
        wp_die();
    }

    public function ajax_get_api()
    {

        check_ajax_referer($this->ajax_nonce_string, 'verify', true );
        echo json_encode(array('success' => true, 'data' => self::get_api_string()));
        wp_die();
    }

    public function get_api_string()
    {
        $user_key   = get_option('orgotech_options')['user_key'];
        $domain_key = get_option('orgotech_options')['domain_key'];
        $post       = array('user_key' => $user_key);
        $ch         = curl_init('https://app.orgocdn.com/api/v1/stats/'.$domain_key);
        // $post2      = array('user_key' => $user_key, 'api_key' => $domain_key);
        // $ch2         = curl_init('https://app.orgocdn.com/api/v1/domaininfo');

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,     $post);
        // curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch2, CURLOPT_POSTFIELDS,     $post2);

        $get_api_string = curl_exec($ch);
        // $get_info = curl_exec($ch2);
        curl_close($ch);
        // curl_close($ch2);
        return $get_api_string;
    }

    /**
     * @return string
     * This function prints out a notice when you first activate the plugin.
     */
    function admin_notice__success() {
    ?>
    <div class="notice notice-error ">
        <p><?php _e( 'Go to' , 'orgocdn') ?> <a href="<?php _e(admin_url( 'admin.php?page=orgocdn-admin-panel' ), 'orgocdn'); ?>"> ORGOTECH </a><?php _e('on the left to get started with your image optimizing!', 'orgocdn' ); ?></p>
    </div>
    <?php
    }
}


 ?>
