<?php

namespace App;

use ZipArchive;

class PluginUploader
{
    private $uploadDir;
    private $extractDir;

    public function __construct()
    {
        $this->uploadDir = __DIR__ . '/../uploads/plugins';
        $this->extractDir = __DIR__ . '/../uploads/plugins/extracted';
        
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
        if (!is_dir($this->extractDir)) {
            mkdir($this->extractDir, 0755, true);
        }
    }

    public function upload($file)
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new \Exception('No file uploaded');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('Upload error: ' . $file['error']);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (strtolower($ext) !== 'zip') {
            throw new \Exception('Only ZIP files are allowed');
        }

        $filename = time() . '_' . basename($file['name']);
        $destination = $this->uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new \Exception('Failed to move uploaded file');
        }

        return $destination;
    }

    public function extract($zipPath)
    {
        $zip = new ZipArchive();
        
        if ($zip->open($zipPath) !== true) {
            throw new \Exception('Failed to open ZIP file');
        }

        $pluginName = $zip->getNameIndex(0);
        $pluginSlug = trim($pluginName, '/');
        
        $extractPath = $this->extractDir . '/' . $pluginSlug;
        
        if (!$zip->extractTo($this->extractDir)) {
            $zip->close();
            throw new \Exception('Failed to extract ZIP file');
        }

        $zip->close();

        return [
            'path' => $extractPath,
            'slug' => $pluginSlug
        ];
    }

    public function parsePluginInfo($pluginPath)
    {
        $mainFile = $this->findMainPluginFile($pluginPath);
        
        if (!$mainFile) {
            throw new \Exception('Could not find main plugin file');
        }

        $content = file_get_contents($mainFile);
        $info = [];

        if (preg_match('/Plugin Name:\s*(.+)/i', $content, $matches)) {
            $info['name'] = trim($matches[1]);
        }

        if (preg_match('/Version:\s*(.+)/i', $content, $matches)) {
            $info['version'] = trim($matches[1]);
        }

        if (preg_match('/Description:\s*(.+)/i', $content, $matches)) {
            $info['description'] = trim($matches[1]);
        }

        if (preg_match('/Author:\s*(.+)/i', $content, $matches)) {
            $info['author'] = trim($matches[1]);
        }

        $info['main_file'] = $mainFile;

        return $info;
    }

    private function findMainPluginFile($pluginPath)
    {
        if (!is_dir($pluginPath)) {
            return null;
        }

        $files = glob($pluginPath . '/*.php');
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (preg_match('/Plugin Name:/i', $content)) {
                return $file;
            }
        }

        return null;
    }

    public function parseAdminMenus($mainFile)
    {
        $content = file_get_contents($mainFile);
        $menus = [];

        preg_match_all('/add_menu_page\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]/', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $menus[] = [
                'page_title' => $match[1],
                'menu_title' => $match[2],
                'type' => 'main'
            ];
        }

        preg_match_all('/add_submenu_page\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]/', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $menus[] = [
                'parent' => $match[1],
                'page_title' => $match[2],
                'menu_title' => $match[3],
                'type' => 'submenu'
            ];
        }

        return $menus;
    }

    public function extractDatabaseSchema($pluginPath)
    {
        $schemas = [];
        $installFiles = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($pluginPath),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                
                if (preg_match('/class.*install/i', $content) || 
                    preg_match('/register_activation_hook/i', $content)) {
                    $installFiles[] = $file->getPathname();
                }
            }
        }

        foreach ($installFiles as $file) {
            $content = file_get_contents($file);
            
            preg_match_all('/CREATE TABLE[^;]+;/is', $content, $matches);
            
            foreach ($matches[0] as $schema) {
                $schemas[] = $schema;
            }
        }

        return $schemas;
    }
}
