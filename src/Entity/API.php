<?php

namespace App\Entity;

use App\Repository\APIRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=APIRepository::class)
 */
class API
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $api;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApi(): ?string
    {
        return $this->api;
    }

    public function setApi(string $api): self
    {
        $this->api = $api;

        return $this;
    }
}
