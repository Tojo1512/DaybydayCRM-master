<?php

namespace App\Api\v1\Services\Dashboard;

use App\Models\Offer;
use Carbon\Carbon;

class OfferService
{
    /**
     * Récupère le nombre total d'offres
     */
    public function getTotalCount(): int
    {
        return Offer::count();
    }

    /**
     * Récupère le nombre d'offres créées dans la période spécifiée
     */
    public function getRecentCount(int $days = 30): int
    {
        $startDate = Carbon::now()->subDays($days);
        
        return Offer::where('created_at', '>=', $startDate)->count();
    }
    
    /**
     * Récupère la valeur totale des offres
     */
    public function getTotalValue(): float
    {
        $total = 0;
        $offers = Offer::with('invoiceLines')->get();
        
        foreach ($offers as $offer) {
            foreach ($offer->invoiceLines as $line) {
                $total += $line->quantity * $line->price;
            }
        }
        
        return $total / 100;
    }
    
    /**
     * Récupère la valeur moyenne des offres
     */
    public function getAverageValue(): float
    {
        $totalCount = $this->getTotalCount();
        
        if ($totalCount === 0) {
            return 0;
        }
        
        return $this->getTotalValue() / $totalCount;
    }

    /**
     * Récupère les statistiques complètes des offres
     */
    public function getStats(): array
    {
        return [
            'total' => $this->getTotalCount(),
            'recent' => $this->getRecentCount(),
            'recent_7_days' => $this->getRecentCount(7),
            'total_value' => $this->getTotalValue(),
            'average_value' => $this->getAverageValue(),
        ];
    }
} 