<?php

declare(strict_types=1);

final class Edge
{
    private string $label;
    private string $source;
    private string $target;
    private array  $data;

    public const EDGE_KEYNAME_ID     = "id";
    public const EDGE_KEYNAME_LABEL  = "label";
    public const EDGE_KEYNAME_SOURCE = "source";
    public const EDGE_KEYNAME_TARGET = "target";
    public const EDGE_KEYNAME_DATA   = "data";

    public function __construct(string $source, string $target, string $label, array $data = [])
    {
        $this->source = $source;
        $this->target = $target;
        $this->label  = $label;
        $this->data   = $data;
    }

    public function getId(): string
    {
        return "{$this->source}-{$this->target}";
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function toArray(): array
    {
        return [
            self::EDGE_KEYNAME_ID     => $this->getId(),
            self::EDGE_KEYNAME_LABEL  => $this->label,
            self::EDGE_KEYNAME_SOURCE => $this->source,
            self::EDGE_KEYNAME_TARGET => $this->target,
            self::EDGE_KEYNAME_DATA   => $this->data
        ];
    }
}