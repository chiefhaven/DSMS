<?php

namespace App\Http\Controllers;

use App\Models\expense;

class ExpenseResourceController extends ExpenseController
{
    public function show(expense $expense)
    {
        return $this->showPage($expense);
    }
}
