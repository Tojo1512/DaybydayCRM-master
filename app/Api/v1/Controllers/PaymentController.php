<?php

namespace App\Api\v1\Controllers;

use App\Api\v1\Services\PaymentService;
use App\Models\Payment;
use App\Services\Invoice\GenerateInvoiceStatus;
use Illuminate\Http\Request;

class PaymentController extends ApiController
{
    /**
     * @var PaymentService
     */
    protected $paymentService;

    /**
     * PaymentController constructor.
     *
     * @param PaymentService $paymentService
     */
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Modifier un paiement
     *
     * @param Request $request
     * @param string $externalId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $externalId)
    {
        // Validation des données
        $validatedData = $request->validate([
            'amount' => 'nullable|numeric|min:0',
            'payment_date' => 'nullable|date',
            'payment_source' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255',
        ]);
        
        // Recherche du paiement par ID externe
        $payment = Payment::where('external_id', $externalId)->first();
        
        // Vérifier si le paiement existe
        if (!$payment) {
            return $this->respondNotFound('Paiement non trouvé');
        }
        
        // Mettre à jour le paiement
        $result = $this->paymentService->updatePayment($payment, $validatedData);
        
        // Vérification supplémentaire pour s'assurer que le statut de la facture est à jour
        if ($result['success']) {
            // Forcer une mise à jour du statut de la facture
            $invoice = $payment->invoice->fresh();
            app(GenerateInvoiceStatus::class, ['invoice' => $invoice])->createStatus();
            
            // Mettre à jour le résultat avec le dernier statut
            $result['data']['invoice_status'] = $invoice->fresh()->status;
            
            return $this->respond($result);
        } else {
            return $this->respondWithError($result['message']);
        }
    }

    /**
     * Supprimer un paiement
     *
     * @param string $externalId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $externalId)
    {
        // Recherche du paiement par ID externe
        $payment = Payment::where('external_id', $externalId)->first();
        
        // Vérifier si le paiement existe
        if (!$payment) {
            return $this->respondNotFound('Paiement non trouvé');
        }
        
        // Sauvegarder l'ID de la facture avant la suppression
        $invoiceId = $payment->invoice_id;
        
        // Supprimer le paiement
        $result = $this->paymentService->deletePayment($payment);
        
        // Vérification supplémentaire pour s'assurer que le statut de la facture est à jour
        if ($result['success'] && $invoiceId) {
            // Récupérer la facture et forcer une mise à jour du statut
            $invoice = \App\Models\Invoice::find($invoiceId);
            if ($invoice) {
                app(GenerateInvoiceStatus::class, ['invoice' => $invoice])->createStatus();
                
                // Mettre à jour le résultat avec le dernier statut
                $result['data']['invoice_status'] = $invoice->fresh()->status;
            }
        }
        
        // Retourner la réponse
        if ($result['success']) {
            return $this->respond($result);
        } else {
            return $this->respondWithError($result['message']);
        }
    }
} 