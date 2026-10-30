<?php

namespace App\Console\Commands;

use App\Models\Cafe;
use App\Models\Roastery;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class SyncBusinessOwnerAccounts extends Command
{
    protected $signature = 'app:sync-business-owner-accounts';

    protected $description = 'Create owner accounts for every cafe and roastery and assign them to their business';

    public function handle(): int
    {
        $businesses = collect([
            ...Cafe::query()->get(['id', 'name', 'owner_id'])->map(fn (Cafe $cafe): array => [
                'model' => Cafe::class,
                'type' => 'cafe',
                'role' => 'admin',
                'record' => $cafe,
            ]),
            ...Roastery::query()->get(['id', 'name', 'owner_id'])->map(fn (Roastery $roastery): array => [
                'model' => Roastery::class,
                'type' => 'roastery',
                'role' => 'roastery',
                'record' => $roastery,
            ]),
        ]);
        $prefixCounts = $businesses->countBy(fn (array $business): string => Str::slug($business['record']->name, ''));
        $created = 0;
        $assigned = 0;

        DB::transaction(function () use ($businesses, $prefixCounts, &$created, &$assigned): void {
            foreach ($businesses as $business) {
                /** @var Cafe|Roastery $record */
                $record = $business['record'];
                $prefix = Str::slug($record->name, '');

                if ($prefix === '') {
                    throw new RuntimeException("Business {$business['type']}#{$record->getKey()} has no name for its owner account.");
                }

                if ($prefixCounts[$prefix] > 1) {
                    $prefix .= "-{$business['type']}-{$record->getKey()}";
                }

                $email = "{$prefix}@wadahngopi.com";
                $owner = User::query()->where('email', $email)->first();

                if ($owner && ($owner->name !== $record->name || $owner->role !== $business['role'])) {
                    throw new RuntimeException("Owner email {$email} is already used by a different account.");
                }

                if (! $owner) {
                    $owner = User::query()->create([
                        'name' => $record->name,
                        'email' => $email,
                        'password' => Str::password(48),
                        'role' => $business['role'],
                    ]);
                    $created++;
                }

                if ((int) $record->owner_id !== (int) $owner->getKey()) {
                    $record->owner_id = $owner->getKey();
                    $record->save();
                    $assigned++;
                }
            }
        });

        $this->info("Created {$created} owner accounts and assigned {$assigned} businesses.");
        $this->warn('Each account has a unique random password that was not displayed. Set a new password from User Management before sharing account access.');

        return self::SUCCESS;
    }
}
