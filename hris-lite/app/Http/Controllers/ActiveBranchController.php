<?php

namespace App\Http\Controllers;

use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActiveBranchController extends Controller
{
    /**
     * Switch the active branch (null = "all branches"). The selection is
     * validated against what the user may access and stored in the session;
     * the redirect reloads the page with the newly scoped data.
     */
    public function update(Request $request, BranchContext $context): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer'],
        ]);

        $branchId = $validated['branch_id'] ?? null;

        abort_unless($context->canActivate($branchId), 403);

        $context->setActive($branchId);

        return back();
    }
}
