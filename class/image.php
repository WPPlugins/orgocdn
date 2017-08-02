<?php

class Orgocdn_Image
{
    /*
    These variables are used to store certain information within the object:
    $image_url - Stores the url of the image this Orgocdn_Image object is currently working on
    $image_path - Currently unused, but was supposed to store the path to the image this Orgocdn_Image object is currently working on
    $supported_formats - Is a list of all supported image formats. Formats that are not in this array will be ignored!
    */
    private $image_url;
    private $image_path;
    private $image_id;
    private $supported_formats = array('jpg', 'jpeg', 'png');

    /**
     * @param string $image_url
     * @return Orgocdn_Image
     * This is the constructor. It creates Orgocdn_Image objects.
     * The only thing this does (besides construct the object) is set the objects $image_url variable to the value given to the constructor
     */
    public function __construct($image)
    {
        require_once(ABSPATH . 'wp-admin/includes/file.php');

        $this->image_path = $image['path'];
        $this->image_url = $image['url'];
        $this->image_id = $image['id'];
    }

    /**
     * @return Array
     * This function retrieves the urls for all images (including images that have been scaled by WordPress. Example of this: image.jpg -> image-300x300.jpg).
     */
    public static function get_images()
    {
        global $wpdb;
        /*
        In order to obtain the image urls, we first need the posts they are associated with!
        To obtain the posts, we run a WP_Query with the following arguments:
        'post_mime_type' => 'image'
        'post_type' => 'attachment'
        'post_status' => 'inherit'
        'posts_per_page' => -1
        */
        $query_images = new WP_Query(array(
            'post_mime_type' => 'image',
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => - 1
        ));


        /*
        The images need to be collected somewhere so we can return them later.
        $images is for now just an empty array and is ready to be filled with urls.

        We'll also need all the possible sizes the images can be scaled to.
        This class contains a function called 'get_sizes()', this function compiles a list of the available sizes' names.
        Additionally, the size 'full' (original size) needs to be appended to the array of sizes in order for the non-scaled version to be obtained as well.
        */
        $images = array();
        $sizes = self::get_sizes();
        array_push($sizes, 'full');

        /*
        We loop through each post obtained by the query from before, and get the image source via 'wp_get_attachment_image_src()'.
        That function returns an array where the first element (index 0) is the url we're looking for.
        */
        foreach($query_images->posts as $post)
        {
            foreach($sizes as $size)
            {
                $src = wp_get_attachment_image_src($post->ID, $size)[0];

                $tablename = $wpdb->prefix . 'orgotech_optimizations';
                $query = $wpdb->query($wpdb->prepare(
                    "
                    SELECT id FROM $tablename WHERE attachment_id = %d
                    ",
                    $post->ID
                ));
                if($query)
                {
                    continue;
                }
                $images[$src] = array('path' => str_replace(get_home_url(), untrailingslashit(get_home_path()), wp_get_attachment_image_src($post->ID, $size)[0]), 'url' => wp_get_attachment_image_src($post->ID, $size)[0], 'id' => $post->ID);
            }
        }
        return $images;
    }

    public static function add_image($image_id, $path, $url)
    {
        global $wpdb;
        $tablename = $wpdb->prefix . 'orgotech_optimizations';
        $wpdb->insert($tablename, array('attachment_id' => $image_id, 'path' => $path, 'url' => $url, 'timestamp' => time(), 'status' => 0));
    }

    public function image_exists()
    {

    }

    /**
     * @return Array
     * This function gets all the available scaling sizes and returns them in an array
     */
    public static function get_sizes()
    {
        global $_wp_additional_image_sizes;
        $sizes = get_intermediate_image_sizes();

        foreach($_wp_additional_image_sizes as $size => $value)
        {
            array_push($sizes, $size);
        }

        return $sizes;
    }
    /**
     * @return void
     * This function optimizes the image which url is stored within the $this->image_url variable
     */
    public function optimize()
    {
        global $wpdb;

        $format_string  = $this->get_format_string();
        $url            = $this->image_url;

        // Take the original url and change it from 'image.jpg' (this is just an example) to 'image.orgocdn-gnsKcG9ITlqCvYO0KMwW.jpg'.

        // If the input image's format is not one of the supported formats, abort!
        if(!preg_match("/^(.*)\.($format_string)$/", $url))
        {
            return false;
        }

        // Transform the url into an absolute path
        $path = $this->image_path;

        // Get the path for the copy of the original image we're going to make
        // $copy_path = preg_replace("/^(.*)\.($format_string)$/", "$1.orgocdn-gnsKcG9ITlqCvYO0KMwW.$2", $path);
        $copy_path = str_replace(wp_upload_dir()['basedir'], ORGOCDN__BACKUP_DIR, $path);
        $copy_dir = dirname($copy_path);
        $get_url = preg_replace("/(http:\/\/)|(https:\/\/)/", "http://orgocdn.com/", $url);
        // If the copy of the original does not exist, make a copy of the original image and name it imagename.orgocdn-gnsKcG9ITlqCvYO0KMwW.jpg (from imagename.jpg)
        $tablename = $wpdb->prefix . 'orgotech_optimizations';
        $wpdb->update($tablename, array('status' => 1), array('url' => $url));

        if(!is_dir($copy_dir)){
            mkdir($copy_dir, 0777, true);
        }

        if(copy($path, $copy_path))
        {
            copy($get_url, $path);
        }

        // file_put_contents($copy_path, file_get_contents($path));
        // $context = stream_context_create(array('http' => array('header'=>'Connection: close\r\n')));
        // $file = file_get_contents($get_url,false,$context);
        // file_put_contents($path, $file);

        return true;

    }

    /**
     * @return string
     * This function returns a string with the formats. Example: array(png, jpeg, jpg) -> 'png|jpeg|jpg'.
     */
    public function get_format_string()
    {
        return implode('|', $this->supported_formats);
    }

    /**
     * @return void
     * This function deletes the optimized version of the image, and restores everything to normal (hopefully).
     */
    public function delete_optimized()
    {
        $format_string  = $this->get_format_string();
        $url            = $this->image_url;

        if(!preg_match("/^(.*)\.($format_string)$/", $url))
        {
            return false;
        }

        $path = str_replace(get_home_url(), untrailingslashit(get_home_path()), $url);
        $copy_path = str_replace(wp_upload_dir()['basedir'], ORGOCDN__BACKUP_DIR, $path);
        error_log($copy_path);
        if(@filesize($copy_path))
        {
            file_put_contents($path, file_get_contents($copy_path));
            unlink($copy_path);
        }
    }
}
 ?>
