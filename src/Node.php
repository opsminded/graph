<?php
namespace Opsminded\Graph;

class Node
{
    private string $id;
    private $payload;

    public function __construct(string $id, $payload = null)
    {
        $this->id = $id;
        $this->payload = $payload;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function setPayload($payload): void
    {
        $this->payload = $payload;
    }
}
