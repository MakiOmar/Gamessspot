<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserNoteRequest;
use App\Models\User;
use App\Models\UserNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserHistoryController extends Controller
{
    public function index(User $user)
    {
        $notes = $user->historyNotes()->with('author:id,name')->paginate(20);

        return view('manager.users.history', compact('user', 'notes'));
    }

    public function store(StoreUserNoteRequest $request, User $user)
    {
        $user->historyNotes()->create(array(
            'author_id' => $request->user('admin')->id,
            'note'      => $request->validated('note'),
        ));

        return redirect()
            ->route('manager.users.history.index', $user)
            ->with('success', 'Note added.');
    }

    public function destroy(Request $request, User $user, UserNote $note): JsonResponse
    {
        if ((int) $note->user_id !== (int) $user->id) {
            abort(404);
        }

        if (! $note->canBeDeletedBy($request->user('admin'))) {
            return response()->json(array( 'success' => false, 'message' => 'Only the author or an admin can delete this note.' ), 403);
        }

        try {
            $note->delete();
        } catch (\Throwable $e) {
            Log::error('Failed to delete user note', array( 'note_id' => $note->id, 'error' => $e->getMessage() ));

            return response()->json(array( 'success' => false, 'message' => 'Could not delete the note.' ), 500);
        }

        return response()->json(array( 'success' => true ));
    }
}
