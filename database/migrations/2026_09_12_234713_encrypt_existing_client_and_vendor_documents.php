<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $encrypt = fn (string $value) => $this->isEncrypted($value) ? $value : Crypt::encryptString($value);

        $this->transform('clients', $encrypt);
        $this->transform('vendors', $encrypt);
    }

    public function down(): void
    {
        $this->transform('clients', fn (string $value) => $this->tryDecrypt($value));
        $this->transform('vendors', fn (string $value) => $this->tryDecrypt($value));
    }

    /**
     * Reads and writes the "document" column via the raw query builder
     * (bypassing Eloquent's cast) so this works regardless of whether the
     * model already declares the "encrypted" cast.
     */
    private function transform(string $table, Closure $callback): void
    {
        DB::table($table)
            ->whereNotNull('document')
            ->where('document', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($table, $callback) {
                foreach ($rows as $row) {
                    DB::table($table)->where('id', $row->id)->update([
                        'document' => $callback($row->document),
                    ]);
                }
            });
    }

    private function tryDecrypt(string $value): string
    {
        try {
            return Crypt::decryptString($value);
        } catch (Throwable) {
            return $value;
        }
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (Throwable) {
            return false;
        }
    }
};
