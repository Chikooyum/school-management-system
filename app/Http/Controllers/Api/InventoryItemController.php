<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InventoryItemController extends Controller
{
    public function index()
    {
        $items = InventoryItem::latest()->get();

        $items->each(function ($item) {
            if ($item->photo_path) {
                $item->photo_url = asset(Storage::url($item->photo_path));
            } else {
                $item->photo_url = null;
            }
        });

        return $items;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'item_code' => 'required|string|unique:inventory_items,item_code',
            'category' => 'required|string',
            'location' => 'required|string',
            'purchase_date' => 'nullable|date',
            'price' => 'nullable|numeric',
            'status' => 'required|in:Baik,Rusak,Perlu Perbaikan',
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('public/inventory');
        }

        $item = InventoryItem::create($validated);

        // Tambahkan URL foto saat response
        if ($item->photo_path) {
            $item->photo_url = asset(Storage::url($item->photo_path));
        }

        return response()->json($item, 201);
    }

    public function update(Request $request, InventoryItem $inventoryItem)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'item_code' => 'required|string|unique:inventory_items,item_code,' . $inventoryItem->id,
            'category' => 'required|string',
            'location' => 'required|string',
            'purchase_date' => 'nullable|date',
            'price' => 'nullable|numeric',
            'status' => 'required|in:Baik,Rusak,Perlu Perbaikan',
            'photo' => 'nullable|image|max:2048',
        ]);

        // Handle foto baru jika di-upload
        if ($request->hasFile('photo')) {
            // Hapus foto lama
            if ($inventoryItem->photo_path) {
                Storage::delete($inventoryItem->photo_path);
            }
            $validated['photo_path'] = $request->file('photo')->store('public/inventory');
        }

        $inventoryItem->update($validated);

        // Tambahkan URL foto
        if ($inventoryItem->photo_path) {
            $inventoryItem->photo_url = asset(Storage::url($inventoryItem->photo_path));
        }

        return response()->json($inventoryItem);
    }

    public function destroy(InventoryItem $inventoryItem)
    {
        if ($inventoryItem->photo_path) {
            Storage::delete($inventoryItem->photo_path);
        }
        $inventoryItem->delete();
        return response()->json(null, 204);
    }
}
