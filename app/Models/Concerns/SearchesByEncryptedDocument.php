<?php

namespace App\Models\Concerns;

/**
 * The `document` column (CPF/CNPJ) is encrypted at rest, so a SQL LIKE
 * can no longer match it. This scans the column in chunks, relying on the
 * model's `encrypted` cast to decrypt each value, and returns the ids of
 * rows whose document matches the search term (compared as typed and with
 * formatting stripped, so a query with or without punctuation both work).
 */
trait SearchesByEncryptedDocument
{
    public static function idsMatchingDocument(string $term): array
    {
        $term = trim($term);

        if ($term === '') {
            return [];
        }

        $digitsOnly = preg_replace('/\D/', '', $term);
        $ids = [];

        static::withTrashed()
            ->select('id', 'document')
            ->whereNotNull('document')
            ->chunk(200, function ($rows) use (&$ids, $term, $digitsOnly) {
                foreach ($rows as $row) {
                    $document = (string) $row->document;

                    $matches = str_contains($document, $term)
                        || ($digitsOnly !== '' && str_contains(preg_replace('/\D/', '', $document), $digitsOnly));

                    if ($matches) {
                        $ids[] = $row->id;
                    }
                }
            });

        return $ids;
    }
}
