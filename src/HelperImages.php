<?php

declare(strict_types=1);

final class HelperImages
{
    private array $images;

    public function __construct(array $images)
    {
        $this->images = $images;
    }

    public function send(string $imageName): void
    {
        if (!isset($this->images[$imageName])) {
            http_response_code(404);
            exit;
        }

        $imageData = base64_decode($this->images[$imageName]["data"]);
        $imageETag = md5($imageData);

        header("Content-Type: image/png");
        header("Content-Length: " . strlen($imageData));

        header("Cache-Control: public, max-age=86400");
        header("Expires: " . gmdate("D, d M Y H:i:s", time() + 86400) . " GMT");
        header("ETag: \"" . $imageETag . "\"");

        echo $imageData;
        exit;
    }
}