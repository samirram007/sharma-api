<?php

namespace Modules\Ticket\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Ticket\Models\Ticket;
use Modules\Ticket\Models\TicketResponse;
use Modules\User\Models\User;

class TicketSeeder extends Seeder
{
    /**
     * Seed demo support tickets with their conversations.
     *
     * Tickets are raised by the main admin account (admin@admin.com). Replies
     * are attributed to another user when one exists (admin/manager/support)
     * so the thread reads as a real back-and-forth; if only one user exists,
     * the replies fall back to that same user so seeding still succeeds.
     */
    public function run(): void
    {
        $requester = User::where('email', 'admin@admin.com')->first()
            ?? User::query()->orderBy('id')->first();

        if (! $requester) {
            $this->command->warn('TicketSeeder: no users found — create a user first (e.g. Admin User, admin@admin.com).');

            return;
        }

        $support = User::where('id', '!=', $requester->id)->orderBy('id')->first()
            ?? $requester;

        $tickets = [
            [
                'days_ago' => 2,
                'ticket' => [
                    'subject' => 'Unable to generate GST report',
                    'description' => 'I am trying to generate the GST report for the current fiscal year but the system keeps showing an error. The error message says "Invalid date range".',
                    'status' => 'open',
                    'priority' => 'high',
                    'category' => 'reports',
                ],
                'conversation' => [
                    ['requester', 'I am attaching a screenshot of the error. I selected the 2025-26 fiscal year, but it still shows "Invalid date range".', 40],
                    ['support', 'Thanks for the screenshot. Does the failure happen for both GSTR-1 and GSTR-3B, or only for one of them?', 3 * 60],
                    ['requester', 'Only GSTR-1 — GSTR-3B generates without any problem.', 24 * 60],
                    ['support', 'We have narrowed it down: the GSTR-1 export fails when the selected period has no outward supplies recorded. Our team is working on a fix. In the meantime, adding at least one outward supply for the period lets the report generate normally. We will update this ticket once the patch is released.', 4 * 60],
                ],
            ],
            [
                'days_ago' => 3,
                'ticket' => [
                    'subject' => 'Need help with stock journal entry',
                    'description' => 'How do I create a stock journal entry for transferring items between godowns? I cannot find the option in the menu.',
                    'status' => 'in_progress',
                    'priority' => 'medium',
                    'category' => 'inventory',
                    'assigned_to' => $support->id,
                ],
                'conversation' => [
                    ['support', 'Stock journal entries are recorded under Transactions > Vouchers. Select "Stock Journal" as the voucher type and choose "Godown Transfer" as the entry type for inter-godown movement.', 2 * 60],
                    ['requester', 'I can see "Stock Journal" in the voucher list, but there is no "Godown Transfer" type — only inward and outward options appear.', 24 * 60],
                    ['support', 'Thanks for checking. "Godown Transfer" only appears when the "Type of Stock Journal" field on the voucher is set to Transfer. We are also verifying your role permissions for the stock journal module and will confirm shortly.', 6 * 60],
                    ['requester', 'That was it — setting the field to Transfer revealed the option and I could move stock between godowns. Please still check the permissions, since the option was hidden earlier.', 2 * 60],
                ],
            ],
            [
                'days_ago' => 5,
                'ticket' => [
                    'subject' => 'Request for new report format',
                    'description' => 'It would be great if we could have a customizable report format for the day book. Currently the format is fixed.',
                    'status' => 'open',
                    'priority' => 'low',
                    'category' => 'feature_request',
                ],
                'conversation' => [
                    ['support', 'Thanks for the suggestion! Could you share which columns you would like the customizable day book format to include?', 5 * 60],
                    ['requester', 'Date, voucher number, ledger, narration, debit/credit amounts and a running balance — and ideally the ability to export to Excel or PDF.', 24 * 60],
                    ['support', 'We have logged this as a feature request and shared it with the product team. We will update this ticket once it is scheduled for a release.', 2 * 24 * 60],
                ],
            ],
            [
                'days_ago' => 6,
                'ticket' => [
                    'subject' => 'Payment voucher not saving',
                    'description' => 'When I try to save a payment voucher, the system shows a timeout error. This happens intermittently.',
                    'status' => 'resolved',
                    'priority' => 'urgent',
                    'category' => 'bug',
                    'assigned_to' => $support->id,
                ],
                'conversation' => [
                    ['support', 'We are investigating the timeouts on payment vouchers. Does this happen for all voucher sizes or mainly for large ones (20+ entries)?', 4 * 60],
                    ['requester', 'It happens for every size, but more frequently in the afternoons.', 24 * 60],
                    ['support', 'Thanks — we found a stuck background job that was locking the voucher table. We have cleared it and deployed a fix that prevents the lock from recurring.', 2 * 60],
                    ['requester', 'Payment vouchers are saving normally now. Thank you for the quick fix!', 30],
                ],
            ],
        ];

        $seeded = 0;
        foreach ($tickets as $demo) {
            $ticket = Ticket::create(array_merge($demo['ticket'], ['created_by' => $requester->id]));

            $createdAt = now()->subDays($demo['days_ago']);
            $ticket->created_at = $createdAt;
            $ticket->updated_at = $createdAt;
            $ticket->save();

            $replyAt = $createdAt->copy();
            foreach ($demo['conversation'] as [$author, $message, $minutesAfter]) {
                $replyAt = $replyAt->copy()->addMinutes($minutesAfter);

                $response = TicketResponse::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $author === 'support' ? $support->id : $requester->id,
                    'message' => $message,
                ]);
                $response->created_at = $replyAt;
                $response->updated_at = $replyAt;
                $response->save();
            }

            if (in_array($ticket->status, [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED], true)) {
                $ticket->resolved_at = $replyAt;
                $ticket->save();
            }

            $seeded++;
        }

        $this->command->info("TicketSeeder: {$seeded} ticket(s) with conversations seeded.");
    }
}
