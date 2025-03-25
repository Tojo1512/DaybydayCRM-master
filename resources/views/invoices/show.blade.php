@extends('layouts.master')

@section('content')
    <div class="row">
        <div class="col-md-7">
            <div class="tablet">
                <div class="tablet__head">
                    <div class="tablet__head-label text-center">
                        <h3 class="tablet__head-title">{{ __('Invoice summary') }}</h3>
                    </div>

                </div>
                <div class="tablet__body">
                    <div class="tablet__items">
                        @foreach($invoice->invoiceLines as $invoice_line)
                                <div class="tablet__item" style="padding: 0;">
                                    <div class="tablet__item__info">
                                        <p class="invoice-title">{{$invoice_line->title}}</p>

                                        <div class="tablet__item__description">
                                            <p class="invoice-info">{{$invoice_line->quantity}} x {{$invoice_line->price_converted}}</p>
                                            <p class="invoice-info small">{{ __($invoice_line->type) }}</p>
                                        </div>
                                    </div>
                                    <div class="tablet__item__toolbar">
                                        <div class="dropdown dropdown-inline">
                                            @if($invoice->canUpdateInvoice())
                                            <form action="{{route('invoiceLine.destroy', $invoice_line->external_id)}}" method="post">
                                                @method('delete')
                                                {{csrf_field()}}
                                                <p>
                                            @endif
                                                    {{$invoice_line->total_value_converted}}
                                            @if($invoice->canUpdateInvoice())
                                                <button type="submit" class="fa fa-btn fa-trash-o btn btn-clean trashcan-icon"></button>
                                                </p>
                                            </form>
                                            @endif

                                        </div>
                                    </div>
                                </div>
                                <hr style="margin-top: 5px;">
                        @endforeach
                        
                        
                        <div class="row" style="margin-top: 15px;">
                            <div class="col-md-9">
                                <p class="invoice-title">{{ __('Subtotal') }}</p>
                            </div>
                            <div class="col-md-3 text-right">
                                <p>{{ $subPrice }}</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-9">
                                <p class="invoice-title">{{ __('Tax') }}</p>
                            </div>
                            <div class="col-md-3 text-right">
                                <p>{{ $vatPrice }}</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-9">
                                <p class="invoice-title">{{ __('Total') }}</p>
                            </div>
                            <div class="col-md-3 text-right">
                                <p><strong>{{ $finalPrice }}</strong></p>
                            </div>
                        </div>
                        
                        @if($hasGlobalDiscount)
                        <div  style="margin-top: 15px; padding: 10px; border-radius: 5px; background-color:rgb(141, 224, 213); border: 1px dashed #e2e8f0;">
                            <p style="margin-bottom: 5px; font-weight: bold; color: #2d3748;">{{ __('Potential discount information') }}</p>
                            <div class="row" style="margin-bottom: 3px;">
                                <div class="col-md-9">
                                    <p class="invoice-title" style="color: #4a5568;">{{ __('Global discount rate') }}</p>
                                </div>
                                <div class="col-md-3 text-right">
                                    <p style="color: #4a5568;">{{ number_format($globalDiscountRate, 2) }}%</p>
                                </div>
                            </div>
                            <div class="row" style="margin-bottom: 3px;">
                                <div class="col-md-9">
                                    <p class="invoice-title" style="color: #4a5568;">{{ __('Discount amount') }}</p>
                                </div>
                                <div class="col-md-3 text-right">
                                    <p style="color: #4a5568;">-{{ $globalDiscountAmountFormatted }}</p>
                                </div>
                            </div>
                            <div class="row" style="margin-bottom: 3px;">
                                <div class="col-md-9">
                                    <p class="invoice-title" style="color: #4a5568; font-weight: bold;">{{ __('Total with discount') }}</p>
                                </div>
                                <div class="col-md-3 text-right">
                                    <p style="color: #4a5568; font-weight: bold;">{{ $totalPriceWithDiscountFormatted }}</p>
                                </div>
                            </div>
                            <p style="margin-top: 5px; font-size: 0.85em; color: #718096; font-style: italic;">{{ __('The discount will only be applied if selected during invoice sending.') }}</p>
                        </div>
                        @endif
                    </div>

                    @if($invoice->canUpdateInvoice())
                    <div class="tablet__action">
                        <button type="button" id="time-manager" class="btn btn-md tablet__action__button ">
                            <span class="tablet__action__button-icon ion-pricetags"></span>
                            <span class="tablet__action__button-text">{{ __('Add product') }}</span>
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="tablet">
                <div class="tablet__body" style="padding-bottom: 3em;">
                    <p class="invoice-title">{{$client->company_name}}
                        <a href="{{route('clients.show', $client->external_id)}}"><i class="ion ion-ios-redo " title="{{ __('Go to client') }}" style="
                        float: right;
                        margin-right: 1em;
                        color:#61788b;
                        "></i></a>
                    </p>
                    <p class="invoice-info">{{$contact_info->name}}</p>
                    <p class="invoice-info">{{$contact_info->email}}</p>
                    <hr style="margin-top: 5px;">
                    <div class="row">
                        <div class="col-md-6" style="padding-bottom: 1em;">
                            <p class="invoice-info-title">@lang('Invoice created')</p>
                            <p class="invoice-info-subtext">{{date(carbonDate(), strtotime($invoice->created_at))}}</p>
                        </div>
                        <div class="col-md-6" style="padding-bottom: 1em;">
                            <p class="invoice-info-title">@lang('Invoice date')</p>
                            <p class="invoice-info-subtext">{{ !$invoice->sent_at ? __('Not send') : date(carbonDate(), strtotime($invoice->sent_at))}}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="invoice-info-title">@lang('Due date')</p>
                            <p class="invoice-info-subtext">{{ !$invoice->due_at ? __('Not set') : date(carbonDate(), strtotime($invoice->due_at))}}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="invoice-info-title">@lang('Amount due')</p>
                            <p class="invoice-info-subtext">{{$amountDueFormatted}}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="invoice-info-title">@lang('Status')</p>
                            <p class="invoice-info-subtext">{{\App\Enums\InvoiceStatus::fromStatus($invoice->status)->getDisplayValue()}}</p>
                        </div>
                        @if($source)
                            <div class="col-md-6">
                                <p class="invoice-info-title">@lang('Reference')</p>
                                <p class="invoice-info-subtext">
                                    <a href="{{$source->getShowRoute()}}">{{__(class_basename(get_class($source)))}}</a>
                                </p>
                            </div>
                        @endif
                        @if($invoice->invoice_number != null)
                            <div class="col-md-6">
                                <p class="invoice-info-title">@lang('Invoice number')</p>
                                <p class="invoice-info-subtext">
                                    {{$invoice->invoice_number}}
                                </p>
                            </div>
                        @endif
                        @if($invoice->offer)
                            <div class="col-md-6">
                                <p class="invoice-info-title">@lang('Based on')</p>
                                <p class="invoice-info-subtext">
                                    <button data-offer-external_id={{$invoice->offer->external_id}} class="btn btn-link" style="padding: 0px;" id="view-original-offer">@lang('Offer')</button> 
                                </p>
                            </div>
                        @endif
                        
                        @if($hasGlobalDiscount)
                            <div class="col-md-6">
                                <p class="invoice-info-title">@lang('Global discount')</p>
                                <p class="invoice-info-subtext">
                                    {{ number_format($globalDiscountRate, 2) }}%
                                </p>
                            </div>
                        @endif
                        
                        <hr>
                        <div class="col-md-6">
                            @if(Entrust::can('invoice-pay'))
                                        <button type="button" id="update-payment" class="btn btn-md btn-brand btn-full-width closebtn"
                                                <?php $titleText =  !$invoice->isSent() ? __("Can't pay an invoice with status draft. Send invoice first or force a new status") : "" ?> title="{{$titleText}}"
                                                {{ !$invoice->isSent() ? 'disabled ' : "" }}
                                                data-toggle="modal" data-target="#update-payment-modal">@lang('Register payment')</button>
                            @endif
                        </div>
                        <div class="col-md-6">
                            @if(Entrust::can('invoice-send'))
                                <button type="button" id="sendInvoice" class="btn btn-md btn-brand btn-full-width closebtn" value="add_time_modal"
                                        <?php $titleText =  $invoice->isSent() ? __('Invoice already sent') : "" ?> title="{{$titleText}}"
                                        {{ $invoice->isSent() ? 'disabled ' : "" }}
                                        data-toggle="modal" data-target="#SendInvoiceModalConfirm" >
                                    {{ __('Send invoice') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            @if($invoice->payments->isNotEmpty())
                @include('invoices._paymentList')
            @endif
        </div>
    </div>
    <div class="modal fade" id="view-offer" tabindex="-1" role="dialog" aria-hidden="true"
         style="display:none;">
        <div class="modal-dialog modal-lg view-offer-inner" style="background:white;">
            
        </div>
    </div>
@if(!$invoice->sent_at)
<div class="modal fade" id="SendInvoiceModalConfirm" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">{{ __('Are you sure?') }}</h4>
            </div>
            <div class="modal-body">
                {!! Form::open([
                    'method' => 'post',
                    'route' => ['invoice.sent', $invoice->external_id],
                    'id' => 'send-invoice-form'
                ]) !!}
                
                <p class="alert alert-warning">{{ __('Once a invoice has been send, no new invoice lines can be added') }}</p>
                
                <!-- Affichage des informations de prix avec et sans remise -->
                <div class="price-info-box" style="margin-bottom: 20px;">
                    <div class="price-info-header">{{ __('Invoice Summary') }}</div>
                    <div class="price-info-content">
                        <div class="price-item final" style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #e2e8f0; font-weight: bold;">
                            <div class="price-label">{{ __('Total without discount') }}:</div>
                            <div class="price-value" id="standard-price">{{ $priceWithoutDiscountFormatted }}</div>
                        </div>
                        
                        @if($hasGlobalDiscount)
                        <div class="discount-option" style="margin: 15px 0; padding: 10px; background-color: #f8f9fa; border-radius: 5px; border: 1px solid #e2e8f0;">
                            <label style="display: flex; align-items: center; margin-bottom: 10px; font-weight: bold;">
                                <input type="checkbox" name="apply_discount" id="apply_discount" value="1" style="margin-right: 10px; transform: scale(1.2);">
                                {{ __('Apply global discount') }} ({{ number_format($globalDiscountRate, 2) }}%)
                            </label>
                            
                            <div class="discount-details" style="display: none;">
                                <div class="price-item discount">
                                    <div class="price-label">{{ __('Discount amount') }}:</div>
                                    <div class="price-value discount-value">-{{ $globalDiscountAmountFormatted }}</div>
                                </div>
                                <div class="price-item">
                                    <div class="price-label">{{ __('Total after discount') }}:</div>
                                    <div class="price-value">{{ $totalPriceWithDiscountFormatted }}</div>
                                </div>
                                <div class="savings-tag" style="margin-top: 10px; font-size: 0.9em; color: #2b6cb0;">
                                    <i class="fa fa-tags"></i> {{ __('Client saves') }}: {{ $globalDiscountAmountFormatted }}
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="no-discount-message" style="margin: 15px 0; padding: 10px; background-color: #f8f9fa; border-radius: 5px;">
                            <i class="fa fa-info-circle"></i> {{ __('No global discount is currently available.') }}
                        </div>
                        @endif
                        
                        <div class="price-item final" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e2e8f0; font-weight: bold;">
                            <div class="price-label">{{ __('Final total') }}:</div>
                            <div class="price-value" id="final-price">{{ $priceWithoutDiscountFormatted }}</div>
                        </div>
                    </div>
                </div>
                
                <!-- Case à cocher de confirmation - optionnelle -->
                <div class="confirm-checkbox">
                    <label>
                        <input type="checkbox" id="confirmSend" name="confirmSend">
                        <span>{{ __('I confirm that I want to send this invoice') }}</span>
                    </label>
                </div>
                
                @if($apiconnected)
                <div class="integration-section">
                    <h5>{{ __('Billing Integration') }}</h5>
                    <p>{{ __('We have found this contact from your billing integration, do you wish for us to create the invoice in your your billing system as well?, than please choose a contact below') }}</p>
                    <select name="invoiceContact"
                            class="form-control bootstrap-select contacts-selectpicker"
                            id="user-search-select" data-live-search="true"
                            data-style="btn btn-md dropdown-toggle btn-light"
                            data-container="body">
                        <option value="" style="color:lightslategrey"> @lang("Nothing selected")</option>
                        @foreach($contacts as $contact)
                            <option data-tokens="{{$contact['name']}}"
                                    value="{{$contact['guid']}}" {{optional($primaryContact)["guid"] == $contact['guid'] ? "selected" : ""}}>{{$contact['name']}}
                            </option>
                        @endforeach
                    </select>
                
                    <div class="mail-option">
                        <label>
                            <input type="checkbox" name="sendMail" id="sendMailCheckbox">
                            <span>{{ __('Send mail with invoice to Customer') }}</span>
                        </label>
                    </div>
                </div>
                @endif
                
                <div id="send-mail" style="display: none">
                    <h5>{{ __('Email Options') }}</h5>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="attachPdf" id="attachPdf" value="1">
                            <span>{{ __('Attach invoice as PDF') }}</span>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label for="recipientMail">{{ __('Recipient') }}</label>
                        <input type="text" class="form-control" name="recipientMail" id="recipientMail" value="{{$invoice->client->primaryContact->email}}">
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">{{ __('Subject') }}</label>
                        <input type="text" class="form-control" name="subject" id="subject" value="{{__('Invoice from :company', ["company" => $companyName])}}">
                    </div>
                    
                    <div class="form-group">
                        <label for="message">{{ __('Message') }}</label>
                        <textarea name="message" id="message" rows="8" class="form-control">@lang("Dear :name\n\nThank you, for being a customer at :company\n\nHere is you Invoice on :price\n\nClick the link below to download the invoice\n\n[link-to-pdf]\n\nRegards\n---\n:company", ["name" => $invoice->client->primaryContact->name, "company" => $companyName, "price" => $finalPrice])</textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary" id="close-invoice" name="submit-invoice" style="background-color: #3498db; font-weight: bold;">
                    {{ __('Send invoice') }}
                </button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>
@endif
    <div class="modal fade" id="add-invoice-line-modal" tabindex="-1" role="dialog" aria-hidden="true" style="display:none;">
        <div class="modal-dialog modal-lg" style="background:white;">
            <invoice-line-modal type="invoiceLine" :resource="{{$invoice}}"/>
        </div>
    </div>
    <div class="modal fade" id="update-payment-modal" tabindex="-1" role="dialog" aria-hidden="true" style="display:none;">
        <div class="modal-dialog">
            <div class="modal-content" style="padding:2em;">
                @include('invoices._updatePaymentModal')
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="text/javascript">
        $(document).ready(function () {
            // Mettre à jour le prix final en fonction de la case à cocher de remise
            $('#apply_discount').on('change', function() {
                var applyDiscount = $(this).is(':checked');
                var priceWithDiscount = '{{ $totalPriceWithDiscountFormatted }}';
                var priceWithoutDiscount = '{{ $priceWithoutDiscountFormatted }}';
                
                if (applyDiscount) {
                    $('#final-price').text(priceWithDiscount);
                    $('.discount-details').slideDown();
                } else {
                    $('#final-price').text(priceWithoutDiscount);
                    $('.discount-details').slideUp();
                }
            });
            
            // Afficher le modal de confirmation si nécessaire
            var showExceedingModal = "{{ session('show_exceeding_modal') ? 'true' : 'false' }}";
            if (showExceedingModal === "true") {
                $('#exceeding-payment-confirm-modal').modal('show');
            }
            
            $('#time-manager').on('click', function () {
                $('#add-invoice-line-modal').modal('show');
            });

            $('.contacts-selectpicker').selectpicker();

            $('#user-search-select').change(function(){
                if($(this).val() == ""){
                    $('#sendMailCheckbox').prop("checked", false);
                    $('#send-mail').hide(150);
                }
            });

            $('#sendMailCheckbox').change(function(){
                if(this.checked)
                    $('#send-mail').show(150);
                else
                    $('#send-mail').hide(150);
            });
            
            // Correction du problème de soumission du formulaire
            $('#close-invoice').on('click', function(event) {
                event.preventDefault(); // Empêcher le comportement par défaut du bouton
                console.log('Bouton Send invoice cliqué - soumission directe du formulaire');
                
                // Afficher clairement un message sur le montant avec ou sans remise
                @if($hasGlobalDiscount)
                console.log('Cette facture inclut une remise globale de {{ $globalDiscountRate }}%');
                console.log('Montant avant remise: {{ $subTotalBeforeDiscountFormatted }}');
                console.log('Montant après remise: {{ $finalPrice }}');
                @endif
                
                // Soumettre directement le formulaire sans validation
                document.getElementById('send-invoice-form').submit();
                return false;
            });
            
            // Alternative en cas de problème avec la méthode ci-dessus
            $('#send-invoice-form').on('submit', function() {
                console.log('Formulaire soumis');
                return true; // Permettre la soumission normale
            });
            
            // Désactiver la fermeture automatique de la modal lors du clic sur le bouton Submit
            $('#SendInvoiceModalConfirm').on('click', '.btn-primary', function(e) {
                e.stopPropagation();
            });
        });
    </script>
@endpush

<!-- Ajouter le modal de confirmation pour les paiements excédentaires -->
<div class="modal fade" id="exceeding-payment-confirm-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="padding:2em;">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">{{ __('Payment exceeds amount due') }}</h4>
            </div>
            <div class="modal-body">
                <p>{{ __('The payment amount') }} ({{ session('payment_amount') }}) {{ __('exceeds the amount due on the invoice') }} ({{ session('amount_due') }}).</p>
                <p>{{ __('Do you want to proceed with this payment?') }}</p>
            </div>
            <div class="modal-footer">
                <form action="{{ route('payments.confirm-exceeding', $invoice->external_id) }}" method="POST">
                    {{ csrf_field() }}
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Proceed with payment') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .discount-line {
        background-color: rgba(46, 204, 113, 0.05);
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 10px;
    }
    
    /* Nouveaux styles pour l'info de prix */
    .price-info-box {
        margin: 20px 0;
        border-radius: 6px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .price-info-header {
        background-color: #4a5568;
        color: white;
        padding: 12px 15px;
        font-weight: 600;
        font-size: 16px;
    }
    
    .price-info-content {
        padding: 15px;
        background-color: #f8f9fa;
    }
    
    .price-item {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px dashed #e2e8f0;
    }
    
    .price-item:last-child {
        border-bottom: none;
    }
    
    .price-item.discount {
        color: #e53e3e;
    }
    
    .price-item.final {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #e2e8f0;
        font-weight: 600;
        font-size: 16px;
    }
    
    .price-value.original {
        text-decoration: line-through;
        color: #718096;
    }
    
    .price-value.discount-value {
        color: #38a169;
    }
    
    .savings-tag {
        margin-top: 10px;
        background-color: #ebf8ff;
        color: #2b6cb0;
        padding: 8px 12px;
        border-radius: 4px;
        font-size: 14px;
        display: inline-block;
    }
    
    .savings-tag i {
        margin-right: 5px;
    }
    
    .no-discount-message {
        margin-top: 10px;
        background-color: #f0fff4;
        color: #2f855a;
        padding: 8px 12px;
        border-radius: 4px;
        font-size: 14px;
    }
    
    .no-discount-message i {
        margin-right: 5px;
    }
    
    .confirm-checkbox {
        margin: 20px 0;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 6px;
        border: 1px solid #e9ecef;
    }
    
    .confirm-checkbox label {
        display: flex;
        align-items: center;
        margin-bottom: 0;
        cursor: pointer;
    }
    
    .confirm-checkbox input[type="checkbox"] {
        margin-right: 10px;
        transform: scale(1.2);
    }
    
    .confirm-checkbox span {
        font-weight: 600;
    }
    
    .highlight {
        animation: pulse 0.5s ease-in-out;
        background-color: #fff3cd;
        border-color: #ffeeba;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.02); }
        100% { transform: scale(1); }
    }
    
    .integration-section {
        margin: 20px 0;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }
    
    .integration-section h5 {
        margin-top: 0;
        margin-bottom: 15px;
        font-weight: 600;
    }
    
    .mail-option {
        margin-top: 15px;
    }
    
    .mail-option label {
        display: flex;
        align-items: center;
    }
    
    .mail-option input[type="checkbox"] {
        margin-right: 10px;
    }
    
    #send-mail {
        margin-top: 20px;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }
    
    #send-mail h5 {
        margin-top: 0;
        margin-bottom: 15px;
        font-weight: 600;
    }
    
    .alert-warning {
        color: #856404;
        background-color: #fff3cd;
        border-color: #ffeeba;
        padding: 10px 15px;
        border-radius: 4px;
    }
</style>
