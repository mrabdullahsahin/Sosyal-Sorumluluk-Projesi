<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Intervention\Image\ImageManager;
/* use Illuminate\Support\Facades\Mail;
use App\User;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\MailServiceProvider;
//use Mail;
use Swift_Transport;
use Swift_Message;
use Swift_Mailer; */

class KullaniciController extends Controller
{
    public function __construct()
    {
    }

    //kullanıcı kayıt işlemleri fonksiyonu
    public function kayit(Request $request)
    {
      //Dooğrulama İşlemleri
      $this->validate($request, [
        'adi_soyadi' => 'required',
        'dogum_tarihi' => 'required|date_format:Y/m/d',
        'memleket_id' => 'required|numeric|min:1|max:81',
        'sifre' => 'required|min:8|max:12',
        'mail' => 'required|email|unique:kullanici',
        'telefon' => 'required|numeric',
        'resim' => 'image',
      ]);

      //Resim Upload İşlemleri
      if($request->file('resim'))
      {
        $image_manager = new ImageManager();
        $image = $request->file('resim');
        $filename  = uniqid().'.'.$image->getClientOriginalExtension();
        $path = \base_path("/public/resim/{$filename}");
        $image_manager->make($image->getRealPath())->resize(200, 200)->save($path);
        $resim = $filename;
      }

      //Veritabanına Kayıt İşlemi
      $kullanici_id = DB::table('kullanici')->insertGetId([
        'adi_soyadi' => $request->input('adi_soyadi'),
        'dogum_tarihi' => $request->input('dogum_tarihi'),
        'memleket_id' => $request->input('memleket_id'),
        'sifre' => $request->input('sifre'),
        'mail' => $request->input('mail'),
        'telefon' => $request->input('telefon'),
        'resim' => (isset($resim)) ? $resim : Null,
      ]);

      //Oturum İçin Token İşlemi
      $token = uniqid();
      DB::table('oturum')->insert([
        'kullanici_id' => $kullanici_id,
        'token_string' => $token,
      ]);

      //Sonuç İşlemleri
      return response()->json([
        'status' => 200,
        'message' => 'Kayit Basarili!',
        'token' => $token,
        'kullanici' => [
          'adi_soyadi' => $request->input('adi_soyadi'),
          'dogum_tarihi' => $request->input('dogum_tarihi'),
          'memleket_id' => $request->input('memleket_id'),
          'sifre' => $request->input('sifre'),
          'mail' => $request->input('mail'),
          'telefon' => $request->input('telefon'),
          'resim' => (isset($resim)) ? url("/resim/{$resim}") : Null,
        ]
      ]);
    }

    //kullanıcı giriş işlemleri fonksiyonu
    public function giris(Request $request)
    {
      //Doğrulama İşlemi
      $this->validate($request ,[
        'mail' => 'required|email',
        'sifre' => 'required',
      ]);

      //Veritabanı Sorgulama İşlemi
      $kullanici = DB::table('kullanici')->where([
        ['mail', '=', $request->input('mail')],
        ['sifre', '=', $request->input('sifre')],
      ])->first();

      //Giriş Doğrulanamıyorsa Aşağıdaki Sorgu Döndürülecek.
      if(empty($kullanici)){
        return response()->json([
          'status' => 401,
          'message' => 'Giris Basarisiz, Lutfen Tekrar Deneyiniz.'
        ]);
      }

      //Oturum İçin Token İşlemi
      $token = uniqid();
      DB::table('oturum')->insert([
        'kullanici_id' => $kullanici->kullanici_id,
        'token_string' => $token,
      ]);

      //Resmi url şeklinde döndüren komut.
      if($kullanici->resim!=null) {
        $kullanici->resim =  url("/resim/{$kullanici->resim}");
      }

      //Sonuç İşlemleri
      return response()->json([
        'status' => 200,
        'message' => 'Giris Basarili',
        'token' => $token,
        'kullanıcı' => $kullanici,
      ]);
    }

    //kullanıcı profil güncelleme fonksiyonu
    public function profil_guncelle(Request $request)
    {
      $this->validate($request, [
        'token_string' => 'required',
        'adi_soyadi' => '',
        'dogum_tarihi' => 'date_format:Y/m/d',
        'memleket_id' => 'numeric|min:1|max:81',
        'sifre' => 'min:8|max:12',
        'mail' => 'email|unique:kullanici',
        'telefon' => 'numeric',
        'resim' => 'image',
      ]);

      $kullanici_bilgisi = DB::table('oturum')->where([
        ['token_string', '=', $request->input('token_string')],
      ])->first();

      $kullanici_id = $kullanici_bilgisi->kullanici_id;

      if($request->has('adi_soyadi')){
        DB::table('kullanici')
                  ->where('kullanici_id', $kullanici_id)
                  ->update(['adi_soyadi' => $request->input('adi_soyadi')]);
      }

      if($request->has('dogum_tarihi')){
        DB::table('kullanici')
                  ->where('kullanici_id', $kullanici_id)
                  ->update(['dogum_tarihi' => $request->input('dogum_tarihi')]);
      }

      if($request->has('memleket_id')){
        DB::table('kullanici')
                  ->where('kullanici_id', $kullanici_id)
                  ->update(['memleket_id' => $request->input('memleket_id')]);
      }

      if($request->has('sifre')){
        DB::table('kullanici')
                  ->where('kullanici_id', $kullanici_id)
                  ->update(['sifre' => $request->input('sifre')]);
      }

      if($request->has('mail')){
        DB::table('kullanici')
                  ->where('kullanici_id', $kullanici_id)
                  ->update(['mail' => $request->input('mail')]);
      }

      if($request->has('telefon')){
        DB::table('kullanici')
                  ->where('kullanici_id', $kullanici_id)
                  ->update(['telefon' => $request->input('telefon')]);
      }

      if($request->has('resim')){
        DB:table('resim')
                  ->where('kullanici_id', $kullanici_id)
                  ->update(['resim' => $request->input('resim')]);

                  //Resim Upload İşlemleri
                  if($request->file('resim'))
                  {
                    $image_manager = new ImageManager();
                    $image = $request->file('resim');
                    $filename  = uniqid().'.'.$image->getClientOriginalExtension();
                    $path = \base_path("/public/resim/{$filename}");
                    $image_manager->make($image->getRealPath())->resize(200, 200)->save($path);
                    $resim = $filename;
                  }
      }

      $kullanici = DB::table('kullanici')->where([
        ['kullanici_id', '=', $kullanici_id],
      ])->first();

      //Resmi url şeklinde döndüren komut.
      if($kullanici->resim!=null) {
        $kullanici->resim =  url("/resim/{$kullanici->resim}");
      }

      return response()->json([
        'status' => 200,
        'message' => 'Guncelleme Basarili',
        'Kullanici' => $kullanici,
      ]);
    }

    //kullanıcı şifremi unuttum fonksiyonu
    public function sifremi_unuttum(Request $request)
    {
      $this->validate($request, [
        'mail' => 'required|email',
      ]);

      $kullanici = DB::table('kullanici')->where([
        ['mail', '=', $request->input('mail')],
      ])->first();

      $data = ['mail' => $kullanici->mail, 'sifre' => $kullanici->sifre];

      return response()-> json([
        'status' => 200,
        'message' => 'Basarili',
        'kullanici' => $kullanici,
      ]);
    }

    //kullanıcı profil görüntüleme fonksiyonu
    public function profil_goruntule(Request $request){
      $this->validate($request, [
        'kullanici_id' => 'required',
      ]);

      $kullanici = DB::table('kullanici')->where([
        ['kullanici_id', '=', $request->input('kullanici_id')],
      ])->first();

      return response()->json([
        'status' => 200,
        'message' => 'Profil basarili bir sekilde cekildi.',
        'kullanici' => [
          'adi_soyadi' => $kullanici->adi_soyadi,
          'dogum_tarihi' => $kullanici->dogum_tarihi,
          'memleket_id' => $kullanici->memleket_id,
          'mail' => $kullanici->mail,
          'telefon' => $kullanici->telefon,
          'resim' => $kullanici->resim == null ? null : url("/resim/{$kullanici->resim}"),
        ]
      ]);
    }

    public function gizli_cevap(Request $request){
      $this->validate($request, [
        'mail' => 'required',
        'gizli_cevap' => 'required',
      ]);

      $kullanici_bilgisi = DB::table('kullanici')->where([
        ['mail', '=', $request->input('mail')],
      ])->first();

      $gizli_cevap = DB::table('kullanici')->where('kullanici_id','=',$kullanici_bilgisi->kullanici_id)->first();

      if($request->input('gizli_cevap') == $gizli_cevap->gizli_cevap){
        return response()->json([
          'status' => 200,
          'message' => 'basarili',
        ]);
      }else{
        return response()->json([
          'status' => 400,
          'message' => 'Hatali giris',
        ]);
      }
    }

    public function sifre_gonder(Request $request){
      $this->validate($request, [
        'mail' => 'required',
        'sifre' => 'required',
      ]);

      $kullanici_bilgisi = DB::table('kullanici')->where([
        ['mail', '=', $request->input('mail')],
      ])->first();

        DB::table('kullanici')
                  ->where('kullanici_id', $kullanici_bilgisi->kullanici_id)
                  ->update(['sifre' => $request->input('sifre')]);

      $kullanici_bilgisi = DB::table('kullanici')->where([
        ['mail', '=', $request->input('mail')],
        ])->first();            

      return response()->json([
        'status' => 200,
        'message' => 'basarili',
        'yeni_sifre' => $kullanici_bilgisi->sifre,
      ]);
      }
    }
