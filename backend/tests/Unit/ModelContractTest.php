<?php

namespace Tests\Unit;

use App\Models\Paquetes\P1_SeguridadAdministracion\Auditoria;
use App\Models\Paquetes\P1_SeguridadAdministracion\Especialidad;
use App\Models\Paquetes\P1_SeguridadAdministracion\Rol;
use App\Models\Paquetes\P2_PersonalMedico\HorarioAtencion;
use App\Models\Paquetes\P2_PersonalMedico\Medico;
use App\Models\Paquetes\P3_GestionPacientes\Paciente;
use App\Models\Paquetes\P4_GestionCitas\CitaMedica;
use App\Models\Paquetes\P4_GestionCitas\EstadoCita;
use App\Models\Paquetes\P4_GestionCitas\HistorialCambioCita;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\PostgresConnection;
use LogicException;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

class ModelContractTest extends TestCase
{
    private ?ConnectionResolverInterface $originalResolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalResolver = Model::getConnectionResolver();
        // Solo permite construir SQL con la gramática PostgreSQL. Nunca abre una conexión.
        $connection = new PostgresConnection(
            fn () => throw new LogicException('Estas pruebas no pueden consultar ni modificar una base de datos.'),
            'model_contract_tests',
            '',
            ['driver' => 'pgsql'],
        );
        $resolver = new ConnectionResolver(['model_tests' => $connection]);
        $resolver->setDefaultConnection('model_tests');
        Model::setConnectionResolver($resolver);
    }

    protected function tearDown(): void
    {
        if ($this->originalResolver === null) {
            Model::unsetConnectionResolver();
        } else {
            Model::setConnectionResolver($this->originalResolver);
        }

        parent::tearDown();
    }

    public function test_model_metadata_matches_the_actual_migration_columns(): void
    {
        $schema = $this->migrationSchema();

        foreach ($this->modelClasses() as $class) {
            $model = new $class;
            $table = $model->getTable();

            $this->assertArrayHasKey($table, $schema, $class);
            $this->assertSame($schema[$table]['primaryKey'], $model->getKeyName(), $class);
            $this->assertSame($schema[$table]['incrementing'], $model->getIncrementing(), $class);
            $this->assertSame('int', $model->getKeyType(), $class);
            $this->assertFalse($model->usesTimestamps(), $class);
            $this->assertNotEmpty($model->getFillable(), $class);

            foreach (array_unique([...$model->getFillable(), ...array_keys($model->getCasts()), ...$model->getHidden()]) as $attribute) {
                $this->assertContains($attribute, $schema[$table]['columns'], $class.'::'.$attribute);
            }
        }

        // Las marcas temporales administradas por PostgreSQL no admiten asignación masiva.
        $this->assertFalse((new CitaMedica)->isFillable('fecha_creacion'));
        $this->assertFalse((new Auditoria)->isFillable('fecha_hora'));
        $this->assertFalse((new HistorialCambioCita)->isFillable('fecha_modificacion'));
        $this->assertTrue((new EstadoCita)->isFillable('id_estado_cita'));
    }

    public function test_namespaces_follow_the_package_paths_and_no_model_is_duplicated(): void
    {
        $modelRoot = dirname(__DIR__, 2).'/app/Models';
        $modelFiles = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($modelRoot)) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $modelFiles[] = $file->getBasename('.php');
            }
        }

        $this->assertCount(10, $modelFiles);
        $this->assertCount(10, array_unique($modelFiles));
        $this->assertSame(1, count(array_filter($modelFiles, fn ($name) => $name === 'User')));

        foreach ($this->modelClasses() as $class) {
            $expectedFile = dirname(__DIR__, 2).'/app/'.str_replace('\\', '/', substr($class, strlen('App\\'))).'.php';
            $this->assertSame(realpath($expectedFile), (new ReflectionClass($class))->getFileName());
        }
    }

    public function test_belongs_to_relations_use_the_expected_foreign_and_owner_keys(): void
    {
        $relations = [
            [User::class, 'rol', Rol::class, 'id_rol', 'id_rol'],
            [User::class, 'paciente', Paciente::class, 'id_paciente', 'id_paciente'],
            [User::class, 'medico', Medico::class, 'id_medico', 'id_medico'],
            [Auditoria::class, 'usuario', User::class, 'id_usuario', 'id_usuario'],
            [HorarioAtencion::class, 'medico', Medico::class, 'id_medico', 'id_medico'],
            [CitaMedica::class, 'paciente', Paciente::class, 'id_paciente', 'id_paciente'],
            [CitaMedica::class, 'medico', Medico::class, 'id_medico', 'id_medico'],
            [CitaMedica::class, 'especialidad', Especialidad::class, 'id_especialidad', 'id_especialidad'],
            [CitaMedica::class, 'estado', EstadoCita::class, 'id_estado_cita', 'id_estado_cita'],
            [HistorialCambioCita::class, 'cita', CitaMedica::class, 'id_cita', 'id_cita'],
            [HistorialCambioCita::class, 'usuario', User::class, 'id_usuario', 'id_usuario'],
            [HistorialCambioCita::class, 'estadoAnterior', EstadoCita::class, 'estado_anterior', 'id_estado_cita'],
            [HistorialCambioCita::class, 'estadoNuevo', EstadoCita::class, 'estado_nuevo', 'id_estado_cita'],
        ];

        foreach ($relations as [$class, $method, $related, $foreignKey, $ownerKey]) {
            $model = new $class;
            $model->setRawAttributes([$foreignKey => 17]);
            $relation = $model->$method();

            $this->assertInstanceOf(BelongsTo::class, $relation);
            $this->assertInstanceOf($related, $relation->getRelated());
            $this->assertSame($foreignKey, $relation->getForeignKeyName());
            $this->assertSame($ownerKey, $relation->getOwnerKeyName());
            $this->assertStringContainsString('"'.$relation->getRelated()->getTable().'"."'.$ownerKey.'" = ?', $relation->toSql());
            $this->assertSame([17], $relation->getBindings());
        }
    }

    public function test_has_one_and_has_many_relations_use_the_real_parent_keys(): void
    {
        $relations = [
            [Rol::class, 'usuarios', User::class, HasMany::class, 'id_rol', 'id_rol'],
            [User::class, 'auditorias', Auditoria::class, HasMany::class, 'id_usuario', 'id_usuario'],
            [User::class, 'historialCambiosCita', HistorialCambioCita::class, HasMany::class, 'id_usuario', 'id_usuario'],
            [Paciente::class, 'usuario', User::class, HasOne::class, 'id_paciente', 'id_paciente'],
            [Medico::class, 'usuario', User::class, HasOne::class, 'id_medico', 'id_medico'],
            [Paciente::class, 'citas', CitaMedica::class, HasMany::class, 'id_paciente', 'id_paciente'],
            [Medico::class, 'citas', CitaMedica::class, HasMany::class, 'id_medico', 'id_medico'],
            [Medico::class, 'horariosAtencion', HorarioAtencion::class, HasMany::class, 'id_medico', 'id_medico'],
            [Especialidad::class, 'citas', CitaMedica::class, HasMany::class, 'id_especialidad', 'id_especialidad'],
            [EstadoCita::class, 'citas', CitaMedica::class, HasMany::class, 'id_estado_cita', 'id_estado_cita'],
            [CitaMedica::class, 'historialCambios', HistorialCambioCita::class, HasMany::class, 'id_cita', 'id_cita'],
        ];

        foreach ($relations as [$class, $method, $related, $relationType, $foreignKey, $localKey]) {
            $model = new $class;
            $model->setRawAttributes([$localKey => 17]);
            $relation = $model->$method();

            $this->assertInstanceOf($relationType, $relation);
            $this->assertInstanceOf($related, $relation->getRelated());
            $this->assertSame($foreignKey, $relation->getForeignKeyName());
            $this->assertSame($localKey, $relation->getLocalKeyName());
            $this->assertStringContainsString('"'.$relation->getRelated()->getTable().'"."'.$foreignKey.'" = ?', $relation->toSql());
            $this->assertSame([17], $relation->getBindings());
        }
    }

    public function test_many_to_many_relations_use_the_existing_pivot_without_timestamps(): void
    {
        $medico = new Medico;
        $medico->setRawAttributes(['id_medico' => 17]);
        $especialidad = new Especialidad;
        $especialidad->setRawAttributes(['id_especialidad' => 17]);

        foreach ([$medico->especialidades(), $especialidad->medicos()] as $relation) {
            $this->assertSame('medicos_especialidades', $relation->getTable());
            $this->assertSame($relation->getParent()->getKeyName(), $relation->getForeignPivotKeyName());
            $this->assertSame($relation->getRelated()->getKeyName(), $relation->getRelatedPivotKeyName());
            $this->assertSame($relation->getParent()->getKeyName(), $relation->getParentKeyName());
            $this->assertSame($relation->getRelated()->getKeyName(), $relation->getRelatedKeyName());
            $this->assertSame([], $relation->getPivotColumns());
            $this->assertFalse($relation->withTimestamps);
            $this->assertStringContainsString('inner join "medicos_especialidades"', $relation->toSql());
            $this->assertSame([17], $relation->getBindings());
        }
    }

    public function test_casts_preserve_dates_times_integers_json_and_nullable_values(): void
    {
        $paciente = new Paciente;
        $paciente->setRawAttributes(['fecha_nacimiento' => '2000-02-15', 'estado' => 0, 'tipo_paciente' => null]);
        $this->assertInstanceOf(CarbonImmutable::class, $paciente->fecha_nacimiento);
        $this->assertSame('2000-02-15', $paciente->makeVisible('fecha_nacimiento')->toArray()['fecha_nacimiento']);
        $this->assertFalse($paciente->estado);
        $this->assertNull($paciente->tipo_paciente);

        $horario = new HorarioAtencion;
        $horario->setRawAttributes(['dia_semana' => '5', 'hora_inicio' => '09:30:00', 'hora_fin' => '11:00:00', 'duracion_cita_minutos' => '30']);
        $this->assertSame(5, $horario->dia_semana);
        $this->assertSame(30, $horario->duracion_cita_minutos);
        $this->assertSame('09:30:00', $horario->hora_inicio);
        $this->assertSame('11:00:00', $horario->hora_fin);

        $cita = new CitaMedica;
        $cita->setRawAttributes([
            'fecha_cita' => '2026-10-30', 'hora_cita' => '09:30:00', 'duracion_minutos' => '30',
            'fecha_creacion' => '2026-10-30 09:00:00-04:00', 'fecha_hora_llegada' => null,
        ]);
        $this->assertSame('2026-10-30', $cita->toArray()['fecha_cita']);
        $this->assertSame('09:30:00', $cita->hora_cita);
        $this->assertSame(30, $cita->duracion_minutos);
        $this->assertInstanceOf(CarbonImmutable::class, $cita->fecha_creacion);
        $this->assertSame(-14400, $cita->fecha_creacion->getOffset());
        $this->assertNull($cita->fecha_hora_llegada);

        $auditoria = new Auditoria;
        $auditoria->setRawAttributes(['detalle' => '{"resultado":"denegado"}', 'id_usuario' => null, 'id_registro_afectado' => null]);
        $this->assertSame(['resultado' => 'denegado'], $auditoria->detalle);
        $this->assertNull($auditoria->id_usuario);
        $this->assertNull($auditoria->id_registro_afectado);

        $historial = new HistorialCambioCita;
        $historial->setRawAttributes(['estado_anterior' => null, 'estado_nuevo' => '2', 'fecha_hora_anterior' => null, 'fecha_modificacion' => '2026-10-30 09:00:00-04:00']);
        $this->assertNull($historial->estado_anterior);
        $this->assertSame(2, $historial->estado_nuevo);
        $this->assertNull($historial->fecha_hora_anterior);
        $this->assertInstanceOf(CarbonImmutable::class, $historial->fecha_modificacion);
    }

    public function test_sensitive_attributes_are_hidden_even_in_loaded_relations(): void
    {
        $models = [new Paciente, new CitaMedica, new HistorialCambioCita, new Auditoria, new User];

        foreach ($models as $model) {
            $hidden = $model->getHidden();
            $this->assertNotEmpty($hidden);
            $model->setRawAttributes(array_fill_keys($hidden, null));

            $this->assertSame([], array_intersect_key($model->toArray(), array_flip($hidden)));
            $this->assertSame([], array_intersect_key(json_decode($model->toJson(), true), array_flip($hidden)));

            $wrapper = new CitaMedica;
            $wrapper->setRelation('relacion_de_prueba', $model);
            $serialized = $wrapper->toArray()['relacion_de_prueba'];
            $this->assertSame([], array_intersect_key($serialized, array_flip($hidden)));
        }

        $paciente = new Paciente;
        $paciente->setRawAttributes(['alergias' => 'Dato ficticio de prueba', 'informacion_medica_basica' => 'Dato ficticio de prueba']);
        $this->assertSame('Dato ficticio de prueba', $paciente->alergias);
        $this->assertArrayNotHasKey('alergias', $paciente->toArray());
        $this->assertArrayNotHasKey('informacion_medica_basica', $paciente->toArray());
    }

    private function modelClasses(): array
    {
        return [User::class, Rol::class, Auditoria::class, Especialidad::class, Medico::class, HorarioAtencion::class, Paciente::class, EstadoCita::class, CitaMedica::class, HistorialCambioCita::class];
    }

    private function migrationSchema(): array
    {
        $schema = [];
        $pattern = <<<'REGEX'
            /Schema::create\('(?<table>[a-z_]+)', function \(Blueprint \$table\) \{(?<body>.*?)^\s*\}\);/ms
            REGEX;

        foreach (glob(dirname(__DIR__, 2).'/database/migrations/*.php') as $file) {
            preg_match_all($pattern, file_get_contents($file), $tables, PREG_SET_ORDER);

            foreach ($tables as $table) {
                preg_match_all('/\$table->\w+\(\'([a-z_]+)\'/', $table['body'], $columns);
                preg_match('/\$table->bigIncrements\(\'([a-z_]+)\'\)/', $table['body'], $primaryKey);
                $incrementing = $primaryKey !== [];

                if (! $incrementing) {
                    preg_match('/\$table->unsignedBigInteger\(\'([a-z_]+)\'\)->primary\(\)/', $table['body'], $primaryKey);
                }

                $schema[$table['table']] = [
                    'columns' => array_unique($columns[1]),
                    'primaryKey' => $primaryKey[1] ?? null,
                    'incrementing' => $incrementing,
                ];
            }
        }

        $this->assertCount(11, $schema);

        return $schema;
    }
}
