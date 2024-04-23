<?php

use App\Events\NewNotification;
use App\Events\TestingEvent;
use App\Http\Controllers\Api\ParameterController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\AcosController;
use App\Http\Controllers\Api\UserRoleController;
use App\Http\Controllers\Api\AclController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\UserAclController;
use App\Http\Controllers\Api\ErrorController;
use App\Http\Controllers\Api\LogTrailController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\HariLiburController;
use App\Http\Controllers\Api\PesananDetailController;
use App\Http\Controllers\Api\PesananHeaderController;
use App\Http\Controllers\Api\UbahPasswordController;
use App\Http\Controllers\Api\OwnerController;
use App\Http\Controllers\Api\SatuanController;
use App\Http\Controllers\Api\GroupProductController;
use App\Http\Controllers\Api\ArmadaController;
use App\Http\Controllers\Api\KaryawanController;
use App\Http\Controllers\Api\BankController;
use App\Http\Controllers\Api\AlatBayarController;
use App\Http\Controllers\Api\CekPesananController;
use App\Http\Controllers\Api\CheckPenjualanController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\GroupCustomerController;
use App\Http\Controllers\Api\PenjualanDetailController;
use App\Http\Controllers\Api\PenjualanHeaderController;
use App\Http\Controllers\Api\PesananFinalDetailController;
use App\Http\Controllers\Api\PesananFinalHeaderController;
use App\Http\Controllers\Api\PembelianDetailController;
use App\Http\Controllers\Api\PembelianHeaderController;
use App\Http\Controllers\Api\HutangController;
use App\Http\Controllers\Api\PelunasanHutangDetailController;
use App\Http\Controllers\Api\PelunasanHutangHeaderController;
use App\Http\Controllers\Api\PelunasanPiutangDetailController;
use App\Http\Controllers\Api\PelunasanPiutangHeaderController;
use App\Http\Controllers\Api\PiutangController;
use App\Http\Controllers\Api\TransaksiBelanjaController;
use App\Http\Controllers\Api\TransaksiArmadaController;
use App\Http\Controllers\Api\PerkiraanController;
use App\Http\Controllers\Api\ReturBeliHeaderController;
use App\Http\Controllers\Api\ReturBeliDetailController;
use App\Http\Controllers\Api\ReturjualDetailController;
use App\Http\Controllers\Api\ReturjualHeaderController;
use App\Http\Controllers\Api\HPPController;
use App\Http\Controllers\Api\KartuStokController;
use App\Http\Controllers\Api\PenyesuaianStokDetailController;
use App\Http\Controllers\Api\PenyesuaianStokHeaderController;

// use App\Http\Controllers\Api\LaporanTransaksiHarianController;

/*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register API routes for your application. These
    | routes are loaded by the RouteServiceProvider within a group which
    | is assigned the "api" middleware group. Enjoy building your API!
    |
    */

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('token', [AuthController::class, 'token']);
Route::get('cekIp', [AuthController::class, 'cekIp']);

Route::get('parameter', [ParameterController::class, 'index']);
route::middleware(['auth:api'])->group(function () {
    Route::resource('dashboard', DashboardController::class)->whereNumber('dashboard');
    Route::get('error/geterrors', [ErrorController::class, 'errorUrl']);
});

route::middleware(['auth:api', 'authorized'])->group(function () 
{
    Route::resource('hpp', HPPController::class)->whereNumber('hpp');
    Route::resource('kartustok', KartuStokController::class)->whereNumber('kartustok');

    Route::get('parameter/export', [ParameterController::class, 'export']);
    Route::get('parameter/detail', [ParameterController::class, 'detail']);
    Route::get('parameter/default', [ParameterController::class, 'default']);
    Route::get('parameter/field_length', [ParameterController::class, 'fieldLength']);
    Route::get('parameter/combo', [ParameterController::class, 'combo']);
    Route::get('parameter/getparambytext', [ParameterController::class, 'getParamByText']);
    Route::get('parameter/getparamfirst', [ParameterController::class, 'getparamfirst']);
    Route::get('parameter/comboapproval', [ParameterController::class, 'comboapproval']);
    Route::get('parameter/combolist', [ParameterController::class, 'combolist']);
    Route::get('parameter/select2', [ParameterController::class, 'select2']);
    Route::get('parameter/getcoa', [ParameterController::class, 'getcoa']);
    Route::get('parameter/{id}', [ParameterController::class, 'show']);
    Route::post('parameter', [ParameterController::class, 'store']);
    Route::patch('parameter/{id}', [ParameterController::class, 'update']);
    Route::delete('parameter/{id}', [ParameterController::class, 'destroy']);

    Route::get('acos/field_length', [AcosController::class, 'fieldLength']);
    Route::resource('acos', AcosController::class)->whereNumber('acos');

    Route::get('logtrail/detail', [LogTrailController::class, 'detail']);
    Route::get('logtrail/header', [LogTrailController::class, 'header']);
    Route::resource('logtrail', LogTrailController::class)->whereNumber('logtrail');

    Route::get('error/field_length', [ErrorController::class, 'fieldLength']);
    Route::get('error/geterror', [ErrorController::class, 'geterror']);
    Route::get('error/export', [ErrorController::class, 'export'])->name('error.export');
    Route::resource('error', ErrorController::class)->whereNumber('error');

    Route::get('role/getroleid', [RoleController::class, 'getroleid']);
    Route::get('role/field_length', [RoleController::class, 'fieldLength']);
    Route::get('role/export', [RoleController::class, 'export'])->name('role.export');
    Route::get('role/{role}/acl', [AclController::class, 'RoleAcl'])->whereNumber('role');
    Route::post('role/{role}/acl', [UserRoleController::class, 'store'])->whereNumber('role');
    Route::resource('role', RoleController::class)->whereNumber('role');

    Route::get('acos/field_length', [AcosController::class, 'fieldLength']);
    Route::resource('acos', AcosController::class);

    Route::get('user/field_length', [UserController::class, 'fieldLength']);
    Route::get('user/export', [UserController::class, 'export'])->name('user.export');
    Route::get('user/combostatus', [UserController::class, 'combostatus']);
    Route::get('user/confirmuser', [UserController::class, 'confirmUser']);
    Route::get('user/combocabang', [UserController::class, 'combocabang']);
    Route::get('user/getuserid', [UserController::class, 'getuserid']);
    Route::get('user/default', [UserController::class, 'default']);
    Route::get('user/{user}/role', [UserRoleController::class, 'index'])->whereNumber('user');
    Route::post('user/{user}/role', [UserController::class, 'storeRoles'])->whereNumber('user');
    Route::get('user/{user}/acl', [UserAclController::class, 'index'])->whereNumber('user');
    Route::post('user/{user}/acl', [UserAclController::class, 'store'])->whereNumber('user');
    Route::resource('user', UserController::class)->whereNumber('user');

    Route::get('menu/field_length', [MenuController::class, 'fieldLength']);
    Route::get('menu/combomenuparent', [MenuController::class, 'combomenuparent']);
    Route::get('menu/controller', [MenuController::class, 'listclassall']);
    Route::get('menu/getdatanamaacos', [MenuController::class, 'getdatanamaacos']);
    Route::get('menu/export', [MenuController::class, 'export'])->name('menu.export');
    Route::resource('menu', MenuController::class)->whereNumber('menu')->whereNumber('menu');

    Route::get('userrole/field_length', [UserRoleController::class, 'fieldLength']);
    Route::get('userrole/detail', [UserRoleController::class, 'detail']);
    Route::get('userrole/detaillist', [UserRoleController::class, 'detaillist']);
    Route::get('userrole/combostatus', [UserRoleController::class, 'combostatus']);
    Route::get('userrole/export', [UserRoleController::class, 'export'])->name('userrole.export');
    Route::resource('userrole', UserRoleController::class)->whereNumber('userrole');

    Route::get('acl/field_length', [AclController::class, 'fieldLength']);
    Route::get('acl/detail/{roleId}', [AclController::class, 'detail'])->whereNumber('roleId');
    Route::get('acl/detaillist', [AclController::class, 'detaillist']);
    Route::get('acl/combostatus', [AclController::class, 'combostatus']);
    Route::get('acl/export', [AclController::class, 'export'])->name('acl.export');
    Route::resource('acl', AclController::class)->whereNumber('acl');

    Route::get('logtrail/detail', [LogTrailController::class, 'detail']);
    Route::get('logtrail/header', [LogTrailController::class, 'header']);
    Route::resource('logtrail', LogTrailController::class)->whereNumber('logtrail');

    Route::get('running_number', [Controller::class, 'getRunningNumber'])->name('running_number');

    Route::get('ubahpassword/field_length', [UbahPasswordController::class, 'fieldLength']);
    Route::resource('ubahpassword', UbahPasswordController::class)->whereNumber('ubahpassword');

    Route::get('harilibur/field_length', [HariLiburController::class, 'fieldLength']);
    Route::get('harilibur/default', [HariLiburController::class, 'default']);
    Route::get('harilibur/export', [HariLiburController::class, 'export']);
    Route::post('harilibur/import', [HariLiburController::class, 'import']);
    Route::get('harilibur/report', [HariLiburController::class, 'report']);
    Route::resource('harilibur', HariLiburController::class)->whereNumber('harilibur');

    Route::post('customer/editingat', [CustomerController::class, 'editingat']);
    Route::post('customer/{id}/cekvalidasi', [CustomerController::class, 'cekValidasi'])->name('customer.cekValidasi')->whereNumber('id');
    Route::get('customer/field_length', [CustomerController::class, 'fieldLength']);
    Route::get('customer/default', [CustomerController::class, 'default']);
    Route::post('customer/import', [CustomerController::class, 'import']);
    Route::resource('customer', CustomerController::class)->whereNumber('customer');

    Route::post('product/editingat', [ProductController::class, 'editingat']);
    Route::post('product/editall', [ProductController::class, 'editall']);
    Route::get('product/getproductall', [ProductController::class, 'getproductall']);
    Route::get('product/field_length', [ProductController::class, 'fieldLength']);
    Route::get('product/default', [ProductController::class, 'default']);
    Route::get('product/export', [ProductController::class, 'export']);
    Route::post('product/import', [ProductController::class, 'import']);
    Route::get('product/report', [ProductController::class, 'report']);
    Route::resource('product', ProductController::class)->whereNumber('product');


    Route::post('pesananheader/editingat', [PesananHeaderController::class, 'editingat']);
    Route::post('pesananheader/import', [PesananHeaderController::class, 'import'])->name('pesananheader.import')->whereNumber('id');
    Route::get('pesananheader/default', [PesananHeaderController::class, 'default']);
    Route::get('pesananheader/{id}/report', [PesananHeaderController::class, 'report'])->name('pesananheader.report')->whereNumber('id');
    Route::get('pesananheader/{id}/export', [PesananHeaderController::class, 'export'])->name('pesananheader.export')->whereNumber('id');
    Route::post('pesananheader/{id}/cekvalidasi', [PesananHeaderController::class, 'cekvalidasi'])->name('pesananheader.cekvalidasi')->whereNumber('id');
    Route::get('pesananheader/combo', [PesananHeaderController::class, 'combo']);
    Route::get('pesananheader/grid', [PesananHeaderController::class, 'grid']);
    Route::post('pesananheader/{id}/cekValidasiAksi', [PesananHeaderController::class, 'cekValidasiAksi'])->name('pesananheader.cekValidasiAksi')->whereNumber('id');
    Route::get('pesananheader/field_length', [PesananHeaderController::class, 'fieldLength']);
    Route::resource('pesananheader', PesananHeaderController::class)->whereNumber('pesananheader');
    Route::resource('pesanandetail', PesananDetailController::class)->whereNumber('pesanandetail');

    Route::get('cekpesanan/getheader', [CekPesananController::class, 'getheader']);
    Route::get('cekpesanan/findallpenjualan', [CekPesananController::class, 'findAllPenjualan']);
    Route::get('cekpesanan/findpesanandetail', [CekPesananController::class, 'findpesanandetail']);
    Route::resource('cekpesanan', CekPesananController::class)->whereNumber('cekpesanan');

    Route::get('pelunasanpiutangheader/geteditpelunasanpiutangheader', [PelunasanPiutangHeaderController::class, 'getEditPelunasanPiutangHeader']);
    Route::get('pelunasanpiutangheader/getpiutang', [PelunasanPiutangHeaderController::class, 'getPiutang']);
    Route::get('pelunasanpiutangheader/default', [PelunasanPiutangHeaderController::class, 'default']);
    Route::get('pelunasanpiutangheader/{id}/report', [PelunasanPiutangHeaderController::class, 'report'])->name('pelunasanpiutangheader.report')->whereNumber('id');
    Route::get('pelunasanpiutangheader/{id}/export', [PelunasanPiutangHeaderController::class, 'export'])->name('pelunasanpiutangheader.export')->whereNumber('id');
    Route::post('pelunasanpiutangheader/{id}/cekvalidasi', [PelunasanPiutangHeaderController::class, 'cekvalidasi'])->name('pelunasanpiutangheader.cekvalidasi')->whereNumber('id');
    Route::get('pelunasanpiutangheader/combo', [PelunasanPiutangHeaderController::class, 'combo']);
    Route::get('pelunasanpiutangheader/grid', [PelunasanPiutangHeaderController::class, 'grid']);
    Route::post('pelunasanpiutangheader/{id}/cekValidasiAksi', [PelunasanPiutangHeaderController::class, 'cekValidasiAksi'])->name('pelunasanpiutangheader.cekValidasiAksi')->whereNumber('id');
    Route::get('pelunasanpiutangheader/field_length', [PelunasanPiutangHeaderController::class, 'fieldLength']);
    Route::resource('pelunasanpiutangheader', PelunasanPiutangHeaderController::class)->whereNumber('pelunasanpiutangheader');
    // Route::resource('pelunasanpiutangdetail', PelunasanPiutangDetailController::class)->whereNumber('pelunasanpiutangdetail');

    Route::post('pesananfinalheader/approvalkacab', [PesananFinalHeaderController::class, 'approvalKacab']);
    Route::post('pesananfinalheader/editingat', [PesananFinalHeaderController::class, 'editingat']);
    Route::get('pesananfinalheader/getallpenjualan', [PesananFinalHeaderController::class, 'getAllPenjualan']);
    Route::get('pesananfinalheader/getallpembelian', [PesananFinalHeaderController::class, 'getAllPembelian']);
    Route::post('pesananfinalheader/processeditallpenjualan', [PesananFinalHeaderController::class, 'editAllPenjualan']);
    Route::post('pesananfinalheader/processeditallpembelian', [PesananFinalHeaderController::class, 'editAllPembelian']);
    Route::post('pesananfinalheader/edithargajual', [PesananFinalHeaderController::class, 'editHargaJual']);
    Route::post('pesananfinalheader/edithargabeli', [PesananFinalHeaderController::class, 'editHargaBeli']);
    Route::get('pesananfinalheader/cekproductpesanan', [PesananFinalHeaderController::class, 'cekProductPesanan']);
    Route::get('pesananfinalheader/cektglcetak', [PesananFinalHeaderController::class, 'cekTglCetak']);
    Route::get('pesananfinalheader/acos', [PesananFinalHeaderController::class, 'acos']);
    Route::post('pesananfinalheader/updatetglcetak', [PesananFinalHeaderController::class, 'updateTglCetak']);
    Route::post('pesananfinalheader/import', [PesananFinalHeaderController::class, 'import'])->name('pesananfinalheader.import')->whereNumber('id');
    Route::get('pesananfinalheader/default', [PesananFinalHeaderController::class, 'default']);
    Route::get('pesananfinalheader/{id}/report', [PesananFinalHeaderController::class, 'report'])->name('pesananfinalheader.report')->whereNumber('id');
    Route::get('pesananfinalheader/{id}/export', [PesananFinalHeaderController::class, 'export'])->name('pesananfinalheader.export')->whereNumber('id');
    Route::post('pesananfinalheader/cekvalidasipenjualan', [PesananFinalHeaderController::class, 'cekValidasiPenjualan'])->name('pesananfinalheader.cekValidasiPenjualan')->whereNumber('id');
    Route::post('pesananfinalheader/cekvalidasipembelian', [PesananFinalHeaderController::class, 'cekValidasiPembelian'])->name('pesananfinalheader.cekValidasiPembelian')->whereNumber('id');
    Route::get('pesananfinalheader/combo', [PesananFinalHeaderController::class, 'combo']);
    Route::post('pesananfinalheader/combain', [PesananFinalHeaderController::class, 'combain']);
    Route::get('pesananfinalheader/grid', [PesananFinalHeaderController::class, 'grid']);
    Route::post('pesananfinalheader/{id}/cekvalidasiaksiedit', [PesananFinalHeaderController::class, 'cekValidasiAksiEdit'])->name('pesananfinalheader.cekvalidasiaksiedit')->whereNumber('id');
    Route::post('pesananfinalheader/{id}/cekvalidasiaksidel', [PesananFinalHeaderController::class, 'cekValidasiAksiDelete'])->name('pesananfinalheader.cekvalidasiaksidel')->whereNumber('id');
    Route::get('pesananfinalheader/field_length', [PesananFinalHeaderController::class, 'fieldLength']);
    Route::post('pesananfinalheader/unapproval', [PesananFinalHeaderController::class, 'unApproval'])->name('pesananfinalheader.unapproval')->whereNumber('id');
    Route::resource('pesananfinalheader', PesananFinalHeaderController::class)->whereNumber('pesananfinalheader');
    Route::get('pesananfinaldetail/reportpembelian', [PesananFinalDetailController::class, 'reportPembelian'])->name('pesananfinaldetail.reportpembelian')->whereNumber('id');
    Route::resource('pesananfinaldetail', PesananFinalDetailController::class)->whereNumber('pesananfinaldetail');

    Route::get('penjualanheader/disabledqtyretur/{id}', [PenjualanHeaderController::class, 'disabledqtyretur']);
    Route::post('penjualanheader/cekvalidasieditall', [PenjualanHeaderController::class, 'cekvalidasieditall']);
    Route::post('penjualanheader/disabledqtyretureditall', [PenjualanHeaderController::class, 'disabledqtyretureditall']);
    Route::post('penjualanheader/cekjumlahqtyretur', [PenjualanHeaderController::class, 'cekjumlahqtyretur']);
    Route::post('penjualanheader/approvalkacab', [PenjualanHeaderController::class, 'approvalKacab']);
    Route::post('penjualanheader/approvalkacabeditall', [PenjualanHeaderController::class, 'approvalkacabeditall']);
    Route::post('penjualanheader/editingat', [PenjualanHeaderController::class, 'editingat']);
    Route::post('penjualanheader/editalleditingat', [PenjualanHeaderController::class, 'editalleditingat']);
    Route::get('penjualanheader/checkusereditall', [PenjualanHeaderController::class, 'checkusereditall']);
    Route::get('penjualanheader/cekmaxqty', [PenjualanHeaderController::class, 'cekMaxQty']);
    Route::get('penjualanheader/editall', [PenjualanHeaderController::class, 'editall']);
    Route::post('penjualanheader/processeditall', [PenjualanHeaderController::class, 'processeditall']);
    Route::post('penjualanheader/import', [PenjualanHeaderController::class, 'import'])->name('penjualanheader.import')->whereNumber('id');
    Route::get('penjualanheader/default', [PenjualanHeaderController::class, 'default']);
    Route::get('penjualanheader/reportprofit', [PenjualanHeaderController::class, 'reportProfit']);
    Route::get('penjualanheader/reportprofitdetail', [PenjualanHeaderController::class, 'reportProfitDetail']);
    Route::post('penjualanheader/generatepenjualan', [PenjualanHeaderController::class, 'generatepenjualan']);
    Route::get('penjualanheader/{id}/invoice', [PenjualanHeaderController::class, 'invoice'])->name('penjualanheader.invoice')->whereNumber('id');
    Route::get('penjualanheader/{id}/export', [PenjualanHeaderController::class, 'export'])->name('penjualanheader.export')->whereNumber('id');
    Route::post('penjualanheader/{id}/cekvalidasi', [PenjualanHeaderController::class, 'cekValidasi'])->name('penjualanheader.cekValidasi')->whereNumber('id');
    Route::get('penjualanheader/combo', [PenjualanHeaderController::class, 'combo']);
    Route::post('penjualanheader/combain', [PenjualanHeaderController::class, 'combain']);
    Route::get('penjualanheader/grid', [PenjualanHeaderController::class, 'grid']);
    Route::post('penjualanheader/{id}/cekvalidasiaksi', [PenjualanHeaderController::class, 'cekValidasiAksi'])->name('penjualanheader.cekValidasiAksi')->whereNumber('id');
    Route::get('penjualanheader/field_length', [PenjualanHeaderController::class, 'fieldLength']);
    Route::post('penjualanheader/batalpenjualan', [PenjualanHeaderController::class, 'batalpenjualan']);
    Route::resource('penjualanheader', PenjualanHeaderController::class)->whereNumber('pesananfinalheader');
    Route::get('penjualandetail/report', [PenjualanDetailController::class, 'report'])->name('penjualandetail.report')->whereNumber('id');
    Route::resource('penjualandetail', PenjualanDetailController::class)->whereNumber('penjualandetail');

    Route::post('pembelianheader/disableddeleteeditall', [PembelianHeaderController::class, 'disableddeleteeditall']);
    Route::get('pembelianheader/disableddelete/{id}', [PembelianHeaderController::class, 'disableddelete']);
    Route::get('pembelianheader/gettransaksibelanja', [PembelianHeaderController::class, 'gettransaksibelanja']);
    Route::post('pembelianheader/cekjumlahqtyretur', [PembelianHeaderController::class, 'cekjumlahqtyretur']);
    Route::get('pembelianheader/cekstokproduct', [PembelianHeaderController::class, 'cekStokProduct']);
    Route::post('pembelianheader/approvalkacab', [PembelianHeaderController::class, 'approvalKacab']);
    Route::post('pembelianheader/editingat', [PembelianHeaderController::class, 'editingat']);
    Route::get('pembelianheader/editall', [PembelianHeaderController::class, 'editall']);
    Route::post('pembelianheader/processeditall', [PembelianHeaderController::class, 'processeditall']);
    Route::post('pembelianheader/import', [PembelianHeaderController::class, 'import'])->name('pembelianheader.import')->whereNumber('id');
    Route::get('pembelianheader/default', [PembelianHeaderController::class, 'default']);
    Route::post('pembelianheader/generatepembelian', [PembelianHeaderController::class, 'generatePembelian']);
    Route::get('pembelianheader/{id}/report', [PembelianHeaderController::class, 'report'])->name('pembelianheader.report')->whereNumber('id');
    Route::get('pembelianheader/{id}/export', [PembelianHeaderController::class, 'export'])->name('pembelianheader.export')->whereNumber('id');
    Route::post('pembelianheader/{id}/cekvalidasi', [PembelianHeaderController::class, 'cekValidasi'])->name('pembelianheader.cekvalidasi')->whereNumber('id');
    Route::get('pembelianheader/combo', [PembelianHeaderController::class, 'combo']);
    Route::post('pembelianheader/combain', [PembelianHeaderController::class, 'combain']);
    Route::get('pembelianheader/grid', [PembelianHeaderController::class, 'grid']);
    Route::get('pembelianheader/createpembelian', [PembelianHeaderController::class, 'createPembelian']);
    Route::delete('pembelianheader/hapuspembelian', [PembelianHeaderController::class, 'hapusPembelian']);
    Route::post('pembelianheader/{id}/cekvalidasiaksi', [PembelianHeaderController::class, 'cekValidasiAksi'])->name('pembelianheader.cekValidasiAksi')->whereNumber('id');
    Route::get('pembelianheader/field_length', [PembelianHeaderController::class, 'fieldLength']);
    Route::resource('pembelianheader', PembelianHeaderController::class)->whereNumber('pembelianheader');
    Route::get('pembeliandetail/report', [PembelianDetailController::class, 'report'])->name('pembeliandetail.report')->whereNumber('id');
    Route::resource('pembeliandetail', PembelianDetailController::class)->whereNumber('pembeliandetail');

    Route::post('transaksibelanja/editingat', [TransaksiBelanjaController::class, 'editingat']);
    Route::post('transaksibelanja/addrow', [TransaksiBelanjaController::class, 'addrow']);
    Route::get('transaksibelanja/editall', [TransaksiBelanjaController::class, 'editall']);
    Route::post('transaksibelanja/processeditall', [TransaksiBelanjaController::class, 'processeditall']);
    Route::get('transaksibelanja/default', [TransaksiBelanjaController::class, 'default']);
    Route::get('transaksibelanja/grid', [TransaksiBelanjaController::class, 'grid']);
    Route::post('transaksibelanja/{id}/cekValidasiAksi', [TransaksiBelanjaController::class, 'cekValidasiAksi'])->name('transaksibelanja.cekValidasiAksi')->whereNumber('id');
    Route::get('transaksibelanja/field_length', [TransaksiBelanjaController::class, 'fieldLength']);
    Route::resource('transaksibelanja', TransaksiBelanjaController::class)->whereNumber('transaksibelanja');

    Route::post('transaksiarmada/editingat', [TransaksiArmadaController::class, 'editingat']);
    Route::post('transaksiarmada/addrow', [TransaksiArmadaController::class, 'addrow']);
    Route::get('transaksiarmada/editall', [TransaksiArmadaController::class, 'editall']);
    Route::post('transaksiarmada/processeditall', [TransaksiArmadaController::class, 'processeditall']);
    Route::get('transaksiarmada/default', [TransaksiArmadaController::class, 'default']);
    Route::get('transaksiarmada/grid', [TransaksiArmadaController::class, 'grid']);
    Route::get('transaksiarmada/field_length', [TransaksiArmadaController::class, 'fieldLength']);
    Route::resource('transaksiarmada', TransaksiArmadaController::class)->whereNumber('transaksiarmada');

    Route::get('returjualheader/geteditpenjualandetail', [ReturjualHeaderController::class, 'getEditPenjualanDetail']);
    Route::post('returjualheader/editingat', [ReturjualHeaderController::class, 'editingat']);
    Route::get('returjualheader/getpenjualandetail', [ReturjualHeaderController::class, 'getPenjualanDetail']);
    Route::get('returjualheader/default', [ReturjualHeaderController::class, 'default']);
    Route::get('returjualheader/{id}/report', [ReturjualHeaderController::class, 'report'])->name('returjualheader.report')->whereNumber('id');
    Route::get('returjualheader/{id}/export', [ReturjualHeaderController::class, 'export'])->name('returjualheader.export')->whereNumber('id');
    Route::post('returjualheader/{id}/cekvalidasi', [ReturjualHeaderController::class, 'cekvalidasi'])->name('returjualheader.cekvalidasi')->whereNumber('id');
    Route::get('returjualheader/combo', [ReturjualHeaderController::class, 'combo']);
    Route::get('returjualheader/grid', [ReturjualHeaderController::class, 'grid']);
    Route::post('returjualheader/{id}/cekvalidasiaksi', [ReturjualHeaderController::class, 'cekValidasiAksi'])->name('returjualheader.cekvalidasiaksi')->whereNumber('id');
    Route::get('returjualheader/field_length', [ReturjualHeaderController::class, 'fieldLength']);
    Route::resource('returjualheader', ReturjualHeaderController::class)->whereNumber('returjualheader');
    Route::resource('returjualdetail', ReturjualDetailController::class)->whereNumber('returjualdetail');

    Route::get('returbeliheader/geteditpembeliandetail', [ReturBeliHeaderController::class, 'getEditPembelianDetail']);
    Route::post('returbeliheader/editingat', [ReturBeliHeaderController::class, 'editingat']);
    Route::get('returbeliheader/getpembeliandetail', [ReturBeliHeaderController::class, 'getPembelianDetail']);
    Route::get('returbeliheader/default', [ReturBeliHeaderController::class, 'default']);
    Route::get('returbeliheader/{id}/report', [ReturBeliHeaderController::class, 'report'])->name('returbeliheader.report')->whereNumber('id');
    Route::get('returbeliheader/{id}/export', [ReturBeliHeaderController::class, 'export'])->name('returbeliheader.export')->whereNumber('id');
    Route::post('returbeliheader/{id}/cekvalidasi', [ReturBeliHeaderController::class, 'cekvalidasi'])->name('returbeliheader.cekvalidasi')->whereNumber('id');
    Route::get('returbeliheader/combo', [ReturBeliHeaderController::class, 'combo']);
    Route::get('returbeliheader/grid', [ReturBeliHeaderController::class, 'grid']);
    Route::post('returbeliheader/{id}/cekvalidasiaksi', [ReturBeliHeaderController::class, 'cekValidasiAksi'])->name('returbeliheader.cekvalidasiaksi')->whereNumber('id');
    Route::get('returbeliheader/field_length', [ReturBeliHeaderController::class, 'fieldLength']);
    Route::resource('returbeliheader', ReturBeliHeaderController::class)->whereNumber('returbeliheader');
    Route::resource('returbelidetail', ReturBeliDetailController::class)->whereNumber('returbelidetail');

    Route::post('penyesuaianstokheader/editingat', [PenyesuaianStokHeaderController::class, 'editingat']);
    Route::get('penyesuaianstokheader/default', [PenyesuaianStokHeaderController::class, 'default']);
    Route::get('penyesuaianstokheader/{id}/report', [PenyesuaianStokHeaderController::class, 'report'])->name('penyesuaianstokheader.report')->whereNumber('id');
    Route::get('penyesuaianstokheader/{id}/export', [PenyesuaianStokHeaderController::class, 'export'])->name('penyesuaianstokheader.export')->whereNumber('id');
    Route::post('penyesuaianstokheader/{id}/cekvalidasi', [PenyesuaianStokHeaderController::class, 'cekvalidasi'])->name('penyesuaianstokheader.cekvalidasi')->whereNumber('id');
    Route::get('penyesuaianstokheader/combo', [PenyesuaianStokHeaderController::class, 'combo']);
    Route::get('penyesuaianstokheader/grid', [PenyesuaianStokHeaderController::class, 'grid']);
    Route::post('penyesuaianstokheader/{id}/cekvalidasiaksi', [PenyesuaianStokHeaderController::class, 'cekValidasiAksi'])->name('penyesuaianstokheader.cekvalidasiaksi')->whereNumber('id');
    Route::get('penyesuaianstokheader/field_length', [PenyesuaianStokHeaderController::class, 'fieldLength']);
    Route::resource('penyesuaianstokheader', PenyesuaianStokHeaderController::class)->whereNumber('penyesuaianstokheader');
    Route::resource('penyesuaianstokdetail', PenyesuaianStokDetailController::class)->whereNumber('penyesuaianstokdetail');

    Route::get('pelunasanhutangheader/geteditpelunasanhutangheader', [PelunasanHutangHeaderController::class, 'getEditPelunasanHutangHeader']);
    Route::get('pelunasanhutangheader/gethutang', [PelunasanHutangHeaderController::class, 'getHutang']);
    Route::get('pelunasanhutangheader/default', [PelunasanHutangHeaderController::class, 'default']);
    Route::get('pelunasanhutangheader/{id}/report', [PelunasanHutangHeaderController::class, 'report'])->name('pelunasanhutangheader.report')->whereNumber('id');
    Route::get('pelunasanhutangheader/{id}/export', [PelunasanHutangHeaderController::class, 'export'])->name('pelunasanhutangheader.export')->whereNumber('id');
    Route::post('pelunasanhutangheader/{id}/cekvalidasi', [PelunasanHutangHeaderController::class, 'cekvalidasi'])->name('pelunasanhutangheader.cekvalidasi')->whereNumber('id');
    Route::get('pelunasanhutangheader/combo', [PelunasanHutangHeaderController::class, 'combo']);
    Route::get('pelunasanhutangheader/grid', [PelunasanHutangHeaderController::class, 'grid']);
    Route::post('pelunasanhutangheader/{id}/cekValidasiAksi', [PelunasanHutangHeaderController::class, 'cekValidasiAksi'])->name('pelunasanhutangheader.cekValidasiAksi')->whereNumber('id');
    Route::get('pelunasanhutangheader/field_length', [PelunasanHutangHeaderController::class, 'fieldLength']);
    Route::resource('pelunasanhutangheader', PelunasanHutangHeaderController::class)->whereNumber('pelunasanhutangheader');
    Route::resource('pelunasanhutangdetail', PelunasanHutangDetailController::class)->whereNumber('pelunasanhutangdetail');

    Route::get('hutang/field_length', [HutangController::class, 'fieldLength']);
    Route::post('hutang/{id}/cekValidasiAksi', [HutangController::class, 'cekValidasiAksi'])->name('hutang.cekValidasiAksi')->whereNumber('id');
    Route::get('hutang/default', [HutangController::class, 'default']);
    Route::get('hutang/export', [HutangController::class, 'export']);
    Route::post('hutang/import', [HutangController::class, 'import']);
    Route::get('hutang/report', [HutangController::class, 'report']);
    Route::resource('hutang', HutangController::class)->whereNumber('hutang');

    Route::get('piutang/field_length', [PiutangController::class, 'fieldLength']);
    Route::post('piutang/{id}/cekValidasiAksi', [PiutangController::class, 'cekValidasiAksi'])->name('piutang.cekValidasiAksi')->whereNumber('id');
    Route::get('piutang/default', [PiutangController::class, 'default']);
    Route::get('piutang/export', [PiutangController::class, 'export']);
    Route::post('piutang/import', [PiutangController::class, 'import']);
    Route::get('piutang/report', [PiutangController::class, 'report']);
    Route::resource('piutang', PiutangController::class)->whereNumber('hutang');

    Route::post('owner/editingat', [OwnerController::class, 'editingat']);
    Route::get('owner/field_length', [OwnerController::class, 'fieldLength']);
    Route::get('owner/default', [OwnerController::class, 'default']);
    Route::get('owner/export', [OwnerController::class, 'export']);
    Route::post('owner/import', [OwnerController::class, 'import']);
    Route::get('owner/report', [OwnerController::class, 'report']);
    Route::resource('owner', OwnerController::class)->whereNumber('owner');

    Route::post('satuan/editingat', [SatuanController::class, 'editingat']);
    Route::get('satuan/field_length', [SatuanController::class, 'fieldLength']);
    Route::get('satuan/default', [SatuanController::class, 'default']);
    Route::get('satuan/export', [SatuanController::class, 'export']);
    Route::post('satuan/import', [SatuanController::class, 'import']);
    Route::get('satuan/report', [SatuanController::class, 'report']);
    Route::resource('satuan', SatuanController::class)->whereNumber('satuan');

    Route::post('groupproduct/editingat', [GroupProductController::class, 'editingat']);
    Route::get('groupproduct/field_length', [GroupProductController::class, 'fieldLength']);
    Route::get('groupproduct/default', [GroupProductController::class, 'default']);
    Route::get('groupproduct/export', [GroupProductController::class, 'export']);
    Route::post('groupproduct/import', [GroupProductController::class, 'import']);
    Route::get('groupproduct/report', [GroupProductController::class, 'report']);
    Route::resource('groupproduct', GroupProductController::class)->parameters(['groupproduct' => 'groupProduct'])->whereNumber('groupproduct');

    Route::post('armada/editingat', [ArmadaController::class, 'editingat']);
    Route::get('armada/field_length', [ArmadaController::class, 'fieldLength']);
    Route::get('armada/default', [ArmadaController::class, 'default']);
    Route::get('armada/export', [ArmadaController::class, 'export']);
    Route::post('armada/import', [ArmadaController::class, 'import']);
    Route::get('armada/report', [ArmadaController::class, 'report']);
    Route::resource('armada', ArmadaController::class)->whereNumber('armada');

    Route::post('karyawan/editingat', [KaryawanController::class, 'editingat']);
    Route::get('karyawan/field_length', [KaryawanController::class, 'fieldLength']);
    Route::get('karyawan/default', [KaryawanController::class, 'default']);
    Route::get('karyawan/export', [KaryawanController::class, 'export']);
    Route::post('karyawan/import', [KaryawanController::class, 'import']);
    Route::get('karyawan/report', [KaryawanController::class, 'report']);
    Route::resource('karyawan', KaryawanController::class)->whereNumber('karyawan');

    Route::post('bank/editingat', [BankController::class, 'editingat']);
    Route::get('bank/field_length', [BankController::class, 'fieldLength']);
    Route::get('bank/default', [BankController::class, 'default']);
    Route::get('bank/export', [BankController::class, 'export']);
    Route::post('bank/import', [BankController::class, 'import']);
    Route::get('bank/report', [BankController::class, 'report']);
    Route::resource('bank', BankController::class)->whereNumber('bank');

    Route::post('perkiraan/{id}/cekValidasiAksi', [PerkiraanController::class, 'cekValidasiAksi'])->name('perkiraan.cekValidasiAksi')->whereNumber('id');
    Route::post('perkiraan/editingat', [PerkiraanController::class, 'editingat']);
    Route::get('perkiraan/field_length', [PerkiraanController::class, 'fieldLength']);
    Route::get('perkiraan/default', [PerkiraanController::class, 'default']);
    Route::get('perkiraan/export', [PerkiraanController::class, 'export']);
    Route::post('perkiraan/import', [PerkiraanController::class, 'import']);
    Route::get('perkiraan/report', [PerkiraanController::class, 'report']);
    Route::resource('perkiraan', PerkiraanController::class)->whereNumber('perkiraan');

    Route::post('alatbayar/editingat', [AlatBayarController::class, 'editingat']);
    Route::get('alatbayar/field_length', [AlatBayarController::class, 'fieldLength']);
    Route::get('alatbayar/default', [AlatBayarController::class, 'default']);
    Route::get('alatbayar/export', [AlatBayarController::class, 'export']);
    Route::post('alatbayar/import', [AlatBayarController::class, 'import']);
    Route::get('alatbayar/report', [AlatBayarController::class, 'report']);
    Route::resource('alatbayar', AlatBayarController::class)->whereNumber('alatbayar')->parameters(['alatbayar' => 'alatBayar']);

    Route::post('supplier/editingat', [SupplierController::class, 'editingat']);
    Route::get('supplier/field_length', [SupplierController::class, 'fieldLength']);
    Route::get('supplier/default', [SupplierController::class, 'default']);
    Route::get('supplier/export', [SupplierController::class, 'export']);
    Route::post('supplier/import', [SupplierController::class, 'import']);
    Route::get('supplier/report', [SupplierController::class, 'report']);
    Route::get('supplier/lookup', [SupplierController::class, 'lookup']);
    Route::resource('supplier', SupplierController::class)->whereNumber('supplier');

    Route::post('groupcustomer/editingat', [GroupCustomerController::class, 'editingat']);
    Route::get('groupcustomer/field_length', [GroupCustomerController::class, 'fieldLength']);
    Route::get('groupcustomer/default', [GroupCustomerController::class, 'default']);
    Route::get('groupcustomer/export', [GroupCustomerController::class, 'export']);
    Route::post('groupcustomer/import', [GroupCustomerController::class, 'import']);
    Route::get('groupcustomer/report', [GroupCustomerController::class, 'report']);
    Route::resource('groupcustomer', GroupCustomerController::class)->parameters(['groupcustomer' => 'groupCustomer'])->whereNumber('groupcustomer');

    Route::resource('checkpenjualan', CheckPenjualanController::class)->parameters(['checkpenjualan' => 'checkpenjualan'])->whereNumber('checkpenjualan');
});

Route::get('parameter/select/{grp}/{subgrp}/{text}', [ParameterController::class, 'getparameterid']);

Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
Route::post('reset-password/{token}', [ForgotPasswordController::class, 'resetPassword'])->name('resetPassword');


Route::get('test', function () {
    $data = event(new TestingEvent(json_encode([
        'message' => "api syaripah",
        'id' => 1,
    ])));
    dd("anjay mabar");
});
