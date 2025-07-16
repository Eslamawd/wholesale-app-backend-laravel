<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    //
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
         $payments = $request->user()->payments()->latest()->paginate(6);
        return response()->json(['payments' => $payments]);
    }
  public function getAllPay()
{
    $payments = Payment::with('user')->paginate(6);

    return response()->json(['payments' => $payments]);
}



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
       $user = $request->user(); 

     $validated = $request->validate([
        'balance' => 'required|integer|min:1',
    ]);
    
    $payload = [
        'balance' => $validated['balance'],
        'user_id' => $user->id
    ];

    $payment = Payment::create($payload);
     return response()->json(['payment' => $payment]);
    }

   
    public function addBalance ($id) {
        $payment = Payment::findOrFail($id);
        $user = User::findOrFail($payment->user_id);
        $user->deposit($payment->balance);
        $user->save();
        $payment->delete();
        return response()->json(['message' => 'Add New Deposit Sucssess']);
    }
}
