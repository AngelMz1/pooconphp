<?php

namespace App;

use App\SupabaseClient;
use PDO;

class SyncManager
{
    private $localPdo;
    private $supabase;
    private $batchSize = 20;

    public function __construct(PDO $localPdo, SupabaseClient $supabase)
    {
        $this->localPdo = $localPdo;
        $this->supabase = $supabase;
    }

    public function processQueue()
    {
        $stats = ['processed' => 0, 'success' => 0, 'errors' => 0];

        // Fetch pending items
        $stmt = $this->localPdo->prepare("SELECT * FROM sync_queue WHERE status = 'pending' ORDER BY id ASC LIMIT :limit");
        $stmt->bindValue(':limit', $this->batchSize, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($items)) {
            return $stats;
        }

        foreach ($items as $item) {
            $stats['processed']++;
            try {
                $this->syncItem($item);
                $this->markAsSynced($item['id']);
                $stats['success']++;
            } catch (\Exception $e) {
                $this->markAsError($item['id'], $e->getMessage());
                $stats['errors']++;
            }
        }

        return $stats;
    }

    private function syncItem($item)
    {
        $table = $item['table_name'];
        $action = $item['action'];
        $data = json_decode($item['data'], true);
        
        // Sanity Check
        if (!$data) throw new \Exception("Invalid JSON data");

        /*
         * Supabase Logic:
         * To force IDs to match Local IDs, we use upsert (INSERT... ON CONFLICT UPDATE) or explicit INSERT.
         * The Supabase Client `insert` method uses POST /table.
         * To handle specific IDs with PostgREST, we just include the ID in the body.
         * PostgREST will use it. If ID exists, we need merge behavior for UPDATE.
         */

        switch ($action) {
            case 'INSERT':
            case 'UPDATE':
                // For Supabase/PostgREST, upsert is best. headers: Prefer: resolution=merge-duplicates.
                // Our SupabaseClient inserts by default. We might need to ensure 'upsert' behavior.
                // Actually, simple insert with ID might fail if exists. 
                // Let's rely on standard insert/update logic for now.
                
                // If it's UPDATE, we technically know the ID exists unless it was created offline.
                // For Local->Cloud sync, "Upsert" is safest for both INSERT/UPDATE actions to ensure state matches.
                // But SupabaseClient 'insert' is basic.
                // Let's try standard 'insert' for INSERT and 'update' for UPDATE.
                
                if ($action === 'INSERT') {
                     // Includes ID if in data
                     $this->supabase->insert($table, $data); 
                } else {
                     // UPDATE requires PK. Find PK from data.
                     // $item['pk_value'] contains the VALUE of PK.
                     // But we need the COLUMN name of PK. This is tricky generically.
                     // Heuristic: table name singular + _id, or id, or id_table.
                     $pkCol = $this->inferPrimaryKey($table, $data);
                     $pkVal = $item['pk_value'];
                     
                     if ($pkCol) {
                         // filter: id=eq.123
                         $this->supabase->update($table, $data, "$pkCol=eq.$pkVal");
                     } else {
                         throw new \Exception("Cannot infer PK for table $table");
                     }
                }
                break;

            case 'DELETE':
                 $pkCol = $this->inferPrimaryKey($table, $data);
                 $pkVal = $item['pk_value'];
                 if ($pkCol) {
                     $this->supabase->delete($table, "$pkCol=eq.$pkVal");
                 }
                 break;
        }
    }

    private function inferPrimaryKey($table, $data)
    {
        // 1. Try 'id'
        if (isset($data['id'])) return 'id';
        
        // 2. Try 'id_singular' e.g. 'id_paciente'
        $singular = rtrim($table, 's'); // pacientes -> paciente
        $candidate = "id_$singular";
        if (isset($data[$candidate])) return $candidate;
        
        // 3. Try singular_id e.g. 'documento_id' (unlikely for PK but maybe)
        
        // 4. Special cases known in this app
        if ($table === 'historias_clinicas') return 'id_historia';
        if ($table === 'formulas_medicas') return 'id_formula';
        
        return null;
    }

    private function markAsSynced($id)
    {
        $stmt = $this->localPdo->prepare("UPDATE sync_queue SET status = 'synced', synced_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
    }

    private function markAsError($id, $msg)
    {
        $stmt = $this->localPdo->prepare("UPDATE sync_queue SET status = 'error', error_message = ? WHERE id = ?");
        $stmt->execute([substr($msg, 0, 1000), $id]);
    }
    
    public function getPendingCount()
    {
        $stmt = $this->localPdo->query("SELECT count(*) FROM sync_queue WHERE status = 'pending'");
        return $stmt->fetchColumn();
    }
}
