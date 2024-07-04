<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use App\Models\Bill;
use App\Models\BillUser;

class AssignBills extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:assign-bills';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        echo 'Finding unassigned bills...' . PHP_EOL;

        while (true) {
            /**
             * Grab any bills with a status of "Submitted" that don't have
             * a corresponding entry in the bill_users table
             */
            $bills = Bill::leftJoin('bill_user', 'bills.id', '=', 'bill_user.bill_id')
                ->whereNull('bill_user.bill_id')
                ->where('bills.bill_stage_id', '=', 2)
                ->select('bills.id')
                ->get();

            // If there are no bills to assign then we're done
            if (count($bills) < 1) {
                echo 'All bills assigned';
                break;
            }

            $billId = $bills[0]->id;
            echo 'Bill ID ' .  $billId . ' needs assigning' . PHP_EOL;

            /**
             * Grab our list of users and the number of "active" bills they have
             * assigned to them. This assumes "On Hold", "Rejected" and "Paid"
             * can be ignored
            */ 
            $userBillCounts = DB::table('bill_user')
                ->select('bill_user.user_id', DB::raw('count(bill_user.id) as numbills'))
                ->leftJoin('bills', 'bill_user.bill_id', '=', 'bills.id')
                ->where('bills.bill_stage_id', '<', 5)
                ->groupBy('bill_user.user_id')
                ->orderBy('numbills')
                ->get();

            // If there are no users with less than 3 assigned bills then we're done
            if ($userBillCounts[0]->numbills > 3) {
                echo 'All users have 3 or more bills';
                break;
            }

            $userId = $userBillCounts[0]->user_id;

            $newBillUserId = DB::table('bill_user')->insertGetId([
                'bill_id' => $billId,
                'user_id' => $userId,
            ]);

            if ($newBillUserId) {
                echo 'Bill ID ' .  $billId . ' assigned' . PHP_EOL;
            } else {
                echo 'Failed to create bill';
                break;
            }
        }
    }

}
