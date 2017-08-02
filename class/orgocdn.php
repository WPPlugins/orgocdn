<?php
class Orgocdn
{
    /**
    * @return void
    * This function displays a notice ONCE after activating the plugin to guide you to the admin panel
    */
    private $option_slug = "orgotech_options";
    public static $process;

    public function __construct()
    {
        // header("Content-type: text/plain");

        $this->register_hooks();
        $this->set_notice();
    }

    public function init()
    {
        $orgo = new Orgocdn();
        $admin = new Orgocdn_Admin();
    }

    public function unregister_settings()
    {
        unregister_setting("orgocdn_options_group", 'orgotech_options', '%s');
    }

    public static function set_notice()
    {
        $options = get_option('orgotech_options');

        if(empty($options))
        {
            $options = array('notice' => true, 'user_key' => '', 'domain_key' => '');
        }
        else
        {
            $options['notice'] = true;
        }

        update_option('orgotech_options', $options);
    }

    public function register_hooks()
    {

    }

    public static function activate()
    {
        self::generate_table();
        self::set_notice();
    }

    public static function generate_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "orgotech_optimizations";
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE `$table_name` (
            `id` int(10) NOT NULL UNIQUE AUTO_INCREMENT PRIMARY KEY,
            `attachment_id` int(10) NOT NULL,
            `path` varchar(255) NOT NULL,
            `url` varchar(255) NOT NULL,
            `timestamp` int(15) NOT NULL,
            `status` tinyint(1) NOT NULL
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    public static function deactivate()
    {
        global $wpdb;

        $tablename = $wpdb->prefix . 'orgotech_optimizations';
        $sql = "DROP TABLE IF EXISTS " . $tablename;

        delete_option('orgotech_options');
        // $images = Orgocdn_Image::get_images();
        //
        // foreach($images as $image)
        // {
        //     $image_obj = new Orgocdn_Image($image);
        //     $image_obj->delete_optimized();
        // }

        $wpdb->query($sql);
    }

    public static function uninstall()
    {

    }
}
 ?>
