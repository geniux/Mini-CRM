<?php

namespace App\Http\Controllers;

use App\Company;
use App\Http\Requests\CompanyCreateRequest;
use Illuminate\Http\Request;
use Storage;

class CompaniesController extends Controller
{
    /**
     * Display a listing of the company.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $companies = Company::where(function ($query) {
            $query->where('name', 'like', '%'.request('q').'%');
        })->paginate();

        return view('companies.index', compact('companies'));
    }

    /**
     * Show the form for creating a new company.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', new Company);

        return view('companies.create');
    }

    /**
     * Store a newly created company in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CompanyCreateRequest $request)
    {
        $newCompany = $request->validated();
        $newCompany['creator_id'] = auth()->id();

        $company = Company::create($newCompany);

        return redirect()->route('companies.show', $company);
    }

    /**
     * Display the specified company.
     *
     * @param  \App\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function show(Company $company)
    {
        $employees = $company->employees()->where(function ($query) {
            $searchQuery = request('q');
            $query->where('first_name', 'like', '%'.$searchQuery.'%');
            $query->orWhere('last_name', 'like', '%'.$searchQuery.'%');
        })->paginate();

        return view('companies.show', compact('company', 'employees'));
    }

    /**
     * Show the form for editing the specified company.
     *
     * @param  \App\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function edit(Company $company)
    {
        $this->authorize('update', $company);

        return view('companies.edit', compact('company'));
    }

    /**
     * Update the specified company in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Company $company)
    {
        $this->authorize('update', $company);

        $companyData = $request->validate([
            'name'    => 'required|max:60',
            'email'   => 'required|email|max:255',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|max:255',
        ]);

        $company->update($companyData);

        return redirect()->route('companies.show', $company);
    }

    /**
     * Remove the specified company from storage.
     *
     * @param  \App\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function destroy(Company $company)
    {
        $this->authorize('delete', $company);

        $this->validate(request(), [
            'company_id' => 'required',
        ]);

        $routeParam = request()->only('page', 'q');

        if (request('company_id') == $company->id && $company->delete()) {
            return redirect()->route('companies.index', $routeParam);
        }

        return back();
    }

    public function logoUpload(Request $request, Company $company)
    {
        $request->validate([
            'logo' => 'required|image|dimensions:min_width=100,min_height=100',
        ]);

        $disk = env('APP_ENV') == 'testing' ? 'avatars' : 'public';

        if (Storage::disk($disk)->exists($company->logo)) {
            Storage::disk($disk)->delete($company->logo);
        }

        $company->logo = $request->logo->store('', $disk);
        $company->save();

        return back();
    }
}
