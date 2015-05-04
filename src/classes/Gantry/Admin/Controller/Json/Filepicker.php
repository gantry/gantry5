<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Admin\Controller\Json;

use Gantry\Component\Controller\JsonController;
use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Response\JsonResponse;
use RocketTheme\Toolbox\File\File;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;


class Filepicker extends JsonController
{
    protected $httpVerbs = [
        'GET'  => [
            '/'            => 'index',
            '/*'           => 'index',
            '/display'     => 'undefined',
            '/display/**'  => 'displayFile',
            '/download'    => 'undefined',
            '/download/**' => 'downloadFile',
        ],
        'POST' => [
            '/'            => 'index',
            '/*'           => 'index',
            '/subfolder'   => 'subfolder',
            '/subfolder/*' => 'subfolder',
            '/upload'   => 'undefined',
            '/upload/**'   => 'upload'
        ],
        'DELETE'  => [
            '/**' => 'delete'
        ]
    ];

    public function index()
    {
        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];
        $base = $locator->base;
        $bookmarks = [];
        $drives = [DS];
        $subfolder = false;
        $filter = false;

        if (isset($_POST)) {
            $drives = isset($_POST['root']) ? ($_POST['root'] != 'false' ? $_POST['root'] : [DS]) : [DS];
            $subfolder = isset($_POST['subfolder']) ? true : false;
            $filter = isset($_POST['filter']) ? ($_POST['filter'] != 'false' ? $_POST['filter'] : false) : false;
        }

        if (!is_array($drives)) {
            $drives = [$drives];
        }

        foreach ($drives as $drive) {
            // cleanup of the path so it's chrooted.
            $drive = str_replace('..', '', $drive);
            $stream = explode('://', $drive);
            $scheme = $stream[0];

            $isStream = $locator->schemeExists($scheme);
            $path = rtrim($base, DS) . DS . ltrim($scheme, DS);

            // It's a stream but the scheme doesn't exist. we skip it.
            if (!$isStream && (count($stream) == 2 || !file_exists($path))) {
                continue;
            }

            if ($isStream && !count($resources = $locator->findResources($drive, false))) {
                continue;
            }

            $key = $isStream ? $drive : preg_replace('#' . DS . '{2,}+#', DS, $drive);

            if (!array_key_exists($key, $bookmarks)) {
                $bookmarks[$key] = $isStream ? $resources : [rtrim(Folder::getRelativePath($path), DS) . DS];
            }
        }

        if (!count($bookmarks)) {
            throw new \RuntimeException((count($drives) > 1 ? 'directories' : 'directory') . ' "' . implode('", "',
                    $drives) . '" not found', 404);
        }

        $files = new \ArrayObject();
        $folders = [];
        $active = [];

        $index = 0;
        // iterating the folder and collecting subfolders and files
        foreach ($bookmarks as $key => $bookmark) {
            $folders[$key] = [];

            if (!$index) {
                $active[] = $key;
            }

            foreach($bookmark as $folder) {
                $folders[$key][$folder] = new \ArrayObject();
                if (!$index) {
                    $active[] = $folder;
                }

                /** @var \SplFileInfo $info */
                foreach (new \DirectoryIterator($base . DS . ltrim($folder, DS)) as $info) {
                    // no dot files nor files beginning with dot
                    if ($info->isDot() || substr($info->getFilename(), 0, 1) == '.') { continue; }

                    $file = new \stdClass();

                    foreach(['getFilename', 'getExtension', 'getPerms', 'getMTime', 'getBasename', 'getPathname', 'getSize', 'getType', 'isReadable', 'isWritable', 'isDir', 'isFile'] as $method){
                        $keyMethod = strtolower(preg_replace("/^(is|get)/", '', $method));
                        $file->{$keyMethod} = $info->{$method}();
                        if ($method == 'getPathname') {
                            $file->{$keyMethod} = Folder::getRelativePath($file->{$keyMethod});
                        } else if ($method == 'getExtension') {
                            $file->isImage = in_array($file->{$keyMethod}, ['jpg', 'jpeg', 'png', 'gif', 'bmp']);
                        }
                    }

                    if ($file->dir) {
                        $folders[$key][$folder]->append($file);
                    } else {
                        if ($filter && !preg_match("/".$filter."/i", $file->filename)) { continue; }
                        if (!$index) {
                            $files->append($file);
                        }
                    }
                }

                $index++;
            }
        }

        $response = [];

        if (!$subfolder) {
            $response['html'] = $this->container['admin.theme']->render('@gantry-admin/ajax/filepicker.html.twig', [
                'active'    => $active,
                'base'      => $base,
                'bookmarks' => $bookmarks,
                'folders'   => $folders,
                'files'     => $files
            ]);
        } else {
            $response['subfolder'] = !$folders[$key][$folder]->count() ? false : $this->container['admin.theme']->render('@gantry-admin/ajax/filepicker/subfolders.html.twig', ['folder' => $folders[$key][$folder]]);
            $response['files'] = !$files->count() ? false : $this->container['admin.theme']->render('@gantry-admin/ajax/filepicker/files.html.twig', ['files' => $files]);
        }

        return new JsonResponse($response);
    }

    public function subfolder()
    {
        $response = [];
        $response['html'] = 'subfolder';

        return new JsonResponse($response);
    }

    public function displayFile()
    {
        $path = implode('/', func_get_args());

        $this->doDownload($path, false);
    }

    public function downloadFile()
    {
        $path = implode('/', func_get_args());

        $this->doDownload($path, true);
    }

    public function upload()
    {
        $path = implode('/', func_get_args());

        // TODO: handle streams
        $targetPath = dirname(GANTRY5_ROOT . '/' . $path);

        if (!isset($_FILES['file']['error']) || is_array($_FILES['file']['error'])) {
            throw new \RuntimeException('No file sent', 400);
        }

        // Check $_FILES['file']['error'] value.
        switch ($_FILES['file']['error']) {
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

        // You should also check filesize here.
        $maxSize = $this->returnBytes(min(ini_get('post_max_size'), ini_get('upload_max_filesize')));
        if ($_FILES['file']['size'] > $maxSize) {
            throw new \RuntimeException('Exceeded filesize limit. File is ' . $_FILES['file']['size'] . ', maximum allowed is ' . $maxSize, 400);
        }

        // Check extension
        $fileParts = pathinfo($_FILES['file']['name']);
        $fileExt = strtolower($fileParts['extension']);

        // TODO: check if download is of supported type.

        // Upload it
        if (!move_uploaded_file($_FILES['file']['tmp_name'], sprintf('%s/%s', $targetPath, $_FILES['file']['name']))) {
            throw new \RuntimeException('Failed to move uploaded file.', 500);
        }

        return new JsonResponse(['success', 'File uploaded successfully']);
    }

    public function delete()
    {
        $path = implode('/', func_get_args());

        if (!$path) {
            throw new \RuntimeException('No file specified for delete', 400);
        }

        // TODO: handle streams
        $targetPath = GANTRY5_ROOT . '/' . $path;

        $file = File::instance($targetPath);

        if (!$file->exists()) {
            throw new \RuntimeException('File not found: ' . $path, 404);
        }

        try {
            $file->delete();
        } catch (\Exception $e) {
            throw new \RuntimeException('File could not be deleted: ' . $path, 500);
        }

        return new JsonResponse(['success', 'File deleted: ' . $path]);
    }

    protected function doDownload($path, $download)
    {
        if (!$path) {
            throw new \RuntimeException('No file specified', 400);
        }

        // TODO: handle streams
        $targetPath = GANTRY5_ROOT . '/' . $path;

        if (!file_exists($targetPath)) {
            throw new \RuntimeException('File not found: ' . $path, 404);
        }

        $hash = md5_file($path);

        // Handle 304 Not Modified
        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            $etag = stripslashes($_SERVER['HTTP_IF_NONE_MATCH']);

            if ($etag == $hash) {
                header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($path)) . ' GMT', true, 304);

                // Give fast response.
                flush();
                exit();
            }
        }

        // Set file headers.
        header('ETag: ' . $hash);
        header('Pragma: public');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($path)) . ' GMT');

        // Get the image file information.
        $info = getimagesize($path);
        $isImage = (bool) $info;

        if (!$download && $isImage) {
            $fileType = $info['mime'];

            // Force re-validate.
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Content-type: ' . $fileType);
            header('Content-Disposition: inline; filename="' . basename($path) . '"');
        } else {
            // Force file download.
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Content-Description: File Transfer');
            header('Content-Type: application/force-download');
            header('Content-Type: application/octet-stream');
            header('Content-Type: application/download');
            header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        }

        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . filesize($path));
        flush();

        // Output the file contents.
        @readfile($path);
        flush();

        exit();
    }

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
            default:
                return $size_str;
        }
    }
}
