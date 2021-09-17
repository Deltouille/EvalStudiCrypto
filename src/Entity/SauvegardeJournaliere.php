<?php

namespace App\Entity;

use App\Repository\SauvegardeJournaliereRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SauvegardeJournaliereRepository::class)
 */
class SauvegardeJournaliere
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length="255")
     */
    private $date;

    /**
     * @ORM\Column(type="integer")
     */
    private $valorisationTotale;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function setDate(string $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getValorisationTotale(): ?int
    {
        return $this->valorisationTotale;
    }

    public function setValorisationTotale(int $valorisationTotale): self
    {
        $this->valorisationTotale = $valorisationTotale;

        return $this;
    }
}
