<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\Rest\Client;

class TodoController extends Controller
{
    public function getTodo()
    {
        $account_sid = getenv("TWILIO_SID");
        $auth_token = getenv("TWILIO_AUTH_TOKEN");
        $sync_sid = getenv("TWILIO_SYNC_SID");

        $client = new Client($account_sid, $auth_token);

        $documents = $client->sync->v1->services($sync_sid)
            ->documents->read();
        $data = [];
        foreach ($documents as $record) {
            $todo = $record->data;
            $todo['sid'] = $record->sid;
            array_push($data, $todo);
        }
        return response()
            ->json($data);

    }

    public function createTodo(Request $request)
    {
        $validatedData = $request->validate([
            'body' => 'required',
        ]);

        $account_sid = getenv("TWILIO_SID");
        $auth_token = getenv("TWILIO_AUTH_TOKEN");
        $sync_sid = getenv("TWILIO_SYNC_SID");

        $client = new Client($account_sid, $auth_token);
        $newTodo = $client->sync->v1->services($sync_sid)
            ->documents
            ->create(array(
                "data" => array(
                    "created_at" => now(),
                    "body" => $validatedData['body'],
                    "isDone" => false,
                ),
            ));
        $todo = $newTodo->data;
        $todo["sid"] = $newTodo->sid;
        return response()
            ->json($todo);

    }

    public function updateTodo(Request $request, $sid)
    {
        $validatedData = $request->validate([
            'body' => 'required',
            'isDone' => 'required',
            'created_at' => 'required',
         ]);

        $account_sid = getenv("TWILIO_SID");
        $auth_token = getenv("TWILIO_AUTH_TOKEN");
        $sync_sid = getenv("TWILIO_SYNC_SID");

        $client = new Client($account_sid, $auth_token);
        $newTodo = $client->sync->v1->services($sync_sid)
            ->documents($sid)
            ->update(array(
                "data" => array(
                    "sid" => $sid,
                    "body" => $validatedData['body'],
                    "isDone" => $validatedData["isDone"],
                    "created_at" => $validatedData["created_at"],
                ),
            ));
        return response()
            ->json($newTodo->data);

    }

    public function deleteTodo($sid)
    {
        $account_sid = getenv("TWILIO_SID");
        $auth_token = getenv("TWILIO_AUTH_TOKEN");
        $sync_sid = getenv("TWILIO_SYNC_SID");

        $client = new Client($account_sid, $auth_token);
        $client->sync->v1->services($sync_sid)
            ->documents($sid)
            ->delete();

        return response()
            ->json(["message" => "Todo sucessfully deleted!"]);

    }
}
