<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use App\Http\Requests\MerchantRequest;
use App\Models\Merchant;
use Yajra\DataTables\Facades\DataTables;

class MerchantController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->ajax()) {
            $query = Merchant::query();

            $data = DataTables::of($query)
                ->addColumn('action', function ($item) {
                    return '
                        <a class="inline-block border border-gray-700 bg-gray-700 text-white rounded-md px-2 py-1 m-1 transition duration-500 ease select-none hover:bg-gray-800 focus:outline-none focus:shadow-outline" 
                            href="' . route('dashboard.merchant.edit', $item->id) . '">
                            Edit
                        </a>
                        <form class="inline-block" action="' . route('dashboard.merchant.destroy', $item->id) . '" method="POST">
                        <button class="border border-red-500 bg-red-500 text-white rounded-md px-2 py-1 m-2 transition duration-500 ease select-none hover:bg-red-600 focus:outline-none focus:shadow-outline" >
                            Hapus
                        </button>
                            ' . method_field('delete') . csrf_field() . '
                        </form>';
                })
                ->editColumn('price', function ($item) {
                    return number_format($item->price);
                })
                ->rawColumns(['action'])
                ->make();

            // dd($data);
            return $data;
        }

        return view('pages.dashboard.merchant.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('pages.dashboard.merchant.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(MerchantRequest $request)
    {
        $data = $request->all();
        $data['slug'] = Str::slug($request->name);
        $profile_photo_path = $request->file('profile_photo_path');

        if ($request->hasFile('profile_photo_path')) {
            $data['profile_photo_path'] = $profile_photo_path->store('public/merchant');;
        }

        Merchant::create($data);

        return redirect()->route('dashboard.merchant.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Merchant  $merchant
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function show(Merchant $merchant)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Merchant  $merchant
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function edit(Merchant $merchant)
    {

        return view('pages.dashboard.merchant.edit', [
            'item' => $merchant
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Merchant  $merchant
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(MerchantRequest $request, Merchant $merchant)
    {
        $data = $request->all();
        $profile_photo_path = $request->file('profile_photo_path');
        if ($request->hasFile('profile_photo_path')) {
            $data['profile_photo_path'] = $profile_photo_path->store('public/merchant');;
        }

        $merchant->update($data);

        return redirect()->route('dashboard.merchant.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Merchant  $merchant
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Merchant $merchant)
    {
        $merchant->delete();

        return redirect()->route('dashboard.merchant.index');
    }
}
