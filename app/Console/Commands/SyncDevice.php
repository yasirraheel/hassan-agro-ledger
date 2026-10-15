<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\AttendanceDeviceAddon\App\Models\AttendanceDevice;

class SyncDevice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync-device:zkteco';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command sync zkteco device data with the software';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $devices = AttendanceDevice::active()->get();
        echo "Total active Device: ".$devices->count()." <br>";
        sync_attendance_device_data($devices);
        echo "Data from all devices synced successfully!";
    }
}
