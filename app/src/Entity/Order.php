<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'orders')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'user_id', nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(length: 120)]
    private ?string $customerName = null;

    #[ORM\Column(length: 190)]
    private ?string $email = null;

    #[ORM\Column(length: 40)]
    private ?string $phone = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $address = null;

    // 'delivery' o 'pickup'
    #[ORM\Column(length: 20)]
    private string $orderType = 'delivery';

    #[ORM\Column(length: 40)]
    private ?string $paymentMethod = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $subtotal = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $deliveryFee = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $total = '0.00';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 30)]
    private string $status = 'Pending';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    private ?string $riderLatitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    private ?string $riderLongitude = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $riderLastUpdate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getCustomerName(): ?string { return $this->customerName; }
    public function setCustomerName(string $v): static { $this->customerName = $v; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $v): static { $this->email = $v; return $this; }

    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(string $v): static { $this->phone = $v; return $this; }

    public function getAddress(): ?string { return $this->address; }
    public function setAddress(string $v): static { $this->address = $v; return $this; }

    public function getOrderType(): string { return $this->orderType; }
    public function setOrderType(string $v): static { $this->orderType = $v; return $this; }

    public function getPaymentMethod(): ?string { return $this->paymentMethod; }
    public function setPaymentMethod(string $v): static { $this->paymentMethod = $v; return $this; }

    public function getSubtotal(): string { return $this->subtotal; }
    public function setSubtotal(string $v): static { $this->subtotal = $v; return $this; }

    public function getDeliveryFee(): string { return $this->deliveryFee; }
    public function setDeliveryFee(string $v): static { $this->deliveryFee = $v; return $this; }

    public function getTotal(): string { return $this->total; }
    public function setTotal(string $v): static { $this->total = $v; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $v): static { $this->notes = $v; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $v): static { $this->status = $v; return $this; }

    public function getRiderLatitude(): ?string { return $this->riderLatitude; }
    public function setRiderLatitude(?string $v): static { $this->riderLatitude = $v; return $this; }

    public function getRiderLongitude(): ?string { return $this->riderLongitude; }
    public function setRiderLongitude(?string $v): static { $this->riderLongitude = $v; return $this; }

    public function getRiderLastUpdate(): ?\DateTimeInterface { return $this->riderLastUpdate; }
    public function setRiderLastUpdate(?\DateTimeInterface $v): static { $this->riderLastUpdate = $v; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    /** @return Collection<int, OrderItem> */
    public function getItems(): Collection { return $this->items; }

    public function addItem(OrderItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOrder($this);
        }
        return $this;
    }
}