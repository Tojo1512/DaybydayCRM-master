<?php

namespace App\Models;

use App\Enums\OfferStatus;
use App\Repositories\Money\Money;
use App\Services\Invoice\InvoiceCalculator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Offer extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'sent_at',
        'status',
        'due_at',
        'client_id',
        'source_id',
        'source_type',
        'status',
        'external_id'
    ];

    public function getRouteKeyName()
    {
        return 'external_id';
    }

    public function invoiceLines()
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }
    
    public function getInvoice()
    {
        return $this;
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function setAsWon()
    {
        $this->status = OfferStatus::won()->getStatus();
        $this->save();
    }

    public function setAsLost()
    {
        $this->status = OfferStatus::lost()->getStatus();
        $this->save();
    }
    
    public function getTotalPrice()
    {
        $calculator = app(InvoiceCalculator::class, ['invoice' => $this]);
        return $calculator->getTotalPrice();
    }

    /**
     * Récupère la valeur totale de l'offre courante sans aucune modification
     */
    public function getTotalValue(): float
    {
        $total = 0;
        
        foreach ($this->invoiceLines as $line) {
            $total += $line->quantity * $line->price;
        }
        
        return $total / 100;
    }
}
