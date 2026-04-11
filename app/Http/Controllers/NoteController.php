<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\Patient;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'notable_type' => 'required|string',
            'notable_id'   => 'required|integer',
            'note_type'    => 'required|string',
            'content'      => 'required|string|max:3000',
            'is_flagged'   => 'nullable|boolean',
            'is_private'   => 'nullable|boolean',
        ]);

        $company  = \App\Models\Company::first();
        $branchId = auth()->user()->primary_branch_id;

        // Resolve the notable model class
        $typeMap = [
            'patient'     => Patient::class,
            'App\\Models\\Patient' => Patient::class,
        ];
        $notableClass = $typeMap[$data['notable_type']] ?? $data['notable_type'];

        Note::create([
            'company_id'   => $company->id,
            'branch_id'    => $branchId,
            'notable_type' => $notableClass,
            'notable_id'   => $data['notable_id'],
            'note_type'    => $data['note_type'],
            'content'      => $data['content'],
            'is_flagged'   => $request->boolean('is_flagged'),
            'is_private'   => $request->boolean('is_private'),
            'created_by'   => auth()->id(),
            'updated_by'   => auth()->id(),
        ]);

        return back()->with('success', 'Note added successfully.');
    }

    public function update(Request $request, Note $note)
    {
        $this->authorizeNoteAction($note);

        $data = $request->validate([
            'note_type' => 'required|string',
            'content'   => 'required|string|max:3000',
            'is_flagged'=> 'nullable|boolean',
            'is_private'=> 'nullable|boolean',
        ]);

        $note->update([
            'note_type'  => $data['note_type'],
            'content'    => $data['content'],
            'is_flagged' => $request->boolean('is_flagged'),
            'is_private' => $request->boolean('is_private'),
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Note updated.');
    }

    public function destroy(Note $note)
    {
        $this->authorizeNoteAction($note);
        $note->delete();
        return back()->with('success', 'Note deleted.');
    }

    /**
     * Only the note author OR elevated roles (admin, manager, doctor, nurse) can edit/delete.
     */
    private function authorizeNoteAction(Note $note): void
    {
        $user = auth()->user();
        $isOwner   = $note->created_by === $user->id;
        $elevated  = $user->isSuperAdmin() || $user->isRole('branch_manager', 'doctor', 'nurse');

        if (!$isOwner && !$elevated) {
            abort(403, 'You can only edit or delete your own notes.');
        }
    }
}
