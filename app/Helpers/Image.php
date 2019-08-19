<?php

namespace App\Helpers;


class Image
{
    public function __construct() {
        \Cloudinary::config(array(
            'cloud_name' => 'dsxar8lse',
            'api_key' => '668927687522514',
            'api_secret' => env('API_IMAGE_KEY'),
        ));
    }

    /**
     * Upload image to host
     * @param string $imgUrl
     * @param array  $options
     * @return mixed
     */
    public function uploadImage(string $imgUrl, array $options) {
        return \Cloudinary\Uploader::upload($imgUrl, $options);
    }
}