<?php

declare(strict_types=1);

final class HelperImages
{
    private array $images = [];

    public function __construct(array $images)
    {
        foreach($images as $name => $image) {
            $this->images[$name] = new ModelImage(
                $name,
                base64_decode($image["data"], true),
                md5($image["data"])
            );
        }
    }

    public function getTypes(): array
    {
        return array_keys($this->images);
    }

    private function getImages(): array
    {
        return $this->images;
    }

    // public function send(string $imageName): void
    // {
    //     if (!isset($this->images[$imageName])) {
    //         http_response_code(404);
    //         exit;
    //     }

    //     $imageData = $this->images[$imageName]->getData();
    //     $imageETag = $this->images[$imageName]->getEtag();

    //     header("Content-Type: image/png");
    //     header("Content-Length: " . strlen($imageData));

    //     header("Cache-Control: public, max-age=86400");
    //     header("Expires: " . gmdate("D, d M Y H:i:s", time() + 86400) . " GMT");
    //     header("ETag: \"" . $imageETag . "\"");

    //     echo $imageData;
    //     exit;
    // }
}