<?php

declare(strict_types=1);

final class HelperImages
{
    public static function getTypes(): array
    {
        global $DATA_IMAGES;
        return array_keys($DATA_IMAGES);
    }

    public static function getImageData(string $image): string
    {
        global $DATA_IMAGES;
        return $DATA_IMAGES[$image]['data'];
    }

    public static function getImageEtag(string $image): string
    {
        global $DATA_IMAGES;
        return $DATA_IMAGES[$image]['etag'];
    }
}