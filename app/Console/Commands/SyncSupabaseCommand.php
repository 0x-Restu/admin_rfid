<?php

namespace App\Console\Commands;

use App\Services\SupabaseService;
use Illuminate\Console\Command;

class SyncSupabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supabase:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi data langsung dari REST API Supabase';

    /**
     * Execute the console command.
     */
    public function handle(SupabaseService $supabase)
    {
        $this->info("Menghubungi Supabase REST API...");
        $stats = $supabase->syncAll();

        $this->table(['Entitas', 'Jumlah Data'], [
            ['Data Orang Tua (parents)', $stats['parents']],
            ['Data Siswa (students)', $stats['students']],
            ['Kartu RFID (cards)', $stats['cards']],
            ['Transaksi Kantin (canteen_transactions)', $stats['canteen_transactions']],
            ['Presensi Gerbang (attendances)', $stats['attendances']],
            ['Tagihan SPP (spp_bills)', $stats['spp_bills']],
        ]);

        $this->info("Sinkronisasi data API berhasil selesai.");
        return Command::SUCCESS;
    }
}
