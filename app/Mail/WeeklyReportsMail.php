<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WeeklyReportsMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @var string */
    public $fechaInicio;
    /** @var string */
    public $fechaFin;
    /** @var int */
    public $teamId;
    /** @var string */
    public $sendAt;

    /** @var array<int,array{path:string,name:string}> */
    public $attachmentsList;

    /**
     * @param array<int,array{path:string,name:string}> $attachments
     */
    public function __construct(string $fechaInicio, string $fechaFin, int $teamId, string $sendAt, array $attachments)
    {
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->teamId = $teamId;
        $this->sendAt = $sendAt;
        $this->attachmentsList = $attachments;
    }

    public function build(): self
    {
        $mail = $this->subject("Reportes Semanales (Equipo {$this->teamId}) {$this->fechaInicio} a {$this->fechaFin}")
            ->view('emails.weekly-reports', [
                'fechaInicio' => $this->fechaInicio,
                'fechaFin' => $this->fechaFin,
                'teamId' => $this->teamId,
                'sendAt' => $this->sendAt,
            ]);

        foreach ($this->attachmentsList as $file) {
            $mail->attach($file['path'], ['as' => $file['name'], 'mime' => 'application/pdf']);
        }

        return $mail;
    }
}
