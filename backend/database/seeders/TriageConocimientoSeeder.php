<?php

namespace Database\Seeders;

use App\Services\Triage\KnowledgeBaseSyncService;
use Illuminate\Database\Seeder;

class TriageConocimientoSeeder extends Seeder
{
    public function run(KnowledgeBaseSyncService $knowledgeBase): void
    {
        $resultado = $knowledgeBase->sincronizarPerfiles(false);

        if ($this->command) {
            $this->command->info(sprintf(
                'Base de conocimientos clínica vectorizada: %d perfiles sincronizados (proveedor: %s).',
                $resultado['sincronizados'],
                $resultado['proveedor']
            ));
        }
    }
}
