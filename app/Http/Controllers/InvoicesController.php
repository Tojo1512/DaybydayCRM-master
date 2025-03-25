<?php

namespace App\Http\Controllers;

use View;
use App\Billy;
use Datatables;
use Carbon\Carbon;
use App\Models\Lead;
use App\Models\Task;
use Ramsey\Uuid\Uuid;
use App\Http\Requests;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Integration;
use App\Models\InvoiceLine;
use App\Enums\InvoiceStatus;
use App\Enums\OfferStatus;
use App\Enums\PaymentSource;
use Illuminate\Http\Request;
use App\Repositories\Tax\Tax;
use App\Repositories\Money\Money;
use App\Repositories\Currency\Currency;
use App\Repositories\Money\MoneyConverter;
use App\Services\Invoice\InvoiceCalculator;
use App\Http\Requests\Invoice\AddInvoiceLine;
use App\Models\Offer;
use App\Models\Product;
use App\Services\InvoiceNumber\InvoiceNumberService;
use Illuminate\Support\Facades\Validator;
use App\Services\Invoice\GenerateInvoiceStatus;

class InvoicesController extends Controller
{
    protected $clients;
    protected $invoices;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('invoices.index');
    }

    /**
     * Display the specified resource.
     *
     * @param Invoice $invoice
     * @return \Illuminate\Http\Response
     */
    public function show(Invoice $invoice)
    {
        if (!auth()->user()->can('invoice-see')) {
            session()->flash('flash_message_warning', __('You do not have permission to view this invoice'));
            return redirect()->route('clients.index');
        }
        
        $apiConnected = false;
        $invoiceContacts = [];
        $primaryContact = null;

        $api = Integration::initBillingIntegration();

        if ($api) {
            $apiConnected = true;

            $invoiceContacts = $api->getContacts();
            if (empty($invoiceContacts)) {
                $apiConnected = false;
            } else {
                $primaryContact = $api->getPrimaryContact($invoice->client);
            }
        }

        $invoiceCalculator = new InvoiceCalculator($invoice);
        
        // Calculer les montants avec et sans remise
        $subTotalBeforeDiscount = $invoiceCalculator->getSubTotalBeforeDiscount();
        $globalDiscountRate = $invoiceCalculator->getGlobalDiscountRate() * 100; // Convertir en pourcentage
        $globalDiscountAmount = $invoiceCalculator->getGlobalDiscountAmount();
        $hasGlobalDiscount = $globalDiscountRate > 0;
        
        // Calculs pour l'affichage sans remise (par défaut)
        $subPrice = $subTotalBeforeDiscount;
        $vatRate = $invoiceCalculator->getTax()->vatRate();
        $vatPrice = new Money($subPrice->getAmount() * $vatRate);
        $totalPrice = new Money($subPrice->getAmount() + $vatPrice->getAmount());
        
        // Calculs pour l'affichage avec remise (informatif)
        $subPriceWithDiscount = $invoiceCalculator->getSubTotal();
        $vatPriceWithDiscount = $invoiceCalculator->getVatTotal();
        $totalPriceWithDiscount = $invoiceCalculator->getTotalPrice();
        
        // Calculer le montant dû en fonction du statut de la facture
        $amountDue = $invoice->discount_applied ? 
            $invoiceCalculator->getAmountDue() : 
            new Money($totalPrice->getAmount() - $invoice->payments()->sum('amount'));
        
        return view('invoices.show')
            ->withInvoice($invoice)
            ->withApiconnected($apiConnected)
            ->withContacts($invoiceContacts)
            ->withfinalPrice(app(MoneyConverter::class, ['money' => $totalPrice])->format())
            ->withsubPrice(app(MoneyConverter::class, ['money' => $subPrice])->format())
            ->withVatPrice(app(MoneyConverter::class, ['money' => $vatPrice])->format())
            ->withAmountDueFormatted(app(MoneyConverter::class, ['money' => $amountDue])->format())
            ->withPrimaryContact(optional($primaryContact)[0])
            ->withPaymentSources(PaymentSource::values())
            ->withAmountDue($amountDue)
            ->withSource($invoice->source)
            ->withCompanyName(Setting::first()->company)
            ->withGlobalDiscountRate($globalDiscountRate)
            ->withGlobalDiscountAmountFormatted(app(MoneyConverter::class, ['money' => $globalDiscountAmount])->format())
            ->withSubTotalBeforeDiscountFormatted(app(MoneyConverter::class, ['money' => $subTotalBeforeDiscount])->format())
            ->withHasGlobalDiscount($hasGlobalDiscount)
            ->withTotalPriceWithDiscountFormatted(app(MoneyConverter::class, ['money' => $totalPriceWithDiscount])->format())
            ->withPriceWithoutDiscountFormatted(app(MoneyConverter::class, ['money' => $totalPrice])->format());
    }


    /**
     * Update the sent status
     * @param Request $request
     * @param $external_id
     * @return mixed
     */
    public function updateSentStatus(Request $request, $external_id)
    {
        if (!auth()->user()->can('invoice-send')) {
            session()->flash('flash_message_warning', __('You do not have permission to send an invoice'));
            return redirect()->route('invoices.show', $external_id);
        }
        /** @var Invoice $invoice */
        $invoice = $this->findByExternalId($external_id);
        if ($invoice->isSent()) {
            session()->flash('flash_message_warning', __('Invoice already sent'));
            return redirect()->route('invoices.show', $external_id);
        }

        // Vérifier si l'utilisateur a choisi d'appliquer la remise
        $applyDiscount = $request->has('apply_discount') ? (bool)$request->apply_discount : false;
        
        // Enregistrement de l'état actuel de la remise globale
        $calculator = app(InvoiceCalculator::class, ['invoice' => $invoice]);
        $globalDiscountRate = $calculator->getGlobalDiscountRate();
        $hasGlobalDiscount = $globalDiscountRate > 0 && $applyDiscount;
        
        // Si l'utilisateur veut appliquer la remise, mettre à jour les lignes de facture directement
        if ($hasGlobalDiscount) {
            $discountMultiplier = (1 - $globalDiscountRate);
            
            // Pour éviter d'appliquer la remise plusieurs fois, vérifions si elle a déjà été appliquée
            $discountAlreadyApplied = session('discount_already_applied_' . $invoice->id, false);
            
            if (!$discountAlreadyApplied) {
                // Pour chaque ligne de facture, appliquer la remise au prix
                foreach ($invoice->invoiceLines as $line) {
                    // Calculer le nouveau prix après remise
                    $originalPrice = $line->price;
                    $discountedPrice = round($originalPrice * $discountMultiplier);
                    
                    // Mettre à jour la ligne de facture avec le prix réduit
                    $line->price = $discountedPrice;
                    $line->save();
                }
                
                // Marquer que la remise a été appliquée pour cette facture
                session(['discount_already_applied_' . $invoice->id => true]);
            }
            
            // Enregistrer le taux de remise dans la session pour le retrouver après
            session(['current_discount_rate' => $globalDiscountRate]);
        } else {
            // Si l'utilisateur ne veut pas appliquer la remise, on enregistre un taux de 0%
            session(['current_discount_rate' => 0]);
            // Réinitialiser le marqueur d'application de remise
            session(['discount_already_applied_' . $invoice->id => false]);
        }

        $result = $invoice->invoice($request->invoiceContact, $applyDiscount);
        if ($request->sendMail && $request->invoiceContact) {
            $attachPdf = $request->attachPdf ? true : false;
            $invoice->sendMail($request->subject, $request->message, $request->recipientMail, $attachPdf);
        }

        $invoice->sent_at =  Carbon::now();
        $invoice->status  =  InvoiceStatus::unpaid()->getStatus();
        $invoice->due_at  =  $result["due_at"];
        $invoice->invoice_number = app(InvoiceNumberService::class)->setInvoiceNumber($result["invoice_number"]);
        
        // Sauvegarder l'information sur l'application de la remise
        $invoice->discount_applied = $hasGlobalDiscount;
        $invoice->save();

        session()->flash('flash_message', __('Invoice successfully sent'));
        return redirect()->back();
    }

    /**
     * Add new invoice line
     * @param $external_id
     * @param AddInvoiceLine $request
     * @return mixed
     * @throws \Exception
     */
    public function newItem($external_id, AddInvoiceLine $request)
    {
        if (!auth()->user()->can('modify-invoice-lines')) {
            session()->flash('flash_message_warning', __('You do not have permission to modify invoice lines'));
            return redirect()->route('invoices.show', $external_id);
        }
        $invoice = $this->findByExternalId($external_id);

        if (!$invoice->canUpdateInvoice()) {
            Session::flash('flash_message_warning', __("Can't insert new invoice line, to already sent invoice"));
            return redirect()->back();
        }

        $product = null;
        if($request->product_id) {
            $product = $request->product_id;
        } elseif($request->product) {
            $product = Product::whereExternalId($request->product)->first()->id;
        }

        InvoiceLine::create([
                'external_id' => Uuid::uuid4()->toString(),
                'title' => $request->title,
                'comment' => $request->comment,
                'quantity' => $request->quantity,
                'type' => $request->type,
                'price' => $request->price * 100,
                'invoice_id' => $invoice->id,
                'product_id' => $product
            ]);

        return redirect()->back();
    }
    
    public function newItems($external_id, Request $request)
    {
        foreach($request->all() as $invoiceLine) {
            $invoiceLine = new AddInvoiceLine($invoiceLine);
            $this->newItem($external_id, $invoiceLine);
        }
    }
    
    public function findByExternalId($external_id)
    {
        return Invoice::whereExternalId($external_id)->first();
    }

    public function paymentsDataTable(Invoice $invoice)
    {
        $payments = $invoice->payments()->select(
            ['external_id', 'amount', 'payment_date', 'description', 'payment_source']
        );

        return Datatables::of($payments)
            ->editColumn('amount', function ($payments) {
                return app(MoneyConverter::class, ['money' => $payments->price])->format();
            })
            ->editColumn('payment_date', function ($payments) {
                return $payments->payment_date ? with(new Carbon($payments->payment_date))
                    ->format(carbonDate()) : '';
            })
            ->editColumn('payment_source', function ($payments) {
                return __($payments->payment_source);
            })
            ->editColumn('description', function ($payments) {
                return substr($payments->description, 0, 80);
            })
            ->addColumn('delete', '
                <form action="{{ route(\'payment.destroy\', $external_id) }}" method="POST">
            <input type="hidden" name="_method" value="DELETE">
            <input type="submit" name="submit" value="' . __('Delete') . '" class="btn btn-link" onClick="return confirm(\'Are you sure you want to delete the payment?\')"">
            {{csrf_field()}}
            </form>')
            ->rawColumns(['delete'])
            ->make(true);
    }

    public function moneyFormat()
    {
        $formats = [];
        $currency = app(Currency::class, ["code" => Setting::select("currency")->first()->currency]);
        $formats = array_merge($formats, $currency->toArray());
        $formats['vatPercentage'] = app(Tax::class)->multipleVatRate();
        $formats['vatRate'] = app(Tax::class)->vatRate();
        
        return $formats;
    }

    public function overdue()
    {
        $invoices = Invoice::pastDueAt()->get();
        
        return view('invoices.overdue')->withInvoices($invoices);
    }

    /**
     * Recalculate invoice statuses
     * This is useful after changes to status calculation logic
     */
    public function recalculateStatuses()
    {
        if (!auth()->user()->can('invoice-manage')) {
            session()->flash('flash_message_warning', __('You do not have permission to manage invoices'));
            return redirect()->route('invoices.index');
        }
        
        $invoices = Invoice::all();
        $count = 0;
        
        foreach ($invoices as $invoice) {
            if ($invoice->payments()->count() > 0) {
                $status = app(GenerateInvoiceStatus::class, ['invoice' => $invoice])->getStatus();
                $oldStatus = $invoice->status;
                
                if ($status !== $oldStatus) {
                    $invoice->status = $status;
                    $invoice->save();
                    $count++;
                }
            }
        }
        
        session()->flash('flash_message', __(':count invoice statuses recalculated', ['count' => $count]));
        return redirect()->route('invoices.index');
    }
}
