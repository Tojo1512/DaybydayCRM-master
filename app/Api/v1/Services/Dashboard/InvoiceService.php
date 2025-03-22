<?php

namespace App\Api\v1\Services\Dashboard;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Services\Invoice\InvoiceCalculator;
use Carbon\Carbon;

class InvoiceService
{
    /**
     * Récupère le nombre total de factures
     */
    public function getTotalCount(): int
    {
        return Invoice::count();
    }

    /**
     * Récupère le nombre de factures créées dans la période spécifiée
     */
    public function getRecentCount(int $days = 30): int
    {
        $startDate = Carbon::now()->subDays($days);
        
        return Invoice::where('created_at', '>=', $startDate)->count();
    }
    
    /**
     * Récupère la valeur totale des factures
     */
    public function getTotalValue(): float
    {
        $total = 0;
        $invoices = Invoice::with('invoiceLines')->get();
        
        foreach ($invoices as $invoice) {
            foreach ($invoice->invoiceLines as $line) {
                $total += $line->quantity * $line->price;
            }
        }
        
        return $total;
    }
    
    /**
     * Récupère la valeur des factures impayées
     */
    public function getUnpaidValue(): float
    {
        $total = 0;
        $invoices = Invoice::with('invoiceLines')
            ->where('due_at', '<', Carbon::now())
            ->where('status', '!=', 'paid')
            ->get();
        
        foreach ($invoices as $invoice) {
            foreach ($invoice->invoiceLines as $line) {
                $total += $line->quantity * $line->price;
            }
        }
        
        return $total;
    }
    
    /**
     * Récupère le nombre de factures impayées
     */
    public function getUnpaidCount(): int
    {
        return Invoice::where('due_at', '<', Carbon::now())
            ->where('status', '!=', 'paid')
            ->count();
    }

    /**
     * Récupère les statistiques complètes des factures
     */
    public function getStats(): array
    {
        return [
            'total' => $this->getTotalCount(),
            'recent' => $this->getRecentCount(),
            'recent_7_days' => $this->getRecentCount(7),
            'total_value' => $this->getTotalValue(),
            'unpaid_value' => $this->getUnpaidValue(),
            'unpaid_count' => $this->getUnpaidCount(),
        ];
    }
} 