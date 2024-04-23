<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        // DB::statement("delete menu");
        // DB::statement("DBCC CHECKIDENT ('menu', RESEED, 1);");

        menu::create(['menuname' => 'DASHBOARD', 'menuseq' => '0', 'menuparent' => '0', 'menuicon' => 'FAS FA-HOME', 'aco_id' => '0', 'link' => 'DASHBOARD', 'menuexe' => '', 'menukode' => '0', 'modifiedby' => '',]);
        menu::create(['menuname' => 'LOGOUT', 'menuseq' => '9', 'menuparent' => '0', 'menuicon' => 'FAS FA-SIGN-OUT-ALT', 'aco_id' => '0', 'link' => 'LOGOUT', 'menuexe' => '', 'menukode' => 'Z', 'modifiedby' => '',]);
        menu::create(['menuname' => 'MASTER', 'menuseq' => '1', 'menuparent' => '0', 'menuicon' => 'FAS FA-USER-TAG', 'aco_id' => '0', 'link' => '', 'menuexe' => '', 'menukode' => '1', 'modifiedby' => '',]);
        menu::create(['menuname' => 'SYSTEM', 'menuseq' => '11', 'menuparent' => '3', 'menuicon' => 'FAB FA-UBUNTU', 'aco_id' => '0', 'link' => '', 'menuexe' => '', 'menukode' => '11', 'modifiedby' => '',]);
        menu::create(['menuname' => 'PARAMETER', 'menuseq' => '111', 'menuparent' => '4', 'menuicon' => 'FAS FA-EXCLAMATION', 'aco_id' => '1', 'link' => '', 'menuexe' => '', 'menukode' => '111', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'USER', 'menuseq' => '112', 'menuparent' => '4', 'menuicon' => 'FAS FA-USER', 'aco_id' => '11', 'link' => '', 'menuexe' => '', 'menukode' => '112', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'MENU', 'menuseq' => '113', 'menuparent' => '4', 'menuicon' => 'FAS FA-BARS', 'aco_id' => '17', 'link' => '', 'menuexe' => '', 'menukode' => '113', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'ROLE', 'menuseq' => '114', 'menuparent' => '4', 'menuicon' => 'FAS FA-USER-TAG', 'aco_id' => '25', 'link' => '', 'menuexe' => '', 'menukode' => '114', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'USER ACL', 'menuseq' => '115', 'menuparent' => '4', 'menuicon' => 'FAS FA-USER-TAG', 'aco_id' => '0', 'link' => '', 'menuexe' => '', 'menukode' => '11A', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'USER ROLE', 'menuseq' => '116', 'menuparent' => '4', 'menuicon' => 'FAS FA-USER-TAG', 'aco_id' => '0', 'link' => '', 'menuexe' => '', 'menukode' => '11B', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'ACL', 'menuseq' => '117', 'menuparent' => '4', 'menuicon' => 'FAS FA-USER-MINUS', 'aco_id' => '0', 'link' => '', 'menuexe' => '', 'menukode' => '11C', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'ERROR', 'menuseq' => '118', 'menuparent' => '4', 'menuicon' => 'FAS FA-BUG', 'aco_id' => '43', 'link' => '', 'menuexe' => '', 'menukode' => '115', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'LOG TRAIL', 'menuseq' => '119', 'menuparent' => '4', 'menuicon' => 'FAB FA-SLACK', 'aco_id' => '55', 'link' => '', 'menuexe' => '', 'menukode' => '116', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'GENERAL', 'menuseq' => '12', 'menuparent' => '3', 'menuicon' => 'FAS FA-CLINIC-MEDICAL', 'aco_id' => '0', 'link' => '', 'menuexe' => '', 'menukode' => '12', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'HARI LIBUR', 'menuseq' => '121', 'menuparent' => '14', 'menuicon' => 'fas fa-calendar-alt', 'aco_id' => '49', 'link' => '', 'menuexe' => '', 'menukode' => '121', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'UBAH PASSWORD', 'menuseq' => '0', 'menuparent' => '4', 'menuicon' => 'FAS FA-key', 'aco_id' => '58', 'link' => '', 'menuexe' => '', 'menukode' => '117', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'OWNER', 'menuseq' => '122', 'menuparent' => '14', 'menuicon' => 'fas fa-user-TIE', 'aco_id' => '60', 'link' => '', 'menuexe' => '', 'menukode' => '122', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'SATUAN', 'menuseq' => '123', 'menuparent' => '14', 'menuicon' => 'FAS FA-TAG', 'aco_id' => '66', 'link' => '', 'menuexe' => '', 'menukode' => '123', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'GROUP PRODUCT', 'menuseq' => '124', 'menuparent' => '14', 'menuicon' => 'FAS FA-BOXES', 'aco_id' => '72', 'link' => '', 'menuexe' => '', 'menukode' => '124', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'ARMADA', 'menuseq' => '125', 'menuparent' => '14', 'menuicon' => 'FAS FA-bicycle', 'aco_id' => '78', 'link' => '', 'menuexe' => '', 'menukode' => '125', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'KARYAWAN', 'menuseq' => '126', 'menuparent' => '14', 'menuicon' => 'fas fa-id-badge', 'aco_id' => '84', 'link' => '', 'menuexe' => '', 'menukode' => '126', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'BANK', 'menuseq' => '127', 'menuparent' => '14', 'menuicon' => 'fas fa-university', 'aco_id' => '90', 'link' => '', 'menuexe' => '', 'menukode' => '127', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'ALAT BAYAR', 'menuseq' => '128', 'menuparent' => '14', 'menuicon' => 'fas fa-hand-holding-usd', 'aco_id' => '96', 'link' => '', 'menuexe' => '', 'menukode' => '128', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'SUPPLIER', 'menuseq' => '129', 'menuparent' => '14', 'menuicon' => 'fas fa-address-book', 'aco_id' => '102', 'link' => '', 'menuexe' => '', 'menukode' => '129', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'GROUP CUSTOMER', 'menuseq' => '130', 'menuparent' => '14', 'menuicon' => 'fas fa-user-friends', 'aco_id' => '108', 'link' => '', 'menuexe' => '', 'menukode' => '2A', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'CUSTOMER', 'menuseq' => '131', 'menuparent' => '14', 'menuicon' => 'fas fa-users', 'aco_id' => '114', 'link' => '', 'menuexe' => '', 'menukode' => '2B', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'PRODUCT', 'menuseq' => '132', 'menuparent' => '14', 'menuicon' => 'fas fa-coins', 'aco_id' => '120', 'link' => '', 'menuexe' => '', 'menukode' => '2C', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'TRANSAKSI', 'menuseq' => '2', 'menuparent' => '0', 'menuicon' => 'fas fa-exchange-alt', 'aco_id' => '0', 'link' => '', 'menuexe' => '', 'menukode' => '2', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'PESANAN', 'menuseq' => '211', 'menuparent' => '28', 'menuicon' => 'fas fa-clipboard-list', 'aco_id' => '126', 'link' => '', 'menuexe' => '', 'menukode' => '211', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'PESANAN FINAL', 'menuseq' => '212', 'menuparent' => '28', 'menuicon' => 'fas fa-clipboard-check', 'aco_id' => '128', 'link' => '', 'menuexe' => '', 'menukode' => '212', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'PEMBELIAN', 'menuseq' => '213', 'menuparent' => '28', 'menuicon' => 'fas fa-shopping-basket', 'aco_id' => '140', 'link' => '', 'menuexe' => '', 'menukode' => '213', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'PENJUALAN', 'menuseq' => '215', 'menuparent' => '28', 'menuicon' => 'fas fa-cash-register', 'aco_id' => '131', 'link' => '', 'menuexe' => '', 'menukode' => '214', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'HUTANG', 'menuseq' => '3', 'menuparent' => '0', 'menuicon' => 'fas fa-money-check', 'aco_id' => '0', 'link' => '', 'menuexe' => '', 'menukode' => '3', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'HUTANG', 'menuseq' => '220', 'menuparent' => '33', 'menuicon' => 'fas fa-credit-card', 'aco_id' => '146', 'link' => '', 'menuexe' => '', 'menukode' => '220', 'modifiedby' => 'ADMIN',]);
        // menu::create(['menuname' => 'PELUNASAN HUTANG', 'menuseq' => '219', 'menuparent' => '33', 'menuicon' => 'fas fa-stamp', 'aco_id' => '173', 'link' => '', 'menuexe' => '', 'menukode' => '219', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'PIUTANG', 'menuseq' => '4', 'menuparent' => '0', 'menuicon' => 'fas fa-money-check', 'aco_id' => '148', 'link' => '', 'menuexe' => '', 'menukode' => '4', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'PIUTANG', 'menuseq' => '221', 'menuparent' => '35', 'menuicon' => 'fas fa-credit-card', 'aco_id' => '150', 'link' => '', 'menuexe' => '', 'menukode' => '221', 'modifiedby' => 'ADMIN',]);
        // menu::create(['menuname' => 'PELUNASAN PIUTANG', 'menuseq' => '222', 'menuparent' => '36', 'menuicon' => 'fas fa-stamp', 'aco_id' => '179', 'link' => '', 'menuexe' => '', 'menukode' => '222', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'CEK PESANAN', 'menuseq' => '216', 'menuparent' => '28', 'menuicon' => 'fas fa-check-double', 'aco_id' => '172', 'link' => '', 'menuexe' => '', 'menukode' => '216', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'TRANSAKSI BELANJA', 'menuseq' => '217', 'menuparent' => '28', 'menuicon' => 'fas fa-file-invoice', 'aco_id' => '185', 'link' => '', 'menuexe' => '', 'menukode' => '217', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'PERKIRAAN', 'menuseq' => '133', 'menuparent' => '14', 'menuicon' => 'fas fa-coins', 'aco_id' => '189', 'link' => '', 'menuexe' => '', 'menukode' => '2D', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'TRANSAKSI ARMADA', 'menuseq' => '218', 'menuparent' => '28', 'menuicon' => 'fas fa-truck', 'aco_id' => '195', 'link' => '', 'menuexe' => '', 'menukode' => '218', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'RETUR', 'menuseq' => '5', 'menuparent' => '0', 'menuicon' => 'fas fa-money-check', 'aco_id' => '197', 'link' => '', 'menuexe' => '', 'menukode' => '5', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'RETUR JUAL', 'menuseq' => '223', 'menuparent' => '41', 'menuicon' => 'fas fa-truck', 'aco_id' => '200', 'link' => '', 'menuexe' => '', 'menukode' => '223', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'RETUR BELI', 'menuseq' => '224', 'menuparent' => '41', 'menuicon' => 'fas fa-truck', 'aco_id' => '206', 'link' => '', 'menuexe' => '', 'menukode' => '224', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'STOK', 'menuseq' => '6', 'menuparent' => '0', 'menuicon' => 'fas fa-money-check', 'aco_id' => '209', 'link' => '', 'menuexe' => '', 'menukode' => '6', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'KARTU STOK', 'menuseq' => '225', 'menuparent' => '44', 'menuicon' => 'fas fa-truck', 'aco_id' => '212', 'link' => '', 'menuexe' => '', 'menukode' => '225', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'HARGA POKOK', 'menuseq' => '226', 'menuparent' => '44', 'menuicon' => 'fas fa-truck', 'aco_id' => '217', 'link' => '', 'menuexe' => '', 'menukode' => '226', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'PENYESUAIAN STOK', 'menuseq' => '227', 'menuparent' => '44', 'menuicon' => 'fas fa-truck', 'aco_id' => '222', 'link' => '', 'menuexe' => '', 'menukode' => '227', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'LAPORAN', 'menuseq' => '7', 'menuparent' => '0', 'menuicon' => 'fas fa-money-check', 'aco_id' => '223', 'link' => '', 'menuexe' => '', 'menukode' => '7', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'LAPORAN PROFIT', 'menuseq' => '228', 'menuparent' => '48', 'menuicon' => 'fas fa-truck', 'aco_id' => '212', 'link' => '', 'menuexe' => '', 'menukode' => '228', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'LAPORAN PROFIT OWNER', 'menuseq' => '229', 'menuparent' => '48', 'menuicon' => 'fas fa-truck', 'aco_id' => '212', 'link' => '', 'menuexe' => '', 'menukode' => '229', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'LAPORAN PIUTANG OWNER', 'menuseq' => '230', 'menuparent' => '48', 'menuicon' => 'fas fa-truck', 'aco_id' => '212', 'link' => '', 'menuexe' => '', 'menukode' => '230', 'modifiedby' => 'ADMIN',]);
        menu::create(['menuname' => 'LAPORAN KARTU STOK', 'menuseq' => '231', 'menuparent' => '48', 'menuicon' => 'fas fa-truck', 'aco_id' => '212', 'link' => '', 'menuexe' => '', 'menukode' => '231', 'modifiedby' => 'ADMIN',]);
    }   
}
