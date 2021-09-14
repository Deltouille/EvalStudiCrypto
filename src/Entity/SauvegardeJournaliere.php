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
     * @ORM\Column(type="date")
     */
    private $date;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $valorisationTotale;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getValorisationTotale(): ?string
    {
        return $this->valorisationTotale;
    }

    public function setValorisationTotale(string $valorisationTotale): self
    {
        $this->valorisationTotale = $valorisationTotale;

        return $this;
    }
}
