<?php
// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

/**
 * @package   Gantry5
 * @author    Tiger12 http://tiger12.com
 * @originalCreator  RocketTheme (Gantry Framework)
 * @currentDeveloper  Tiger12, LLC
 * @copyright Copyright (C) 2007 - 2022 Tiger12, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Admin\Controller\Json;

use Gantry\Component\Admin\JsonController;
use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Response\JsonResponse;
use Gantry\Framework\Gantry;
use RocketTheme\Toolbox\File\File;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceIterator;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * Class Filepicker
 * @package Gantry\Admin\Controller\Json
 */
class Filepicker extends JsonController
{
    /** @var string */
    protected $base;
    /** @var string */
    protected $value;
    /** @var bool */
    protected $filter = false;
    /** @var array */
    protected $httpVerbs = [
        'GET'    => [
            '/'            => 'index',
            '/*'           => 'index',
            '/display'     => 'undefined',
            '/display/**'  => 'displayFile',
            '/download'    => 'undefined',
            '/download/**' => 'downloadFile',
        ],
        'POST'   => [
            '/'            => 'index',
            '/*'           => 'index',
            '/subfolder'   => 'subfolder',
            '/subfolder/*' => 'subfolder',
            '/upload'      => 'undefined',
            '/upload/**'   => 'upload'
        ],
        'DELETE' => [
            '/'   => 'undefined',
            '/**' => 'delete'
        ]
    ];

    /**
     * @return JsonResponse
     */
    public function index()
    {
        /** @var UniformResourceLocator $locator */
        $locator   = $this->container['locator'];
        $bookmarks = [];
        $drives    = ['/'];
        $subfolder = false;

        $this->base = $locator->base;

        if ($this->method === 'POST') {
            $root         = $this->request->post['root'];
            $drives       = isset($root) ? ($root !== 'false' ? (array) $root : ['/']) : ['/'];
            $subfolder    = $this->request->post['subfolder'] ? true : false;
            $filter       = $this->request->post['filter'];
            $this->filter = isset($filter) ? ($filter !== 'false' ? $filter : false) : false;
            $this->value  = $this->request->post['value'] ?: '';
        }

        foreach ($drives as $drive) {
            // cleanup of the path so it's chrooted.
            $drive  = str_replace('..', '', $drive);

            $isStream = $locator->isStream($drive);
            $path     = rtrim($this->base, '/') . '/' . ltrim($drive, '/');

            // It's a stream but the scheme doesn't exist. we skip it.
            if (!$isStream && (strpos($drive, '://') || !file_exists($path))) {
                continue;
            }

            if ($isStream && !$locator->findResources($drive)) {
                continue;
            }

            $key = $isStream ? $drive : preg_replace('#/{2,}+#', '/', $drive);

            if (!array_key_exists($key, $bookmarks)) {
                $bookmarks[$key] = $isStream
                    ? [$locator->getIterator($drive)]
                    : [rtrim(Folder::getRelativePath($path), '/') . '/'];
            }
        }

        if (!count($bookmarks)) {
            throw new \RuntimeException('Requested directory was not found.', 404);
        }

        $folders = [];
        $active  = [];

        $index = 0;
        $activeFallback = '';

        // iterating the folder and collecting subfolders and files
        foreach ($bookmarks as $key => $bookmark) {
            $folders[$key] = [];

            if (!$index) {
                $activeFallback = $key;
            }

            foreach ($bookmark as $folder) {
                $isStream = $this->isStream($folder);

                if ($isStream) {
                    unset($bookmarks[$key]);
                    $iterator = new \IteratorIterator($folder);
                    $folder   = $key;
                } else {
                    $iterator = new \DirectoryIterator($this->base . '/' . ltrim($folder, '/'));
                }

                $folders[$key][$folder] = new \ArrayObject();
                if (!$index && !$this->value) {
                    $active[] = $folder;
                }

                /** @var \DirectoryIterator $info */
                foreach ($iterator as $info) {
                    // no dot files nor files beginning with dot
                    if ($info->isDot() || substr($info->getFilename(), 0, 1) === '.') {
                        continue;
                    }

                    $file = new \stdClass();
                    $this->attachData($file, $info, $folder);

                    if ($file->dir) {
                        if ($file->pathname === dirname($this->value)) {
                            $active[] = $file->pathname;
                        }

                        $folders[$key][$folder]->append($file);
                    } else {
                        /*if ($filter && !preg_match("/" . $filter . "/i", $file->filename)) {
                            continue;
                        }
                        if ((!$index && !$this->value) || (in_array(dirname($file->pathname), $active))) {
                            $files->append($file);
                        }*/
                    }
                }

                if ($isStream) {
                    $bookmarks[$key][] = $key;
                }

                $index++;
            }
        }

        if (!count($active)) {
            $active[] = $activeFallback;
        }

        $lastItem = end($active);
        $files    = $this->listFiles($lastItem);
        $response = [];

        reset($active);
        if (!$subfolder) {
            $response['html'] = $this->render(
                '@gantry-admin/ajax/filepicker.html.twig', [
                    'active'    => $active,
                    'base'      => $this->base,
                    'bookmarks' => $bookmarks,
                    'folders'   => $folders,
                    'files'     => $files,
                    'filter'    => $this->filter,
                    'value'     => $this->value
                ]
            );
        } else {
            $current = isset($folder) && isset($folders[$key][$folder]) ? $folders[$key][$folder] : null;
            $count = $current ? $current->count() : 0;
            if ($current && $count) {
                $response['subfolder'] = $this->render(
                    '@gantry-admin/ajax/filepicker/subfolders.html.twig',
                    ['folder' => $current]
                );
            } else {
                $response['subfolder'] = false;
            }

            $response['files'] = $this->render(
                '@gantry-admin/ajax/filepicker/files.html.twig',
                ['files' => $files, 'value' => $this->value]
            );
        }

        return new JsonResponse($response);
    }

    /**
     * @param object $node
     * @param object $iteration
     * @param string $folder
     */
    protected function attachData(&$node, $iteration, $folder)
    {
        foreach (
            ['getFilename', 'getExtension', 'getPerms', 'getMTime', 'getBasename', 'getPathname', 'getSize', 'getType', 'isReadable', 'isWritable',
             'isDir', 'isFile'] as $method
        ) {
            $keyMethod          = strtolower(preg_replace('/^(is|get)/', '', $method));
            $node->{$keyMethod} = $iteration->{$method}();

            if ($method === 'getPathname') {
                $node->{$keyMethod} = $this->isStream($folder) ? $iteration->getUrl() : Folder::getRelativePath($node->{$keyMethod});
            } else {
                if ($method === 'getExtension') {
                    $node->isImage = in_array(strtolower($node->{$keyMethod}), ['jpg', 'jpeg', 'png', 'gif', 'ico', 'svg', 'bmp', 'webp']);
                }
            }
        }
    }

    /**
     * @param string $folder
     * @return \ArrayObject
     */
    protected function listFiles($folder)
    {
        $isStream = $this->isStream($folder);
        $locator  = $this->container['locator'];
        $iterator = $isStream ? new \IteratorIterator($locator->getIterator($folder)) : new \DirectoryIterator($this->base . '/' . ltrim($folder, '/'));
        $files    = new \ArrayObject();

        /** @var \DirectoryIterator $info */
        foreach ($iterator as $info) {
            // no dot files nor files beginning with dot
            if ($info->isDot() || substr($info->getFilename(), 0, 1) === '.') {
                continue;
            }

            $file = new \stdClass();
            $this->attachData($file, $info, $folder);

            if (!$file->dir) {
                if ($this->filter && !preg_match("/" . $this->filter . "/i", $file->filename)) {
                    continue;
                }

                $file->isInCustom = false;

                if ($isStream) {
                    $stream         = explode('://', $folder);
                    $stream         = array_shift($stream) . '://';
                    $customLocation = $locator->findResource($stream, true, true);
                    if (substr($info->getPathname(), 0, strlen($customLocation)) === $customLocation) {
                        $file->isInCustom = true;
                    }
                }


                $files->append($file);
            }
        }

        $files->asort();

        return $files;
    }

    /**
     * @return JsonResponse
     */
    public function subfolder()
    {
        $response         = [];
        $response['html'] = 'subfolder';

        return new JsonResponse($response);

    }

    public function displayFile()
    {
        $path = implode('/', func_get_args());

        $this->doDownload($path, false);
    }

    /**
     * @param string $path
     * @param bool $download
     */
    protected function doDownload($path, $download)
    {
        if (!$path) {
            throw new \RuntimeException('No file specified', 400);
        }

        // TODO: handle streams
        $targetPath = GANTRY5_ROOT . '/' . $path;

        if (!file_exists($targetPath)) {
            throw new \RuntimeException('File not found.', 404);
        }

        $hash = md5_file($targetPath);

        // Handle 304 Not Modified
        if (isset($this->request->server['HTTP_IF_NONE_MATCH'])) {
            $etag = stripslashes($this->request->server['HTTP_IF_NONE_MATCH']);

            if ($etag == $hash) {
                header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($targetPath)) . ' GMT', true, 304);

                // Give fast response.
                flush();
                exit();
            }
        }

        // Set file headers.
        header('ETag: ' . $hash);
        header('Pragma: public');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($targetPath)) . ' GMT');

        // Get the image file information.
        $info    = getimagesize($targetPath);
        $isImage = (bool)$info;

        if (!$download && $isImage) {
            $fileType = $info['mime'];

            // Force re-validate.
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Content-type: ' . $fileType);
            header('Content-Disposition: inline; filename="' . Gantry::basename($targetPath) . '"');
        } else {
            // Force file download.
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Content-Description: File Transfer');
            header('Content-Type: application/force-download');
            header('Content-Type: application/octet-stream');
            header('Content-Type: application/download');
            header('Content-Disposition: attachment; filename="' . Gantry::basename($targetPath) . '"');
        }

        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . filesize($targetPath));
        flush();

        // Output the file contents.
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
        @readfile($targetPath);
        flush();

        exit();
    }

    public function downloadFile()
    {
        $path = implode('/', func_get_args());

        $this->doDownload($path, true);
    }

    /**
     * @return JsonResponse
     */
    public function upload()
    {
        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];
        $path    = implode('/', func_get_args());

        if (function_exists('check_ajax_referer') && !check_ajax_referer('gantry5-layout-manager', '_wpnonce', false)) {
            throw new \RuntimeException('Invalid request token.', 403);
        }

        if (base64_decode($path, true) !== false) {
            $path = urldecode(base64_decode($path));
        }

        if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
            throw new \RuntimeException('No file sent', 400);
        }
        $uploadedFile = $_FILES['file'];

        if (!isset($uploadedFile['error']) || is_array($uploadedFile['error'])) {
            throw new \RuntimeException('No file sent', 400);
        }

        // Check uploaded file error value.
        switch ($uploadedFile['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new \RuntimeException('No file sent', 400);
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new \RuntimeException('Exceeded filesize limit.', 400);
            default:
                throw new \RuntimeException('Unkown errors', 400);
        }

        $maxSize = $this->returnBytes(min(ini_get('post_max_size'), ini_get('upload_max_filesize')));
        $uploadedSize = isset($uploadedFile['size']) ? (int) $uploadedFile['size'] : 0;
        if ($uploadedSize > $maxSize) {
            throw new \RuntimeException('Exceeded filesize limit.', 400);
        }

        // Check extension
        $uploadedName = isset($uploadedFile['name']) && is_string($uploadedFile['name']) ? Gantry::basename($uploadedFile['name']) : '';
        if (function_exists('sanitize_file_name')) {
            $uploadedName = sanitize_file_name($uploadedName);
        }
        $tmpName = isset($uploadedFile['tmp_name']) ? $uploadedFile['tmp_name'] : '';
        if ($uploadedName === '' || $tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new \RuntimeException('Invalid uploaded file.', 400);
        }

        $fileParts = Gantry::pathinfo($uploadedName);
        $fileExt   = strtolower($fileParts['extension']);

        // TODO: check if download is of supported type.

        $targetPath = $this->getUploadTargetPath($path, $locator);

        // Upload it
        $destination = sprintf('%s/%s', $targetPath, $uploadedName);
        $destination = preg_replace('#//#', '/', $destination);

        Folder::create($targetPath);

        if (!$this->writeUploadedFile($tmpName, $destination)) {
            throw new \RuntimeException('Failed to move uploaded file.', 500);
        }

        $finfo = new \stdClass();
        $this->attachData($finfo, new \SplFileInfo($destination), $targetPath);

        return new JsonResponse(['success' => 'File uploaded successfully', 'finfo' => $finfo, 'url' => $path]);
    }

    /**
     * Persist an uploaded file through the active filesystem layer.
     *
     * @param string $source
     * @param string $destination
     * @return bool
     */
    protected function writeUploadedFile($source, $destination)
    {
        if (defined('ABSPATH')) {
            if (!function_exists('WP_Filesystem')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }

            WP_Filesystem();
            global $wp_filesystem;

            $content = file_get_contents($source);
            return $content !== false && $wp_filesystem && $wp_filesystem->put_contents($destination, $content, FS_CHMOD_FILE);
        }

        return $this->moveLocalFile($source, $destination);
    }

    /**
     * Move a local file without using the directory-only Folder::move() helper.
     *
     * @param string $source
     * @param string $destination
     * @return bool
     */
    protected function moveLocalFile($source, $destination)
    {
        try {
            Folder::moveFile($source, $destination);
            return true;
        } catch (\RuntimeException $e) {
            return false;
        }
    }

    /**
     * Resolve the real directory where the uploaded file should be written.
     *
     * @param string $path
     * @param UniformResourceLocator $locator
     * @return string
     */
    protected function getUploadTargetPath($path, UniformResourceLocator $locator)
    {
        $path = str_replace('\\', '/', $path);

        $stream = explode('://', $path, 2);
        $scheme = $stream[0];

        if ($locator->schemeExists($scheme)) {
            $directory = $this->getUploadDirectory($path);
            $targetPath = $locator->findResource($directory, true, true);

            if (!$targetPath) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to resolve upload target: %s',
                        esc_html($directory)
                    ),
                    500
                );
            }

            return rtrim($targetPath, '/\\');
        }

        $directory = dirname($path);
        $directory = $directory === '.' ? '' : trim($directory, '/');

        return rtrim(GANTRY5_ROOT, '/\\') . ($directory ? '/' . $directory : '');
    }

    /**
     * Extract the folder portion from the encoded upload path while keeping stream prefixes intact.
     *
     * @param string $path
     * @return string
     */
    protected function getUploadDirectory($path)
    {
        $stream = explode('://', $path, 2);

        if (count($stream) === 2) {
            $scheme = $stream[0];
            $target = $stream[1];
            $separator = strrpos($target, '/');

            if ($separator === false) {
                return $scheme . '://';
            }

            return $scheme . '://' . substr($target, 0, $separator);
        }

        $separator = strrpos($path, '/');

        if ($separator === false) {
            return $path;
        }

        return substr($path, 0, $separator);
    }

    /**
     * @param string $size_str
     * @return float|int|string
     */
    protected function returnBytes($size_str)
    {
        switch (strtolower(substr($size_str, -1))) {
            case 'm':
            case 'mb':
                return (int)$size_str * 1048576;
            case 'k':
            case 'kb':
                return (int)$size_str * 1024;
            case 'g':
            case 'gb':
                return (int)$size_str * 1073741824;
        }

        return $size_str;
    }

    /**
     * @return JsonResponse
     */
    public function delete()
    {
        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];
        $path    = implode('/', func_get_args());

        if (base64_decode($path, true) !== false) {
            $path = urldecode(base64_decode($path));
        }

        $stream = explode('://', $path);
        $scheme = $stream[0];

        if (!$path) {
            throw new \RuntimeException('No file specified for delete', 400);
        }

        $isStream = $locator->schemeExists($scheme);
        if ($isStream) {
            $targetPath = $locator->findResource($path, true, true);
        } else {
            $targetPath = GANTRY5_ROOT . '/' . $path;
        }

        $file = File::instance($targetPath);

        if (!$file->exists()) {
            throw new \RuntimeException('File not found.', 404);
        }

        try {
            $file->delete();
        } catch (\Exception $e) {
            throw new \RuntimeException('File could not be deleted.', 500);
        }
        $file->free();

        return new JsonResponse(['success', 'File deleted: ' . $targetPath]);
    }

    /**
     * @param string|UniformResourceIterator $folder
     * @return bool
     */
    private function isStream($folder)
    {
        return $folder instanceof UniformResourceIterator || strpos($folder, '://');
    }
}
