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
use Gantry\Component\Filesystem\PathGuard;
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
    /** @var bool|string */
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

    /** @var PathGuard|null */
    protected $pathGuard;

    /** @var string|null */
    protected $accessLevel;

    /**
     * Resolve filesystem access level for the current user.
     *
     * @return string 'full' or 'images'
     */
    protected function getAccessLevel()
    {
        if ($this->accessLevel !== null) {
            return $this->accessLevel;
        }

        if ($this->authorize('filesystem.manage')) {
            $this->accessLevel = 'full';
        } elseif ($this->authorize('filesystem.images')) {
            $this->accessLevel = 'images';
        } else {
            throw new \RuntimeException('Forbidden', 403);
        }

        return $this->accessLevel;
    }

    /**
     * @return string
     */
    protected function getSiteRoot()
    {
        return GANTRY5_ROOT;
    }

    /**
     * @param string $tmp
     * @return bool
     */
    protected function isUploadedFile($tmp)
    {
        return is_uploaded_file($tmp);
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $access = $this->getAccessLevel();

        /** @var UniformResourceLocator $locator */
        $locator   = $this->container['locator'];
        $bookmarks = [];
        $drives    = ['/'];
        $subfolder = false;
        $guard     = $this->getPathGuard();

        $this->base = $locator->base;

        if ($this->method === 'POST') {
            $root         = $this->request->post['root'];
            $drives       = isset($root) ? ($root !== 'false' ? (array) $root : ['/']) : ['/'];
            $subfolder    = $this->request->post['subfolder'] ? true : false;
            $filter       = $this->request->post['filter'];
            $this->filter = isset($filter) ? ($filter !== 'false' ? $filter : false) : false;
            $this->value  = $this->request->post['value'] ?: '';

            // Guard against invalid regex patterns.
            if ($this->filter !== false && is_string($this->filter)) {
                if (@preg_match('/' . $this->filter . '/i', '') === false) {
                    $this->filter = false;
                }
            }
        }

        if ($access === 'images') {
            $this->filter = '\.(jpe?g|png|gif|webp|avif|svg)$';
        }

        $readSchemes = ['gantry-media', 'gantry-theme', 'gantry-assets'];
        if ($access === 'images') {
            $readSchemes = ['gantry-media'];
        }

        foreach ($drives as $drive) {
            try {
                $drive = PathGuard::assertCleanInput($drive);
            } catch (\RuntimeException $e) {
                continue;
            }

            $isStream = $locator->isStream($drive);

            if ($isStream) {
                $parts = explode('://', $drive, 2);
                $scheme = isset($parts[0]) ? $parts[0] : '';
                if (!in_array($scheme, $readSchemes, true)) {
                    continue;
                }
                if (!$locator->findResources($drive)) {
                    continue;
                }
            } else {
                if (strpos($drive, '://') !== false) {
                    continue;
                }
                $path = rtrim($this->base, '/') . '/' . ltrim($drive, '/');
                if (!file_exists($path)) {
                    continue;
                }
                $real = realpath($path);
                // Images mode: only write roots; full mode: any read root.
                $writeOnly = ($access === 'images');
                if ($real === false || !$guard->isUnderRoots($real, $writeOnly)) {
                    continue;
                }
            }

            $key = $isStream ? $drive : preg_replace('#/{2,}+#', '/', $drive);

            if (!array_key_exists($key, $bookmarks)) {
                $bookmarks[$key] = $isStream
                    ? [$locator->getIterator($drive)]
                    : [rtrim(Folder::getRelativePath(rtrim($this->base, '/') . '/' . ltrim($drive, '/'), $this->getSiteRoot()), '/') . '/'];
            }
        }

        // Fall back to media drive when nothing allowed remains.
        if (!count($bookmarks)) {
            $fallback = 'gantry-media://';
            try {
                if ($locator->isStream($fallback) && $locator->findResources($fallback)) {
                    $bookmarks[$fallback] = [$locator->getIterator($fallback)];
                }
            } catch (\Exception $e) {
                // ignore
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

                    // security hardening: skip forbidden files
                    if (!$info->isDir() && PathGuard::isForbiddenFile($info->getFilename())) {
                        continue;
                    }

                    // Images mode: folders plus image files only.
                    if ($access === 'images' && !$info->isDir() && !PathGuard::isImageFile($info->getFilename())) {
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
                $node->{$keyMethod} = $this->isStream($folder)
                    ? $iteration->getUrl()
                    : Folder::getRelativePath($node->{$keyMethod}, $this->getSiteRoot());
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
        $access   = $this->getAccessLevel();
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

            // security hardening: skip forbidden files
            if (PathGuard::isForbiddenFile($info->getFilename())) {
                continue;
            }

            $file = new \stdClass();
            $this->attachData($file, $info, $folder);

            if (!$file->dir) {
                if ($access === 'images' && !PathGuard::isImageFile($file->filename)) {
                    continue;
                }

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
        $this->getAccessLevel();

        $response         = [];
        $response['html'] = 'subfolder';

        return new JsonResponse($response);

    }

    public function displayFile()
    {
        $this->getAccessLevel();

        if (!$this->checkRequestToken()) {
            throw new \RuntimeException('Invalid security token; please reload the page and try again.', 403);
        }

        $path = implode('/', func_get_args());

        $this->doDownload($path, false);
    }

    /**
     * Validate and resolve a download/display path.
     *
     * @param string $path
     * @param bool $download
     * @return array{path:string,inline:bool,mime:?string,name:string}
     */
    protected function prepareDownload($path, $download)
    {
        $access = $this->getAccessLevel();

        if (!$path) {
            throw new \RuntimeException('No file specified', 400);
        }

        $targetPath = $this->resolveRequestPath($path, false);

        if (!is_file($targetPath) || PathGuard::isForbiddenFile($targetPath)) {
            throw new \RuntimeException('Access denied.', 403);
        }

        if ($access === 'images' && !PathGuard::isImageFile($targetPath)) {
            throw new \RuntimeException('Access denied.', 403);
        }

        // Get the image file information.
        $info    = @getimagesize($targetPath);
        $safeInlineMimes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/avif',
            'image/bmp',
            'image/x-icon',
            'image/vnd.microsoft.icon',
        ];
        $isSafeImage = is_array($info) && isset($info['mime']) && in_array($info['mime'], $safeInlineMimes, true);

        $safeName = str_replace(['"', "\r", "\n"], '', Gantry::basename($targetPath));
        $inline = !$download && $isSafeImage;
        $mime = $inline ? $info['mime'] : null;

        return [
            'path'   => $targetPath,
            'inline' => $inline,
            'mime'   => $mime,
            'name'   => $safeName,
        ];
    }

    /**
     * @param string $path
     * @param bool $download
     */
    protected function doDownload($path, $download)
    {
        $prepared = $this->prepareDownload($path, $download);
        $targetPath = $prepared['path'];

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
        header('X-Content-Type-Options: nosniff');

        if ($prepared['inline']) {
            // Force re-validate.
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Content-type: ' . $prepared['mime']);
            header('Content-Disposition: inline; filename="' . $prepared['name'] . '"');
        } else {
            // Force file download.
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Content-Description: File Transfer');
            header('Content-Type: application/force-download');
            header('Content-Type: application/octet-stream');
            header('Content-Type: application/download');
            header('Content-Disposition: attachment; filename="' . $prepared['name'] . '"');
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
        $this->getAccessLevel();

        if (!$this->checkRequestToken()) {
            throw new \RuntimeException('Invalid security token; please reload the page and try again.', 403);
        }

        $path = implode('/', func_get_args());

        $this->doDownload($path, true);
    }

    /**
     * @return JsonResponse
     */
    public function upload()
    {
        $access = $this->getAccessLevel();

        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];
        $path    = implode('/', func_get_args());
        $guard   = $this->getPathGuard();

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
        if ($uploadedName === '' || $tmpName === '' || !$this->isUploadedFile($tmpName)) {
            throw new \RuntimeException('Invalid uploaded file.', 400);
        }

        if ($access === 'images') {
            $ext = strtolower(pathinfo($uploadedName, PATHINFO_EXTENSION));
            if ($ext === '' || !in_array($ext, PathGuard::imageExtensions(), true)) {
                throw new \RuntimeException('File type not allowed.', 400);
            }
        }

        $extraAllowed = $this->getJoomlaMediaExtensions();
        PathGuard::assertUploadAllowed($uploadedName, $tmpName, $extraAllowed);

        $targetPath = $this->getUploadTargetPath($path, $locator);

        // Ensure the target directory is (or will be) inside a write root.
        if (!is_dir($targetPath)) {
            if (!$guard->canCreateDirectory($targetPath)) {
                throw new \RuntimeException('Access denied.', 403);
            }
            Folder::create($targetPath);
        }

        $destination = $guard->resolveNewFile($targetPath, $uploadedName);

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

            global $wp_filesystem;
            if (empty($wp_filesystem) && \function_exists('WP_Filesystem')) {
                if (!\function_exists('request_filesystem_credentials')) {
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                }
                \WP_Filesystem();
            }
            if (!empty($wp_filesystem)) {
                $perms = \defined('FS_CHMOD_FILE') ? FS_CHMOD_FILE : (0666 & ~umask());
                $wp_filesystem->chmod($destination, $perms);
            }

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
    protected function getUploadTargetPath($path, $locator)
    {
        $path = str_replace('\\', '/', $path);
        $path = PathGuard::assertCleanInput($path);

        $stream = explode('://', $path, 2);
        $scheme = $stream[0];

        if ($locator->schemeExists($scheme)) {
            if (!in_array($scheme, ['gantry-media', 'gantry-theme'], true)) {
                throw new \RuntimeException('Access denied.', 403);
            }

            $directory = $this->getUploadDirectory($path);
            $targetPath = $locator->findResource($directory, true, true);

            if (!$targetPath) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to resolve upload target: %s',
                        htmlspecialchars($directory, ENT_QUOTES, 'UTF-8')
                    ),
                    500
                );
            }

            return rtrim($targetPath, '/\\');
        }

        $directory = dirname($path);
        $directory = $directory === '.' ? '' : trim($directory, '/');

        return rtrim($this->getSiteRoot(), '/\\') . ($directory ? '/' . $directory : '');
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
        $access = $this->getAccessLevel();

        if ($access === 'images') {
            throw new \RuntimeException('Forbidden', 403);
        }

        $path = implode('/', func_get_args());

        if (base64_decode($path, true) !== false) {
            $path = urldecode(base64_decode($path));
        }

        if (!$path) {
            throw new \RuntimeException('No file specified for delete', 400);
        }

        $targetPath = $this->resolveRequestPath($path, true);

        if (!is_file($targetPath) || PathGuard::isForbiddenFile($targetPath)) {
            throw new \RuntimeException('Access denied.', 403);
        }

        $file = File::instance($targetPath);

        if (!$file->exists()) {
            throw new \RuntimeException('File not found.', 404);
        }

        $basename = basename($targetPath);

        try {
            $file->delete();
        } catch (\Exception $e) {
            throw new \RuntimeException('File could not be deleted.', 500);
        }
        $file->free();

        return new JsonResponse(['success', 'File deleted: ' . $basename]);
    }

    /**
     * Security hardening: verify CSRF token on GET file serve routes.
     *
     * @return bool
     */
    protected function checkRequestToken()
    {
        if (function_exists('check_ajax_referer')) {
            return (bool) check_ajax_referer('gantry5-layout-manager', '_wpnonce', false);
        }

        if (class_exists('\Joomla\CMS\Session\Session')) {
            return \Joomla\CMS\Session\Session::checkToken('get') || \Joomla\CMS\Session\Session::checkToken('post');
        }

        return false;
    }

    /**
     * @param string $path
     * @param bool $write
     * @return string
     */
    protected function resolveRequestPath($path, $write = false)
    {
        $access = $this->getAccessLevel();
        $path = PathGuard::assertCleanInput($path);
        $guard = $this->getPathGuard();
        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];

        // Images mode always resolves against write roots.
        if ($access === 'images') {
            $write = true;
        }

        if (strpos($path, '://') !== false) {
            $parts = explode('://', $path, 2);
            $scheme = isset($parts[0]) ? $parts[0] : '';
            $readSchemes = ['gantry-media', 'gantry-theme', 'gantry-assets'];
            $writeSchemes = ['gantry-media', 'gantry-theme'];

            if ($access === 'images') {
                $allowed = ['gantry-media', 'gantry-theme'];
            } else {
                $allowed = $write ? $writeSchemes : $readSchemes;
            }

            if (!in_array($scheme, $allowed, true)) {
                throw new \RuntimeException('Access denied.', 403);
            }

            $resolved = $locator->findResource($path, true);
            if ($resolved === false || $resolved === null) {
                throw new \RuntimeException('File not found.', 404);
            }

            return $guard->resolveExisting($resolved, $write);
        }

        $absolute = rtrim($this->getSiteRoot(), '/') . '/' . ltrim($path, '/');

        return $guard->resolveExisting($absolute, $write);
    }

    /**
     * Guard roots used to build PathGuard (overridable in tests).
     *
     * @return array{read:string[],write:string[]}
     */
    protected function getGuardRoots()
    {
        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];

        $writeRoots = [];
        $readRoots = [];

        $resourceSets = [
            'gantry-media://' => true,
            'gantry-theme://images' => true,
            'gantry-theme://custom' => true,
            'gantry-assets://' => false,
        ];

        foreach ($resourceSets as $uri => $writable) {
            try {
                $found = $locator->findResources($uri, true, true);
                if (is_array($found)) {
                    foreach ($found as $p) {
                        if ($writable) {
                            $writeRoots[] = $p;
                        } else {
                            $readRoots[] = $p;
                        }
                    }
                }
            } catch (\Exception $e) {
                // ignore missing schemes
            }
        }

        if (isset($this->container['theme.path'])) {
            $themePath = $this->container['theme.path'];
            $writeRoots[] = $themePath . '/images';
            $writeRoots[] = $themePath . '/custom';
        }

        if (defined('JPATH_ROOT')) {
            $writeRoots[] = JPATH_ROOT . '/images';
            $writeRoots[] = JPATH_ROOT . '/media/gantry5';
            $readRoots[] = JPATH_ROOT . '/media';
        }

        if (function_exists('wp_upload_dir')) {
            $uploads = wp_upload_dir();
            if (!empty($uploads['basedir'])) {
                $writeRoots[] = $uploads['basedir'];
            }
            if (defined('WP_CONTENT_DIR')) {
                $writeRoots[] = WP_CONTENT_DIR . '/gantry5';
            }
        }

        return [
            'read'  => $readRoots,
            'write' => $writeRoots,
        ];
    }

    /**
     * Build and cache the path allowlist guard.
     *
     * @return PathGuard
     */
    protected function getPathGuard()
    {
        if ($this->pathGuard instanceof PathGuard) {
            return $this->pathGuard;
        }

        $roots = $this->getGuardRoots();
        $this->pathGuard = new PathGuard($this->getSiteRoot(), $roots['read'], $roots['write']);

        return $this->pathGuard;
    }

    /**
     * Joomla com_media allowed extensions list, or null when unavailable/empty.
     *
     * @return array|null
     */
    protected function getJoomlaMediaExtensions()
    {
        if (!class_exists('\Joomla\CMS\Component\ComponentHelper')) {
            return null;
        }

        try {
            $params = \Joomla\CMS\Component\ComponentHelper::getParams('com_media');
            $list = $params->get(
                'restrict_uploads_extensions',
                $params->get('upload_extensions', '')
            );
        } catch (\Exception $e) {
            return null;
        }

        if (!is_string($list) || $list === '') {
            return null;
        }

        $parts = explode(',', $list);
        $out = [];
        foreach ($parts as $part) {
            $part = strtolower(trim($part));
            if ($part !== '') {
                $out[] = $part;
            }
        }

        return count($out) ? $out : null;
    }

    /**
     * @param string|UniformResourceIterator $folder
     * @return bool
     */
    private function isStream($folder)
    {
        return $folder instanceof UniformResourceIterator || strpos($folder, '://') !== false;
    }
}
