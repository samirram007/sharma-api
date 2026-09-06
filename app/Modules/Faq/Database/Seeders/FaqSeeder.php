<?php

namespace Modules\Faq\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Faq\Models\Faq;

class FaqSeeder extends Seeder
{
    /**
     * Seed the FAQ knowledge base. Entries are matched on the question so the
     * seeder can be re-run without duplicating existing records.
     */
    public function run(): void
    {
        $faqs = [
            // ------------------------------------------------------------ general
            [
                'question' => 'How do I get started with the application?',
                'answer' => 'Follow this path once: (1) create your Company under Masters > Organization > Company, (2) create and open a Fiscal Year under Masters > Organization > Fiscal Year, (3) build the chart of accounts (account groups and ledgers), (4) enter opening balances, (5) add parties and inventory masters, and (6) create users with the right permissions. You are then ready to record vouchers.',
                'category' => 'general',
                'sort_order' => 6,
                'is_published' => true,
            ],
            [
                'question' => 'How do I create and open a fiscal year?',
                'answer' => 'Go to Masters > Organization > Fiscal Year and add a new year with its start and end dates (for example 01-04-2025 to 31-03-2026), then open it. Transactions and reports work within the currently open fiscal year, and opening balances are recorded when a year is opened.',
                'category' => 'general',
                'sort_order' => 7,
                'is_published' => true,
            ],
            [
                'question' => 'How do I close a fiscal year and open the next one?',
                'answer' => 'Close the completed year from Year-End Process > Close Fiscal Year, create and open the next fiscal year under Masters > Organization > Fiscal Year, post the opening journal and opening balances for the new year, and verify everything with the Opening Entry Report before recording new transactions.',
                'category' => 'general',
                'sort_order' => 8,
                'is_published' => true,
            ],
            [
                'question' => 'Can I manage more than one company?',
                'answer' => 'Yes. Create each legal entity under Masters > Organization > Company with its own currency and statutory details. Companies share the same application, and each user is associated with the fiscal year they work in.',
                'category' => 'general',
                'sort_order' => 9,
                'is_published' => true,
            ],
            [
                'question' => 'Can I import data from another system?',
                'answer' => 'Yes, the system supports importing data through CSV/Excel files. Go to Settings > Import Data and follow the wizard.',
                'category' => 'general',
                'sort_order' => 10,
                'is_published' => true,
            ],

            // ----------------------------------------------------------- masters
            [
                'question' => 'How do I set up my company details?',
                'answer' => 'Open Masters > Organization > Company and fill in the company name, address, currency and registration/statutory details. This information is used across vouchers, parties and reports.',
                'category' => 'masters',
                'sort_order' => 11,
                'is_published' => true,
            ],
            [
                'question' => 'How do I build the chart of accounts?',
                'answer' => 'Account groups come first: create them under Masters > Accounts > Chart of Accounts and assign each an account nature (asset, liability, income or expense) so they appear in the right place on the Balance Sheet and Profit & Loss. Ledgers are then added under their groups.',
                'category' => 'masters',
                'sort_order' => 12,
                'is_published' => true,
            ],
            [
                'question' => 'How do I add an account ledger?',
                'answer' => 'Go to Masters > Accounts > Account Ledger and click Add. Choose the account group the ledger belongs to (for example Cash under Current Assets) and provide the ledger name and code. Vouchers can only be posted to existing ledgers.',
                'category' => 'masters',
                'sort_order' => 13,
                'is_published' => true,
            ],
            [
                'question' => 'What are voucher types and how do I configure them?',
                'answer' => 'Voucher types (payment, receipt, journal, etc.) determine how transactions behave — which side defaults to debit or credit, numbering and whether stock is involved. Maintain them under Masters > Accounts > Voucher Type, then pick a type when recording a voucher.',
                'category' => 'masters',
                'sort_order' => 14,
                'is_published' => true,
            ],
            [
                'question' => 'How do I add a new supplier?',
                'answer' => 'Go to Masters > Party > Supplier and click "Add Supplier". Fill in the supplier details including name, code, and contact information.',
                'category' => 'masters',
                'sort_order' => 15,
                'is_published' => true,
            ],
            [
                'question' => 'What is the difference between a supplier and a distributor?',
                'answer' => 'Suppliers are vendors you purchase from, while distributors are the channel you sell to. Register suppliers under Masters > Party > Supplier and distributors under Masters > Party > Distributor — both can be linked to ledgers for automatic accounting.',
                'category' => 'masters',
                'sort_order' => 16,
                'is_published' => true,
            ],
            [
                'question' => 'How do I add transporters for delivery and freight?',
                'answer' => 'Open Masters > Party > Transporter and add each logistics provider. Transporters are used when recording deliveries and when analysing freight by transporter in reports.',
                'category' => 'masters',
                'sort_order' => 17,
                'is_published' => true,
            ],
            [
                'question' => 'How do I set up a godown?',
                'answer' => 'Go to Masters > Inventory > Godown and add the warehouse with its address. You can define storage units inside a godown, and stock items are tracked against the godowns/storage units they are stored in.',
                'category' => 'masters',
                'sort_order' => 18,
                'is_published' => true,
            ],
            [
                'question' => 'How do I add employees, departments and designations?',
                'answer' => 'First create the departments and designations under Masters > Payroll, then add the employee under Masters > Payroll > Employee and link the department, designation and (optionally) a user account.',
                'category' => 'masters',
                'sort_order' => 19,
                'is_published' => true,
            ],
            [
                'question' => 'How do I set up delivery places, routes and vehicles?',
                'answer' => 'These support your delivery service: add the destinations under Masters > Miscellaneous > Delivery Places, group them into Delivery Routes, and register the Delivery Vehicles used for dispatch. Delivery note and freight reports use this data.',
                'category' => 'masters',
                'sort_order' => 20,
                'is_published' => true,
            ],

            // --------------------------------------------------------- inventory
            [
                'question' => 'How do I create a stock item?',
                'answer' => 'Open Masters > Inventory > Stock Item and click Add. Assign the item to a stock group and category, choose its stock unit, and set the rate/price details. If you track batches or serial numbers, enable them on the item before recording stock movements.',
                'category' => 'inventory',
                'sort_order' => 21,
                'is_published' => true,
            ],
            [
                'question' => 'What are stock groups, categories and units used for?',
                'answer' => 'Stock groups organise items into a hierarchy (for example by product family), stock categories provide a secondary classification (for example taxable vs non-taxable), and stock units define how quantities are measured (pcs, boxes, kg). Maintain all three under Masters > Inventory.',
                'category' => 'inventory',
                'sort_order' => 22,
                'is_published' => true,
            ],
            [
                'question' => 'How do I record inward or outward stock movement?',
                'answer' => 'Use a stock journal entry: open the stock journal, choose Inward (goods received) or Outward (goods issued), select the stock items with quantities and the godown involved, and save. Quantities and valuations update automatically.',
                'category' => 'inventory',
                'sort_order' => 23,
                'is_published' => true,
            ],
            [
                'question' => 'How do I transfer stock between godowns?',
                'answer' => 'Create a stock journal entry and choose the godown transfer option: select the item, the source godown/storage unit and the destination godown, enter the quantity and save. The item moves between godowns without affecting the books as an inward/outward pair.',
                'category' => 'inventory',
                'sort_order' => 24,
                'is_published' => true,
            ],
            [
                'question' => 'Can I track items by batch or serial number?',
                'answer' => 'Yes. Enable batch and/or serial tracking on the stock item master, then assign batch/serial numbers on the vouchers and stock journals that move the item. Reports and stock-in-hand views respect these tracks.',
                'category' => 'inventory',
                'sort_order' => 25,
                'is_published' => true,
            ],
            [
                'question' => 'How do I enter opening stock for a new fiscal year?',
                'answer' => 'When the year is opened, bring the stock forward through the opening balance / opening stock entry screens, specifying the item quantities per godown. Verify the result in the Opening Entry Report and Stock In Hand before recording live transactions.',
                'category' => 'inventory',
                'sort_order' => 26,
                'is_published' => true,
            ],

            // ------------------------------------------------------ transactions
            [
                'question' => 'How do I create a new voucher?',
                'answer' => 'Navigate to the Transactions menu and select the type of voucher you want to create. Fill in the required fields and click Save.',
                'category' => 'transactions',
                'sort_order' => 27,
                'is_published' => true,
            ],
            [
                'question' => 'How do I record a payment voucher?',
                'answer' => 'Open Transactions > Vouchers and choose Payment as the voucher type. Select the date, the ledger being paid (for example a supplier) and the bank/cash ledger, enter the amount and save. The payment appears in the day book and updates both ledgers.',
                'category' => 'transactions',
                'sort_order' => 28,
                'is_published' => true,
            ],
            [
                'question' => 'How do I record a receipt voucher?',
                'answer' => 'Open Transactions > Vouchers and choose Receipt. Select the date, the ledger from which money is received (for example a distributor) and the bank/cash ledger, enter the amount and save. Receipts are summarised in the Receipt Book report.',
                'category' => 'transactions',
                'sort_order' => 29,
                'is_published' => true,
            ],
            [
                'question' => 'How do I record a journal entry?',
                'answer' => 'Choose Journal as the voucher type in Transactions > Vouchers, then add the debit and credit ledger entries so the voucher balances. Journal vouchers are used for adjustments, provisions and transfers that are not payment or receipt transactions.',
                'category' => 'transactions',
                'sort_order' => 30,
                'is_published' => true,
            ],
            [
                'question' => 'How do I enter opening balances for ledgers?',
                'answer' => 'Use Opening Balance Setup under Transactions (or the year-end flow). Select each ledger with an opening balance, enter the amount (and stock quantities where applicable) and save. Verify with the Opening Entry Report.',
                'category' => 'transactions',
                'sort_order' => 31,
                'is_published' => true,
            ],
            [
                'question' => 'What happens after a voucher is saved?',
                'answer' => 'The voucher is posted immediately: the day book and ledgers update, stock registers change for stock items, and the Balance Sheet / Profit & Loss reflect the transaction. Review and correct any mistake on the same day to keep the books clean.',
                'category' => 'transactions',
                'sort_order' => 32,
                'is_published' => true,
            ],
            [
                'question' => 'How do I correct a wrong voucher?',
                'answer' => 'Open the voucher from the voucher list or the day book and edit or delete it. Deleting removes its effect from ledgers and stock; re-enter the corrected voucher right away.',
                'category' => 'transactions',
                'sort_order' => 33,
                'is_published' => true,
            ],

            // ------------------------------------------------------------ reports
            [
                'question' => 'How do I generate reports?',
                'answer' => 'Navigate to the Reports section from the sidebar menu. Select the report type and set the date range and other filters as needed.',
                'category' => 'reports',
                'sort_order' => 34,
                'is_published' => true,
            ],
            [
                'question' => 'What is the day book and how do I use it?',
                'answer' => 'The day book is the chronological register of every voucher. Open Reports > Day Book to review the day\'s entries in date order, filter by date/voucher type, and drill into a voucher to correct it. Day Book (Self) shows only the entries you entered.',
                'category' => 'reports',
                'sort_order' => 35,
                'is_published' => true,
            ],
            [
                'question' => 'How do I view the Balance Sheet and Profit & Loss?',
                'answer' => 'Open Reports > Balance Sheet or Profit & Loss and select the fiscal year. The statements are built from your ledger balances — the account nature assigned to each group decides whether it appears under assets, liabilities, income or expenses.',
                'category' => 'reports',
                'sort_order' => 36,
                'is_published' => true,
            ],
            [
                'question' => 'Which stock reports are available?',
                'answer' => 'Under Reports > Stock & Inventory you can view Stock In Hand by item summary, godown-wise, zone-wise, item-wise and voucher-wise, plus the Opening Entry report. Use them to reconcile physical stock with the system.',
                'category' => 'reports',
                'sort_order' => 37,
                'is_published' => true,
            ],
            [
                'question' => 'Which delivery and freight reports are available?',
                'answer' => 'Under Reports > Freight & Logistics you can view delivery notes by zone or godown and freight summaries by zone, transporter, voucher, item or godown. These reports help you track dispatches and logistics costs.',
                'category' => 'reports',
                'sort_order' => 38,
                'is_published' => true,
            ],
            [
                'question' => 'How do I export a report?',
                'answer' => 'Open the report you need and use the export option in the report toolbar to download it (Excel/PDF formats are supported on the reporting screens).',
                'category' => 'reports',
                'sort_order' => 39,
                'is_published' => true,
            ],

            // ------------------------------------------------------ administration
            [
                'question' => 'How do I create a user and assign a role?',
                'answer' => 'Go to Administration > User and add the person, then open Administration > Roles to create the roles you need. Finally, grant each role its permissions under Administration > Roles & Permissions and link the user to the role.',
                'category' => 'administration',
                'sort_order' => 40,
                'is_published' => true,
            ],
            [
                'question' => 'How do I manage user roles and permissions?',
                'answer' => 'Go to Administration > Roles to create and manage roles. You can assign specific permissions to each role.',
                'category' => 'administration',
                'sort_order' => 41,
                'is_published' => true,
            ],
            [
                'question' => 'Why are some menus hidden for a user?',
                'answer' => 'Menus are driven by permissions. If a role lacks the corresponding module or feature permission, the menu item is hidden. Check the role under Administration > Roles & Permissions, the App Module / App Features enabled for it, and the Menu Features mapping.',
                'category' => 'administration',
                'sort_order' => 42,
                'is_published' => true,
            ],
            [
                'question' => 'What do App Modules, App Features and Menu Features control?',
                'answer' => 'App Modules are the application\'s functional areas, App Features are the individual capabilities inside a module, and Menu Features tie sidebar menu items to feature permissions. Together they decide what each role can see and do.',
                'category' => 'administration',
                'sort_order' => 43,
                'is_published' => true,
            ],
            [
                'question' => 'How do I change how menus are displayed?',
                'answer' => 'Use Administration > Menu Manager to adjust the label, order and visibility of menu items without touching the underlying permissions.',
                'category' => 'administration',
                'sort_order' => 44,
                'is_published' => true,
            ],
        ];

        $seeded = 0;
        foreach ($faqs as $faq) {
            Faq::firstOrCreate(['question' => $faq['question']], $faq);
            $seeded++;
        }

        $this->command->info("FaqSeeder: {$seeded} FAQ(s) ensured.");
    }
}
