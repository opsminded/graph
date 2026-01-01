<?php

declare(strict_types=1);

namespace Opsminded\Graph\Model;

final class Status
{
    private string $id;
    private string $status;

    public function __construct(string $id, string $status)
    {
        $this->id     = $id;
        $this->status = $status;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}