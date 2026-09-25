<?php

namespace App\Filament\Resources\AutomationJobs\Pages;

use App\Domain\Automation\Actions\RequestAutomationJob;
use App\Domain\Automation\Data\AutomationJobRequest;
use App\Enum\Automation\AutomationJobSource;
use App\Filament\Resources\AutomationJobs\AutomationJobResource;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Validation\ValidationException;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class ListAutomationJobs extends ListRecords
{
    protected static string $resource = AutomationJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('requestDailyTripSummary')
                ->label('Solicitar viagens encerradas')
                ->icon('heroicon-o-arrow-up-tray')
                ->schema([
                    DateRangePicker::make('periodo')
                        ->label('Período do relatório')
                        ->defaultYesterday()
                        ->autoApply()
                        ->firstDayOfWeek(0)
                        ->alwaysShowCalendar()
                        ->maxDate(today())
                        ->required(),
                ])
                ->action(function (array $data, RequestAutomationJob $requestAutomationJob): void {
                    [$from, $to] = $this->parseDateRange((string) $data['periodo']);
                    $job = $requestAutomationJob->handle(new AutomationJobRequest(
                        reportKey: 'daily_trip_summary',
                        parameters: [
                            'from' => $from,
                            'to' => $to,
                        ],
                        source: AutomationJobSource::MANUAL,
                        requestedByUserId: auth()->id(),
                        idempotencyKey: 'manual:daily_trip_summary:'.auth()->id().':'.$from.':'.$to,
                    ));

                    Notification::make()
                        ->title('Solicitação criada')
                        ->body('Job local #'.$job->id.' criado e enviado para a fila.')
                        ->success()
                        ->send();
                }),
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parseDateRange(string $period): array
    {
        $dates = array_map('trim', explode(' - ', $period, 2));

        if (count($dates) !== 2 || in_array('', $dates, true)) {
            throw ValidationException::withMessages([
                'periodo' => 'Informe um intervalo de datas válido.',
            ]);
        }

        try {
            $from = CarbonImmutable::createFromFormat('!d/m/Y', $dates[0]);
            $to = CarbonImmutable::createFromFormat('!d/m/Y', $dates[1]);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'periodo' => 'Informe um intervalo de datas válido.',
            ]);
        }

        if ($from === false || $to === false || $from->format('d/m/Y') !== $dates[0] || $to->format('d/m/Y') !== $dates[1] || $from->isAfter($to)) {
            throw ValidationException::withMessages([
                'periodo' => 'Informe um intervalo de datas válido.',
            ]);
        }

        return [$from->toDateString(), $to->toDateString()];
    }
}
