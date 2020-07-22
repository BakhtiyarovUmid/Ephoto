<?php


set_time_limit(0);
ini_set('max_execution_time', 99999);

/**
 * Class EPhoto360
 * @author @LordDeveloper | @JupiterAPI
 */
class EPhoto360
{
    /**
     * @var null
     */
    private $htmlContent = null;
    /**
     * @var null
     */
    private static $image_url = null;


    /**
     * @param $action
     * @param array $args
     * @return bool|string
     */
    public function execute($action, $args = [])
    {
        $url = $action;
        if (!filter_var($action, FILTER_VALIDATE_URL))
            $url = 'https://en.ephoto360.com/' . $action;
        $ch = curl_init($url);
        $isEmpty = empty($args);
        curl_setopt_array($ch,
            [
                CURLOPT_POST            => !$isEmpty,
                CURLOPT_POSTFIELDS      => $isEmpty ? false : $args,
                CURLOPT_COOKIEFILE      => 'cookies.txt',
                CURLOPT_COOKIEJAR       => 'cookies.txt',
                CURLOPT_RETURNTRANSFER  => true
            ]);
        $response = curl_exec($ch);
        $this->htmlContent = $response;
        curl_close($ch);
        return $response;
    }


    /**
     * @param $imageFile
     * @param $build_server
     * @param $build_server_id
     * @return bool|false|string
     */
    public function uploadImage($imageFile, $build_server, $build_server_id)
    {
        if (!file_exists($imageFile)) return false;
        $upload = @json_decode(
            $this->execute(
                $build_server . '/upload',
                [
                    'file' => new CURLFile($imageFile)
                ]
            ), true
        );
        if (empty($upload)) return false;
        list($width, $height) = getimagesize($imageFile);
        $image = [
            'image'         => $upload['uploaded_file'],
            'image_thumb'   => $upload['thumb_file'],
            'icon_file'     => $upload['icon_file'],
            'x'             => 0,
            'y'             => 0,
            'width'         => $width,
            'height'        => $height,
            'rotate'        => 0,
            'scaleX'        => 1,
            'scaleY'        => 1,
            'thumb_width'   => $width,
        ];
        return json_encode(
            $image
        );

    }

    /**
     * @param $imageFile
     * @param $effect
     * @return bool|string
     */
    public function addEffect($imageFile, $effect)
    {
        $effect = $this->effect($effect);
        $action = $effect . '.html';
        $this->execute($action);
        $form = $this->formData();
        $build_server = $form['build_server'];
        $build_server_id = $form['build_server_id'];
        $image = $this->uploadImage(
            $imageFile,
            $build_server,
            $build_server_id
        );
        if (!$image) return false;
        $this->execute(
            $action,
            array_merge(
                $form,
                [
                    'file_image_input'  => '',
                    'image[]'           => $image,
                    'submit'            => 'GO',
                    'build_server'      => $build_server,
                    'build_server_id'   => $build_server_id
                ]
            )
        );
        $xpath = $this->xpath();
        $form_value = json_decode($xpath->query('//div[@id="form_value"]')[0]->nodeValue, true);
        $output = $this->execute('effect/create-image', http_build_query($form_value));
        $response = json_decode($output, true);
        if (isset($response['image']))
            return self::$image_url = $form['build_server'] . $response['image'];
        return false;
    }

    /**
     * @param $text
     * @param $effect
     * @param null $image
     * @return bool|string
     */
    public function writeText($text, $effect, $image = null)
    {
        $effect = $this->effect($effect);
        $action = $effect . '.html';
        $this->execute($action);
        $form = $this->formData();
        $build_server = $form['build_server'];
        $build_server_id = $form['build_server_id'];
        $type = gettype($text);
        switch ($type) {
            case 'array':
                if (!is_null($image) && file_exists($image))
                    $image = $this->uploadImage(
                        $image,
                        $build_server,
                        $build_server_id
                    );
                $args = array_merge(
                    [
                        'file_image_input'  => '',
                        'image[]'           => $image,
                        'text[0]'           => $text[0],
                        'text[1]'           => $text[1],
                        'submit'            => 'GO'
                    ],
                    $form
                );
                break;
            case 'string':
                $args = array_merge(
                    [
                        'text[0]'   => $text,
                        'submit'    => 'GO'
                    ],
                    $form
                );
                break;

        }
        $this->execute($action, $args);
        $xpath = $this->xpath();
        $form_value = json_decode($xpath->query('//div[@id="form_value"]')[0]->nodeValue, true);
        $output = $this->execute('effect/create-image', http_build_query($form_value));
        $response = json_decode($output, true);
        if (isset($response['image']))
            return self::$image_url = $form['build_server'] . $response['image'];
        return false;
    }

    /**
     * @return array
     */
    private function formData()
    {
        $xpath = $this->xpath();
        $id = [1, 3][array_rand([1, 3])];
        return [
            'token'             => $xpath->query('//input[@name="token"]')[0]->attributes[2]->value,
            'build_server'      => 'https://s' . $id . '.ephoto360.com',
            'build_server_id'   => $id
        ];

    }

    /**
     * @return DOMXPath|bool
     */
    private function xpath()
    {
        $content = $this->htmlContent;
        if (is_null($content)) return false;
        $dom = new DOMDocument();
        @$dom->loadHTML($content);
        return new DOMXPath($dom);
    }

    /**
     * @return bool
     */
    public function displayImage()
    {
        $image_url = self::$image_url;
        if (!$image_url) return false;
        $image = imagecreatefromstring(file_get_contents($image_url));
        header('Content-Type: image/jpeg');
        imagejpeg($image);
        imagedestroy($image);

    }

    /**
     * @param $effect
     * @return string
     */
    private function effect($effect)
    {
        return rtrim(basename($effect), '.html');
    }
}