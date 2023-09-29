<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WithdrawController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data['transactions'] = Transaction::where('user_id', Auth::id())->where('transaction_type', 'withdraw')->latest()->paginate(10);
        return view('withdraw.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'amount' => 'required',
        ]);

        if ($validate->fails()) {
            $data['status'] = false;
            $data['message'] = "Invalid input contains! Please check your entries...";
            $data['errors'] = $validate->errors();
            return response(json_encode($data, JSON_PRETTY_PRINT), 400)->header('Content-Type', 'application/json');
        }

        $total_fee = 0;

        if (Auth::user()->account_type == "individual") {
            $currentDate = Carbon::now();
            $isFriday = $currentDate->dayOfWeek == Carbon::FRIDAY;
            if (!$isFriday) {
                $currentMonth = now()->month;
                $sumOfTotalAmount = Transaction::whereMonth('created_at', '=', $currentMonth)->sum('amount');
                if ($sumOfTotalAmount > 5000) {
                    if ($request->amount > 1000) {
                        $chargeable_amount = $request->amount - 1000;
                        $total_fee = (0.015 / 100) * $chargeable_amount;
                    }
                }
            }
        } else {

            $total_withdraw =  Transaction::where('user_id', Auth::id())->sum('amount');

            if ($total_withdraw >= 50000) {
                $total_fee = (0.015 / 100) * $request->amount;
            } else {
                $total_fee = (0.025 / 100) * $request->amount;
            }
        }



        if (($request->amount + $total_fee)  <= Auth::user()->balance) {


            DB::beginTransaction();
            try {
                $transaction = new Transaction();
                $transaction->date = now();
                $transaction->amount = $request->amount;
                $transaction->transaction_type = 'withdraw';
                $transaction->user_id  = Auth::id();
                $transaction->fee =  $total_fee;
                $transaction->save();

                if ($transaction) {
                    $user = User::find($transaction->user_id);
                    $user->balance = $user->balance -  ($transaction->amount + $total_fee);
                    $user->save();
                }



                DB::commit();
                $data['status'] = true;
                $data['message'] = "Withdraw successfully!";
                return response()->json($data, 200);
            } catch (\Throwable $th) {
                DB::rollBack();
                $data['status'] = false;
                $data['message'] = "Sorry, failed to save the record!";
                $data['errors'] = $th;
                return response()->json($data, 500);
            }
        } else {
            $data['message'] = "Balance Not Available!";
            $data['status'] = false;
            return response()->json($data, 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
