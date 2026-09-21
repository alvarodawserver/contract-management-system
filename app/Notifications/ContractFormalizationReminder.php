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
            ->greeting("Hola, {$notifiable->name}")
            ->line("El contrato **{$contract->reference} · {$contract->title}** ({$contract->department->name}) sigue sin formalizar.")
            ->line("Fecha límite para formalizarlo: {$contract->formalization_deadline->format('d/m/Y')} (quedan {$timeLeft}).")
            ->line("Datos que faltan: {$missing}.")
            ->line('Si no se formaliza a tiempo, el contrato pasará a la papelera y habrá que restaurarlo para continuar.');
    }
}
