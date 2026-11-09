<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'order_items')]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'order_id', nullable: false, onDelete: 'CASCADE')]
    private ?Order $order = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'product_id', nullable: true, onDelete: 'SET NULL')]
    private ?Product $product = null;

    #[ORM\Column(length: 60)]
    private ?string $productKey = null;

    #[ORM\Column(length: 120)]
    private ?string $productName = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $unitPrice = '0.00';

    #[ORM\Column]
    private int $quantity = 1;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $lineTotal = '0.00';

    public function getId(): ?int { return $this->id; }

    public function getOrder(): ?Order { return $this->order; }
    public function setOrder(?Order $order): static { $this->order = $order; return $this; }

    public function getProduct(): ?Product { return $this->product; }
    public function setProduct(?Product $product): static { $this->product = $product; return $this; }

    public function getProductKey(): ?string { return $this->productKey; }
    public function setProductKey(string $v): static { $this->productKey = $v; return $this; }

    public function getProductName(): ?string { return $this->productName; }
    public function setProductName(string $v): static { $this->productName = $v; return $this; }

    public function getUnitPrice(): string { return $this->unitPrice; }
    public function setUnitPrice(string $v): static { $this->unitPrice = $v; return $this; }

    public function getQuantity(): int { return $this->quantity; }
    public function setQuantity(int $v): static { $this->quantity = $v; return $this; }

    public function getLineTotal(): string { return $this->lineTotal; }
    public function setLineTotal(string $v): static { $this->lineTotal = $v; return $this; }
}