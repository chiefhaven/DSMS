<?php

namespace App\Http\Controllers;

use App\Models\expenseCategory;
use App\Http\Requests\StoreexpenseCategoryRequest;
use App\Http\Requests\UpdateexpenseCategoryRequest;

class ExpenseCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
     * @param  \App\Http\Requests\StoreexpenseCategoryRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreexpenseCategoryRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\expenseCategory  $expenseCategory
     * @return \Illuminate\Http\Response
     */
    public function show(expenseCategory $expenseCategory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\expenseCategory  $expenseCategory
     * @return \Illuminate\Http\Response
     */
    public function edit(expenseCategory $expenseCategory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateexpenseCategoryRequest  $request
     * @param  \App\Models\expenseCategory  $expenseCategory
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateexpenseCategoryRequest $request, expenseCategory $expenseCategory)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\expenseCategory  $expenseCategory
     * @return \Illuminate\Http\Response
     */
    public function destroy(expenseCategory $expenseCategory)
    {
        //
    }
}
