<?php

declare(strict_types=1);

namespace ExampleApiClient\DTO;

use JMS\Serializer\Annotation as JMS;

class CommentDTO
{
    /**
     * @JMS\Type("integer")
     */
    private int $id;

    /**
     * @JMS\Type("string")
     */
    private string $name;

    /**
     * @JMS\Type("string")
     */
    private string $text;

    public function __construct(
        int $id,
        string $name,
        string $text
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->text = $text;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getText(): string
    {
        return $this->text;
    }
}
