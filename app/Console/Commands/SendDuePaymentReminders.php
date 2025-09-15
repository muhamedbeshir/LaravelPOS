<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\CustomerSetting;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class SendDuePaymentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customers:send-due-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send payment reminders to customers with due balances';

    protected $whatsappService;
    protected $settings;

    public function __construct(WhatsAppService $whatsappService)
    {
        parent::__construct();
        $this->whatsappService = $whatsappService;
        try {
            $this->settings = CustomerSetting::first();
        } catch (\Exception $e) {
            $this->settings = null;
        }
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->settings || !$this->settings->send_due_date_reminders) {
            $this->info('Due date reminders are disabled in settings.');
            return;
        }

        $customers = Customer::credit()
            ->withDueBalance()
            ->get();

        $remindersSent = 0;

        foreach ($customers as $customer) {
            $dueDate = now()->addDays($customer->due_days);
            $daysDifference = now()->diffInDays($dueDate, false);

            // Send reminder if due date is approaching or has passed
            if ($daysDifference <= $this->settings->reminder_days_before || $daysDifference < 0) {
                if ($this->whatsappService->sendDuePaymentReminder($customer)) {
                    $remindersSent++;
                    $this->info("Reminder sent to {$customer->name}");
                } else {
                    $this->error("Failed to send reminder to {$customer->name}");
                }
            }
        }

        $this->info("Sent {$remindersSent} reminders successfully.");
    }
}
