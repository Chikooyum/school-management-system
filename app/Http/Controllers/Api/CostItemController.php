<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CostItem; // <-- Import model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CostItemController extends Controller
{
    /**
     * Menampilkan semua item biaya.
     * GET /api/cost-items
     */
    public function index()
    {
        return response()->json(CostItem::latest()->get());
    }

    /**
     * Menyimpan item biaya baru.
     * POST /api/cost-items
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:cost_items,name',
            'type' => 'required|in:Tetap,Dinamis',
            'amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $costItem = CostItem::create($validator->validated());

        return response()->json($costItem, 201);
    }

    /**
     * Menampilkan detail satu item biaya.
     * GET /api/cost-items/{id}
     */
    public function show(CostItem $costItem)
    {
        return response()->json($costItem);
    }

    /**
     * Memperbarui item biaya.
     * PUT /api/cost-items/{id}
     */
    public function update(Request $request, CostItem $costItem)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255|unique:cost_items,name,' . $costItem->id,
            'type' => 'sometimes|required|in:Tetap,Dinamis',
            'amount' => 'sometimes|required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $costItem->update($validator->validated());

        return response()->json($costItem);
    }

    /**
     * Menghapus item biaya.
     * DELETE /api/cost-items/{id}
     */
    public function destroy(CostItem $costItem)
    {
        // Tambahkan pengecekan jika item biaya sudah terpakai di tagihan
        if ($costItem->studentBills()->exists()) {
            return response()->json([
                'message' => 'Item biaya ini tidak bisa dihapus karena sudah digunakan dalam tagihan siswa.'
            ], 409); // 409 Conflict
        }

        $costItem->delete();

        return response()->json(null, 204);
    }

    /**
     * Mengubah status aktif/nonaktif sebuah item biaya.
     * POST /api/cost-items/{id}/toggle
     */
    public function toggleStatus(CostItem $costItem)
    {
        // Hanya biaya dinamis yang bisa di-toggle
        if ($costItem->type !== 'Dinamis') {
            return response()->json(['message' => 'Hanya biaya dinamis yang bisa diubah statusnya.'], 400);
        }

        $costItem->is_active = !$costItem->is_active;
        $costItem->save();

        return response()->json($costItem);
    }
}
