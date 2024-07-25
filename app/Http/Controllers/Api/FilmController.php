<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Film;
use Validator;
use Storage;
use Illuminate\Http\Request;

class FilmController extends Controller
{
    public function index()
    {
        $films = Film::with(['genre', 'aktor'])->get();
        return response()->json([
            'success' => true,
            'message' => 'Data Film',
            'data' => $films,
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|unique:films',
            'deskripsi' => 'required|string',
            'foto' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'url_video' => 'required|string',
            'id_kategori' => 'required|exists:kategoris,id',
            'genre' => 'required',
            'aktor' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi Gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $path = $request->file('foto')->store('public/foto');

            $film = Film::create([
                'judul' => $request->judul,
                'deskripsi' => $request->deskripsi,
                'foto' => $path,
                'url_video' => $request->url_video,
                'id_kategori' => $request->id_kategori,
            ]);

            $film->genre()->attach($request->genre);
            $film->aktor()->attach($request->aktor);

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil disimpan',
                'data' => $film,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => ' Terjadi kesalahan',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $film = Film::with(['genre', 'aktor'])->findOrFail($id);
            return response()->json([
                'success' => true,
                'message' => 'Detail Film',
                'data' => $film,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan',
                'errors' => $e->getMessage(),
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $film = Film::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|unique:films,judul' . $id,
            'deskripsi' => 'required|string',
            'foto' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'url_video' => 'required|string',
            'id_kategori' => 'required|exists:kategoris,id',
            'genre' => 'required|array',
            'aktor' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi Gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            if ($request->hasFile('foto')) {
                // Delete old photo
                Storage::delete($film->foto);

                $path = $request->file('foto')->store('public/foto');
                $film->foto = $path;
            }

            $film->update($request->only(['judul', 'deskripsi', 'url_video', 'id_kategori']));

            if ($request->has('genre')) {
                $film->genre()->sync($request->genre);
            }

            if ($request->has('aktor')) {
                $film->aktor()->sync($request->aktor);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diperbaharui',
                'data' => $film,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $film = Film::findOrFail($id);

            // Delete photo
            Storage::delete($film->foto);

            $film->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data deleted successfully',
                'data' => null,
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found',
                'errors' => $e->getMessage(),
            ], 404);
        }
    }
}
