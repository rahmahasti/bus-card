<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Card;
use App\Models\Transaction;

class CardController extends Controller
{
    // Method untuk generate ID kartu
    protected function generateCardId()
    {
        do {
            // Generate 8 digit unique ID
            $card_id = str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
        } while (Card::where('card_id', $card_id)->exists());

        return $card_id;
    }

    // Endpoint untuk membuat kartu baru
    public function createCard(Request $request)
    {
        $card_id = $this->generateCardId();

        $card = Card::create([
            'card_id' => $card_id,
            'balance' => 0
        ]);

        return response()->json([
            'message' => 'Kartu berhasil dibuat',
            'card_id' => $card_id
        ], 201);
    }

    // Endpoint: GET /api/card/balance/{card_id}
    public function getBalance($card_id)
    {
        // Validasi format card_id
        if (!preg_match('/^\d{8}$/', $card_id)) {
            return response()->json(['error' => 'Invalid card ID format'], 400);
        }

        $card = Card::where('card_id', $card_id)->first();

        if (!$card) {
            return response()->json(['error' => 'Card not found'], 404);
        }

        return response()->json(['card_id' => $card->card_id, 'balance' => $card->balance]);
    }

    // Endpoint: POST /api/card/topup
    public function topUp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'card_id' => 'required|string|size:8|exists:cards,card_id',
            'amount' => 'required|numeric|min:1000|max:1000000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation Error',
                'details' => $validator->errors()
            ], 400);
        }

        try {
            $card = Card::where('card_id', $request->card_id)->lockForUpdate()->first();

            if (!$card) {
                return response()->json(['error' => 'Kartu tidak ditemukan'], 404);
            }

            $amount = $request->amount;
            $card->balance += $amount;
            $card->save();

            // Tambahkan transaction tanpa timestamps
            $transaction = new Transaction();
            $transaction->card_id = $card->card_id;
            $transaction->amount = $amount;
            $transaction->description = 'Top-up saldo';
            $transaction->transaction_date = now();
            $transaction->save();

            return response()->json([
                'message' => 'Top-up berhasil',
                'new_balance' => $card->balance
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Gagal melakukan top up',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    // Endpoint: GET /api/card/transactions/{card_id}
    public function getTransactions($card_id)
    {
        // Validasi format card_id
        if (!preg_match('/^\d{8}$/', $card_id)) {
            return response()->json(['error' => 'Invalid card ID format'], 400);
        }

        $card = Card::where('card_id', $card_id)->first();

        if (!$card) {
            return response()->json(['error' => 'Card not found'], 404);
        }

        $transactions = $card->transactions;

        return response()->json($transactions);
    }
    
    // Endpoint: POST /api/card/pay
    public function pay(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'card_id' => 'required|string|size:8|exists:cards,card_id',
            'fare' => 'required|numeric|min:1000|max:100000'
        ], [
            'card_id.exists' => 'Kartu tidak ditemukan',
            'fare.min' => 'Biaya minimal Rp 1.000',
            'fare.max' => 'Biaya maksimal Rp 100.000'
        ]);

        // Jika validasi gagal
        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation Error',
                'details' => $validator->errors()
            ], 400);
        }

        try {
            // Cari kartu dan lock untuk update
            $card = Card::where('card_id', $request->card_id)->lockForUpdate()->first();

            if (!$card) {
                return response()->json(['error' => 'Kartu tidak ditemukan'], 404);
            }

            $fare = $request->fare;

            // Periksa saldo
            if ($card->balance < $fare) {
                return response()->json([
                    'error' => 'Saldo tidak mencukupi', 
                    'current_balance' => $card->balance
                ], 400);
            }

            // Kurangi saldo
            $card->balance -= $fare;
            $card->save();

            // Catat transaksi
            $transaction = new Transaction();
            $transaction->card_id = $card->card_id;
            $transaction->amount = -$fare;
            $transaction->description = 'Pembayaran tiket';
            $transaction->transaction_date = now();
            $transaction->save();

            // Kembalikan respons
            return response()->json([
                'message' => 'Pembayaran berhasil',
                'remaining_balance' => $card->balance,
                'transaction_id' => $transaction->id
            ], 200);

        } catch (\Exception $e) {
            // Tangani error yang tidak terduga
            return response()->json([
                'error' => 'Gagal melakukan pembayaran',
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
