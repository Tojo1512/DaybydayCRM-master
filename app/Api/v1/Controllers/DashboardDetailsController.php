<?php

namespace App\Api\v1\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Offer;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardDetailsController extends ApiController
{
    /**
     * Récupère les détails des clients
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClientsDetails()
    {
        $clients = Client::with('user')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($client) {
                return [
                    'id' => $client->id,
                    'external_id' => $client->external_id,
                    'name' => $client->company_name,
                    'primary_contact' => $client->primaryContact ? $client->primaryContact->name : null,
                    'email' => $client->primaryContact ? $client->primaryContact->email : null,
                    'created_at' => $client->created_at->format('Y-m-d'),
                    'user_assigned' => $client->user ? $client->user->name : null
                ];
            });
        
        return $this->respond([
            'success' => true,
            'data' => $clients
        ]);
    }

    /**
     * Récupère les détails des projets
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProjectsDetails()
    {
        $projects = Project::with(['client', 'user'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'external_id' => $project->external_id,
                    'title' => $project->title,
                    'client' => $project->client ? $project->client->company_name : null,
                    'deadline' => $project->deadline ? Carbon::parse($project->deadline)->format('Y-m-d') : null,
                    'status' => $project->status ? $project->status->title : null,
                    'created_at' => $project->created_at->format('Y-m-d'),
                    'user_assigned' => $project->user ? $project->user->name : null
                ];
            });
        
        return $this->respond([
            'success' => true,
            'data' => $projects
        ]);
    }

    /**
     * Récupère les détails des tâches
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTasksDetails()
    {
        $tasks = Task::with(['client', 'user', 'status'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'external_id' => $task->external_id,
                    'title' => $task->title,
                    'client' => $task->client ? $task->client->company_name : null,
                    'deadline' => $task->deadline ? Carbon::parse($task->deadline)->format('Y-m-d') : null,
                    'status' => $task->status ? $task->status->title : null,
                    'created_at' => $task->created_at->format('Y-m-d'),
                    'user_assigned' => $task->user ? $task->user->name : null
                ];
            });
        
        return $this->respond([
            'success' => true,
            'data' => $tasks
        ]);
    }

    /**
     * Récupère les détails des tâches actives
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActiveTasksDetails()
    {
        $tasks = Task::with(['client', 'user', 'status'])
            ->whereNull('status_id')
            ->orWhere('status_id', '<', 3)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'external_id' => $task->external_id,
                    'title' => $task->title,
                    'client' => $task->client ? $task->client->company_name : null,
                    'deadline' => $task->deadline ? Carbon::parse($task->deadline)->format('Y-m-d') : null,
                    'status' => $task->status ? $task->status->title : null,
                    'created_at' => $task->created_at->format('Y-m-d'),
                    'user_assigned' => $task->user ? $task->user->name : null
                ];
            });
        
        return $this->respond([
            'success' => true,
            'data' => $tasks
        ]);
    }

    /**
     * Récupère les détails des offres
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOffersDetails()
    {
        $offers = Offer::with(['client', 'invoiceLines'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($offer) {
                $total = 0;
                foreach ($offer->invoiceLines as $line) {
                    $total += $line->quantity * $line->price;
                }
                
                return [
                    'id' => $offer->id,
                    'external_id' => $offer->external_id,
                    'client' => $offer->client ? $offer->client->company_name : null,
                    'status' => $offer->status,
                    'sent_at' => $offer->sent_at ? Carbon::parse($offer->sent_at)->format('Y-m-d') : null,
                    'created_at' => $offer->created_at->format('Y-m-d'),
                    'total_value' => $total / 100, // Conversion en format décimal
                    'number_of_lines' => $offer->invoiceLines->count()
                ];
            });
        
        return $this->respond([
            'success' => true,
            'data' => $offers
        ]);
    }

    /**
     * Récupère les détails des factures
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInvoicesDetails()
    {
        $invoices = Invoice::with(['client', 'invoiceLines'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($invoice) {
                $total = 0;
                foreach ($invoice->invoiceLines as $line) {
                    $total += $line->quantity * $line->price;
                }
                
                return [
                    'id' => $invoice->id,
                    'external_id' => $invoice->external_id,
                    'client' => $invoice->client ? $invoice->client->company_name : null,
                    'invoice_number' => $invoice->invoice_number,
                    'status' => $invoice->status,
                    'sent_at' => $invoice->sent_at ? Carbon::parse($invoice->sent_at)->format('Y-m-d') : null,
                    'due_at' => $invoice->due_at ? Carbon::parse($invoice->due_at)->format('Y-m-d') : null,
                    'created_at' => $invoice->created_at->format('Y-m-d'),
                    'total_value' => $total / 100, // Conversion en format décimal
                    'based_on_offer' => $invoice->offer_id ? true : false
                ];
            });
        
        return $this->respond([
            'success' => true,
            'data' => $invoices
        ]);
    }

    /**
     * Récupère les détails des factures impayées
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUnpaidInvoicesDetails()
    {
        $invoices = Invoice::with(['client', 'invoiceLines'])
            ->where('due_at', '<', Carbon::now())
            ->where('status', '!=', 'paid')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($invoice) {
                $total = 0;
                foreach ($invoice->invoiceLines as $line) {
                    $total += $line->quantity * $line->price;
                }
                
                return [
                    'id' => $invoice->id,
                    'external_id' => $invoice->external_id,
                    'client' => $invoice->client ? $invoice->client->company_name : null,
                    'invoice_number' => $invoice->invoice_number,
                    'status' => $invoice->status,
                    'sent_at' => $invoice->sent_at ? Carbon::parse($invoice->sent_at)->format('Y-m-d') : null,
                    'due_at' => $invoice->due_at ? Carbon::parse($invoice->due_at)->format('Y-m-d') : null,
                    'created_at' => $invoice->created_at->format('Y-m-d'),
                    'total_value' => $total / 100, // Conversion en format décimal
                    'days_overdue' => $invoice->due_at ? Carbon::now()->diffInDays(Carbon::parse($invoice->due_at), false) : null
                ];
            });
        
        return $this->respond([
            'success' => true,
            'data' => $invoices
        ]);
    }

    /**
     * Récupère les détails des paiements
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentsDetails()
    {
        $payments = Payment::with(['invoice', 'invoice.client'])
            ->orderBy('payment_date', 'asc')
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'external_id' => $payment->external_id,
                    'client' => $payment->invoice && $payment->invoice->client ? $payment->invoice->client->company_name : null,
                    'invoice_number' => $payment->invoice ? $payment->invoice->invoice_number : null,
                    'amount' => $payment->amount / 100, // Conversion en format décimal
                    'payment_date' => Carbon::parse($payment->payment_date)->format('Y-m-d'),
                    'payment_source' => $payment->payment_source,
                    'description' => $payment->description
                ];
            });
        
        return $this->respond([
            'success' => true,
            'data' => $payments
        ]);
    }
} 