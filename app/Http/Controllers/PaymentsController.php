<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payment\PaymentRequest;
use App\Models\Integration;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Invoice\GenerateInvoiceStatus;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use App\Services\Invoice\InvoiceCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Role;

class PaymentsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Payment $payment
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function destroy(Payment $payment)
    {
        if (!Auth::check()) {
            session()->flash('flash_message', __("Vous devez être connecté pour effectuer cette action"));
            return redirect()->route('login');
        }

        $user = Auth::user();
        if (!$user->hasRole(Role::OWNER_ROLE)) {
            session()->flash('flash_message', __("Vous n'avez pas les permissions nécessaires pour supprimer un paiement"));
            return redirect()->back();
        }

        if (!$user->can('payment-delete')) {
            session()->flash('flash_message', __("Vous n'avez pas la permission de supprimer un paiement"));
            return redirect()->back();
        }

        $api = Integration::initBillingIntegration();
        if ($api) {
            $api->deletePayment($payment);
        }

        $payment->delete();
        session()->flash('flash_message', __('Paiement supprimé avec succès'));
        return redirect()->back();
    }

    public function addPayment(PaymentRequest $request, Invoice $invoice)
    {
        if (!$invoice->isSent()) {
            session()->flash('flash_message_warning', __("Can't add payment on Invoice"));
            return redirect()->route('invoices.show', $invoice->external_id);
        }
        
        // Utiliser le calculateur pour obtenir le montant réel dû (après application de la remise globale)
        $invoiceCalculator = app(InvoiceCalculator::class, ['invoice' => $invoice]);
        $isExceeding = $invoiceCalculator->isPaymentExceedingAmount($request->amount);
        
        // Si le montant dépasse mais que l'utilisateur a confirmé, on procède
        if ($isExceeding && !$request->has('confirm_exceeding')) {
            // Stocker les données du formulaire en session pour les récupérer après confirmation
            session(['payment_data' => $request->all()]);
            
            // Afficher le montant réel dû, en tenant compte de la remise globale
            $amountDue = $invoiceCalculator->getAmountDue()->getAmount() / 100;
            
            return redirect()->route('invoices.show', $invoice->external_id)
                ->with('show_exceeding_modal', true)
                ->with('amount_due', $amountDue)
                ->with('payment_amount', $request->amount);
        }

        $payment = Payment::create([
            'external_id' => Uuid::uuid4()->toString(),
            'amount' => $request->amount * 100,
            'payment_date' => Carbon::parse($request->payment_date),
            'payment_source' => $request->source,
            'description' => $request->description,
            'invoice_id' => $invoice->id
        ]);
        
        $api = Integration::initBillingIntegration();
        if ($api && $invoice->integration_invoice_id) {
            $result = $api->createPayment($payment);
            $payment->integration_payment_id = $result["Guid"];
            $payment->integration_type = get_class($api);
            $payment->save();
        }
        app(GenerateInvoiceStatus::class, ['invoice' => $invoice])->createStatus();

        session()->flash('flash_message', __('Payment successfully added'));
        return redirect()->back();
    }
    
    public function confirmExceedingPayment(Request $request, Invoice $invoice)
    {
        // Récupérer les données du paiement stockées en session
        $paymentData = session('payment_data');
        if (!$paymentData) {
            return redirect()->route('invoices.show', $invoice->external_id);
        }
        
        // Ajouter directement les données de paiement dans la base de données
        $payment = Payment::create([
            'external_id' => Uuid::uuid4()->toString(),
            'amount' => $paymentData['amount'] * 100,
            'payment_date' => Carbon::parse($paymentData['payment_date']),
            'payment_source' => $paymentData['source'],
            'description' => $paymentData['description'] ?? '',
            'invoice_id' => $invoice->id
        ]);
        
        $api = Integration::initBillingIntegration();
        if ($api && $invoice->integration_invoice_id) {
            $result = $api->createPayment($payment);
            $payment->integration_payment_id = $result["Guid"];
            $payment->integration_type = get_class($api);
            $payment->save();
        }
        app(GenerateInvoiceStatus::class, ['invoice' => $invoice])->createStatus();
        
        // Nettoyer la session
        session()->forget('payment_data');
        
        session()->flash('flash_message', __('Payment successfully added'));
        return redirect()->route('invoices.show', $invoice->external_id);
    }
}
