<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ProductAudience;
use Illuminate\Contracts\View\View;

class AudienceController extends Controller
{
    public function show(ProductAudience $audience): View
    {
        $audience->load(['products' => fn ($query) => $query->where('available', true)]);

        return view('pages.audience', ['audience' => $audience]);
    }
}
