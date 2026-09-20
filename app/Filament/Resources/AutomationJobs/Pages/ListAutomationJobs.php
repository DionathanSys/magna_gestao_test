<?php

namespace App\Filament\Resources\AutomationJobs\Pages;

use App\Domain\Automation\Actions\RequestAutomationJob;
use App\Domain\Automation\Data\AutomationJobRequest;
use App\Enum\Automation\AutomationJobSource;
use App\Filament\Resources\AutomationJobs\AutomationJobResource;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

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
                    DatePicker::make('date')
                        ->label('Data do relatório')
                        ->default(today()->subDay())
                        ->maxDate(today())
                        ->required(),
                ])
                ->action(function (array $data, RequestAutomationJob $requestAutomationJob): void {
                    $date = (string) $data['date'];
                    $job = $requestAutomationJob->handle(new AutomationJobRequest(
                        reportKey: 'daily_trip_summary',
                        parameters: ['date' => $date],
                        source: AutomationJobSource::MANUAL,
                        requestedByUserId: auth()->id(),
                        idempotencyKey: 'manual:daily_trip_summary:'.auth()->id().':'.$date,
                        metadata: [
                            'requested_from' => 'filament',
                        ],
                    ));

                    Notification::make()
                        ->title('Solicitação criada')
                        ->body('Job local #'.$job->id.' criado e enviado para a fila.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
