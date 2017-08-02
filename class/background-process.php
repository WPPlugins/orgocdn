<?php
class Orgocdn_Process extends WP_Background_Process
{
    protected $action = "orgocdn_optimize_process";
    protected $items = array();
    protected function task( $item )
    {
        global $wpdb;

        if( empty($this->get_batch()->key) )
        {
            return false;
        }

        $image_obj = new Orgocdn_Image($item);

        $image_obj->optimize();
        usleep(10000);
        return false;
    }

    protected function complete()
    {
        global $wpdb;
        $tablename = $wpdb->prefix . 'orgotech_optimizations';
        update_option('orgotech_background_running', 2);
        $wpdb->update($tablename, array('status' => 2), array('status' => '1'));
        parent::complete();
    }
}

function wpbp_http_request_args( $r, $url ) {
	$r['headers']['Authorization'] = 'Basic ' . base64_encode( USERNAME . ':' . PASSWORD );

	return $r;
}
add_filter( 'http_request_args', 'wpbp_http_request_args', 10, 2);
?>
