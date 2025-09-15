<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerSetting;
use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    protected $apiKey;
    protected $settings;

    public function __construct()
    {
        $this->apiKey = config('services.whatsapp.api_key');
        try {
            $this->settings = CustomerSetting::first();
        } catch (\Exception $e) {
            $this->settings = null;
        }
    }

    public function sendInvoiceNotification(Customer $customer, $invoice)
    {
        if (!$this->settings || !$this->settings->send_invoice_notifications) {
            return;
        }

        $message = "Dear {$customer->name},\n\n";
        $message .= "Your invoice #{$invoice->id} has been created.\n";
        $message .= "Amount: {$invoice->total_amount}\n";
        
        if ($customer->payment_type === 'credit') {
            $message .= "Due Date: " . now()->addDays($customer->due_days)->format('Y-m-d') . "\n";
        }

        $message .= "\nThank you for your business!";

        return $this->sendMessage($customer->phone, $message);
    }

    public function sendDuePaymentReminder(Customer $customer)
    {
        if (!$this->settings || !$this->settings->send_due_date_reminders) {
            return;
        }

        $message = "Dear {$customer->name},\n\n";
        $message .= "This is a reminder that you have an outstanding balance of {$customer->credit_balance}.\n";
        $message .= "Please arrange for payment at your earliest convenience.\n\n";
        $message .= "Thank you for your cooperation.";

        return $this->sendMessage($customer->phone, $message);
    }

    protected function sendMessage($phone, $message)
    {
        if (!$this->settings || !$this->settings->enable_whatsapp_notifications) {
            return;
        }

        try {
            // This is a placeholder for the actual WhatsApp API integration
            // You'll need to replace this with your chosen WhatsApp API provider
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey
            ])->post('your-whatsapp-api-endpoint', [
                'phone' => $phone,
                'message' => $message
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            \Log::error('WhatsApp notification failed: ' . $e->getMessage());
            return false;
        }
    }
} 