<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $setting = \Helper::getSetting();

        // dados internos (cupons, regras de comissão/rollover) não vão para o navegador
        $public = $setting ? (clone $setting)->makeHidden([
            'cupom', 'cupomvalor', 'ngr_percent', 'revshare_reverse', 'perc_sub_lv1', 'perc_sub_lv2', 'perc_sub_lv3',
            'rollover_protection', 'suitpay_is_enable', 'bspay_is_enable', 'sharkpay_is_enable', 'digitopay_is_enable',
            'asaas_is_enable', 'mercadopago_is_enable', 'default_gateway', 'storage',
        ]) : null;

        return response()->json(['setting' => $public], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
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
