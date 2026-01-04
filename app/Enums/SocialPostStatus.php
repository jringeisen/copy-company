<?php

namespace App\Enums;

enum SocialPostStatus: string
{
    case Draft = 'draft';
    case Queued = 'queued';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Failed = 'failed';

    public function displayName(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Queued => 'Queued',
            self::Scheduled => 'Scheduled',
            self::Published => 'Published',
            self::Failed => 'Failed',
        };
    }

    /**
     * Get all statuses as dropdown options.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function toDropdownOptions(bool $includeAll = false): array
    {
        $options = [];

        if ($includeAll) {
            $options[] = ['value' => 'all', 'label' => 'All Statuses'];
        }

        foreach (self::cases() as $status) {
            $options[] = [
                'value' => $status->value,
                'label' => $status->displayName(),
            ];
        }

        return $options;
    }
}
