<?php

namespace AppBundle\Request\Datatables;

class Search implements \JsonSerializable
{
    /** @var string */
    private $value;
    /** @var bool */
    private $regex;

    /**
     * @param string $value
     * @param bool $regex
     */
    public function __construct(string $value, bool $regex)
    {
        $this->value = $value;
        $this->regex = $regex;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return bool
     */
    public function isRegex(): bool
    {
        return $this->regex;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return !empty($this->value);
    }

    public function jsonSerialize()
    {
        return [
            'value' => $this->value,
            'regex' => $this->regex
        ];
    }
}
