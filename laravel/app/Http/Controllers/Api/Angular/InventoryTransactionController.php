<?php

namespace App\Http\Controllers\Api\Angular;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class InventoryTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Collection
    {
        return InventoryTransaction::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
