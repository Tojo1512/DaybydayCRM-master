<?php

namespace App\Api\v1\Services\Dashboard;

use App\Models\Task;
use Carbon\Carbon;

class TaskService
{
    /**
     * Récupère le nombre total de tâches
     */
    public function getTotalCount(): int
    {
        return Task::count();
    }

    /**
     * Récupère le nombre de tâches créées dans la période spécifiée
     */
    public function getRecentCount(int $days = 30): int
    {
        $startDate = Carbon::now()->subDays($days);
        
        return Task::where('created_at', '>=', $startDate)->count();
    }
    
    /**
     * Récupère le nombre de tâches en cours
     */
    public function getActiveCount(): int
    {
        return Task::whereNull('status_id')
            ->orWhere('status_id', '<', 3) // Supposons que les statuts 1 et 2 sont actifs
            ->count();
    }
    
    /**
     * Récupère le nombre de tâches terminées
     */
    public function getCompletedCount(): int
    {
        return Task::where('status_id', '>=', 3)->count(); // Supposons que les statuts >= 3 sont terminés
    }

    /**
     * Récupère les statistiques complètes des tâches
     */
    public function getStats(): array
    {
        return [
            'total' => $this->getTotalCount(),
            'recent' => $this->getRecentCount(),
            'recent_7_days' => $this->getRecentCount(7),
            'active' => $this->getActiveCount(),
            'completed' => $this->getCompletedCount(),
        ];
    }
} 