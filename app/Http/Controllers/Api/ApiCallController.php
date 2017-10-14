<?php

namespace App\Http\Controllers\Api;

use App\Call;
use Validator;
use App\Contact;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use App\Http\Resources\Call\Call as CallResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ApiCallController extends ApiController
{
    /**
     * Get the list of notes.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $calls = auth()->user()->account->calls()
                                ->paginate($this->getLimitPerPage());

        return CallResource::collection($calls);
    }

    /**
     * Get the detail of a given call
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $callId)
    {
        try {
            $call = Call::where('account_id', auth()->user()->account_id)
                ->where('id', $callId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new CallResource($call);
    }

    /**
     * Store the call
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'content' => 'required|max:100000',
            'called_at' => 'required|date',
            'contact_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
           return $this->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $request->input('contact_id'))
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        try {
            $call = Call::create($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        $call->account_id = auth()->user()->account->id;
        $call->save();

        return new CallResource($call);
    }

    /**
     * Update the note
     * @param  Request $request
     * @param  Integer $callId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $callId)
    {
        try {
            $call = Call::where('account_id', auth()->user()->account_id)
                ->where('id', $callId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'content' => 'required|max:100000',
            'called_at' => 'required|date',
            'contact_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
           return $this->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $request->input('contact_id'))
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        try {
            $call->update($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        return new CallResource($call);
    }

    /**
     * Delete a note
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $callId)
    {
        try {
            $call = Call::where('account_id', auth()->user()->account_id)
                ->where('id', $callId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $call->delete();

        return $this->respondObjectDeleted($call->id);
    }

    /**
     * Get the list of calls for the given contact.
     *
     * @return \Illuminate\Http\Response
     */
    public function calls(Request $request, $contactId)
    {
        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $contactId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $calls = $contact->calls()
                ->paginate($this->getLimitPerPage());

        return CallResource::collection($calls);
    }
}