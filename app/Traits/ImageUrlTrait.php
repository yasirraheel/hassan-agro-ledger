<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait ImageUrlTrait
{
    public function toArray()
    {
        $array = parent::toArray();

        if (property_exists($this, 'imageFields') && is_array($this->imageFields)) {
            foreach ($this->imageFields as $field) {
                if (!empty($array[$field]) && !filter_var($array[$field], FILTER_VALIDATE_URL)) {
                    $array[$field] = Storage::url($array[$field]);
                }
            }
        }

        return $array;
    }

    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if (property_exists($this, 'imageFields') && in_array($key, $this->imageFields) && $value) {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return Storage::url($value);
            }
        }

        return $value;
    }
}