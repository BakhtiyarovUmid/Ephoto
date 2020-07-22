<?php
/**
 * ephoto360.com wrapper
 * @author @LordDeveloper | @JupiterAPI
 */

error_reporting(0);
ini_set('display_errors', 0);
require_once __DIR__ . '/EPhoto360.php';

$ephoto = new EPhoto360;


extract($_REQUEST);
$action = $_GET['act'];
switch (strtolower($action)) {

    case 'writetext':
        if (isset($text) && isset($effect)) {
            $image = [];
            if (isset($image_url)) {
                if (!is_dir('tmp')) mkdir('tmp', 0740);
                $image = __DIR__ . '/tmp/' . uniqid() . '.jpg';
                copy($image_url, $image);
            }
            $url = $ephoto->writeText(
                $text,
                $effect,
                $image
            );
            if (!is_null($image) && file_exists($image))
                unlink($image);
        }

        break;
    case 'addeffect':
        if (isset($image_url) && isset($effect)) {
            if (!is_dir('tmp')) mkdir('tmp', 0740);
            $image = __DIR__ . '/tmp/' . uniqid() . '.jpg';
            copy($image_url, $image);

            $url = $ephoto->addEffect(
                $image,
                $effect
            );
            if (file_exists($image))
                unlink($image);
        }

}
switch (strtolower($output)) {
    case 'image':
        $ephoto->displayImage();
        break;
    case 'url':
        echo $url;
        break;
    default:
        if (is_null($url)) exit(
        json_encode(
            [
                'status'    => false,
                'message'   => 'request invalid'
            ]
        )
        );
        exit(
        json_encode(
            [
                'status'    => !empty($url),
                'image_url' => $url ?: 'Can not find image url'
            ]
        )
        );
}