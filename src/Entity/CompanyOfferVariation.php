<?php



namespace App\Entity;

use App\Repository\CompanyOfferVariationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompanyOfferVariationRepository::class)]
#[ORM\Table(name: 'company_offer_variations')]
#[ORM\UniqueConstraint(name: 'uq_company_offer', columns: ['company_id', 'offer_id'])]
class CompanyOfferVariation
{
    public const TYPE_PERCENT = 'percent';
    public const TYPE_FIXED   = 'fixed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(name: 'company_id', nullable: false, onDelete: 'CASCADE')]
    private Company $company;

    #[ORM\ManyToOne(targetEntity: Offer::class)]
    #[ORM\JoinColumn(name: 'offer_id', nullable: false, onDelete: 'CASCADE')]
    private Offer $offer;

    #[ORM\Column( name: 'variation_type', length: 10)]
    private string $variationType = self::TYPE_PERCENT;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $value = '0.00';

    #[ORM\Column(name: 'is_active', type: Types::BOOLEAN)]
    private bool $isActive = true;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    private \DateTime $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getCompany(): Company { return $this->company; }
    public function setCompany(Company $company): self { $this->company = $company; return $this; }

    public function getOffer(): Offer { return $this->offer; }
    public function setOffer(Offer $offer): self { $this->offer = $offer; return $this; }

    public function getVariationType(): string { return $this->variationType; }
    public function setVariationType(string $variationType): self
    {
        if (!in_array($variationType, [self::TYPE_PERCENT, self::TYPE_FIXED], true)) {
            throw new \InvalidArgumentException("Type invalide : $variationType");
        }
        $this->variationType = $variationType;
        return $this;
    }

    public function getValue(): string { return $this->value; }
    public function setValue(string $value): self { $this->value = $value; return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; return $this; }

    public function getCreatedAt(): \DateTime { return $this->createdAt; }
    public function setCreatedAt(\DateTime $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): \DateTime { return $this->updatedAt; }
    public function setUpdatedAt(\DateTime $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }

    public function touch(): self { $this->updatedAt = new \DateTime(); return $this; }

    public function applyTo(float $basePrice): float
    {
        $v = (float) $this->value;
        if ($this->variationType === self::TYPE_PERCENT) {
            return max(0.0, $basePrice * (1 + $v / 100));
        }
        return max(0.0, $basePrice + $v);
    }

    public function __toString(): string
    {
        $sign  = (float) $this->value >= 0 ? '+' : '';
        $label = $this->variationType === self::TYPE_PERCENT
            ? "{$sign}{$this->value}%"
            : "{$sign}{$this->value} MAD";
        return "{$this->company} / {$this->offer} : {$label}";
    }
}
