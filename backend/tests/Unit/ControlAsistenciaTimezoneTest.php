<?php

namespace Tests\Unit;

use App\Services\Paquetes\P1_SeguridadAdministracion\AuditService;
use App\Services\Paquetes\P5_ControlAtencion\ControlAsistenciaService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class ControlAsistenciaTimezoneTest extends TestCase
{
    public function test_explicit_arrival_time_aligns_with_appointment_date_in_bolivia_timezone(): void
    {
        $service = new ControlAsistenciaService(new AuditService);

        $llegada = $service->resolverFechaHoraLlegada('2026-11-16', '09:00:00', '08:55');

        $this->assertSame('America/La_Paz', $llegada->timezoneName);
        $this->assertSame('2026-11-16 08:55:00', $llegada->format('Y-m-d H:i:s'));
    }

    public function test_same_day_arrival_within_two_hours_uses_reference_clock_in_bolivia_timezone(): void
    {
        $service = new ControlAsistenciaService(new AuditService);
        $ahoraRef = Carbon::createFromFormat('Y-m-d H:i:s', '2026-11-19 09:04:00', 'America/La_Paz');

        $llegada = $service->resolverFechaHoraLlegada('2026-11-19', '09:00:00', null, $ahoraRef);

        $this->assertSame('America/La_Paz', $llegada->timezoneName);
        $this->assertSame('2026-11-19 09:04:00', $llegada->format('Y-m-d H:i:s'));
    }

    public function test_different_date_or_out_of_window_arrival_aligns_to_appointment_slot(): void
    {
        $service = new ControlAsistenciaService(new AuditService);
        $ahoraRef = Carbon::createFromFormat('Y-m-d H:i:s', '2026-11-19 21:43:00', 'America/La_Paz');

        $llegada = $service->resolverFechaHoraLlegada('2026-11-16', '09:00:00', null, $ahoraRef);

        $this->assertSame('America/La_Paz', $llegada->timezoneName);
        $this->assertSame('2026-11-16 09:00:00', $llegada->format('Y-m-d H:i:s'));
    }
}
