<?php

namespace App\Services\ERP;

/**
 * Registre central des modules ERP.
 *
 * Rôle: garder la trace des modules ERP enregistrés dynamiquement
 * (facturation, RH, stock, etc.) au fur et à mesure qu'ils sont
 * construits dans app/Modules. Pour l'instant, aucun module n'est
 * encore branché — cette classe existe pour que ERPServiceProvider
 * puisse démarrer l'application sans erreur, et sert de point
 * d'ancrage pour la suite (chargement des ModuleServiceProvider
 * de chaque module ERP).
 */
class ERPManager
{
    /**
     * @var array<string, array{name: string, active: bool}>
     */
    protected array $modules = [];

    public function register(string $key, string $name, bool $active = true): void
    {
        $this->modules[$key] = [
            'name' => $name,
            'active' => $active,
        ];
    }

    public function has(string $key): bool
    {
        return isset($this->modules[$key]);
    }

    public function isActive(string $key): bool
    {
        return $this->modules[$key]['active'] ?? false;
    }

    /**
     * @return array<string, array{name: string, active: bool}>
     */
    public function all(): array
    {
        return $this->modules;
    }
}
