<?php
namespace App\Models;

use App\Repositories\Money\Money;
use App\Repositories\Money\MoneyConverter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\Invoice\InvoiceCalculator;

class InvoiceLine extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'external_id',
        'type',
        'quantity',
        'title',
        'comment',
        'price',
        'invoice_id',
        'product_id',
        'offer_id',
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'external_id';
    }

    public function tasks()
    {
        return $this->belongsTo(Task::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function task()
    {
        return $this->invoice->task;
    }

    public function getTotalValueAttribute()
    {
        // Calculer le montant de base
        $baseTotal = $this->quantity * $this->price;
        
        // Si cette ligne fait partie d'une facture avec une remise globale active
        if ($this->invoice) {
            $calculator = app(InvoiceCalculator::class, ['invoice' => $this->invoice]);
            $discountRate = $calculator->getGlobalDiscountRate();
            
            if ($discountRate > 0) {
                // Appliquer la remise globale à cette ligne
                return $baseTotal * (1 - $discountRate);
            }
        }
        
        return $baseTotal;
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    public function getTotalValueConvertedAttribute()
    {
        // Utiliser directement l'attribut total_value qui contient déjà la logique de remise
        $money = new Money($this->getTotalValueAttribute());
        return app(MoneyConverter::class, ['money' => $money])->format();
    }
    
    public function getPriceConvertedAttribute()
    {
        $money = new Money($this->price);
        return app(MoneyConverter::class, ['money' => $money])->format();
    }
}
