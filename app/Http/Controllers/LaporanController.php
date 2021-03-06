<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Session;

use App\Acara;
use App\Peserta;
use App\Penyertaan;
use App\Agensi;
use App\Pengesahan;
use App\Kontinjen;

use DB;

class LaporanController extends Controller
{
    public function __construct() {

        $this->middleware('auth');
    }

    public function keseluruhan() {

        if(Auth::user()->status == 0) {
            Session::flush();
            Session::flash('error', 'Ralat.');
            Auth::logout();
            return redirect()->route('login');
        }
        
    	$pesertas = Peserta::where('agensi_id', Auth::user()->agensi->id)
                    ->orderBy('vege', 'desc')
                    ->orderBy('jantina', 'asc')
                    ->orderBy('tarafJawatan', 'desc')
                    ->orderBy('nama', 'asc')
                    ->get();

        return View('members.laporan.keseluruhan', compact('pesertas'));
    }

    // Members
    public function acaraKeseluruhan() {
    	
    	$acaras = Acara::orderBy('nama', 'asc')->get();

    	return view('members.laporan.acara-keseluruhan', compact('acaras'));
    }

    // ADMIN
    public function penginapan() {

        $agencies = Agensi::all();
        $acaras = Acara::all();

        foreach($agencies as $agency){

            // echo "<h2>Agensi : " . $agency->nama . '</h2><br />';

            foreach($acaras as $acara) {

                // echo "<h3>Acara : " . $acara->nama . '</h3><br />';

                $countLelaki = $acara->peserta->where('agensi_id', $agency->id)
                                ->where('jantina', 'LELAKI')
                                ->count();

                $countWanita = $acara->peserta->where('agensi_id', $agency->id)
                                ->where('jantina', 'WANITA')
                                ->count();

                // echo "LELAKI : " . $countLelaki . '<br />';
                // echo "WANITA : " . $countWanita . '<br />';
            }

            // echo '<br />';
        }

        return view('members.laporan.penginapan', compact('agencies', 'acaras'));
    }

    public function senaraiSemak() {

        $ringkasan = Array();

        $pengesahan     = Pengesahan::where('agensi_id', Auth::user()->agensi->id)->first();

        if($pengesahan == null) {
            $pengesahan = new Pengesahan;
            $pengesahan->agensi_id = Auth::user()->agensi->id;
            $pengesahan->status    = "TIDAK";
            $pengesahan->save();     
        }

        $pengesahan = $pengesahan->status;


        $jumlahPeserta  = Peserta::where('agensi_id', Auth::user()->agensi->id)->count();
        $jumlahLelaki   = Peserta::where('agensi_id', Auth::user()->agensi->id)
                            ->where('jantina', 'LELAKI')
                            ->count();
        $jumlahWanita   = Peserta::where('agensi_id', Auth::user()->agensi->id)
                            ->where('jantina', 'WANITA')
                            ->count();
        $kesempurnaan    = "TIDAK";

        if($jumlahPeserta == ($jumlahLelaki + $jumlahWanita))
            $kesempurnaan = "YA";

        $ringkasan["pengesahan"]    = $pengesahan;
        $ringkasan["jumlahPeserta"] = $jumlahPeserta;
        $ringkasan["jumlahLelaki"]  = $jumlahLelaki;
        $ringkasan["jumlahWanita"]  = $jumlahWanita;
        $ringkasan["kesempurnaan"]  = $kesempurnaan;

        $acaras = Acara::all();

        return view('members.laporan.senarai-semak', compact('ringkasan', 'acaras'));
    }

    public function kontinjen() {
        $agencies = Agensi::orderBy('nama', 'asc')->pluck('nama', 'id');

        return view('members.kontinjen', compact('agencies'));
    }

    public function kontinjenPost(Request $request) {

        // return $request->all();
        $agensi = Agensi::where('id', $request->get('agensi_id'))->first();
        $kontinjens = Kontinjen::where('agensi_id', $request->get('agensi_id'))
                        ->orderBy('role', 'asc')
                        ->get();

        return view('members.laporan.kontinjen', compact('kontinjens', 'agensi'));
    }

    public function agensiAcara() {

        $agencies = Agensi::orderBy('nama', 'asc')->pluck('nama', 'id');

        return view('members.laporan.agensiAcara', compact('agencies'));
    }

    public function agensiAcaraPost(Request $request) {

        $agensi = Agensi::where('id', $request->get('agensi_id'))->first();
        $games = Acara::orderBy('nama', 'asc')->get();

        return view('members.laporan.keputusanAgensiAcara', compact('games', 'agensi'));
    }


    public function agensiAcara2() {

        $agencies = Agensi::orderBy('nama', 'asc')->pluck('nama', 'id');

        return view('members.laporan.agensiAcara2', compact('agencies'));
    }

    public function agensiAcaraPost2(Request $request) {

        $agensi = Agensi::where('id', $request->get('agensi_id'))->first();
        $games = Acara::orderBy('nama', 'asc')->get();

        return view('members.laporan.keputusanAgensiAcara2', compact('games', 'agensi'));
    }




    public function senaraiPengurus() {

        $agencies = Agensi::orderBy('nama', 'asc')->pluck('nama', 'id');

        return view('members.laporan.carianPengurus', compact('agencies'));
    }

    public function senaraiPengurusPost(Request $request) {

        $agensi = Agensi::where('id', $request->get('agensi_id'))->first();
        $games = Acara::all();
        $managers = Peserta::where('role', '!=', 'ATLET')->where('agensi_id', $request->get('agensi_id'))->get();

        return view('members.laporan.senaraiPengurus', compact('games', 'managers', 'agensi'));
    }

    public function laporanPengurusPertandingan() {

        $games = Acara::orderBy('nama', 'asc')->pluck('nama', 'id');

        return view('members.laporan.pengurus-pertandingan', compact('games'));
    }

    public function laporanPengurusPertandinganPost(Request $request) {

        $acara = Acara::where('id', $request->get('acara_id'))->first();

        $pesertas = Peserta::all();

        $pesertas = $pesertas->filter(function($peserta) use ($request) {
                            foreach($peserta->acara as $acara) {
                                if($acara->id == $request->get('acara_id')){
                                    return true;
                                }
                            }
                        });

        $pesertas = $pesertas->sortByDesc('role')->sortBy('agensi_id');

        return view('members.laporan.keputusanPengurusPertandingan', compact('pesertas', 'acara'));
    }



}
