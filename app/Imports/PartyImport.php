<?php

namespace App\Imports;

use App\Models\Party;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class PartyImport implements ToCollection
{
    protected $businessId;
    protected $existingParties = [];

    public function __construct($businessId)
    {
        $this->businessId = $businessId;
        $this->existingParties = Party::where('business_id', $businessId)->pluck('phone')->toArray();
    }

    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $index => $row) {
                if ($index === 0) continue; // Skip header row

                // Read Excel columns
                $name         = trim($row[0]);
                $type         = ucfirst(trim($row[1]));
                $phone        = trim($row[2]);
                $balance      = trim($row[3] ?? 0);
                $balanceType  = trim($row[4] ?? null);
                $email        = trim($row[5]);
                $creditLimit  = trim($row[6] ?? 0);
                $address      = trim($row[7]);
                $taxNo        = trim($row[8] ?? null);

                if (!$name || !$type) continue;
                if (in_array($phone, $this->existingParties)) continue;
                if (!in_array($type, ['Retailer', 'Dealer', 'Wholesaler', 'Supplier', 'Customer'])) continue;

                Party::create([
                    'name' => $name,
                    'phone' => $phone,
                    'email' => $email,
                    'address' => $address,
                    'tax_no' => $taxNo,
                    'credit_limit' => $creditLimit,
                    'opening_balance' => $balance,
                    'business_id' => $this->businessId,
                    'opening_balance_type' => $balanceType,
                    'due' => $balanceType == 'due' ? $balance : 0,
                    'wallet' => $balanceType == 'due' ? 0 : $balance,
                    'type' => $type == 'Customer' ? 'Retailer' : $type,
                ]);
            }
        });
    }
}
