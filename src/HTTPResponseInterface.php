<?php

declare(strict_types=1);

interface HTTPResponseInterface
{
    public const KEYNAME_CODE    = "code";
    public const KEYNAME_STATUS  = "status";
    public const KEYNAME_HEADERS = "headers";
    public const KEYNAME_MESSAGE = "message";
    public const KEYNAME_DATA    = "data";

    public const VALUE_STATUS_SUCCESS = "success";
    public const VALUE_STATUS_ERROR   = "error";

    public const JSON_RESPONSE_CONTENT_TYPE = "Content-Type: application/json; charset=utf-8";
}
