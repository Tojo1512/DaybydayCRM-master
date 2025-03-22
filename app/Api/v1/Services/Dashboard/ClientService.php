<?php

namespace App\Api\v1\Services\Dashboard;

use App\Models\Client;
use Carbon\Carbon;

class ClientService
{
    /**
     * Récupère le nombre total de clients
     */
    public function getTotalCount(): int
    {
        return Client::count();
    }

    /**
     * Récupère le nombre de clients créés dans la période spécifiée
     */
    public function getRecentCount(int $days = 30): int
    {
        $startDate = Carbon::now()->subDays($days);
        
        return Client::where('created_at', '>=', $startDate)->count();
    }

    /**
     * Récupère les statistiques complètes des clients
     */
    public function getStats(): array
    {
        return [
            'total' => $this->getTotalCount(),
            'recent' => $this->getRecentCount(),
            'recent_7_days' => $this->getRecentCount(7),
            // La colonne 'inactive' n'existe pas dans la base de données
            // 'inactive' => Client::where('inactive', 1)->count(),
        ];
    }
} 