<?php
namespace Steevo\Component\Collector\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class FilesModel extends BaseDatabaseModel
{
    const BASE_PATH = 'images/collector';

    public function getRootPath(string $context, string $id): string
    {
        if ($context === 'collection') {
            return JPATH_ROOT . '/' . self::BASE_PATH . '/collections/' . (int) $id;
        }
        return JPATH_ROOT . '/' . self::BASE_PATH . '/defined/' . $id;
    }

    public function getRootRelative(string $context, string $id): string
    {
        if ($context === 'collection') {
            return self::BASE_PATH . '/collections/' . (int) $id;
        }
        return self::BASE_PATH . '/defined/' . $id;
    }

    public function ensureRootExists(string $context, string $id): bool
    {
        $path = $this->getRootPath($context, $id);
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
            file_put_contents($path . '/index.html', '');
        }
        return is_dir($path);
    }

    public function browse(string $context, string $id, string $subpath = ''): array
    {
        $root    = $this->getRootPath($context, $id);
        $subpath = $this->sanitizeSubpath($subpath);
        $current = $root . ($subpath ? '/' . $subpath : '');

        if (!is_dir($current)) {
            return ['dirs' => [], 'files' => []];
        }

        $dirs  = [];
        $files = [];

        foreach (new \DirectoryIterator($current) as $item) {
            if ($item->isDot()) continue;
            if ($item->getFilename() === 'index.html') continue;

            if ($item->isDir()) {
                $dirs[] = (object) [
                    'name'  => $item->getFilename(),
                    'path'  => ($subpath ? $subpath . '/' : '') . $item->getFilename(),
                    'count' => count(glob($item->getPathname() . '/*.*')) ?: 0,
                ];
            } else {
                $ext     = strtolower($item->getExtension());
                $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
                $relPath = $this->getRootRelative($context, $id)
                         . ($subpath ? '/' . $subpath : '')
                         . '/' . $item->getFilename();

                $files[] = (object) [
                    'name'     => $item->getFilename(),
                    'path'     => ($subpath ? $subpath . '/' : '') . $item->getFilename(),
                    'rel_path' => $relPath,
                    'size'     => $item->getSize(),
                    'ext'      => $ext,
                    'is_image' => $isImage,
                    'url'      => \Joomla\CMS\Uri\Uri::root() . $relPath,
                    'modified' => $item->getMTime(),
                ];
            }
        }

        usort($dirs,  fn($a, $b) => strnatcasecmp($a->name, $b->name));
        usort($files, fn($a, $b) => strnatcasecmp($a->name, $b->name));

        return ['dirs' => $dirs, 'files' => $files];
    }

    public function getReferences(string $relPath): array
    {
        $db = $this->getDbo();

        $db->setQuery(
            $db->getQuery(true)
               ->select(['f.collection', 'f.tablecolumn', 'f.field AS field_label'])
               ->from($db->quoteName('#__collector_fields', 'f'))
               ->join('INNER', '#__collector_fields_type AS ft ON ft.id = f.type AND ft.type = ' . $db->quote('image'))
               ->where($db->quoteName('f.state') . ' = 1')
        );
        $imageFields = $db->loadObjectList() ?: [];

        $references = [];
        foreach ($imageFields as $field) {
            $historyTable = '#__collector_items_history_' . (int) $field->collection;

            try {
                $db->setQuery(
                    $db->getQuery(true)
                       ->select(['h.item AS item_id', 'i.fulltitle', 'h.' . $db->quoteName($field->tablecolumn) . ' AS field_value'])
                       ->from($db->quoteName($historyTable, 'h'))
                       ->join('INNER', '#__collector_items AS i ON i.id = h.item')
                       ->where('h.' . $db->quoteName($field->tablecolumn) . ' LIKE ' . $db->quote('%' . $relPath . '%'))
                       ->where('h.state = 1')
                );
                $rows = $db->loadObjectList() ?: [];
            } catch (\Exception $e) {
                continue;
            }

            foreach ($rows as $row) {
                $references[] = (object) [
                    'collection'  => (int) $field->collection,
                    'item_id'     => (int) $row->item_id,
                    'fulltitle'   => $row->fulltitle,
                    'field_label' => $field->field_label,
                    'field_col'   => $field->tablecolumn,
                ];
            }
        }

        return $references;
    }

    public function moveFile(string $context, string $id, string $fromSubpath, string $toSubpath): array
    {
        $root    = $this->getRootPath($context, $id);
        $fromAbs = $root . '/' . $this->sanitizeSubpath($fromSubpath);
        $toAbs   = $root . '/' . $this->sanitizeSubpath($toSubpath);

        if (!file_exists($fromAbs)) {
            return ['moved' => false, 'references_updated' => 0];
        }

        $toDir = dirname($toAbs);
        if (!is_dir($toDir)) {
            mkdir($toDir, 0755, true);
        }

        $moved = rename($fromAbs, $toAbs);
        if (!$moved) {
            return ['moved' => false, 'references_updated' => 0];
        }

        $oldRelPath = $this->getRootRelative($context, $id) . '/' . $this->sanitizeSubpath($fromSubpath);
        $newRelPath = $this->getRootRelative($context, $id) . '/' . $this->sanitizeSubpath($toSubpath);

        $updated = $this->updateReferences($oldRelPath, $newRelPath);

        return ['moved' => true, 'references_updated' => $updated];
    }

    public function renameFile(string $context, string $id, string $subpath, string $newName): array
    {
        $newName = basename($newName);
        $root    = $this->getRootPath($context, $id);
        $fromAbs = $root . '/' . $this->sanitizeSubpath($subpath);
        $toAbs   = dirname($fromAbs) . '/' . $newName;

        if (!file_exists($fromAbs)) {
            return ['renamed' => false, 'references_updated' => 0];
        }

        $renamed = rename($fromAbs, $toAbs);
        if (!$renamed) {
            return ['renamed' => false, 'references_updated' => 0];
        }

        $dir        = dirname($subpath);
        $oldRelPath = $this->getRootRelative($context, $id) . '/' . ($dir !== '.' ? $dir . '/' : '') . basename($subpath);
        $newRelPath = $this->getRootRelative($context, $id) . '/' . ($dir !== '.' ? $dir . '/' : '') . $newName;

        $updated = $this->updateReferences($oldRelPath, $newRelPath);

        return ['renamed' => true, 'references_updated' => $updated];
    }

    public function deleteFile(string $context, string $id, string $subpath): array
    {
        $root    = $this->getRootPath($context, $id);
        $absPath = $root . '/' . $this->sanitizeSubpath($subpath);

        if (!file_exists($absPath)) {
            return ['deleted' => false, 'references' => 0];
        }

        $relPath    = $this->getRootRelative($context, $id) . '/' . $this->sanitizeSubpath($subpath);
        $references = count($this->getReferences($relPath));

        is_dir($absPath) ? $this->deleteDirectory($absPath) : unlink($absPath);

        return ['deleted' => true, 'references' => $references];
    }

    public function mkdir(string $context, string $id, string $subpath, string $name): bool
    {
        $name    = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $name);
        $root    = $this->getRootPath($context, $id);
        $subpath = $this->sanitizeSubpath($subpath);
        $newDir  = $root . ($subpath ? '/' . $subpath : '') . '/' . $name;

        if (is_dir($newDir)) return true;

        mkdir($newDir, 0755, true);
        file_put_contents($newDir . '/index.html', '');
        return is_dir($newDir);
    }

    public function upload(string $context, string $id, string $subpath, array $file): array
    {
        $root    = $this->getRootPath($context, $id);
        $subpath = $this->sanitizeSubpath($subpath);
        $destDir = $root . ($subpath ? '/' . $subpath : '');

        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $filename = preg_replace('/[^a-zA-Z0-9\-_\.]/', '-', basename($file['name']));
        $dest     = $destDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['uploaded' => false, 'filename' => ''];
        }

        return ['uploaded' => true, 'filename' => $filename];
    }

    private function updateReferences(string $oldPath, string $newPath): int
    {
        $db = $this->getDbo();

        $db->setQuery(
            $db->getQuery(true)
               ->select(['f.collection', 'f.tablecolumn'])
               ->from($db->quoteName('#__collector_fields', 'f'))
               ->join('INNER', '#__collector_fields_type AS ft ON ft.id = f.type AND ft.type = ' . $db->quote('image'))
               ->where($db->quoteName('f.state') . ' = 1')
        );
        $imageFields = $db->loadObjectList() ?: [];

        $updated = 0;
        foreach ($imageFields as $field) {
            $historyTable = '#__collector_items_history_' . (int) $field->collection;

            try {
                $db->setQuery(
                    'UPDATE ' . $db->quoteName($historyTable)
                    . ' SET ' . $db->quoteName($field->tablecolumn)
                    . ' = REPLACE(' . $db->quoteName($field->tablecolumn) . ', '
                    . $db->quote($oldPath) . ', ' . $db->quote($newPath) . ')'
                    . ' WHERE ' . $db->quoteName($field->tablecolumn) . ' LIKE '
                    . $db->quote('%' . $oldPath . '%')
                )->execute();

                $updated += (int) $db->getAffectedRows();
            } catch (\Exception $e) {
                continue;
            }
        }

        return $updated;
    }

    private function sanitizeSubpath(string $subpath): string
    {
        $parts = array_filter(
            explode('/', str_replace('\\', '/', $subpath)),
            fn($p) => $p !== '' && $p !== '.' && $p !== '..'
        );
        return implode('/', $parts);
    }

    private function deleteDirectory(string $path): void
    {
        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        ) as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($path);
    }

    public function getDefineGroups(): array
    {
        $db = $this->getDbo();
        $db->setQuery(
            $db->getQuery(true)
               ->select('id, name')
               ->from($db->quoteName('#__collector_defined'))
               ->order('name')
        );
        return $db->loadObjectList() ?: [];
    }

    /**
     * Migration : déplace images/collector/collection/{alias}/ vers
     * images/collector/collections/{id}/ et met à jour les références.
     *
     * @return array  Rapport par collection
     */
    public function migrateCollectionFolders(): array
    {
        $db = $this->getDbo();
        $db->setQuery(
            $db->getQuery(true)
               ->select(['id', 'alias', 'name'])
               ->from($db->quoteName('#__collector'))
        );
        $collections = $db->loadObjectList() ?: [];

        $report = [];

        foreach ($collections as $col) {
            $oldDir = JPATH_ROOT . '/' . self::BASE_PATH . '/collection/' . $col->alias;
            $newDir = JPATH_ROOT . '/' . self::BASE_PATH . '/collections/' . $col->id;

            $oldRel = self::BASE_PATH . '/collection/' . $col->alias;
            $newRel = self::BASE_PATH . '/collections/' . $col->id;

            if (!is_dir($oldDir)) {
                $report[] = (object) [
                    'collection' => $col->name,
                    'status'     => 'skipped',
                    'message'    => 'Aucun ancien dossier trouvé.',
                ];
                continue;
            }

            // Créer le dossier parent si nécessaire
            $parentDir = dirname($newDir);
            if (!is_dir($parentDir)) {
                mkdir($parentDir, 0755, true);
            }

            if (is_dir($newDir)) {
                $report[] = (object) [
                    'collection' => $col->name,
                    'status'     => 'error',
                    'message'    => 'Le dossier cible existe déjà.',
                ];
                continue;
            }

            $moved = rename($oldDir, $newDir);

            if (!$moved) {
                $report[] = (object) [
                    'collection' => $col->name,
                    'status'     => 'error',
                    'message'    => 'Échec du déplacement physique.',
                ];
                continue;
            }

            $updated = $this->updateReferences($oldRel, $newRel);

            $report[] = (object) [
                'collection' => $col->name,
                'status'     => 'success',
                'message'    => $updated . ' référence(s) mise(s) à jour.',
            ];
        }

        return $report;
    }

    /**
     * Compte combien de collections ont un ancien dossier à migrer.
     */
    public function countCollectionsToMigrate(): int
    {
        $db = $this->getDbo();
        $db->setQuery(
            $db->getQuery(true)
               ->select(['alias'])
               ->from($db->quoteName('#__collector'))
        );
        $aliases = $db->loadColumn() ?: [];

        $count = 0;
        foreach ($aliases as $alias) {
            if (is_dir(JPATH_ROOT . '/' . self::BASE_PATH . '/collection/' . $alias)) {
                $count++;
            }
        }
        return $count;
    }
}