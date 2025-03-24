<?php

namespace App\Api\v1\Services\Dashboard;

use App\Models\Payment;
use Carbon\Carbon;

class PaymentService
{
    /**
     * Récupère le nombre total de paiements
     */
    public function getTotalCount(): int
    {
        return Payment::count();
    }

    /**
     * Récupère le nombre de paiements créés dans la période spécifiée
     */
    public function getRecentCount(int $days = 30): int
    {
        $startDate = Carbon::now()->subDays($days);
        
        return Payment::where('created_at', '>=', $startDate)->count();
    }
    
    /**
     * Récupère la valeur totale des paiements
     */
    public function getTotalValue(): float
    {
        return Payment::sum('amount') / 100;
    }
    
    /**
     * Récupère la valeur des paiements récents
     */
    public function getRecentValue(int $days = 30): float
    {
        $startDate = Carbon::now()->subDays($days);
        
        return Payment::where('created_at', '>=', $startDate)
            ->sum('amount');
    }

    /**
     * Récupère les statistiques complètes des paiements
     */
    public function getStats(): array
    {
        return [
            'total' => $this->getTotalCount(),
            'recent' => $this->getRecentCount(),
            'recent_7_days' => $this->getRecentCount(7),
            'total_value' => $this->getTotalValue(),
            'recent_value' => $this->getRecentValue(),
            'recent_7_days_value' => $this->getRecentValue(7),
        ];
    }
} 