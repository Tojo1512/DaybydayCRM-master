<?php

namespace App\Api\v1\Services;

use App\Models\Payment;
use App\Models\Invoice;
use App\Services\Invoice\GenerateInvoiceStatus;
use App\Services\Invoice\InvoiceCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class PaymentService
{
    /**
     * Modifie un paiement existant
     *
     * @param Payment $payment
     * @param array $data
     * @return array
     */
    public function updatePayment(Payment $payment, array $data): array
    {
        try {
            DB::beginTransaction();
            
            // Récupérer la facture associée au paiement
            $invoice = $payment->invoice;
            $invoiceId = $invoice->id; // Sauvegarder l'ID pour rechargement ultérieur
            
            // Sauvegarder l'ancien montant pour comparaison
            $oldAmount = $payment->amount;
            
            // Mise à jour des champs du paiement
            if (isset($data['amount'])) {
                $payment->amount = $data['amount'] * 100; // Conversion en centimes
            }
            
            if (isset($data['payment_date'])) {
                $payment->payment_date = Carbon::parse($data['payment_date']);
            }
            
            if (isset($data['payment_source'])) {
                $payment->payment_source = $data['payment_source'];
            }
            
            if (isset($data['description'])) {
                $payment->description = $data['description'];
            }
            
            // Sauvegarder les modifications
            $payment->save();
            
            // Mettre à jour le statut de la facture avec un rechargement
            // Recharger la facture pour obtenir les données les plus récentes
            $freshInvoice = Invoice::with('payments')->find($invoiceId);
            app(GenerateInvoiceStatus::class, ['invoice' => $freshInvoice])->createStatus();
            
            DB::commit();
            
            // Recharger la facture pour le statut mis à jour
            $freshInvoice = Invoice::find($invoiceId);
            
            return [
                'success' => true,
                'message' => 'Paiement modifié avec succès',
                'data' => [
                    'payment' => $payment,
                    'invoice_status' => $freshInvoice->status
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'message' => 'Erreur lors de la modification du paiement: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Supprime un paiement
     *
     * @param Payment $payment
     * @return array
     */
    public function deletePayment(Payment $payment): array
    {
        try {
            DB::beginTransaction();
            
            // Récupérer la facture associée au paiement
            $invoice = $payment->invoice;
            $invoiceId = $invoice->id; // Sauvegarder l'ID pour rechargement ultérieur
            
            // Supprimer le paiement
            $payment->delete();
            
            // Mettre à jour le statut de la facture avec un rechargement
            $freshInvoice = Invoice::with('payments')->find($invoiceId);
            app(GenerateInvoiceStatus::class, ['invoice' => $freshInvoice])->createStatus();
            
            DB::commit();
            
            // Recharger la facture pour le statut mis à jour
            $freshInvoice = Invoice::find($invoiceId);
            
            return [
                'success' => true,
                'message' => 'Paiement supprimé avec succès',
                'data' => [
                    'invoice_status' => $freshInvoice->status
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'message' => 'Erreur lors de la suppression du paiement: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Vérifie si un paiement dépasse le montant dû
     *
     * @param Invoice $invoice
     * @param float $amount
     * @return bool
     */
    public function isExceedingAmount(Invoice $invoice, float $amount): bool
    {
        return app(InvoiceCalculator::class, ['invoice' => $invoice])->isPaymentExceedingAmount($amount);
    }
    
    /**
     * Récupère le montant dû pour une facture
     *
     * @param Invoice $invoice
     * @return float
     */
    public function getAmountDue(Invoice $invoice): float
    {
        return app(InvoiceCalculator::class, ['invoice' => $invoice])->getAmountDue()->getAmount() / 100;
    }
} 