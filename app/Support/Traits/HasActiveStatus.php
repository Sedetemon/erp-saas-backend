<?php

namespace App\Support\Traits;

trait HasActiveStatus
{

    public function scopeActive($query)
    {
        return $query->where(
            'is_active',
            true
        );
    }


    public function activate(): void
    {
        $this->update([
            'is_active' => true
        ]);
    }


    public function deactivate(): void
    {
        $this->update([
            'is_active' => false
        ]);
    }

}
