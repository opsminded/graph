<?php

declare(strict_types=1);

interface HTTPResponseInterface
{
    public function send(): void;
}
