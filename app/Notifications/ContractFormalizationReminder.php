<?php

namespace App\Notifications;

use App\Models\Contract;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractFormalizationReminder extends Notification
{
    /**
     * @var array<string, string>
     */
    private const FIELD_LABELS = [
        'amount' => 'importe final',
        'start_date' => 'fecha de inicio',
        'end_date' => 'fecha de fin',
        'responsible' => 'responsable',
    ];

    public function __construct(public Contract $contract) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $contract = $this->contract;
        $timeLeft = today()->settings(['locale' => 'es'])->diffForHumans($contract->formalization_deadline, [
            'syntax' => CarbonInterface::DIFF_ABSOLUTE,
            'parts' => 2,
            'join' => ' y ',
        ]);
        $missing = collect($contract->missingFormalizationFields())
            ->map(fn (string $field): string => self::FIELD_LABELS[$field])
            ->implode(', ');

        return (new MailMessage)
            ->subject("Recordatorio: el contrato {$contract->reference} está pendiente de formalizar")
            ->greeting("Estimado/a {$notifiable->name}:")
            ->line("Le informamos de que el contrato **{$contract->reference} · {$contract->title}**, perteneciente al departamento de {$contract->department->name}, continúa pendiente de formalización.")
            ->line("El plazo para formalizarlo finaliza el {$contract->formalization_deadline->format('d/m/Y')} (quedan {$timeLeft}).")
            ->line("Los datos pendientes de completar son: {$missing}.")
            ->line('Se le informa que si el contrato no es formalizado a tiempo quedará como inválido y habrá que restaurarlo posteriormente.')
            ->salutation('Módulo de Contratación');
    }
}
