<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Error;
use Illuminate\Support\Facades\DB;


class ErrorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // DB::statement("delete [error]");
        // DB::statement("DBCC CHECKIDENT ('[error]', RESEED, 1);");

        error::create(['kodeerror' => 'WI', 'keterangan' => 'WAJIB DI ISI', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'SPI', 'keterangan' => 'SUDAH PERNAH INPUT', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'SAP', 'keterangan' => 'SUDAH DI APPROVAL', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'BADJ', 'keterangan' => 'EDIT/DELETE TIDAK DIPERBOLEHKAN. KARENA DATA BUKAN BERASAL DARI JURNAL UMUM.', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'SDC', 'keterangan' => 'SUDAH CETAK', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'WP', 'keterangan' => 'WAJIB DI PILIH', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'EMAIL', 'keterangan' => 'HARUS ALAMAT E-MAIL YANG VALID', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'GT-ANGKA-0', 'keterangan' => 'NILAI HARUS LEBIH BESAR DARI 0', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'MIN', 'keterangan' => 'HARUS DIBAWAH', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'BTSANGKA', 'keterangan' => 'ANGKA', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'BATASNILAI', 'keterangan' => 'HARUS', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'MAX', 'keterangan' => 'HARUS DIATAS', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'TSF', 'keterangan' => 'ISIAN TIDAK SESUAI FORMAT', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'NTC', 'keterangan' => 'NILAI TIDAK COCOK', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'FXLS', 'keterangan' => 'HARUS BERTIPE XLS ATAU XLSX', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'SATL', 'keterangan' => 'PROSES TIDAK BISA LANJUT KARENA SUDAH DIPAKAI DI TRANSAKSI LAIN', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'STM', 'keterangan' => 'SISA TIDAK BOLEH MINUS', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'TDT', 'keterangan' => 'TRANSAKSI BERASAL DARI INPUTAN TRANSAKSI LAIN', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'ETS', 'keterangan' => 'HANYA BISA EDIT/DELETE DI TANGGAL YANG SAMA', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'TBT', 'keterangan' => 'TIDAK BISA MEMILIH TANGGAL TERSEBUT', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'TSTB', 'keterangan' => 'TANGGAL SUDAH TIDAK BERLAKU', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'TSPTB', 'keterangan' => 'TANGGAL TIDAK BISA DI PROSES  SEBELUM TANGGAL TUTUP BUKU', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'HF', 'keterangan' => 'FORMAT JAM SALAH', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'DF', 'keterangan' => 'FORMAT TANGGAL SALAH', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'ENTER', 'keterangan' => 'TEKAN ENTER PADA CELL UNTUK MENYIMPAN PERUBAHAN', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'WG', 'keterangan' => 'FILE HARUS BERUPA GAMBAR', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'TBMINUS', 'keterangan' => 'NILAI TIDAK BOLEH MINUS', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'NTM', 'keterangan' => 'NILAI TIDAK BOLEH MINUS', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'NTLK', 'keterangan' => 'NILAI TIDAK BOLEH < DARI', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'NTLB', 'keterangan' => 'NILAI TIDAK BOLEH > DARI', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'HPDL', 'keterangan' => 'HARAP PILIH DARI LIST', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'FTTS', 'keterangan' => 'FORMAT TANGGAL TIDAK SESUAI DENGAN', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'TVD', 'keterangan' => 'DATA YANG DIMASUKAN TIDAK VALID', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'TTLK', 'keterangan' => 'TANGGAL TIDAK BOLEH < DARI', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'HSD', 'keterangan' => 'HARUS SAMA DENGAN', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'HDSD', 'keterangan' => 'HARUS DIATAS ATAU SAMA DENGAN', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'HBSD', 'keterangan' => 'HARUS DIBAWAH ATAU SAMA DENGAN', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'DDTA', 'keterangan' => 'DATA DETAIL TIDAK ADA', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'URBA', 'keterangan' => 'UPAH RITASI BELUM ADA', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'PSB', 'keterangan' => 'HARAP PILIH SALAH SATU BARIS	', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'DTA', 'keterangan' => 'DATA TIDAK ADA', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'BAED', 'keterangan' => 'BUKA APPROVAL EDIT DATA', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'TEPT', 'keterangan' => 'DATA TIDAK BISA DIEDIT PADA TANGGAL INI', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'NTS', 'keterangan' => 'NILAI TIDAK BOLEH SAMA', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'TED', 'keterangan' => 'TRANSAKSI TIDAK BISA DIEDIT', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'CTPHS', 'keterangan' => 'CUSTOMER & TANGGAL PENGIRIMAN HARUS SAMA ATAU DATA BELUM DI CREATE PENJUALAN', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'TBDCPJ', 'keterangan' => 'TIDAK BISA DELETE PESANAN FINAL KRN SUDAH CREATE PENJUALAN', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'SPHA', 'keterangan' => 'STATUS PESANAN FINAL HARUS AKTIF', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'NBPBSA', 'keterangan' => 'NO BUKTI PEMBELIAN SUDAH ADA', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'HPKSPC', 'keterangan' => 'HAPUS PEMBELIAN TERLEBIH DAHULU KARENA SUDAH PERNAH CREATE', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'SPESC', 'keterangan' => 'SEMUA PEMBELIAN SUDAH DI CREATE', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'TBDPF', 'keterangan' => 'TRANSAKSI TSB TIDAK BOLEH DI EDIT/DELETE KRN TRANSAKSI BERASAL DARI PESANAN FINAL', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'TBSCRP', 'keterangan' => 'TRANSAKSI TSB TIDAK BOLEH DI EDIT/DELETE KRN TRANSAKSI SUDAH CETAK REPORT PEMBELIAN', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'PTBH', 'keterangan' => 'PEMBELIAN TIDAK BOLEH DIHAPUS KRN TIDAK ADA DATA PENJUALAN BARU', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'PTBC', 'keterangan' => 'PESANAN TIDAK BISA DI CREATE KE PENJUALAN KARENA SUDAH DI COMBINE', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'TSCP', 'keterangan' => 'TRANSAKSI TSB TIDAK BOLEH DI EDIT/DELETE KRN TRANSAKSI SUDAH CREATE PENJUALAN', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'SMBE', 'keterangan' => 'SUDAH MELEWATI BATAS EDIT', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'DSE', 'keterangan' => 'DATA DI FORM INI,SEDANG DI EDIT', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'SDE', 'keterangan' => 'SEDANG DIEDIT', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'NBPK', 'keterangan' => 'NOMOR BUKTI PEMBELIAN KOSONG, CREATE PEMBELIAN TERLEBIH DAHULU', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'NBBP', 'keterangan' => 'NOMOR BUKTI PENJUALAN KOSONG, TIDAK BISA MELAKUKAN BATAL PENJUALAN', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'NBCP', 'keterangan' => 'NOMOR BUKTI PENJUALAN KOSONG, TIDAK BISA MELAKUKAN CREATE PENJUALAN', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'TBEPH', 'keterangan' => 'TIDAK BISA EDIT PEMBELIAN KRN SUDAH MELAKUKAN PELUNASAN HUTANG', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'TBDPH', 'keterangan' => 'TIDAK BISA DELETE PEMBELIAN KRN SUDAH MELAKUKAN PELUNASAN HUTANG', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'TBEPP', 'keterangan' => 'TIDAK BISA EDIT PENJUALAN KRN SUDAH MELAKUKAN PELUNASAN PIUTANG', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'TBDPP', 'keterangan' => 'TIDAK BISA DELETE PENJUALAN KRN SUDAH MELAKUKAN PELUNASAN PIUTANG', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'TBEPFP', 'keterangan' => 'TIDAK BISA EDIT PESANAN FINAL KRN SUDAH MELAKUKAN PELUNASAN PIUTANG', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'TBECPJ', 'keterangan' => 'TIDAK BISA EDIT PESANAN FINAL KRN SUDAH CREATE PENJUALAN', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'CTB', 'keterangan' => 'CUSTOMER TIDAK BISA DI ', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'CTB', 'keterangan' => 'CUSTOMER TIDAK BISA DI ', 'modifiedby' => 'ADMIN',]);
        error::create(['kodeerror' => 'NBPJSA', 'keterangan' => 'NOMOR BUKTI PENJUALAN SUDAH ADA', 'modifiedby' => 'ADMIN',]);

    }
}