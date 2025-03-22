<?php

namespace App\Api\v1\Services\Dashboard;

use App\Models\Project;
use Carbon\Carbon;

class ProjectService
{
    /**
     * Récupère le nombre total de projets
     */
    public function getTotalCount(): int
    {
        return Project::count();
    }

    /**
     * Récupère le nombre de projets créés dans la période spécifiée
     */
    public function getRecentCount(int $days = 30): int
    {
        $startDate = Carbon::now()->subDays($days);
        
        return Project::where('created_at', '>=', $startDate)->count();
    }
    
    /**
     * Récupère le nombre de projets en cours
     */
    public function getActiveCount(): int
    {
        return Project::where('status_id', '<', 3)->count(); // Supposons que les statuts 1 et 2 sont actifs
    }

    /**
     * Récupère les statistiques complètes des projets
     */
    public function getStats(): array
    {
        return [
            'total' => $this->getTotalCount(),
            'recent' => $this->getRecentCount(),
            'recent_7_days' => $this->getRecentCount(7),
            'active' => $this->getActiveCount(),
        ];
    }
} 