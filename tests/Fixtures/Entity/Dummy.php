<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\UuidV4 as Uuid;

#[ORM\Entity]
class Dummy
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $uuid;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $name = null;

    #[ORM\OneToMany(mappedBy: 'dummy', targetEntity: DummyAssociation::class)]
    private Collection $dummyAssociations;

    public function __construct(?Uuid $uuid = null)
    {
        $this->uuid = $uuid ?? Uuid::v4();
        $this->dummyAssociations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    /**
     * @return Collection<int, DummyAssociation>
     */
    public function getDummyAssociations(): Collection
    {
        return $this->dummyAssociations;
    }

    public function addDummyAssociation(DummyAssociation $dummyAssociation): self
    {
        if (!$this->dummyAssociations->contains($dummyAssociation)) {
            $this->dummyAssociations[] = $dummyAssociation;

            $dummyAssociation->setDummy($this);
        }

        return $this;
    }

    public function removeDummyAssociation(DummyAssociation $dummyAssociation): self
    {
        if ($this->dummyAssociations->remove($dummyAssociation)) {
            if ($dummyAssociation->getDummy() === $this) {
                $dummyAssociation->setDummy(null);
            }
        }

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }
}
