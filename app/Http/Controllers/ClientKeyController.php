<?php

namespace App\Http\Controllers;

use App\Models\ClientKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;

class ClientKeyController extends Controller
{
    public function index()
    {
        return ClientKey::all(); // List all keys
    }

    public function store(Request $request)
    {
        $request->validate([
            'peer_id' => 'required|unique:client_keys',
            'key_value' => 'required',
        ]);

        return ClientKey::create($request->only(['peer_id', 'key_value']));
    }

    public function show($id)
    {
        return ClientKey::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $key = ClientKey::findOrFail($id);
        $key->update($request->only(['peer_id', 'key_value', 'availability']));
        return $key;
    }

    public function destroy($id)
    {
        ClientKey::destroy($id);
        return response()->json(['message' => 'Deleted']);
    }

    //connect device to available key and change availability status to FALSE
    public function claimAvailableKey(): JsonResponse
    {
        // Use a transaction and lock the row for update
        $clientKey = DB::transaction(function () {
            $key = \App\Models\ClientKey::where('availability', true)
                ->lockForUpdate()
                ->first();

            if (!$key) {
                return null;
            }

            $key->availability = false;
            $key->save();

            return $key;
        });

        if (!$clientKey) {
            return response()->json(['message' => 'No available keys.'], 404);
        }

        return response()->json([
            'key_value' => $clientKey->key_value,
            'peer_id' => $clientKey->peer_id,
        ]);
    }

    //disconnect device from key and change availability status to TRUE
    public function disconnectKey(Request $request)
    {
        $request->validate([
            'peer_id' => 'required|string|exists:client_keys,peer_id',
        ]);

        $clientKey = \App\Models\ClientKey::where('peer_id', $request->peer_id)->first();

        if ($clientKey->availability === true) {
            return response()->json(['message' => 'Key already marked as available.'], 200);
        }

        $clientKey->availability = true;
        $clientKey->save();

        return response()->json(['message' => 'Disconnected.']);
    }

    public function importPeersFromCustomPath(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $basePath = $request->input('path');

        if (!is_dir($basePath)) {
            return response()->json(['message' => 'Directory not found.'], 404);
        }

        $peerDirs = File::directories($basePath);
        $imported = 0;
        $skipped = [];

        foreach ($peerDirs as $dirPath) {
            $confFiles = File::files($dirPath);

            $confFile = collect($confFiles)->first(fn($f) => str_ends_with($f->getFilename(), '.conf'));
            $pubKeyFile = collect($confFiles)->first(fn($f) => str_starts_with($f->getFilename(), 'publickey-'));

            if (!$confFile || !$pubKeyFile) {
                $skipped[] = basename($dirPath) . ' (missing files)';
                continue;
            }

            $key_value = File::get($confFile);
            $peer_id = trim(File::get($pubKeyFile));

            if (ClientKey::where('peer_id', $peer_id)->exists()) {
                $skipped[] = basename($dirPath) . ' (already exists)';
                continue;
            }

            ClientKey::create([
                'peer_id' => $peer_id,
                'key_value' => $key_value,
                'availability' => true,
            ]);

            $imported++;
        }

        return response()->json([
            'message' => 'Import complete.',
            'imported' => $imported,
            'skipped' => $skipped,
        ]);
    }

    public function resetAllAvailability()
    {
        \App\Models\ClientKey::query()->update(['availability' => true]);

        return response()->json([
            'message' => 'All key reset to true.'
        ]);
    }


}
