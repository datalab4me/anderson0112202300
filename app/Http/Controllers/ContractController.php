<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Estimate;
use App\Models\Contract;
use App\Models\ContractModel;
use App\Models\Referral;
use App\Models\Package;
use App\Models\PartyTime;
use App\Models\Observation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ContractController extends Controller
{
    private $repository;

    public function __construct(Contract $contract) {
        $this->repository = $contract;
    }

    public function index() {
        $contracts = $this->repository->all();

        foreach ($contracts as $contract) {
            $contract->dayOfWeek = Carbon::parse($contract->date);
            $contract->dayOfWeek = $contract->dayOfWeek->translatedFormat('l');
        }

        return view('pages.Contracts.index', [
            'contracts' => $contracts,
        ]);
    }

    public function create() {
        $signalPercentage = Cache::get('signal');
        $referrals = Referral::all();
        $packages = Package::all();

        $partyTimes = PartyTime::all();
        $observations = Observation::all();

        return view('pages.Contracts.create', [
            'referrals' => $referrals,
            'packages' => $packages,
            'partyTimes' => $partyTimes,
            'observations' => $observations,
            'signalPercentage' => $signalPercentage,
        ]);
    }

    public function store(Request $request) {
        $contract = new Contract;

        $contract->name = $request->name;
        $contract->birthday = $request->birthday;
        $contract->gender = $request->gender;
        $contract->date = $request->date;
        $contract->age = date_create($request->birthday)->diff(date_create($request->date))->y;

        $contract->package_id = $request->package;
        $contract->referral_id = $request->referral;
        $contract->frespname = $request->frespname;
        $contract->frespcel = $request->frespcel;
        $contract->frespemail = $request->frespemail;
        $contract->srespname = $request->srespname;
        $contract->srespcel = $request->srespcel;

        $contract->total = $request->total;
        $contract->signal = $request->signal;
        $contract->division = $request->division;
        $contract->installments = $request->installments;


        $partyTime = PartyTime::find($request->partyTime_id);

        $contract->inittime = $partyTime->start;
        $contract->endtime = $partyTime->end;

        $contract->icomment = $request->icomment;
        $contract->ecomment = $request->ecomment;
        $contract->estimate_id = $request->estimate;



        $contract->save();
        $contract->cod = strval($contract->id + 1037);
        $contract->save();

        $contract->observations()->sync($request->observations);

        return redirect()->route('contracts.index');

    }

    public function edit($id) {
        $contract = $this->repository->find($id);
        if (!$contract) {
            return redirect()->route('contracts.index');
        }

        $signalPercentage = Cache::get('signal');
        $referrals = Referral::all();
        $packages = Package::all();

        $partyTimes = PartyTime::all();
        $observations = Observation::all();

        return view('pages.Contracts.edit', [
            'referrals' => $referrals,
            'packages' => $packages,
            'partyTimes' => $partyTimes,
            'observations' => $observations,
            'signalPercentage' => $signalPercentage,
            'contract' => $contract,
        ]);
    }

    public function update(Request $request, $id){
        $contract = Contract::find($id);

        $contract->name = $request->name;
        $contract->birthday = $request->birthday;
        $contract->gender = $request->gender;
        $contract->date = $request->date;
        $contract->age = date_create($request->birthday)->diff(date_create($request->date))->y;

        $contract->package_id = $request->package;
        $contract->referral_id = $request->referral;
        $contract->frespname = $request->frespname;
        $contract->frespcel = $request->frespcel;
        $contract->frespemail = $request->frespemail;
        $contract->srespname = $request->srespname;
        $contract->srespcel = $request->srespcel;

        $contract->total = $request->total;
        $contract->signal = $request->signal;
        $contract->division = $request->division;
        $contract->installments = $request->installments;


        $partyTime = PartyTime::find($request->partyTime_id);

        $contract->inittime = $partyTime->start;
        $contract->endtime = $partyTime->end;

        $contract->icomment = $request->icomment;
        $contract->ecomment = $request->ecomment;

        $contract->save();

        $contract->observations()->sync($request->observations);


        if (!$id) {
            return back();
        }



        // $employee->jobfunctions()->sync($request->jobfunctions);

        return redirect()->route('contracts.index');
    }

    public function show($id){
        $contract = $this->repository->find($id);

        if (!$contract) { return back(); }

        $contract->dayOfWeek = Carbon::parse($contract->date);
        $contract->dayOfWeek = $contract->dayOfWeek->translatedFormat('l');

        return view('pages.Contracts.show', [
            'contract' => $contract,
        ]);
    }

    public function search(){
        return view('pages.Contracts.search');
    }

    public function results(Request $request){

        $name = $request->name;
        $date = $request->date;
        $birthday = $request->birthday;
        $cod = $request->cod;

        // Invalida pesquisa nula. Precisa refatorar
        if($request->name === null) $name = "dshushdk";
        if($request->cod === null) $cod = "dshushdk";
        if($request->date === null) $date = "dshushdk";
        if($request->birthday === null) $birthday = "dshushdk";



        $contracts = $this->repository->where('name', 'LIKE', "%$name%")
                                    ->orWhere('cod', $cod)
                                    ->orWhere('date', $date)
                                    ->orWhere('birthday', $birthday)
                                    ->get();


        foreach ($contracts as $contract) {
            $contract->dayOfWeek = Carbon::parse($contract->date);
            $contract->dayOfWeek = $contract->dayOfWeek->translatedFormat('l');
        }

        return view('pages.Contracts.index', [
            'contracts' => $contracts
        ]);
    }
    
    
    public function index_calendar() {
        $contracts = $this->repository->with('package')->get();

        foreach ($contracts as $contract) {
            $contract->dayOfWeek = Carbon::parse($contract->date);
            $contract->dayOfWeek = $contract->dayOfWeek->translatedFormat('l');
        }

        return view('pages.Contracts.calendar', [
            'contracts' => $contracts,
        ]);
    }
    
    
    public function generate(Request $request) {
        
        
        if($_POST):
            
            $contract_id = $request->contract;
            $model = $request->model;
            $template = $model;
            
            $contract = Contract::find($contract_id);
            
            $cod = $contract->cod;
            $name = $contract->name;
            
            $birthday = $contract->birthday;
            $birthday_arr = explode("-", $birthday);
            $birthday = $birthday_arr[2].'/'.$birthday_arr[1].'/'.$birthday_arr[0];
            
            $date = $contract->date;
            $date_arr = explode("-", $date);
            $date = $date_arr[2].'/'.$date_arr[1].'/'.$date_arr[0];
            
            $inittime = substr($contract->inittime, 0, 5);
            $endtime = substr($contract->endtime, 0, 5);

            $frespname = $contract->frespname;
            $frespcel = $contract->frespcel;
            $frespemail = $contract->frespemail;
            $total = $contract->total;
            $signal = $contract->signal;
            $package_name = $contract->package->name;
            $gests = $contract->package->gests;
            
            $model = str_replace('[!CODIGO!]', $cod, $model);
            $model = str_replace('[!NOME!]', $name, $model);
            $model = str_replace('[!NASCIMENTO!]', $birthday, $model);
            $model = str_replace('[!EVENTO_DATA!]', $date, $model);
            $model = str_replace('[!EVENTO_INICIO!]', $inittime, $model);
            $model = str_replace('[!EVENTO_FINAL!]', $endtime, $model);
            $model = str_replace('[!RESPONSAVEL_NOME!]', $frespname, $model);
            $model = str_replace('[!RESPONSAVEL_CELULAR!]', $frespcel, $model);
            $model = str_replace('[!RESPONSAVEL_EMAIL!]', $frespemail, $model);
            $model = str_replace('[!EVENTO_VALOR!]', $total, $model);
            $model = str_replace('[!EVENTO_SINAL!]', $signal, $model);
            $model = str_replace('[!EVENTO_NOME!]', $package_name, $model);
            $model = str_replace('[!EVENTO_CONVIDADOS!]', $gests, $model);
            
            
            $contract->contract_template = $template;
            $contract->contract_finish = $model;
            $contract->save();
            
            return redirect()->route('contracts.index');
            
        endif;
 
        
        $signalPercentage = Cache::get('signal');
        $referrals = Referral::all();
        $packages = Package::all();

        $partyTimes = PartyTime::all();
        $observations = Observation::all();

        $contracts = $this->repository->with('package')->get();
        
        $contract_id = 1;
        $contrato_padrao = ContractModel::find($contract_id);
        
        
        return view('pages.Contracts.template', [
            'contract_model' => $contrato_padrao,
            'contracts' => $contracts,
            'referrals' => $referrals,
            'observations' => $observations,
            'signalPercentage' => $signalPercentage,
        ]);
    }
    
    public function generated($id) {
        
        $contract = Contract::find($id);

        return view('pages.Contracts.generated', [
            'contract' => $contract,
        ]);
    }
    
    public function model(Request $request) {
        
        $contract_id = 1;
        
        if($_POST):
            
            $conteudo = $request->conteudo;
            $conteudo = str_replace("'", "", $conteudo);
            
            $contract = ContractModel::find($contract_id);
            $contract->conteudo = $conteudo;
            $contract->save();
            
            return redirect()->route('contracts.model');
            
        endif;
 
        
        $contract = ContractModel::find($contract_id);
        
        
        return view('pages.Contracts.model', [
            'contract' => $contract
        ]);
    }
}
