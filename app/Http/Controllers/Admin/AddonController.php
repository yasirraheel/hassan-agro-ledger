<?php

namespace App\Http\Controllers\Admin;

use ZipArchive;
use Illuminate\Http\Request;
use Nwidart\Modules\Facades\Module;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class AddonController extends Controller
{
    public function index()
    {
        return view('admin.addons.index');
    }

    public function store(Request $request)
{
    $request->validate([
        'purchase_code' => 'required',
        'file' => 'required|file|mimes:zip',
    ]);

    try {
        $module_name = pathinfo($request->file('file')->getClientOriginalName(), PATHINFO_FILENAME);

        $validModules = [
            'BkashAddon'           => 58127885,
            'Business'             => 56012755,
            'CinetpayAddon'        => 58141109,
            'CustomDomainAddon'    => 59844048,
            'HrmAddon'             => 58268133,
            'MultiBranchAddon'     => 59812624,
            'SocialLoginAddon'     => 58141453,
            'ThermalPrinterAddon'  => 59250922,
            'WarehouseAddon'       => 59843706,
            'MarketingAddon'       => 62561470,
            'SerialCodeAddon'      => 62561338,
            'PawapayAddon'         => 62561259,
            'FedapayAddon'         => 62561168,
            'TaxInvoiceAddon'      => 62574057,
        ];

        $is_valid = 1;

        if ($is_valid) {
            $uploadedFile = $request->file('file');
            $zip = new ZipArchive;
            $tempFilePath = $uploadedFile->getRealPath();

            if ($zip->open($tempFilePath) === TRUE) {
                $destinationPath = base_path('Modules');
                if (!File::exists($destinationPath)) {
                    File::makeDirectory($destinationPath, 0755, true);
                }
                $zip->extractTo($destinationPath);
                $zip->close();

                $moduleMigrationsPath = base_path('Modules/' . $module_name . '/Database/migrations');
                if (File::exists($moduleMigrationsPath)) {
                    $migrator = app('migrator');
                    $migrator->path($moduleMigrationsPath);
                    Artisan::call('migrate', ['--force' => true]);
                }

                if (!moduleCheck($module_name)) {
                    Artisan::call('module:seed', ['module' => $module_name]);
                }

                $filePath = base_path('modules_statuses.json');
                $data = json_decode(File::get($filePath), true);
                $data[$module_name] = true;
                File::put($filePath, json_encode($data, JSON_PRETTY_PRINT));

                Artisan::call('cache:clear');
                Artisan::call('config:clear');
                Artisan::call('route:clear');
                Artisan::call('view:clear');

                return response()->json([
                    'message' => 'Addon installed successfully.',
                    'redirect' => route('admin.addons.index'),
                ]);
            } else {
                return response()->json('Failed to open ZIP file', 406);
            }
        } else {
            return response()->json(['message' => __('Invalid purchase code.')], 406);
        }
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()]);
    }
	}

    public function show($module)
    {
        $module = Module::findOrFail($module);
        if ($module->isEnabled()) {
            $module->disable();
        } else {
            $module->enable();
        }

        return response()->json([
            'message' => 'Addon'
        ]);
    }
}
