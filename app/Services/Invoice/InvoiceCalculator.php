<?php
namespace App\Services\Invoice;

use App\Models\Offer;
use App\Models\Invoice;
use App\Models\DiscountSetting;
use App\Repositories\Tax\Tax;
use App\Repositories\Money\Money;

class InvoiceCalculator
{
    /**
     * @var Invoice
     */
    private $invoice;
    /**
     * @var Tax
     */
    private $tax;
    
    /**
     * @var DiscountSetting|null
     */
    private $discountSetting;

    public function __construct($invoice)
    {
        if(!$invoice instanceof Invoice && !$invoice instanceof Offer ) {
            throw new \Exception("Not correct type for Invoice Calculator");
        }
        $this->tax = new Tax();
        $this->invoice = $invoice;
        $this->discountSetting = DiscountSetting::getActive();
    }

    public function isPaymentExceedingAmount(float $paymentAmount): bool
    {
        $amountDue = $this->getAmountDue()->getAmount() / 100; // Conversion en format décimal
        return $paymentAmount > $amountDue;
    }

    public function getVatTotal()
    {
        $price = $this->getSubTotal()->getAmount();
        return new Money($price * $this->tax->vatRate());
    }

    /**
     * Récupère le taux de remise global actif
     * 
     * @return float
     */
    public function getGlobalDiscountRate()
    {
        if (!$this->discountSetting) {
            return 0;
        }
        
        return $this->discountSetting->global_discount_rate / 100; // Conversion de pourcentage en décimal
    }
    
    /**
     * Calcule le montant de la remise globale
     * 
     * @return Money
     */
    public function getGlobalDiscountAmount(): Money
    {
        $subTotal = $this->getSubTotalBeforeDiscount()->getAmount();
        $discountRate = $this->getGlobalDiscountRate();
        
        return new Money($subTotal * $discountRate);
    }
    
    /**
     * Calcule le sous-total avant remise
     * 
     * @return Money
     */
    public function getSubTotalBeforeDiscount(): Money
    {
        $price = 0;
        $invoiceLines = $this->invoice->fresh()->invoiceLines;

        foreach ($invoiceLines as $invoiceLine) {
            $price += $invoiceLine->quantity * $invoiceLine->price;
        }
        return new Money($price / $this->tax->multipleVatRate());
    }

    public function getTotalPrice(): Money
    {
        $price = 0;
        $invoiceLines = $this->invoice->fresh()->invoiceLines;

        foreach ($invoiceLines as $invoiceLine) {
            $price += $invoiceLine->quantity * $invoiceLine->price;
        }
        
        // Appliquer la remise globale si elle existe
        if ($this->getGlobalDiscountRate() > 0) {
            $price -= $this->getGlobalDiscountAmount()->getAmount() * $this->tax->multipleVatRate();
        }

        return new Money($price);
    }

    public function getSubTotal(): Money
    {
        $subTotal = $this->getSubTotalBeforeDiscount()->getAmount();
        
        // Appliquer la remise globale si elle existe
        if ($this->getGlobalDiscountRate() > 0) {
            $subTotal -= $this->getGlobalDiscountAmount()->getAmount();
        }
        
        return new Money($subTotal);
    }

    public function getAmountDue()
    {
        $freshInvoice = $this->invoice->fresh();
        $paymentSum = $freshInvoice->payments()->sum('amount');
        
        return new Money($this->getTotalPrice()->getAmount() - $paymentSum);
    }

    public function getInvoice()
    {
        return $this->invoice;
    }

    public function getTax()
    {
        return $this->tax;
    }
    
    /**
     * Récupère les paramètres de remise globale
     * 
     * @return DiscountSetting|null
     */
    public function getDiscountSetting()
    {
        return $this->discountSetting;
    }
    
    /**
     * Récupère la valeur totale brute sans aucune remise
     * 
     * @return Money
     */
    public function getTotalValue(): Money
    {
        $total = 0;
        $invoiceLines = $this->invoice->fresh()->invoiceLines;
        
        foreach ($invoiceLines as $line) {
            $total += $line->quantity * $line->price;
        }
        
        return new Money($total);
    }
}
