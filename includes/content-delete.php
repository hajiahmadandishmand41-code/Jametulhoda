<?php
require_once __DIR__ . '/storage.php';
/** Commit DB deletion with an outbox; remote failures remain retryable, never silently lost. */
function deleteContentRecord(string $table, int $id, ?string $postType = null): bool {
    if (!in_array($table, ['posts','lessons','books'], true) || $id<1) return false;
    $db=getDB(); $db->beginTransaction();
    try {
        $stmt=$db->prepare("SELECT * FROM $table WHERE id=? FOR UPDATE");$stmt->execute([$id]);$row=$stmt->fetch();
        if (!$row || ($postType && ($row['post_type']??'')!==$postType)) { $db->rollBack(); return false; }
        $files=[];
        foreach(['featured_image','featured_video','audio_file','video_file','pdf_file','word_file','cover_image'] as $column) if (!empty($row[$column])) $files[]=$row[$column];
        if ($table==='posts') {
            $stmt=$db->prepare('SELECT image_path FROM post_images WHERE post_id=?');$stmt->execute([$id]);$files=array_merge($files,$stmt->fetchAll(PDO::FETCH_COLUMN));
            $db->prepare('DELETE FROM post_images WHERE post_id=?')->execute([$id]);
        }
        $ref=$table==='posts'?'post':($table==='lessons'?'lesson':'book');
        $stmt=$db->prepare('SELECT file_path FROM media_files WHERE ref_type=? AND ref_id=?');$stmt->execute([$ref,$id]);$files=array_merge($files,$stmt->fetchAll(PDO::FETCH_COLUMN));
        $db->prepare('DELETE FROM media_files WHERE ref_type=? AND ref_id=?')->execute([$ref,$id]);
        $db->prepare("DELETE FROM $table WHERE id=?")->execute([$id]);
        $enqueue=$db->prepare('INSERT INTO storage_deletions (reference) VALUES (?) ON CONFLICT DO NOTHING');
        foreach(array_unique($files) as $file) if(storageKey($file)) $enqueue->execute([$file]);
        $db->commit();
    } catch(Throwable $e) { if($db->inTransaction())$db->rollBack();throw $e; }
    processStorageDeletions();
    return true;
}
function processStorageDeletions(): int {
    $db=getDB();$deleted=0;
    foreach($db->query('SELECT reference FROM storage_deletions ORDER BY created_at LIMIT 100')->fetchAll() as $job) {
        try {
            if(deleteStoredFile($job['reference'])) {
                $db->prepare('DELETE FROM storage_deletions WHERE reference=?')->execute([$job['reference']]);$deleted++;
            }
        } catch(Throwable $e) { error_log('Storage deletion pending; retry with bin/storage-gc.php'); }
    }
    return $deleted;
}
